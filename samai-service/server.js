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
        // de ASP.NET con target=_blank (abre el PDF en pestaña nueva, o a veces
        // dispara una descarga directa según cómo responda TYBA ese día). No hay
        // URL fija que guardar: hay que clickear y capturar lo que pase.
        const anexos = [];
        for (const fila of filas) {
            try {
                const selector = `input[name="${fila.inputName}"]`;
                await page.waitForSelector(selector, { state: 'visible', timeout: 10000 }).catch(() => {});

                let popup = null, download = null;
                const popupWait = context.waitForEvent('page', { timeout: 15000 })
                    .then(p => { popup = p; }).catch(() => {});
                const downloadWait = page.waitForEvent('download', { timeout: 15000 })
                    .then(d => { download = d; }).catch(() => {});

                // FIX: antes se disparaba con page.evaluate(() => el.click()), un clic
                // sintético de JS. Chromium puede bloquear la apertura de target=_blank
                // cuando el clic no viene de una interacción "real" — por eso el popup
                // nunca llegaba. page.click() sí cuenta como interacción real.
                await page.click(selector);

                // Seguir en cuanto CUALQUIERA de los dos llegue (no esperar los 15s
                // completos de ambos), con un pequeño margen por si llegan casi juntos.
                await Promise.race([popupWait, downloadWait]);
                await new Promise(r => setTimeout(r, 300));

                let buffer;
                if (download) {
                    const rutaTemp = await download.path();
                    buffer = fs.readFileSync(rutaTemp);
                    console.log(`  [${fila.nombre}] descarga directa (${buffer.length} bytes)`);
                } else if (popup) {
                    await popup.waitForLoadState('domcontentloaded', { timeout: 15000 }).catch(() => {});
                    const pdfUrl = popup.url();
                    console.log(`  [${fila.nombre}] popup: ${pdfUrl}`);
                    const resp = await context.request.get(pdfUrl);
                    buffer = await resp.body();
                    await popup.close();
                } else {
                    throw new Error('ni pestaña nueva ni descarga tras el clic (15s)');
                }

                if (!buffer || buffer.length === 0) {
                    console.log(`  ✗ "${fila.nombre}" descargó 0 bytes, se omite`);
                    continue;
                }

                const ext = (fila.nombre.match(/\.([a-zA-Z0-9]+)$/) || [null, 'pdf'])[1].toUpperCase();
                anexos.push({
                    nombre:            fila.nombre,
                    tipo:              ext,
                    contenido_base64:  buffer.toString('base64'),
                });
                console.log(`  ✓ Descargado: ${fila.nombre} (${buffer.length} bytes)`);
            } catch (e) {
                console.log(`  ✗ No se pudo descargar "${fila.nombre}": ${e.message.split('\n')[0]}`);
            }
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
//  HEALTH + START
// ═══════════════════════════════════════════════════════════════

app.get("/health", (req, res) => res.json({
    status: "ok",
    browser: browser?.isConnected() ?? false,
    endpoints: ['/samai/actuaciones', '/publicaciones/consultar', '/tyba/actuaciones']
}));

getBrowser().catch(e => console.error("Error pre-lanzando browser:", e));

app.listen(PORT, () => console.log(`\n🏛  Servicio Node.js en http://localhost:${PORT}\n   - SAMAI:         POST /samai/actuaciones\n   - Publicaciones: POST /publicaciones/consultar\n   - TYBA:          POST /tyba/actuaciones\n`));