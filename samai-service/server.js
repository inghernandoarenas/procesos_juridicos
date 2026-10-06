import express from "express";
import { chromium } from "playwright";
import fs from "fs";

const app  = express();
const PORT = 3001;
app.use(express.json());

// ── Browser persistente ────────────────────────────────────────
let browser   = null;
let launching = false;

async function getBrowser() {
    if (browser && browser.isConnected()) return browser;
    if (launching) {
        await new Promise(r => setTimeout(r, 500));
        return getBrowser();
    }
    launching = true;
    console.log("  Iniciando browser...");
    browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    launching = false;
    console.log("  Browser listo");
    return browser;
}

// ═══════════════════════════════════════════════════════════════
//  SAMAI
// ══════════════════════════════════════════════════════════════

// FIX: el fetch() nativo de Node recibe HTTP 404 de SAMAI (probablemente
// un WAF/anti-bot que filtra por huella TLS/HTTP, no solo por cabeceras —
// ya se probó igualando headers/User-Agent al de PHP y sigue en 404).
// Un navegador Chromium real (Playwright) es indistinguible del tráfico
// normal, así que ahora la búsqueda del GUID se hace DESDE la página,
// con page.evaluate(fetch(...)), reusando las cookies de sesión que deja
// la carga inicial de procesos.aspx — igual que lo haría un usuario real.
//
// NOTA (actualizado): SAMAI migró su endpoint de búsqueda. Ya no es
// Jprocesos.ashx/listaprocesosdata (devolvía un array plano con ACCIONES
// tipo HTML/onclick), sino Jprocesos.ashx/buscar (devuelve JSON limpio
// { ok, data: { items, page, total, ... } } con el guid directamente en
// cada item — confirmado capturando la petición real desde DevTools).
async function obtenerGuidEnPagina(page, radicado) {
    const corp = radicado.replace(/\D/g, '').substring(0, 7);

    const buscar = (corpValue) => page.evaluate(async ({ radicado, corpValue }) => {
        try {
            const r = await fetch('/Vistas/Casos/Jprocesos.ashx/buscar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                },
                body: JSON.stringify({
                    tipoBusqueda: 'radicado',
                    criterio:     radicado,
                    fraseExacta:  false,
                    ambito:       'corporacion',
                    corporacion:  corpValue,
                    seccion:      '',
                    ponente:      '',
                    fechaDesde:   '',
                    fechaHasta:   '',
                    estado:       '',
                    tipoParte:    '',
                    pagina:       1,
                    tamanoPagina: 10,
                }),
            });
            const status = r.status;
            if (!r.ok) return { status, items: [] };
            const json = await r.json().catch(() => null);
            const items = (json && json.ok && json.data && Array.isArray(json.data.items))
                ? json.data.items : [];
            return { status, items };
        } catch (e) {
            return { status: 0, items: [], err: e.message };
        }
    }, { radicado, corpValue });

    let res = await buscar(corp);
    console.log(`  GUID(browser): corp="${corp}" → HTTP ${res.status}, ${res.items.length} item(s)${res.err ? ' err=' + res.err : ''}`);
    if (!res.items.length) {
        res = await buscar('');
        console.log(`  GUID(browser): corp="" → HTTP ${res.status}, ${res.items.length} item(s)${res.err ? ' err=' + res.err : ''}`);
    }
    if (!res.items.length) return null;

    // Preferir el item cuyo radicado coincide exactamente; si ninguno
    // coincide (no debería pasar), probar con todos igual.
    const limpio = radicado.replace(/[^0-9]/g, '');
    let candidatos = res.items.filter(it => (it.radicado || '').replace(/[^0-9]/g, '') === limpio);
    if (!candidatos.length) candidatos = res.items;

    const match = candidatos.find(it => !!it.guid);
    if (!match) {
        console.log(`  GUID(browser): ningún item de los ${res.items.length} traía campo guid`);
        return null;
    }
    return match.guid;
}

async function resolverCaptcha(page) {
    const texto = await page.evaluate(() => {
        const m = document.body.innerText.match(/Ingrese sin espacios[^:]*:\s*([A-Z0-9 ]+)/i);
        if (m) return m[1].trim().replace(/\s+/g, '');
        const chars = [];
        for (const el of document.querySelectorAll('span, div, td')) {
            if (el.children.length > 0) continue;
            const st = window.getComputedStyle(el);
            const bg = st.backgroundColor;
            const t  = (el.innerText || '').trim();
            if (t.length >= 1 && t.length <= 4
                && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'rgb(255, 255, 255)' && bg !== '')
                chars.push(t);
        }
        return chars.join('').replace(/\s+/g, '');
    });
    const captcha = (texto || '').replace(/\s+/g, '').toUpperCase();
    console.log(`  Captcha: "${captcha}"`);
    if (!captcha) return false;
    const inputHandle = await page.evaluateHandle(() => {
        for (const inp of document.querySelectorAll('input[type="text"]')) {
            const r = inp.getBoundingClientRect();
            if (r.width > 0 && r.height > 0 && inp.offsetParent !== null) return inp;
        }
        return null;
    });
    const el = inputHandle.asElement();
    if (!el) return false;
    await el.fill(captcha);
    await page.evaluate(() => {
        const btns = Array.from(document.querySelectorAll('button, input[type="button"], input[type="submit"]'));
        const btn  = btns.find(b => (b.innerText || b.value || '').toLowerCase().includes('continu'));
        if (btn) btn.click();
    });
    await page.waitForLoadState('domcontentloaded', { timeout: 15000 }).catch(() => {});
    return true;
}

