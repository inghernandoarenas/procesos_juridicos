# Despliegue en Oracle Cloud Always Free

Guía paso a paso para poner Procesos Jurídicos en un servidor público gratis,
sin perder nada de lo que ya funciona (sincronización con portales, cron,
notificaciones). Todo corre en **una sola VM** — PHP, MySQL y el
microservicio Node/Playwright — igual que hoy corre todo junto en tu XAMPP
local.

---

## 0. Antes de empezar

- Crea una cuenta en [Oracle Cloud](https://www.oracle.com/cloud/free/) (piden
  tarjeta solo para verificar identidad; el plan Always Free no cobra).
- Ten a mano tu repositorio de GitHub (`procesos_juridicos`) ya actualizado
  con los últimos cambios.

## 1. Crear la VM (consola de Oracle Cloud)

1. Menú → **Compute → Instances → Create Instance**.
2. Nombre: `procesos-juridicos`.
3. **Image and shape** → Edit → Image: **Ubuntu 22.04** (o 24.04) → Shape:
   **VM.Standard.A1.Flex** (Ampere, es la del plan Always Free con más RAM —
   puedes asignarle hasta 4 OCPU / 24 GB gratis; con 1 OCPU / 6 GB sobra para
   esta app).
4. **Add SSH keys**: genera un par de llaves o sube tu llave pública (la vas
   a necesitar para conectarte).
5. Crea la instancia y anota su **IP pública**.
6. **Networking → Virtual Cloud Network → Security Lists** (de la subred de
   tu VM) → **Add Ingress Rules**: agrega reglas para los puertos **80**
   (HTTP) y **443** (HTTPS), origen `0.0.0.0/0`. *(Este es un firewall
   aparte del de la VM — si solo abres el de la VM y no este, igual no va a
   entrar tráfico.)*

## 2. Conectarte y preparar el software base

```bash
ssh -i tu_llave_privada.pem ubuntu@<IP_DE_LA_VM>
```

Sube el script `infra/setup_oracle_vm.sh` (puedes copiar/pegar su
contenido con `nano setup_oracle_vm.sh`, o `scp` desde tu máquina) y
ejecútalo:

```bash
chmod +x setup_oracle_vm.sh
sudo ./setup_oracle_vm.sh
```

Esto instala Apache, PHP, MySQL, Composer y Node.js, y abre el firewall
local (`ufw`) en los puertos 80/443.

## 3. Subir el proyecto

La URL debe quedar igual que en local (`/procesos_juridicos/...`), así que
el proyecto va **dentro** del DocumentRoot por defecto de Apache:

```bash
cd /var/www/html
sudo git clone https://github.com/inghernandoarenas/procesos_juridicos.git
sudo chown -R ubuntu:ubuntu procesos_juridicos
cd procesos_juridicos

composer install --no-dev

cd samai-service
npm install
PLAYWRIGHT_BROWSERS_PATH=./.playwright-browsers npx playwright install --with-deps chromium
cd ..
```

## 4. Base de datos

```bash
sudo mysql
```
Dentro de MySQL:
```sql
CREATE DATABASE oficina_juridica CHARACTER SET utf8mb4;
CREATE USER 'oficina_app'@'localhost' IDENTIFIED BY 'PON_AQUI_UNA_CLAVE_FUERTE';
GRANT ALL PRIVILEGES ON oficina_juridica.* TO 'oficina_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
Importar la estructura (y opcionalmente los datos de prueba):
```bash
mysql -u oficina_app -p oficina_juridica < bd/oficina_juridica.sql
```

## 5. Credenciales (secrets.php)

**Nunca** pongas credenciales reales directo en el código — así fue como el
token de Twilio quedó expuesto en el repositorio antes de este despliegue.

```bash
cp backend/config/secrets.example.php backend/config/secrets.php
nano backend/config/secrets.php
```
Rellena:
- `db_username` / `db_password` → los del paso 4.
- `twilio_account_sid` / `twilio_auth_token` → **genera un token nuevo** en
  el Twilio Console (Account → API keys & tokens → Auth Token → Create new)
  y pon ese, no el viejo.

## 6. Permisos de escritura

```bash
sudo chown -R www-data:www-data /var/www/html/procesos_juridicos/logs
sudo chown -R www-data:www-data /var/www/html/procesos_juridicos/plantillas   # subir/reemplazar plantillas Word
sudo chown -R www-data:www-data /var/www/html/procesos_juridicos/samai-service/.playwright-browsers
```

## 7. URL del sistema (para los links en las notificaciones)

Edita `backend/cron/sincronizar_actuaciones.php` y cambia:
```php
define('SISTEMA_URL', 'http://localhost');
```
por tu IP pública o dominio real, por ejemplo:
```php
define('SISTEMA_URL', 'http://<IP_DE_LA_VM>/procesos_juridicos');
```

## 8. samai-service como servicio permanente

```bash
sudo cp infra/samai-service.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable samai-service
sudo systemctl start samai-service
sudo systemctl status samai-service   # debe decir "active (running)"
```

## 9. Cron de sincronización automática

```bash
sudo crontab -e -u www-data
```
Agrega (corre cada hora, ajusta a tu gusto):
```
0 * * * * /usr/bin/php /var/www/html/procesos_juridicos/backend/cron/sincronizar_actuaciones.php >> /var/www/html/procesos_juridicos/logs/cron.log 2>&1
```

## 10. Probar

Abre `http://<IP_DE_LA_VM>/procesos_juridicos/frontend/login.php` en el
navegador. Si algo no carga, revisa:
```bash
sudo tail -f /var/log/apache2/error.log
sudo journalctl -u samai-service -f
```

## 11. (Opcional, recomendado antes de mostrarlo a clientes) Dominio + HTTPS

Con un dominio propio (o uno gratis tipo `duckdns.org`) apuntando a la IP:
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d tu-dominio.com
```

---

## Checklist de seguridad ya resuelta en el código

- ✅ `.htaccess` bloqueando acceso web directo a `bd/`, `logs/`,
  `samai-service/`, `vendor/` y `backend/config/`.
- ✅ Credenciales (BD y Twilio) movidas a `backend/config/secrets.php`
  (fuera del repositorio, vía `.gitignore`).
- ⚠️ **Pendiente de hacer tú**: rotar el Auth Token de Twilio en su
  Console — el que estaba en el código ya se dio por comprometido.
- ⚠️ Antes de anunciar la URL a terceros, considera: contraseña temporal
  distinta por usuario de prueba, y revisar que los datos de ejemplo en la
  BD no sean datos reales de clientes si vas a dejarla pública para demos.
