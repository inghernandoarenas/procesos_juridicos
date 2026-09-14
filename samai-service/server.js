import express from "express";
import { chromium } from "playwright";

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
// ═══════════════════════════════════════════════════════════════

async function obtenerGuid(radicado) {
    const corp = radicado.replace(/\D/g, '').substring(0, 7);
    const r = await fetch(
        'https://samai.consejodeestado.gov.co/Vistas/Casos/Jprocesos.ashx/listaprocesosdata',
        {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json; charset=UTF-8',
                'Accept':           'application/json',
                'Referer':          'https://samai.consejodeestado.gov.co/Vistas/Casos/procesos.aspx',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                FW_tipobusqueda: 'FW_Rbtradicado', FW_ppexacta: '',
                FW_tipoarea: 'FW_RbtCorporacion', FW_Txtcriterios: radicado,
                FW_LstCorporacion: corp, FW_LstSeccion: '', FW_LstPonente: '',
                FW_FechaI: '', FW_FechaF: '', FW_LstcriterioV: '', FW_LstcriterioP: '',
            }),
        }
    );
    const data = await r.json();
    if (!Array.isArray(data) || !data.length) return null;
    const m = (data[0].ACCIONES || '').match(/goprocs_gestion\('([^']+)','([^']+)'/);
    return m ? m[1] + m[2] : null;
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

async function extraerActuacionesSamai(page) {
    return page.evaluate(() => {
        const esFecha    = v => /^\d{2}\/\d{2}\/\d{4}$/.test(v) || /^\d{4}-\d{2}-\d{2}$/.test(v);
        const parseFecha = v => {
            if (/^\d{2}\/\d{2}\/\d{4}$/.test(v)) {
                const p = v.split('/'); return `${p[2]}-${p[1]}-${p[0]}`;
            }
            return v.substring(0, 10);
        };
        const resultado = [];
        for (const tabla of document.querySelectorAll('table')) {
            const filas = tabla.querySelectorAll('tr');
            if (filas.length < 2) continue;
            const header = filas[0].innerText.toLowerCase();
            if (!header.includes('fecha') && !header.includes('actuaci')) continue;
            for (let i = 1; i < filas.length; i++) {
                const cols = Array.from(filas[i].querySelectorAll('td'))
                    .map(c => c.innerText.trim().replace(/\s+/g, ' '));
                if (cols.length < 2) continue;
                let lastFechaIdx = -1;
                for (let j = 0; j < cols.length; j++)
                    if (esFecha(cols[j])) lastFechaIdx = j;
                if (lastFechaIdx < 0) continue;
                const fecha = parseFecha(cols[lastFechaIdx]);
                let actuacion = null, obs = null;
                for (let j = lastFechaIdx + 1; j < cols.length; j++) {
                    const v = cols[j];
                    if (!esFecha(v) && v.length > 2 && !actuacion) actuacion = v;
                    else if (actuacion && obs === null) obs = v || null;
                }
                if (fecha && actuacion)
                    resultado.push({ fecha, actuacion, observaciones: obs, _rowIdx: i });
            }
            if (resultado.length > 0) break;
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

    let guid;
    try { guid = await obtenerGuid(radicado); }
    catch(e) { return res.status(500).json({ error: e.message }); }
    if (!guid) return res.json({ actuaciones: [], mensaje: 'No encontrado en SAMAI' });
    console.log(`  GUID: ${guid} (${Date.now()-t0}ms)`);

    let context, page;
    try {
        const br = await getBrowser();
        context  = await br.newContext({
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            locale: 'es-CO',
        });
        page = await context.newPage();
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
        console.error(`  ✗ ${error.message}`);
        res.status(500).json({ error: error.message });
    }
});

// ═══════════════════════════════════════════════════════════════
//  PUBLICACIONES PROCESALES
// ═══════════════════════════════════════════════════════════════

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
        const todasPublicaciones = parsearPublicaciones(texto, codigo_despacho);

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
//  TYBA — Justicia XXI Web
// ═══════════════════════════════════════════════════════════════

const TYBA_BASE = 'https://procesojudicial.ramajudicial.gov.co/Justicia21';
const TYBA_CONSULTA  = `${TYBA_BASE}/Administracion/Ciudadanos/frmConsulta.aspx?opcion=consulta`;
const TYBA_DETALLE   = `${TYBA_BASE}/Administracion/Ciudadanos/frmConsultaProceso.aspx`;

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

        // 1. Cargar página de consulta
        await page.goto(TYBA_CONSULTA, { waitUntil: 'domcontentloaded', timeout: 30000 });
        console.log(`  Página cargada (${Date.now()-t0}ms)`);
        await page.waitForTimeout(2000);

        // 2. Llenar radicado
        await page.fill('#MainContent_txtCodigoProceso', radicado);
        console.log(`  Radicado ingresado`);

        // 3. Esperar que reCAPTCHA esté listo y hacer submit
        // El reCAPTCHA v2 se resuelve automáticamente cuando Playwright lo carga
        // Intentar click en el checkbox del captcha si existe
        try {
            const captchaFrame = page.frameLocator('iframe[title*="reCAPTCHA"]').first();
            await captchaFrame.locator('#recaptcha-anchor').click({ timeout: 5000 });
            console.log(`  reCAPTCHA clicked`);
            await page.waitForTimeout(2000);
        } catch(e) {
            console.log(`  reCAPTCHA no encontrado o ya resuelto`);
        }

        // 4. Click en Consultar
        await page.click('#MainContent_btnConsultar');
        await page.waitForTimeout(3000);
        console.log(`  Búsqueda enviada (${Date.now()-t0}ms)`);

        // 5. Extraer resultado de la tabla
        const resultados = await page.evaluate(() => {
            const filas = document.querySelectorAll('#MainContent_gvResultado tr, table tr');
            const datos = [];
            filas.forEach((fila, i) => {
                if (i === 0) return; // skip header
                const celdas = fila.querySelectorAll('td');
                if (celdas.length >= 3) {
                    // Buscar link de detalle
                    const link = fila.querySelector('a, input[type=image]');
                    datos.push({
                        codigo: celdas[1]?.innerText?.trim() || '',
                        clase:  celdas[2]?.innerText?.trim() || '',
                        depto:  celdas[3]?.innerText?.trim() || '',
                        ciudad: celdas[4]?.innerText?.trim() || '',
                        despacho: celdas[5]?.innerText?.trim() || '',
                        href:   link?.href || '',
                        onclick: link?.getAttribute('onclick') || '',
                    });
                }
            });
            return datos;
        });

        console.log(`  Resultados encontrados: ${resultados.length}`);

        if (resultados.length === 0) {
            await context.close();
            return res.json({ actuaciones: [], mensaje: 'No encontrado en TYBA' });
        }

        // 6. Ir al detalle del primer resultado — click en la lupa
        try {
            // Intentar click en el ícono de la lupa (primer resultado)
            const lupa = await page.$('#MainContent_gvResultado td a, #MainContent_gvResultado td input[type=image], table td a img');
            if (lupa) {
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {}),
                    lupa.click(),
                ]);
            } else {
                // Intentar navegar por onclick
                const onclick = resultados[0].onclick;
                if (onclick) {
                    await page.evaluate((oc) => eval(oc), onclick);
                    await page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {});
                }
            }
            console.log(`  Detalle cargado (${Date.now()-t0}ms)`);
        } catch(e) {
            console.log(`  Error navegando al detalle: ${e.message.split('\n')[0]}`);
        }

        await page.waitForTimeout(2000);

        // 7. Extraer actuaciones del detalle
        const actuaciones = await page.evaluate(() => {
            // Buscar tab de actuaciones y hacer click
            const tabs = document.querySelectorAll('a[href*="Actuaciones"], li a, .nav-tabs a');
            let actTab = null;
            tabs.forEach(t => {
                if ((t.innerText || '').toLowerCase().includes('actuac')) actTab = t;
            });
            if (actTab) actTab.click();

            // Extraer datos del proceso
            const getVal = (label) => {
                const inputs = document.querySelectorAll('input[type=text], input[readonly]');
                for (const inp of inputs) {
                    const lbl = inp.previousElementSibling || inp.closest('td')?.previousElementSibling;
                    if (lbl && (lbl.innerText || '').includes(label)) return inp.value || '';
                }
                return '';
            };

            // Extraer filas de actuaciones
            const actuaciones = [];
            const tablas = document.querySelectorAll('table');
            tablas.forEach(tabla => {
                const headers = tabla.querySelector('tr');
                if (!headers) return;
                const headerText = headers.innerText.toLowerCase();
                if (!headerText.includes('fecha') && !headerText.includes('actuac')) return;

                const filas = tabla.querySelectorAll('tr');
                filas.forEach((fila, i) => {
                    if (i === 0) return;
                    const celdas = fila.querySelectorAll('td');
                    if (celdas.length >= 2) {
                        const textos = Array.from(celdas).map(c => c.innerText.trim());
                        // Buscar fecha en las celdas
                        const fechaCell = textos.find(t => /\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/.test(t) || /\d{4}-\d{2}-\d{2}/.test(t));
                        const actCell   = textos.find(t => t.length > 3 && !(/^\d{1,2}[\/\-]/.test(t)));
                        if (fechaCell) {
                            actuaciones.push({
                                fecha:         fechaCell,
                                actuacion:     actCell || textos[1] || '',
                                observaciones: textos[2] || null,
                            });
                        }
                    }
                });
            });
            return actuaciones;
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
//  HEALTH + START
// ═══════════════════════════════════════════════════════════════

app.get("/health", (req, res) => res.json({
    status: "ok",
    browser: browser?.isConnected() ?? false,
    endpoints: ['/samai/actuaciones', '/publicaciones/consultar', '/tyba/actuaciones']
}));

getBrowser().catch(e => console.error("Error pre-lanzando browser:", e));

app.listen(PORT, () => console.log(`\n🏛  Servicio Node.js en http://localhost:${PORT}\n   - SAMAI:         POST /samai/actuaciones\n   - Publicaciones: POST /publicaciones/consultar\n   - TYBA:          POST /tyba/actuaciones\n`));