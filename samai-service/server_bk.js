import express from "express";
import { chromium } from "playwright";

const app  = express();
const PORT = 3001;
app.use(express.json());

// ── Browser persistente — se abre una vez y se reutiliza ───────
let browser   = null;
let launching = false;

async function getBrowser() {
    if (browser && browser.isConnected()) return browser;
    if (launching) {
        // Esperar a que termine el lanzamiento en curso
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

// ── Obtener guid via fetch (sin browser) ──────────────────────
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

// ── Resolver captcha ───────────────────────────────────────────
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

// ── Extraer actuaciones del HTML ───────────────────────────────
async function extraerActuaciones(page) {
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

                // Tomar la ÚLTIMA celda de fecha como fecha de actuación
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

// ── Endpoint principal ─────────────────────────────────────────
app.post("/samai/actuaciones", async (req, res) => {
    const { radicado } = req.body;
    if (!radicado) return res.status(400).json({ error: "Radicado requerido" });

    const t0 = Date.now();
    console.log(`[${new Date().toLocaleTimeString()}] ${radicado}`);

    // PASO 1: guid sin browser (~600ms)
    let guid;
    try { guid = await obtenerGuid(radicado); }
    catch(e) { return res.status(500).json({ error: e.message }); }
    if (!guid) return res.json({ actuaciones: [], mensaje: 'No encontrado en SAMAI' });
    console.log(`  GUID: ${guid} (${Date.now()-t0}ms)`);

    // PASO 2: reutilizar browser existente (evita ~5s de lanzamiento)
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
        console.log(`  Página cargada (${Date.now()-t0}ms)`);

        // PASO 3: captcha si aparece
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

        // PASO 4: extraer
        const actuaciones = await extraerActuaciones(page);
        console.log(`  ✓ ${actuaciones.length} actuaciones (${Date.now()-t0}ms total)`);

        await context.close(); // cerrar contexto, NO el browser

        const resultado = actuaciones.map(({ _rowIdx, ...a }) => a);
        res.json({ actuaciones: resultado });

    } catch (error) {
        if (context) await context.close().catch(() => {});
        // Si el browser murió, forzar reinicio
        if (browser && !browser.isConnected()) browser = null;
        console.error(`  ✗ ${error.message}`);
        res.status(500).json({ error: error.message });
    }
});

app.get("/health", (req, res) => res.json({ status: "ok", browser: browser?.isConnected() ?? false }));

// Pre-lanzar browser al iniciar para que el primer request sea más rápido
getBrowser().catch(e => console.error("Error pre-lanzando browser:", e));

app.listen(PORT, () => console.log(`\n🏛  Servicio SAMAI en http://localhost:${PORT}\n`));