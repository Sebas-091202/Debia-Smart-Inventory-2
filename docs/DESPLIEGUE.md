# Despliegue: Supabase + AWS Lightsail

Guía para poner Debia Smart Inventory en internet con:

- la base de datos en **Supabase** (PostgreSQL);
- la aplicación PHP en una instancia **Lightsail** (Ubuntu + Apache).

Los pasos están en orden; cada uno supone que el anterior terminó bien.

```
Navegador ──HTTPS──▶ Lightsail (Apache + PHP, carpeta public/) ──SSL──▶ Supabase (esquema "inventario")
```

---

## 1. Supabase: crear la base

1. **Región.** Crea el proyecto en la **misma región de AWS que la instancia de Lightsail**; por ejemplo, `us-east-1` si la instancia está en Virginia. Cada página hace varias consultas y, con la base en otro continente, cada una suma latencia.
2. **Esquema.** Ve a *SQL Editor* → *New query*, pega el contenido de [`database/esquema.sql`](../database/esquema.sql) y pulsa *Run*.
3. **Rol de la aplicación** (recomendado). Abre [`database/rol_aplicacion.sql`](../database/rol_aplicacion.sql).
   1. Reemplaza `CAMBIAR_POR_UNA_CLAVE_LARGA` por una contraseña aleatoria de 32 o más caracteres.
   2. Ejecútalo.
   3. Guarda esa contraseña en un gestor de contraseñas, no en el repositorio.
4. **Comprueba que la API REST no publica las tablas.** Ve a *Project Settings* → *Data API* → *Exposed schemas*. Ahí **no** debe aparecer `inventario`.

   La aplicación no usa la API REST de Supabase; se conecta directo a PostgreSQL. Además, las tablas tienen RLS activado y los roles `anon` y `authenticated` no tienen permisos.
5. **Datos de conexión.** Ve a *Connect* → **Session pooler**. Funciona por IPv4, que es lo que usa Lightsail. De ahí copia:
   - el host, que tiene la forma `aws-0-<region>.pooler.supabase.com`;
   - el puerto `5432`;
   - el usuario, que tiene la forma `postgres.<ref>`. Si usas el rol del paso 3, es `dsi_app.<ref>`.

   > ⚠️ No uses el **Transaction pooler** (puerto 6543): no admite las sentencias preparadas que usa la aplicación.
6. **Certificado SSL** (recomendado). Ve a *Database* → *Settings* → *SSL Configuration* → *Download certificate*. Así la conexión, además de ir cifrada, valida que el servidor es de verdad Supabase. Ver paso 3.5.

> **Plan gratuito:** Supabase pausa los proyectos inactivos y no incluye copias de seguridad descargables. Para producción considera el plan Pro, o al menos los respaldos del paso 5.

---

## 2. Migrar los datos actuales (MySQL de XAMPP → Supabase)

Se hace **una sola vez**, desde el PC que tiene XAMPP con la base `debia_smart_inventory`.

1. **Habilita PostgreSQL en el PHP de XAMPP.** En `C:\xampp\php\php.ini` quita el `;` de la línea:
   ```ini
   extension=pdo_pgsql
   ```
   Luego reinicia Apache desde el panel de XAMPP.