// ACTUALIZADO: antes se adivinaba la tabla/columnas con heurística genérica
// (buscar valores con forma de fecha). Ahora se apunta directo a la tabla
// real confirmada por HTML: #MainContent_GridViewHistoricoActuaciones, con
// columnas fijas: 0 Ver | 1 Fecha registro | 2 Fecha actuación | 3 Actuación
// | 4 Anotación/detalle | 5 Estado | 6 Anexos | 7 Índice.
// "Anotación / detalle" (col 4) es el campo que en nuestra BD es "observaciones".
async function extraerActuacionesSamai(page) {
    return page.evaluate(() => {
        const parseFecha = v => {
            const m = v.trim().match(/^(\d{2})\/(\d{2})\/(\d{4})/); // dd/mm/yyyy (con o sin hora)
            if (m) return `${m[3]}-${m[2]}-${m[1]}`;
            if (/^\d{4}-\d{2}-\d{2}/.test(v)) return v.substring(0, 10);
            return null;
        };

        let tabla = document.querySelector('#MainContent_GridViewHistoricoActuaciones');
        if (!tabla) {
            // Fallback por si el ID cambia: buscar tabla cuyo encabezado
            // contenga "fecha actuaci" y "anotaci"/"detalle".
            tabla = Array.from(document.querySelectorAll('table')).find(t => {
                const h = (t.querySelectorAll('tr')[0]?.innerText || '').toLowerCase();
                return h.includes('fecha actuaci') && (h.includes('anotaci') || h.includes('detalle'));
            });
        }
        if (!tabla) return [];

        const filas = tabla.querySelectorAll('tr');
        if (filas.length < 2) return [];

        const resultado = [];
        for (let i = 1; i < filas.length; i++) {
            const cols = Array.from(filas[i].querySelectorAll('td')).map(td => td.innerText.trim());
            if (cols.length < 5) continue;

            const fecha         = parseFecha(cols[2] || '');
            const actuacion     = cols[3] || '';
            const observaciones = cols[4] || '';

            if (fecha && actuacion) {
                resultado.push({
                    fecha,
                    actuacion,
                    observaciones: observaciones || null,
                    _rowIdx: i,
                });
            }
        }
        return resultado;
    });
}

// Endpoint SAMAI
app.post("/samai/actuaciones", async (req, res) => {
    const { radicado } = req.body;
    if (!radicado) return res.status(400).json({ error: "Radicado requerido" });

    const t0 = Date.now();
    console.log(`[${new Date().toLocaleTimeString()}] SAMAI ${radicado}`);

    let context, page;
    try {
        const br = await getBrowser();
        context  = await br.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            locale: 'es-CO',
        });
        page = await context.newPage();

        // 1. Cargar la página de búsqueda real primero — establece cookies/sesión
        //    igual que un usuario real, antes de intentar el POST del listado.
        await page.goto('https://samai.consejodeestado.gov.co/Vistas/Casos/procesos.aspx',
            { waitUntil: 'domcontentloaded', timeout: 30000 });
        console.log(`  Página búsqueda cargada (${Date.now()-t0}ms)`);

        // 2. Buscar el GUID del proceso, haciendo el POST DESDE el navegador
        const guid = await obtenerGuidEnPagina(page, radicado);
        if (!guid) {
            await context.close();
            console.log(`  GUID no encontrado (${Date.now()-t0}ms)`);
            return res.json({ actuaciones: [], mensaje: 'No encontrado en SAMAI' });
        }
        console.log(`  GUID: ${guid} (${Date.now()-t0}ms)`);

        // 3. Ir al detalle del proceso con el guid encontrado
        const url = `https://samai.consejodeestado.gov.co/Vistas/Casos/list_procesos.aspx?guid=${guid}`;
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });

        const tieneCaptcha = await page.evaluate(() =>
            document.body.innerText.toLowerCase().includes('ingrese sin espacios')
        );
        if (tieneCaptcha) {
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {}),
                resolverCaptcha(page),
            ]);
            console.log(`  Captcha resuelto (${Date.now()-t0}ms)`);
        }
        await page.waitForTimeout(800);

        const actuaciones = await extraerActuacionesSamai(page);
        console.log(`  ✓ ${actuaciones.length} actuaciones (${Date.now()-t0}ms)`);
        await context.close();
        res.json({ actuaciones: actuaciones.map(({ _rowIdx, ...a }) => a) });
    } catch (error) {
        if (context) await context.close().catch(() => {});
        if (browser && !browser.isConnected()) browser = null;
        console.error(`   ${error.message}`);
        res.status(500).json({ error: error.message });
    }
});

// ═══════════════════════════════════════════════════════════════
//  PUBLICACIONES PROCESALES
// ══════════════════════════════════════════════════════════════

const PORTLET = 'co_com_avanti_efectosProcesales_PublicacionesEfectosProcesalesPortletV2_INSTANCE_BIyXQFHVaYaq';
const PUB_BASE = 'https://publicacionesprocesales.ramajudicial.gov.co';

function parsearPublicaciones(texto, codigoDespacho) {
    const lineas   = texto.split('\n');
    const results  = [];
    let tituloPend = null;

    for (const raw of lineas) {
        const linea = raw.trim();
        if (!linea) continue;

        // Línea de categorías
        if (/^Categor[ií]as\s*\|/i.test(linea)) {
            const cats = linea.substring(linea.indexOf('|') + 1).trim();

            const ext = (str, desde, hasta) => {
                const pat = hasta
                    ? new RegExp(desde + '[:\\s]+(.+?)(?=\\s+' + hasta + ':)', 'is')
                    : new RegExp(desde + '[:\\s]+(.+?)$', 'is');
                const m = str.match(pat);
                return m ? m[1].trim() : '';
            };

            results.push({
                titulo:       tituloPend || '',
                fecha:        null,
                tipo:         ext(cats, 'Tipo de publicaci[oó]n', 'Departamento'),
                departamento: ext(cats, 'Departamento',           'Municipio'),
                municipio:    ext(cats, 'Municipio',              'Entidad'),
                entidad:      ext(cats, 'Entidad',                'Especialidad'),
                especialidad: ext(cats, 'Especialidad',           'Despacho'),
                despacho:     ext(cats, 'Despacho',               null) || codigoDespacho,
            });
            tituloPend = null;
            continue;
        }

        // Línea de fecha
        const fechaM = linea.match(/Fecha de Publicaci[oó]n:\s*(\d{4}-\d{2}-\d{2})/i);
        if (fechaM && results.length > 0 && results[results.length - 1].fecha === null) {
            results[results.length - 1].fecha = fechaM[1];
            continue;
        }

        // Título pendiente
        if (linea.length > 3) tituloPend = linea;
    }

    return results.filter(r => r.fecha);
}

