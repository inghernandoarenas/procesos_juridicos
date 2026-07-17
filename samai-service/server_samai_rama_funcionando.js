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

        // 1. Cargar página inicial para establecer sesión
        await page.goto(`${PUB_BASE}/web/publicaciones-procesales/inicio`,
            { waitUntil: 'domcontentloaded', timeout: 45000 });
        console.log(`  Sesión establecida (${Date.now()-t0}ms)`);
        await page.waitForTimeout(3000);

        const PFX = `_${PORTLET}_`;

        // 2. Llenar fechas (los campos son type=date, formato YYYY-MM-DD)
        try {
            await page.fill(`[name="${PFX}fechaInicio"]`, fecha_inicio);
            await page.fill(`[name="${PFX}fechaFin"]`, fecha_fin);
            console.log(`  Fechas llenadas: ${fecha_inicio} → ${fecha_fin}`);
        } catch(e) {
            console.log('  Error fechas:', e.message.split('\n')[0]);
        }

        // 3. Esperar que cargue el select de despachos y seleccionar
        try {
            await page.waitForSelector(`[name="${PFX}idDespacho"]`, { timeout: 10000 });
            // Obtener las opciones disponibles para debug
            const opciones = await page.evaluate((sel) => {
                const s = document.querySelector(sel);
                if (!s) return [];
                return Array.from(s.options).slice(0, 5).map(o => ({ v: o.value, t: o.text.substring(0,40) }));
            }, `[name="${PFX}idDespacho"]`);
            console.log('  Opciones despacho (primeras 5):', JSON.stringify(opciones));

            // Intentar seleccionar por valor exacto
            await page.selectOption(`[name="${PFX}idDespacho"]`, { value: codigo_despacho });
            console.log(`  Despacho seleccionado: ${codigo_despacho}`);
        } catch(e) {
            console.log('  Error despacho:', e.message.split('\n')[0]);
        }

        // 4. Click en botón buscar
        try {
            const btn = await page.$(`button[type=submit], input[type=submit]`);
            if (btn) {
                const btnText = await btn.evaluate(el => el.innerText || el.value);
                console.log(`  Botón encontrado: "${btnText}"`);
                await Promise.all([
                    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {}),
                    btn.click(),
                ]);
                console.log(`  Formulario enviado (${Date.now()-t0}ms)`);
            } else {
                console.log('  Sin botón submit — haciendo Enter en campo fecha');
                await page.press(`[name="${PFX}fechaFin"]`, 'Enter');
            }
        } catch(e) {
            console.log('  Error submit:', e.message.split('\n')[0]);
        }

        await page.waitForTimeout(4000);

        // 5. Extraer texto de resultados
        const texto = await page.evaluate(() => {
            const selectors = [
                '[id*="BIyXQFHVaYaq"] .portlet-body',
                '[id*="BIyXQFHVaYaq"]',
                '.portlet-body',
                '#content',
                'main',
            ];
            for (const sel of selectors) {
                const el = document.querySelector(sel);
                if (el && el.innerText.includes('Categor')) return el.innerText;
            }
            return document.body.innerText;
        });

        console.log(`  Tiene Categorías: ${texto.includes('Categor')}`);
        console.log(`  Tiene Fecha Publicación: ${texto.includes('Fecha de Publicaci')}`);
        console.log(`  Texto (300): ${texto.substring(0, 300).replace(/\n/g,' ')}`);

        await context.close();

        const publicaciones = parsearPublicaciones(texto, codigo_despacho);
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
//  HEALTH + START
// ═══════════════════════════════════════════════════════════════

app.get("/health", (req, res) => res.json({
    status: "ok",
    browser: browser?.isConnected() ?? false,
    endpoints: ['/samai/actuaciones', '/publicaciones/consultar']
}));

getBrowser().catch(e => console.error("Error pre-lanzando browser:", e));

app.listen(PORT, () => console.log(`\n🏛  Servicio Node.js en http://localhost:${PORT}\n   - SAMAI:         POST /samai/actuaciones\n   - Publicaciones: POST /publicaciones/consultar\n`));