2. **Configura la conexión.** En la raíz del proyecto, copia `.env.example` como `.env` y completa los datos de Supabase del paso 1.5:
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASS`
3. **Ejecuta la migración** desde la raíz del proyecto:
   ```bash
   php bin/migrar_desde_mysql.php
   ```
   Qué hace el script:
   - **solo lee** de MySQL;
   - en Supabase escribe todo en una transacción y conserva los ids;
   - **se niega** a ejecutarse si las tablas destino ya tienen datos, así que repetirlo por error no duplica nada.

   El resultado esperado es una línea por tabla con el número de filas copiadas. Por ejemplo: `equipos 282 filas`.
4. **Verifica.** En Supabase → *Table Editor*, elige el esquema `inventario` y revisa que estén los datos.

No se migran:
- `intentos_login`, porque son datos temporales;
- `preventivos_programados` e `historial_equipos`, porque son funciones retiradas de la aplicación.

Esas filas siguen en MySQL si algún día se necesitan.

---

## 3. Lightsail: preparar la instancia

Se asume **Ubuntu 22.04/24.04** (blueprint "OS Only"). Si la instancia es el blueprint **LAMP de Bitnami**, cambian las rutas:
- Apache está en `/opt/bitnami/apache`;
- PHP está en `/opt/bitnami/php`;
- el certificado se emite con `sudo /opt/bitnami/bncert-tool` en lugar de certbot.

### 3.1 Red

En la consola de Lightsail:

1. **IP estática.** *Networking* → *Create static IP*. Adjúntala a la instancia, para que la IP no cambie al reiniciarla.
2. **Firewall (IPv4 e IPv6).** Deja abiertos solo estos puertos:
   - **HTTP 80**, para la emisión del certificado y la redirección a HTTPS;
   - **HTTPS 443**;
   - **SSH 22**, restringido a tu IP ("Restrict to IP address"), no abierto a todo internet.

   La base de datos está en Supabase, así que no hay que abrir ningún puerto de base de datos.
3. **DNS.** En el proveedor del dominio, crea un registro **A** que apunte a la IP estática; por ejemplo, `inventario.tudominio.com` → `IP`.

### 3.2 Paquetes

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 php libapache2-mod-php php-pgsql php-mbstring git certbot python3-certbot-apache unattended-upgrades
php -v          # debe ser 8.1 o superior
php -m | grep pdo_pgsql
```

### 3.3 Código

El repositorio debe ser **privado**.

```bash
sudo git clone <url-del-repositorio-privado> /var/www/debia-smart-inventory
sudo chown -R root:www-data /var/www/debia-smart-inventory
sudo find /var/www/debia-smart-inventory -type d -exec chmod 750 {} \;
sudo find /var/www/debia-smart-inventory -type f -exec chmod 640 {} \;
```

Apache, que corre como `www-data`, puede leer los archivos pero no modificarlos.

### 3.4 Configuración (.env)

```bash
sudo cp /var/www/debia-smart-inventory/.env.example /var/www/debia-smart-inventory/.env
sudo nano /var/www/debia-smart-inventory/.env       # datos de Supabase, APP_ENV=production
sudo chown root:www-data /var/www/debia-smart-inventory/.env
sudo chmod 640 /var/www/debia-smart-inventory/.env
```

### 3.5 Certificado de Supabase (opcional, recomendado)

1. Copia el certificado descargado en el paso 1.6 a `/etc/ssl/certs/supabase-ca.crt`.
2. En `.env` define:
   ```
   DB_SSLROOTCERT=/etc/ssl/certs/supabase-ca.crt
   ```
   Con eso la conexión usa `verify-full`.

### 3.6 Apache y HTTPS

```bash
sudo cp /var/www/debia-smart-inventory/deploy/apache-vhost.conf /etc/apache2/sites-available/debia-smart-inventory.conf
sudo nano /etc/apache2/sites-available/debia-smart-inventory.conf   # poner el dominio real
sudo a2ensite debia-smart-inventory && sudo a2dissite 000-default
sudo apache2ctl configtest && sudo systemctl reload apache2
sudo certbot --apache -d inventario.tudominio.com                  # elegir "redirect"
```

Certbot renueva el certificado solo; puedes verificarlo con `sudo certbot renew --dry-run`.

La aplicación detecta HTTPS y a partir de ahí:
- marca la cookie de sesión como `Secure`;
- envía HSTS.

### 3.7 Endurecimiento de PHP y Apache

En `/etc/php/8.*/apache2/php.ini`:

```ini
expose_php = Off
display_errors = Off
log_errors = On
session.cookie_httponly = 1
```

En `/etc/apache2/conf-available/security.conf`:

```apache
ServerTokens Prod
ServerSignature Off
```

Luego aplica los cambios:

```bash
sudo systemctl restart apache2
sudo dpkg-reconfigure -plow unattended-upgrades   # parches de seguridad automáticos
```

---

## 4. Primer acceso

Abre `https://inventario.tudominio.com` y entra con un administrador migrado.