// Endpoint publicaciones
app.post("/publicaciones/consultar", async (req, res) => {
    const { codigo_despacho, fecha_inicio, fecha_fin } = req.body;
    if (!codigo_despacho || !fecha_inicio || !fecha_fin)
        return res.status(400).json({ error: "Faltan parámetros: codigo_despacho, fecha_inicio, fecha_fin" });

    const t0 = Date.now();
    console.log(`[${new Date().toLocaleTimeString()}] PUB despacho=${codigo_despacho} rango=${fecha_inicio}/${fecha_fin}`);

    const url = `${PUB_BASE}/web/publicaciones-procesales/inicio`
        + `?p_p_id=${encodeURIComponent(PORTLET)}`
        + `&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view`
        + `&_${encodeURIComponent(PORTLET)}_action=busqueda`
        + `&_${encodeURIComponent(PORTLET)}_fechaInicio=${encodeURIComponent(fecha_inicio)}`
        + `&_${encodeURIComponent(PORTLET)}_fechaFin=${encodeURIComponent(fecha_fin)}`
        + `&_${encodeURIComponent(PORTLET)}_idDepto=%2B`
        + `&_${encodeURIComponent(PORTLET)}_idDespacho=${encodeURIComponent(codigo_despacho)}`
        + `&_${encodeURIComponent(PORTLET)}_verTotales=true`;

    let context, page;
    try {
        const br = await getBrowser();
        context  = await br.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            locale: 'es-CO',
            extraHTTPHeaders: { 'Accept-Language': 'es-CO,es;q=0.9' },
        });
        page = await context.newPage();

        // Extraer códigos DANE del código de despacho
        // Estructura: DDMMMEEENNNX donde DD=depto, DDDDD=municipio
        const idDepto = codigo_despacho.substring(0, 2);
        const idMuni  = codigo_despacho.substring(0, 5);

        // Construir URL directa con parámetros — sin filtro de despacho
        // El portal filtra por depto+municipio y nosotros filtramos por despacho en el parser
        const urlBusqueda = `${PUB_BASE}/web/publicaciones-procesales/inicio`
            + `?p_p_id=${encodeURIComponent(PORTLET)}`
            + `&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view`
            + `&_${encodeURIComponent(PORTLET)}_action=busqueda`
            + `&_${encodeURIComponent(PORTLET)}_fechaInicio=${encodeURIComponent(fecha_inicio)}`
            + `&_${encodeURIComponent(PORTLET)}_fechaFin=${encodeURIComponent(fecha_fin)}`
            + `&_${encodeURIComponent(PORTLET)}_idDepto=${idDepto}`
            + `&_${encodeURIComponent(PORTLET)}_idMuni=${idMuni}`
            + `&_${encodeURIComponent(PORTLET)}_verTotales=true`;

        console.log(`  URL depto=${idDepto} muni=${idMuni}`);

        // 1. Navegar directo a la búsqueda con parámetros GET
        await page.goto(urlBusqueda, { waitUntil: 'domcontentloaded', timeout: 45000 });
        console.log(`  Página cargada (${Date.now()-t0}ms)`);

        // 2. Esperar que el portlet renderice resultados
        await page.waitForTimeout(4000);

        // 3. Extraer texto
        // 6. Extraer texto Y links de detalle (articleId)
        const resultado = await page.evaluate(() => {
            const selectors = [
                '[id*="BIyXQFHVaYaq"] .portlet-body',
                '[id*="BIyXQFHVaYaq"]',
                '.portlet-body',
                '#content',
                'main',
            ];
            let textoEl = null;
            for (const sel of selectors) {
                const el = document.querySelector(sel);
                if (el && el.innerText.includes('Fecha de Publicaci')) { textoEl = el; break; }
            }
            const texto = textoEl ? textoEl.innerText : document.body.innerText;

            // Extraer articleId por cada publicación
            const detalles = [];
            document.querySelectorAll('a[href*="articleId"]').forEach(a => {
                const m = a.href.match(/articleId=(\d+)/);
                if (m && !detalles.find(d => d.articleId === m[1])) {
                    detalles.push({ articleId: m[1], href: a.href });
                }
            });
            return { texto, detalles };
        });

        const texto   = resultado.texto;
        const detalles = resultado.detalles;
        console.log(`  Tiene Fecha Publicación: ${texto.includes('Fecha de Publicaci')}`);
        console.log(`  ArticleIds encontrados: ${detalles.length}`);

        await context.close();

        // Parsear todas las publicaciones del municipio
        const todasPublicaciones = parsearPublicaciones(texto, codigoDespacho);

        // Debug: mostrar despachos únicos encontrados
        const despachosEncontrados = [...new Set(todasPublicaciones.map(p => p.despacho))];
        console.log(`  Despachos en portal: ${JSON.stringify(despachosEncontrados)}`);
        console.log(`  Buscando código: ${codigo_despacho}`);

        // Filtrar por despacho: comparar código numérico (primeros 12 dígitos)
        // El portal devuelve: "080012213000 - NOMBRE DEL DESPACHO"
        // Nuestro código: "080012213000" o "080012213000X"
        const codNuestro = codigo_despacho.replace(/[^0-9]/g,'').substring(0,12);
        const publicaciones = todasPublicaciones.filter(p => {
            if (!p.despacho) return false; // si no tiene despacho, NO incluir
            const codPortal = p.despacho.replace(/[^0-9]/g,'').substring(0,12);
            const match = codPortal === codNuestro;
            if (!match) console.log(`  SKIP: ${codPortal} != ${codNuestro}`);
            return match;
        });
        console.log(`  Total municipio: ${todasPublicaciones.length} | Filtradas: ${publicaciones.length}`);

        // Asociar articleId a cada publicación
        // Primero construir mapa titulo→articleId de los links
        // Los títulos de los links coinciden con los títulos de las publicaciones
        publicaciones.forEach((pub) => {
            const titulo = pub.titulo.toLowerCase().trim();
            // Buscar coincidencia por título en los detalles
            const det = detalles.find(d => {
                // Comparar por texto del link (innerText) con el título
                return titulo && d.href && d.href.includes('articleId');
            });
            // Si no hay coincidencia exacta, asignar por orden
            if (!pub.articleId) {
                const idx = publicaciones.indexOf(pub);
                if (detalles[idx]) pub.articleId = detalles[idx].articleId;
            }
        });
        // Asignación por orden como fallback confiable
        publicaciones.forEach((pub, i) => {
            if (!pub.articleId && detalles[i]) pub.articleId = detalles[i].articleId;
        });
        console.log(`  ✓ ${publicaciones.length} publicaciones (${Date.now()-t0}ms)`);
        res.json({ publicaciones });

    } catch (error) {
        if (context) await context.close().catch(() => {});
        if (browser && !browser.isConnected()) browser = null;
        console.error(`  ✗ PUB error: ${error.message}`);
        res.status(500).json({ error: error.message });
    }
});