- **Base nueva, sin usuarios.** Crea el primer administrador desde la consola del servidor:
  ```bash
  cd /var/www/debia-smart-inventory
  sudo -u www-data php bin/crear_admin.php admin.dsi "Nombre Apellido" correo@empresa.com 1234567890
  ```
  El script muestra una contraseña temporal **una sola vez**. Cámbiala desde el menú *Cambiar Contraseña*.
  Si usas el correo de `App\Config::CORREO_CUENTA_PRINCIPAL` (sistemas@consultoriasdebia.com.co), esa cuenta queda como **cuenta principal**.

- **Cuentas de las demás personas.** No hay registro público. La cuenta principal las crea en *Usuarios* → *Nuevo Usuario*, les asigna permisos (*Editar equipo*, *Crear nuevo usuario*, *Editar usuario*, *Cambio de contraseña*, *Ver inicios de sesión*, *Ver bitácora de cambios*) y entrega la contraseña por un canal seguro. Solo la cuenta principal asigna permisos y cambia nombres de usuario.

---

## 5. Operación

| Tarea | Cómo |
|---|---|
| **Actualizar la app** | `cd /var/www/debia-smart-inventory && sudo git pull && sudo systemctl reload apache2` |
| **Cambios en la base** | Si la actualización trae un archivo nuevo en `database/migraciones/`, ejecútalo en Supabase → *SQL Editor* **antes** de actualizar la app. Cada archivo se ejecuta una sola vez y en orden de fecha. |
| **Respaldo de la base** | Plan Pro de Supabase (diario automático). Además, o como única opción en el plan gratuito, un volcado diario desde la instancia, ver abajo. |
| **Respaldo de la instancia** | Lightsail → instancia → *Snapshots* → *Enable automatic snapshots*. |
| **Acceso a Supabase** | *Database* → *Settings* → *Network Restrictions*: solo la IP estática de la instancia y la de la oficina, cada una con `/32`, y *Enforce SSL* activado. Si cambia la IP de la oficina, actualízala ahí y en la regla SSH del firewall de Lightsail. |
| **Revisión matutina** | `sudo bash /var/www/debia-smart-inventory/bin/revision_matutina.sh`. Solo lee: estado de Apache, respaldo de anoche, errores de la app, intentos de login fallidos, cambios de usuarios, reinicio pendiente, disco y vencimiento del certificado. Cada línea dice OK, AVISO o ALERTA. |
| **Errores de la app** | `sudo tail -f /var/log/apache2/debia-smart-inventory-error.log`. Los usuarios solo ven un mensaje genérico; el detalle queda en este log. |
| **Bitácora de acciones** | Menú *Bitácora de Cambios* (permiso *Ver bitácora de cambios*), tabla `inventario.logs_sistema`: quién creó, editó o eliminó equipos, mantenimientos y usuarios, con fecha y hora. En la edición de equipos guarda cada campo que cambió. |
| **Inicios de sesión** | Menú *Inicios de Sesión* (permiso *Ver inicios de sesión*), tabla `inventario.inicios_sesion`: cada intento de ingreso con resultado, IP y navegador. El login se bloquea 15 minutos tras 5 fallos del mismo usuario e IP, o tras 30 fallos desde una misma IP. |
| **Tablas nuevas** | `rol_aplicacion.sql` solo da permisos sobre las tablas que existían al ejecutarlo. Toda migración que cree una tabla debe dar a `dsi_app` sus `GRANT` y su política RLS (ver `2026-10-22_auditoria_accesos.sql`), o la app y el respaldo fallarán al usarla. |

### Volcado diario de la base desde Lightsail

1. Instala el cliente de PostgreSQL de la **misma versión mayor** que Supabase, la que aparece en *Settings* → *Infrastructure* (hoy 17). Debian 12 solo trae la 15, y un `pg_dump` más viejo que el servidor se niega a trabajar, así que se usa el repositorio oficial de PostgreSQL:
   ```bash
   sudo apt install -y postgresql-common
   sudo /usr/share/postgresql-common/pgdg/apt.postgresql.org.sh   # pide pulsar Enter
   sudo apt install -y postgresql-client-17
   pg_dump --version   # debe decir 17.x
   ```
   Si Supabase sube de versión mayor, instala el cliente de esa versión.