// ═══════════════════════════════════════════════════════════════
//  PUBLICACIONES - DETALLE (PDFs)
// ═══════════════════════════════════════════════════════════════

app.post("/publicaciones/detalle", async (req, res) => {
    const { article_id, detail_url } = req.body;
    if (!article_id && !detail_url)
        return res.status(400).json({ error: "Faltan parámetros: article_id o detail_url" });

    console.log(`[${new Date().toLocaleTimeString()}] DETALLE articleId=${article_id}`);

    let context, page;
    try {
        const br = await getBrowser();
        context  = await br.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        });
        page = await context.newPage();

        const url = detail_url || `${PUB_BASE}/web/publicaciones-procesales/inicio`
            + `?p_p_id=${encodeURIComponent(PORTLET)}`
            + `&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view`
            + `&_${encodeURIComponent(PORTLET)}_jspPage=%2FMETA-INF%2Fresources%2Fdetail.jsp`
            + `&_${encodeURIComponent(PORTLET)}_articleId=${article_id}`;

        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await page.waitForTimeout(2000);

        // Esperar que cargue la tabla de documentos
        await page.waitForTimeout(2000);

        // Extraer links de PDFs y fechas
        const datos = await page.evaluate((base) => {
            const pdfs = [];

            // Selector principal: links con uuid (formato real del portal)
            document.querySelectorAll('a[href*="uuid"], a[href*="document_library/get_file"], a[href*=".pdf"]').forEach(a => {
                const href = a.href || '';
                if (!href) return;
                const nombre = (a.innerText || '').trim() || href.split('?')[0].split('/').pop();
                const url = href.startsWith('http') ? href : base + href;
                if (!pdfs.find(p => p.url === url)) {
                    pdfs.push({ url, nombre: nombre.substring(0, 250) });
                }
            });

            // Extraer fechas de la tabla
            const fechas = [];
            document.querySelectorAll('td').forEach(td => {
                const t = (td.innerText || '').trim();
                if (/\d{2}-[a-z]{3}-\d{4}/i.test(t)) fechas.push(t);
            });

            // Debug info
            const allLinks = Array.from(document.querySelectorAll('a')).map(a => a.href).filter(h => h.includes('document') || h.includes('pdf') || h.includes('uuid')).slice(0,5);

            return { pdfs, fechas: [...new Set(fechas)].slice(0, 10), debugLinks: allLinks };
        }, PUB_BASE);

        console.log(`  Debug links: ${JSON.stringify(datos.debugLinks)}`);

        await context.close();
        console.log(`  PDFs encontrados: ${datos.pdfs.length}`);
        res.json({ pdfs: datos.pdfs, fechas: datos.fechas });

    } catch (error) {
        if (context) await context.close().catch(() => {});
        if (browser && !browser.isConnected()) browser = null;
        console.error(`  ✗ DETALLE error: ${error.message}`);
        res.status(500).json({ error: error.message });
    }
});

// ═══════════════════════════════════════════════════════════════
//  TYBA — Justicia XXI Web (CORREGIDO)
// ═══════════════════════════════════════════════════════════════

const TYBA_BASE = 'https://procesojudicial.ramajudicial.gov.co/Justicia21';
const TYBA_CONSULTA  = `${TYBA_BASE}/Administracion/Ciudadanos/frmConsulta.aspx?opcion=consulta`;

// Busca el proceso por radicado (con reintento si TYBA rechaza por captcha)
// y abre su página de detalle. Compartido entre /tyba/actuaciones y /tyba/anexos
// para no duplicar el flujo ni el fix del captcha.
async function buscarYAbrirDetalleTyba(page, radicado, t0, etiqueta = 'TYBA') {
    const MAX_INTENTOS = 3;
    let pageText = '';
    let rechazadoPorCaptcha = true;
    for (let intento = 1; intento <= MAX_INTENTOS && rechazadoPorCaptcha; intento++) {
        await page.goto(TYBA_CONSULTA, { waitUntil: 'domcontentloaded', timeout: 30000 });
        console.log(`  [${etiqueta}][intento ${intento}/${MAX_INTENTOS}] Página cargada (${Date.now()-t0}ms)`);
        await page.waitForTimeout(2000);

        await page.fill('#MainContent_txtCodigoProceso', radicado);
        console.log(`  [${etiqueta}] Radicado ingresado`);
        await page.waitForTimeout(1000);

        await Promise.all([
            page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {}),
            page.click('#MainContent_btnConsultar'),
        ]);
        await page.waitForTimeout(2000);
        console.log(`  [${etiqueta}] Búsqueda enviada (${Date.now()-t0}ms)`);

        pageText = await page.evaluate(() => document.body.innerText.substring(0, 500));
        rechazadoPorCaptcha = /¡\s*Error\s*!/i.test(pageText) && /cap(t)?cha/i.test(pageText);
        if (rechazadoPorCaptcha) console.log(`  ⚠ [${etiqueta}] TYBA rechazó por captcha (intento ${intento}/${MAX_INTENTOS})`);
    }
    if (rechazadoPorCaptcha) {
        throw new Error(`TYBA rechazó la consulta por captcha ("El valor de la Capcha no coincide") tras ${MAX_INTENTOS} intentos`);
    }

    const resultados = await page.evaluate(() => {
        const posiblesTables = ['#MainContent_gvResultado', '#tblResultado', '.datatable', 'table'];
        let filas = [];
        for (const sel of posiblesTables) {
            const tabla = document.querySelector(sel);
            if (tabla) {
                filas = Array.from(tabla.querySelectorAll('tr'));
                if (filas.length > 1) break;
            }
        }
        const datos = [];
        filas.forEach((fila, i) => {
            if (i === 0) return;
            const celdas = fila.querySelectorAll('td');
            if (celdas.length >= 3) {
                const link = fila.querySelector('a, input[type=image]');
                datos.push({
                    onclick: link?.getAttribute('onclick') || link?.closest('tr')?.querySelector('[onclick]')?.getAttribute('onclick') || '',
                });
            }
        });
        return datos;
    });
    console.log(`  [${etiqueta}] Resultados encontrados: ${resultados.length}`);
    if (resultados.length === 0) return { ok: false };

    try {
        const lupa = await page.$('#MainContent_gvResultado td input[type=image], #MainContent_gvResultado td a, table td input[type=image], table td a');
        if (lupa) {
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {}),
                lupa.click(),
            ]);
            console.log(`  [${etiqueta}] Detalle cargado (${Date.now()-t0}ms)`);
        } else if (resultados[0]?.onclick) {
            await page.evaluate((oc) => { try { eval(oc); } catch(e) {} }, resultados[0].onclick);
            await page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {});
            console.log(`  [${etiqueta}] Detalle cargado via onclick (${Date.now()-t0}ms)`);
        } else {
            console.log(`  [${etiqueta}] No se encontró link al detalle`);
            return { ok: false };
        }
    } catch(e) {
        console.log(`  [${etiqueta}] Error navegando al detalle: ${e.message.split('\n')[0]}`);
        return { ok: false };
    }

    return { ok: true };
}

app.post("/tyba/actuaciones", async (req, res) => {
    const { radicado } = req.body;
    if (!radicado) return res.status(400).json({ error: "Radicado requerido" });

    const t0 = Date.now();
    console.log(`[${new Date().toLocaleTimeString()}] TYBA ${radicado}`);

    let context, page;
    try {
        const br = await getBrowser();
        context  = await br.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            locale: 'es-CO',
        });
        page = await context.newPage();

        const detalle = await buscarYAbrirDetalleTyba(page, radicado, t0, 'ACTUACIONES');
        if (!detalle.ok) {
            await context.close();
            return res.json({ actuaciones: [], mensaje: 'No encontrado en TYBA' });
        }

        // Esperar la tabla de actuaciones. Antes: waitForSelector "visible" con 10s
        // que fallaba y luego 3s fijos (13s perdidos en cada consulta). Ahora se
        // espera solo a que existan filas en el DOM y se continúa apenas aparecen.
        try {
            await page.waitForSelector('#MainContent_grdActuaciones tr td', { state: 'attached', timeout: 25000 });
            console.log(`  Tabla actuaciones lista (${Date.now()-t0}ms)`);
        } catch(e) {
            console.log(`  Tabla actuaciones no apareció en 25s (${Date.now()-t0}ms)`);
        }

        // 7. Extraer actuaciones SOLO de #MainContent_grdActuaciones
        // Columnas REALES (confirmadas con el HTML de la tabla):
        //   0 [lupa/botón]  |  1 Ciclo  |  2 Tipo Actuación  |  3 Fecha Actuación  |  4 Fecha de Registro
        // (antes se leía desde la celda 0 como si fuera "Ciclo", y todo quedaba corrido una posición)
        const actuaciones = await page.evaluate(() => {
            const tabla = document.querySelector('#MainContent_grdActuaciones');
            if (!tabla) return [];

            const limpiar = v => (v || '').replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();
            const filas = tabla.querySelectorAll('tr');
            const resultado = [];
            const vistos = {}; // para distinguir filas con datos idénticos sin depender del orden

            filas.forEach((fila, i) => {
                if (i === 0) return; // encabezado
                const celdas = Array.from(fila.querySelectorAll('td'));
                if (celdas.length < 5) return; // descarta fila de paginación u otras

                const ciclo    = limpiar(celdas[1].innerText);
                const tipo     = limpiar(celdas[2].innerText);
                const fechaAct = limpiar(celdas[3].innerText);
                const fechaReg = limpiar(celdas[4].innerText);

                if (!fechaAct || !tipo) return;

                // id_api determinístico (no depende de la posición de la fila, que
                // cambia cuando llegan actuaciones nuevas). Si dos filas son idénticas
                // se distinguen con un contador de repetición.
                const raw = `${ciclo}|${tipo}|${fechaAct}|${fechaReg}`;
                vistos[raw] = (vistos[raw] || 0) + 1;
                const clave = vistos[raw] > 1 ? `${raw}#${vistos[raw]}` : raw;
                let hash = 5381;
                for (let j = 0; j < clave.length; j++) {
                    hash = ((hash << 5) + hash) + clave.charCodeAt(j);
                }
                const idApi = 'TYBA_' + (hash >>> 0).toString(16).padStart(8, '0');

                const partes = [];
                if (ciclo)    partes.push(`Ciclo: ${ciclo}`);
                if (fechaReg) partes.push(`Registro: ${fechaReg}`);

                resultado.push({
                    id_api:        idApi,
                    fecha:         fechaAct,
                    actuacion:     tipo,
                    observaciones: partes.length ? partes.join(' | ') : null,
                });
            });
            return resultado;
        });

        console.log(`  ✓ ${actuaciones.length} actuaciones TYBA (${Date.now()-t0}ms)`);
        await context.close();

        // Normalizar fechas DD/MM/YYYY → YYYY-MM-DD
        const normalizarFecha = (f) => {
            if (/^\d{4}-\d{2}-\d{2}$/.test(f)) return f;
            const m = f.match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/);
            if (!m) return f;
            const y = m[3].length === 2 ? '20' + m[3] : m[3];
            return `${y}-${m[2].padStart(2,'0')}-${m[1].padStart(2,'0')}`;
        };

        res.json({
            actuaciones: actuaciones.map(a => ({
                ...a,
                fecha: normalizarFecha(a.fecha),
            }))
        });

    } catch (error) {
        if (context) await context.close().catch(() => {});
        if (browser && !browser.isConnected()) browser = null;
        console.error(`  ✗ TYBA error: ${error.message}`);
        res.status(500).json({ error: error.message });
    }
});