2. Crea `/etc/cron.daily/respaldo-dsi` (sin punto en el nombre, o `run-parts` lo ignora):
   ```bash
   #!/bin/bash
   # Volcado diario del esquema "inventario" de Supabase; conserva 30 días.
   set -euo pipefail
   umask 077

   ENV=/var/www/debia-smart-inventory/.env
   DESTINO=/var/backups/dsi

   # Lee CLAVE=valor del .env igual que App\Config, sin ejecutarlo como
   # script: una contraseña con $, ` o & llega intacta.
   leer() {
       local valor
       valor=$(grep -E "^[[:space:]]*$1[[:space:]]*=" "$ENV" | tail -n 1 | cut -d= -f2- | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')
       case $valor in
           \"*\" | \'*\') valor=${valor:1:${#valor}-2} ;;
       esac
       printf '%s' "$valor"
   }

   export PGPASSWORD
   PGPASSWORD=$(leer DB_PASS)
   conexion="host=$(leer DB_HOST) port=$(leer DB_PORT) dbname=$(leer DB_NAME) user=$(leer DB_USER) sslmode=require"
   archivo="$DESTINO/inventario-$(date +%F).sql.gz"
   trap 'rm -f "$archivo.tmp"' EXIT

   mkdir -p "$DESTINO"
   # --enable-row-security: dsi_app no salta RLS; su política le deja leer todas las filas.
   pg_dump "$conexion" --schema=inventario --no-owner --enable-row-security | gzip > "$archivo.tmp"
   mv "$archivo.tmp" "$archivo"
   find "$DESTINO" -name 'inventario-*.sql.gz' -mtime +30 -delete
   ```
3. Dale permisos y pruébalo a mano:
   ```bash
   sudo chmod 700 /etc/cron.daily/respaldo-dsi
   sudo /etc/cron.daily/respaldo-dsi
   sudo ls -lh /var/backups/dsi
   sudo zcat /var/backups/dsi/inventario-$(date +%F).sql.gz | grep -c '^COPY inventario'   # 7: una por tabla
   sudo run-parts --test /etc/cron.daily   # debe listar respaldo-dsi
   ```
   Si al pegar el script la terminal del navegador de Lightsail duplica los saltos de línea, `#!/bin/bash` queda en la línea 2 y el script falla con `set: Illegal option -o pipefail`. Se arregla con `sudo sed -i -e 's/\r$//' -e '/^$/d' /etc/cron.daily/respaldo-dsi`.

Los volcados quedan en el disco de la instancia, así que los snapshots de Lightsail también se los llevan.

---

## 6. Desarrollo local (XAMPP)

1. **Extensión.** Habilita `extension=pdo_pgsql` en `C:\xampp\php\php.ini` (paso 2.1).
2. **`.env` en la raíz del proyecto:**
   - `APP_ENV=local`, que muestra los errores en pantalla;
   - conexión a una base de **desarrollo**, que puede ser:
     - un segundo proyecto gratuito de Supabase, con `esquema.sql` ejecutado;
     - o un PostgreSQL instalado localmente, con `DB_SSLMODE=disable`.

   **No desarrolles contra la base de producción.**
3. **URL:** <http://localhost/debia-smart-inventory/public/>

En XAMPP, el `.htaccess` de la raíz bloquea todo lo que no está en `public/`, igual que en el servidor.

---

## 7. Lista de verificación antes de abrir a los usuarios

- [ ] `https://` carga con candado y `http://` redirige a `https://`.
- [ ] `https://dominio/.env`, `/app/Config.php` y `/.git/config` responden **404/403**. Fuera de `public/` nada es accesible.
- [ ] En Supabase, *Exposed schemas* **no** incluye `inventario`.
- [ ] `.env` tiene `APP_ENV=production` y permisos `640`.
- [ ] El puerto 22 está restringido a tu IP.
- [ ] Las contraseñas de prueba se cambiaron y cada persona tiene su propia cuenta.
- [ ] Los snapshots automáticos de Lightsail y el respaldo de la base están activos.
- [ ] Supabase solo acepta conexiones desde la IP de la instancia y la de la oficina, y exige SSL.