// ═══════════════════════════════════════════════════════════════
//  TYBA — ANEXOS
// ═══════════════════════════════════════════════════════════════

// Clic real de Playwright sobre $selector y captura lo que pase: pestaña
// nueva, descarga directa, o navegación de la MISMA pestaña a un visor tipo
// "Descargando.aspx" (los tres se han visto en distintos botones de TYBA).
// Devuelve { buffer, error } — si falla, buffer es null y error trae el motivo.
async function clicYDescargar(page, context, selector, etiqueta) {
    await page.waitForSelector(selector, { state: 'visible', timeout: 10000 }).catch(() => {});

    const urlAntes = page.url();

    // Diagnóstico: ¿hay algo tapando el botón? (si force:true no alcanza,
    // esto nos dice exactamente qué elemento está encima)
    const tapado = await page.evaluate((sel) => {
        const el = document.querySelector(sel);
        if (!el) return null;
        const r = el.getBoundingClientRect();
        const cx = r.left + r.width / 2, cy = r.top + r.height / 2;
        const top = document.elementFromPoint(cx, cy);
        if (top === el || el.contains(top)) return null;
        return top ? `${top.tagName}.${top.className || '(sin clase)'}` : '(fuera de pantalla)';
    }, selector).catch(() => null);
    if (tapado) console.log(`  [${etiqueta}] ⚠ algo tapa el botón: ${tapado}`);

    let popup = null, download = null;
    const popupWait = context.waitForEvent('page', { timeout: 15000 })
        .then(p => { popup = p; }).catch(() => {});
    const downloadWait = page.waitForEvent('download', { timeout: 15000 })
        .then(d => { download = d; }).catch(() => {});

    // FIX histórico: un clic sintético de JS (page.evaluate(() => el.click()))
    // puede ser bloqueado por Chromium al abrir target=_blank. page.click()
    // sí cuenta como interacción real.
    // FIX: { force: true } — tras la primera descarga, algo (barra de
    // descargas de Chromium, overlay residual) puede quedar tapando el botón
    // de las siguientes filas; el clic "normal" de Playwright se niega a
    // clickear un elemento que detecta como obstruido. force:true salta esa
    // verificación (seguimos apuntando al elemento exacto por su atributo
    // "name", así que no hay riesgo de clickear algo distinto por error).
    await page.click(selector, { force: true });

    await Promise.race([popupWait, downloadWait, page.waitForNavigation({ timeout: 15000 }).catch(() => {})]);
    await new Promise(r => setTimeout(r, 300));

    try {
        if (download) {
            const rutaTemp = await download.path();
            const buffer = fs.readFileSync(rutaTemp);
            console.log(`  [${etiqueta}] descarga directa (${buffer.length} bytes)`);
            return { buffer, error: null };
        }
        if (popup) {
            await popup.waitForLoadState('domcontentloaded', { timeout: 15000 }).catch(() => {});
            const pdfUrl = popup.url();
            console.log(`  [${etiqueta}] popup: ${pdfUrl}`);
            const resp = await context.request.get(pdfUrl);
            const buffer = await resp.body();
            await popup.close();
            return { buffer, error: null };
        }
        // Tercer caso: la MISMA pestaña navegó a otra URL (ej: Descargando.aspx)
        // en vez de abrir una nueva — hay que leer eso y luego volver atrás.
        if (page.url() !== urlAntes) {
            console.log(`  [${etiqueta}] navegación misma pestaña: ${page.url()}`);
            const resp = await context.request.get(page.url());
            const buffer = await resp.body();
            await page.goBack({ waitUntil: 'domcontentloaded', timeout: 15000 }).catch(() => {});
            return { buffer, error: null };
        }
        return { buffer: null, error: 'ni pestaña nueva, ni descarga, ni navegación tras el clic (15s)' };
    } catch (e) {
        return { buffer: null, error: e.message.split('\n')[0] };
    }
}

app.post("/tyba/anexos", async (req, res) => {
    const { radicado } = req.body;
    if (!radicado) return res.status(400).json({ error: "Radicado requerido" });

    const t0 = Date.now();
    console.log(`[${new Date().toLocaleTimeString()}] TYBA ANEXOS ${radicado}`);

    let context, page;
    try {
        const br = await getBrowser();
        context = await br.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            locale: 'es-CO',
            acceptDownloads: true, // TYBA puede servir el PDF como descarga directa en vez de pestaña nueva
        });
        page = await context.newPage();

        const detalle = await buscarYAbrirDetalleTyba(page, radicado, t0, 'ANEXOS');
        if (!detalle.ok) {
            await context.close();
            return res.json({ anexos: [], mensaje: 'No se encontró el proceso para extraer anexos' });
        }

        // FIX: los archivos adjuntos están en la pestaña "Archivos"
        // (#MainContent_grdArchivos) — no existe una pestaña "Anexos" en TYBA,
        // por eso antes esto siempre devolvía 0 sin importar qué tuviera el proceso.
        await page.evaluate(() => {
            const tabs = Array.from(document.querySelectorAll('a[data-toggle="tab"]'));
            const tab = tabs.find(a => a.innerText.trim().toLowerCase() === 'archivos');
            if (tab) tab.click();
        });
        await page.waitForTimeout(1000);

        const filas = await page.evaluate(() => {
            const tabla = document.querySelector('#MainContent_grdArchivos');
            if (!tabla) return [];
            return Array.from(tabla.querySelectorAll('tr')).slice(1).map((tr) => {
                const celdas = tr.querySelectorAll('td');
                const btn = celdas[0]?.querySelector('input[type="image"]');
                return {
                    inputName: btn?.getAttribute('name') || null,
                    nombre:    (celdas[1]?.innerText || '').trim(),
                    tamanioKb: (celdas[2]?.innerText || '').trim(),
                };
            }).filter(f => f.inputName && f.nombre);
        });
        console.log(`  Archivos en tabla: ${filas.length} ${JSON.stringify(filas.map(f => f.nombre))}`);

        // Cada botón NO es un link — es un input[type=image] que hace un postback
        // de ASP.NET (abre el PDF en pestaña nueva, dispara una descarga directa,
        // o navega la misma pestaña, según cómo responda TYBA ese día).
        const anexos = [];
        for (const fila of filas) {
            const selector = `input[name="${fila.inputName}"]`;
            const { buffer, error } = await clicYDescargar(page, context, selector, fila.nombre);

            if (error || !buffer || buffer.length === 0) {
                console.log(`  ✗ No se pudo descargar "${fila.nombre}": ${error || 'descargó 0 bytes'}`);
                continue;
            }

            const ext = (fila.nombre.match(/\.([a-zA-Z0-9]+)$/) || [null, 'pdf'])[1].toUpperCase();
            anexos.push({
                nombre:           fila.nombre,
                tipo:             ext,
                contenido_base64: buffer.toString('base64'),
            });
            console.log(`  ✓ Descargado: ${fila.nombre} (${buffer.length} bytes)`);
        }

        console.log(`  ✓ ${anexos.length}/${filas.length} anexos descargados (${Date.now()-t0}ms)`);
        await context.close();
        res.json({ anexos });

    } catch (error) {
        if (context) await context.close().catch(() => {});
        if (browser && !browser.isConnected()) browser = null;
        console.error(`  ✗ TYBA ANEXOS error: ${error.message}`);
        res.status(500).json({ error: error.message });
    }
});

// ═══════════════════════════════════════════════════════════════
//  TYBA — ANEXOS POR ACTUACIÓN
// ═══════════════════════════════════════════════════════════════
//
// Cada actuación tiene su propia lupa (👁) que abre un panel de detalle con
// SU propio archivo adjunto (ej: el PDF del auto que se dictó en esa
// actuación), distinto de los archivos generales del proceso (pestaña
// "Archivos" / 01DEMANDA.pdf, que ya cubre /tyba/anexos).
//
// El id_api de cada actuación se recalcula aquí con EXACTAMENTE el mismo
// algoritmo (mismo orden de lectura de filas, mismo hash djb2, mismo
// contador de duplicados) que usa /tyba/actuaciones al guardarlas — así el
// backend en PHP puede encontrar la actuación correcta en la BD y asociarle
// el anexo, sin tener que adivinar ni volver a pedir el radicado.

// Clickea la pestaña "Actuaciones" para que sea visible — SOLO necesario
// antes de hacerle clic de verdad a una lupa (page.click exige visibilidad).
// NO se usa para leer datos: ver leerIdApiActuaciones más abajo.
async function activarPestanaActuaciones(page) {
    await page.evaluate(() => {
        const tabs = Array.from(document.querySelectorAll('a[data-toggle="tab"]'));
        const tab = tabs.find(a => a.innerText.trim().toLowerCase() === 'actuaciones');
        if (tab) tab.click();
    });
    await page.waitForSelector('#MainContent_grdActuaciones', { state: 'visible', timeout: 10000 }).catch(() => {});
}

// Lee la lista con el id_api de cada fila (mismo cálculo exacto que usa
// /tyba/actuaciones al guardarlas) y el "name" del botón de su lupa.
//
// FIX: esta función hacía clic en la pestaña "Actuaciones" antes de leer, lo
// que generaba id_api que NO coincidían con los guardados en la BD (TYBA
// parece re-renderizar el grid de forma distinta tras ese clic — la tabla
// ya viene en el HTML inicial, como hace /tyba/actuaciones sin tocar
// pestaña alguna). Ahora esta función es un espejo EXACTO de esa lectura:
// sin clics, sin efectos secundarios, solo DOM.
async function leerIdApiActuaciones(page) {
    return page.evaluate(() => {
        const tabla = document.querySelector('#MainContent_grdActuaciones');
        if (!tabla) return [];

        const limpiar = v => (v || '').replace(/\u00a0/g, ' ').replace(/\s+/g, ' ').trim();
        const filas = tabla.querySelectorAll('tr');
        const resultado = [];
        const vistos = {};

        filas.forEach((fila, i) => {
            if (i === 0) return;
            const celdas = Array.from(fila.querySelectorAll('td'));
            if (celdas.length < 5) return;

            const btn      = celdas[0]?.querySelector('input[type="image"]');
            const ciclo    = limpiar(celdas[1].innerText);
            const tipo     = limpiar(celdas[2].innerText);
            const fechaAct = limpiar(celdas[3].innerText);
            const fechaReg = limpiar(celdas[4].innerText);

            if (!fechaAct || !tipo || !btn) return;

            const raw = `${ciclo}|${tipo}|${fechaAct}|${fechaReg}`;
            vistos[raw] = (vistos[raw] || 0) + 1;
            const clave = vistos[raw] > 1 ? `${raw}#${vistos[raw]}` : raw;
            let hash = 5381;
            for (let j = 0; j < clave.length; j++) {
                hash = ((hash << 5) + hash) + clave.charCodeAt(j);
            }
            const idApi = 'TYBA_' + (hash >>> 0).toString(16).padStart(8, '0');

            resultado.push({
                idApi,
                inputName: btn.getAttribute('name'),
                etiqueta:  `${tipo} (${fechaAct})`,
            });
        });
        return resultado;
    });
}

app.post("/tyba/anexos-actuaciones", async (req, res) => {
    const { radicado } = req.body;
    if (!radicado) return res.status(400).json({ error: "Radicado requerido" });

    const t0 = Date.now();
    console.log(`[${new Date().toLocaleTimeString()}] TYBA ANEXOS-ACTUACIONES ${radicado}`);

    // 1. Una primera carga solo para saber CUÁNTAS actuaciones hay y sus
    //    id_api — no se descarga nada todavía desde aquí.
    let context, page, filasInfo;
    try {
        const br = await getBrowser();
        context = await br.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            locale: 'es-CO',
            acceptDownloads: true,
        });
        page = await context.newPage();

        const detalle = await buscarYAbrirDetalleTyba(page, radicado, t0, 'ANEXOS-ACT:lista');
        if (!detalle.ok) {
            await context.close();
            return res.json({ anexosPorActuacion: [], mensaje: 'No se encontró el proceso' });
        }

        filasInfo = await leerIdApiActuaciones(page);
        await context.close();
        console.log(`  ${filasInfo.length} actuaciones a revisar`);
    } catch (error) {
        if (context) await context.close().catch(() => {});
        if (browser && !browser.isConnected()) browser = null;
        console.error(`  ✗ TYBA ANEXOS-ACTUACIONES error (listando): ${error.message}`);
        return res.status(500).json({ error: error.message });
    }

    // 2. Por cada actuación: FIX — en vez de navegar "lupa → detalle →
    //    Regresar → siguiente lupa" dentro de la MISMA página cargada, se
    //    recarga la búsqueda desde cero cada vez (igual que ya hacen con
    //    éxito /tyba/actuaciones y /tyba/anexos). Varios postbacks AJAX
    //    seguidos sobre la misma carga de TYBA dejaban el ViewState/
    //    EventValidation de la página en un estado donde el clic ya no
    //    disparaba nada (ni error, ni efecto) — por eso solo la primera
    //    actuación de cada corrida funcionaba y el resto fallaba igual.
    //    Es más lento (cada una vuelve a buscar desde cero, ~10s extra) pero
    //    nunca acumula ese estado roto. Corre en background, así que el
    //    tiempo extra no afecta al usuario.
    const anexosPorActuacion = [];
    for (const fi of filasInfo) {
        let ctxFila;
        try {
            const br = await getBrowser();
            ctxFila = await br.newContext({
                userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                locale: 'es-CO',
                acceptDownloads: true,
            });
            const pageFila = await ctxFila.newPage();

            const det = await buscarYAbrirDetalleTyba(pageFila, radicado, t0, `ANEXOS-ACT:${fi.etiqueta}`);
            if (!det.ok) { await ctxFila.close(); continue; }

            await activarPestanaActuaciones(pageFila); // solo para que el DOM permita clickear la lupa

            const selectorLupa = `input[name="${fi.inputName}"]`;
            await pageFila.waitForSelector(selectorLupa, { state: 'visible', timeout: 10000 }).catch(() => {});
            await pageFila.click(selectorLupa, { force: true });
            await pageFila.waitForSelector('#MainContent_pnlRegistroActuaciones', { state: 'visible', timeout: 15000 });

            const archivosInfo = await pageFila.evaluate(() => {
                const tabla = document.querySelector('#MainContent_grdArchivosActuaciones');
                if (!tabla) return [];
                return Array.from(tabla.querySelectorAll('tr')).slice(1).map(tr => {
                    const celdas = tr.querySelectorAll('td');
                    const btn = celdas[0]?.querySelector('input[type="image"]');
                    return {
                        inputName: btn?.getAttribute('name') || null,
                        nombre:    (celdas[1]?.innerText || '').trim(),
                    };
                }).filter(f => f.inputName && f.nombre);
            });

            const archivos = [];
            for (const af of archivosInfo) {
                const selectorDescarga = `input[name="${af.inputName}"]`;
                const { buffer, error } = await clicYDescargar(pageFila, ctxFila, selectorDescarga, `${fi.etiqueta} → ${af.nombre}`);
                if (error || !buffer || buffer.length === 0) {
                    console.log(`  ✗ [${fi.etiqueta}] "${af.nombre}": ${error || 'descargó 0 bytes'}`);
                    continue;
                }
                const ext = (af.nombre.match(/\.([a-zA-Z0-9]+)$/) || [null, 'pdf'])[1].toUpperCase();
                archivos.push({ nombre: af.nombre, tipo: ext, contenido_base64: buffer.toString('base64') });
                console.log(`  ✓ [${fi.etiqueta}] "${af.nombre}" (${buffer.length} bytes)`);
            }

            if (archivos.length > 0) {
                anexosPorActuacion.push({ id_api: fi.idApi, archivos });
            } else if (archivosInfo.length === 0) {
                console.log(`  (sin archivos) [${fi.etiqueta}]`);
            }

            await ctxFila.close();
        } catch (e) {
            console.log(`  ✗ [${fi.etiqueta}] error procesando: ${e.message.split('\n')[0]}`);
            if (ctxFila) await ctxFila.close().catch(() => {});
        }
    }

    const totalArchivos = anexosPorActuacion.reduce((n, a) => n + a.archivos.length, 0);
    console.log(`  ✓ ${totalArchivos} archivo(s) en ${anexosPorActuacion.length} actuación(es) (${Date.now()-t0}ms)`);
    res.json({ anexosPorActuacion });
});

// ═══════════════════════════════════════════════════════════════
//  HEALTH + START
// ═══════════════════════════════════════════════════════════════

app.get("/health", (req, res) => res.json({
    status: "ok",
    browser: browser?.isConnected() ?? false,
    endpoints: ['/samai/actuaciones', '/publicaciones/consultar', '/tyba/actuaciones', '/tyba/anexos', '/tyba/anexos-actuaciones']
}));

getBrowser().catch(e => console.error("Error pre-lanzando browser:", e));

app.listen(PORT, () => console.log(`\n🏛  Servicio Node.js en http://localhost:${PORT}\n   - SAMAI:         POST /samai/actuaciones\n   - Publicaciones: POST /publicaciones/consultar\n   - TYBA:          POST /tyba/actuaciones\n   - TYBA anexos:   POST /tyba/anexos\n   - TYBA anexos x actuación: POST /tyba/anexos-actuaciones\n`));