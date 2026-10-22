#!/bin/bash
# =====================================================================
# Revisión matutina de seguridad y operación de Debia Smart Inventory.
# Solo lee: no modifica nada del servidor ni de la base de datos.
#
# En el servidor:
#   sudo bash /var/www/debia-smart-inventory/bin/revision_matutina.sh
# =====================================================================
set -uo pipefail

APP=${APP:-/var/www/debia-smart-inventory}
LOG=${LOG:-/var/log/apache2/debia-smart-inventory-error.log}
RESPALDOS=${RESPALDOS:-/var/backups/dsi}

# El log de Apache fecha las líneas como "[Thu Oct 22 ...]": se revisan
# hoy y ayer, también en el log rotado (.1).
DIAS="$(LC_ALL=C date '+%b %d')|$(LC_ALL=C date -d yesterday '+%b %d')"

titulo() { printf '\n== %s\n' "$1"; }

# Lee CLAVE=valor del .env igual que App\Config, sin ejecutarlo como
# script (mismo criterio que /etc/cron.daily/respaldo-dsi).
leer() {
    local valor
    valor=$(grep -E "^[[:space:]]*$1[[:space:]]*=" "$APP/.env" | tail -n 1 | cut -d= -f2- | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')
    case $valor in
        \"*\" | \'*\') valor=${valor:1:${#valor}-2} ;;
    esac
    printf '%s' "$valor"
}

titulo 'Servicio web'
if systemctl is-active --quiet apache2; then
    echo 'OK: Apache en ejecución'
else
    echo 'ALERTA: Apache no está en ejecución'
fi

titulo 'Respaldo de la base'
ultimo=$(ls -1t "$RESPALDOS"/inventario-*.sql.gz 2>/dev/null | head -n 1)
if [[ -z $ultimo ]]; then
    echo 'ALERTA: no hay ningún respaldo'
elif [[ -n $(find "$ultimo" -mmin -1500) ]]; then
    echo "OK: $(basename "$ultimo") ($(du -h "$ultimo" | cut -f1))"
else
    echo "ALERTA: el último respaldo tiene más de 25 horas: $(basename "$ultimo")"
fi

titulo 'Errores de la aplicación (hoy y ayer)'
registro=$(cat "$LOG.1" "$LOG" 2>/dev/null | grep -E "^\[[A-Z][a-z]{2} ($DIAS) ")
errores=$(grep -E 'SQLSTATE|php:' <<<"$registro" | grep -v 'unable to stat')
if [[ -z $errores ]]; then
    echo 'OK: ninguno'
else
    echo 'ALERTA: revisar estas líneas (las 15 más recientes):'
    tail -n 15 <<<"$errores"
fi
echo "Sondeos de bots bloqueados: $(grep -cE 'AH01630|AH10244|unable to stat' <<<"$registro")"

titulo 'Accesos y cambios sensibles (últimas 24 h)'
export PGPASSWORD PGCLIENTENCODING=UTF8
PGPASSWORD=$(leer DB_PASS)
conexion="host=$(leer DB_HOST) port=$(leer DB_PORT) dbname=$(leer DB_NAME) user=$(leer DB_USER) sslmode=require"
consulta() { psql "$conexion" -XAt -c "$1" 2>&1; }

if accesos=$(consulta "SELECT 'Inicios de sesión: ' || COUNT(*) FILTER (WHERE resultado = 'EXITOSO') || ' exitosos | '
            || COUNT(*) FILTER (WHERE resultado = 'CREDENCIALES') || ' fallidos | '
            || COUNT(*) FILTER (WHERE resultado = 'BLOQUEADO') || ' bloqueos por fuerza bruta'
        FROM inventario.inicios_sesion WHERE fecha > now() - interval '24 hours'"); then
    echo "$accesos"
    consulta "SELECT 'IP con más fallos: ' || ip || ' (' || COUNT(*) || ')' FROM inventario.inicios_sesion
        WHERE fecha > now() - interval '24 hours' AND resultado IN ('CREDENCIALES', 'BLOQUEADO')
        GROUP BY ip ORDER BY COUNT(*) DESC LIMIT 3"
    cambios=$(consulta "SELECT to_char(l.fecha AT TIME ZONE 'America/Bogota', 'YYYY-MM-DD HH24:MI') || '  '
            || COALESCE(u.usuario, 'consola') || '  ' || l.accion || '  ' || COALESCE(l.detalle, '')
        FROM inventario.logs_sistema l LEFT JOIN inventario.usuarios u ON u.id = l.usuario_id
        WHERE l.fecha > now() - interval '24 hours'
          AND l.accion IN ('CREAR USUARIO', 'ACTUALIZAR USUARIO', 'CAMBIAR CONTRASEÑA', 'ELIMINAR EQUIPO')
        ORDER BY l.fecha DESC LIMIT 20")
    echo "${cambios:-Sin cambios de usuarios ni equipos eliminados}"
else
    echo "ALERTA: no se pudo consultar la base: $accesos"
fi

titulo 'Sistema'
if [[ -f /var/run/reboot-required ]]; then
    echo 'AVISO: hay actualizaciones instaladas que piden reiniciar el servidor'
else
    echo 'OK: no hay reinicio pendiente'
fi

actualizacion=$(grep -h 'Packages that will be upgraded' /var/log/unattended-upgrades/unattended-upgrades.log 2>/dev/null | tail -n 1 | cut -c1-16)
echo "Última actualización automática: ${actualizacion:-sin registros}"

uso=$(df --output=pcent / | tail -n 1 | tr -dc '0-9')
if (( uso >= 85 )); then
    echo "ALERTA: disco al ${uso} %"
else
    echo "OK: disco al ${uso} %"
fi

dias_certificado=$(certbot certificates 2>/dev/null | grep -m 1 -oE 'VALID: [0-9]+' | tr -dc '0-9')
if [[ -z $dias_certificado ]]; then
    echo 'AVISO: no se pudo leer el certificado HTTPS'
elif (( dias_certificado < 20 )); then
    echo "ALERTA: el certificado HTTPS vence en ${dias_certificado} días (debería renovarse solo)"
else
    echo "OK: certificado HTTPS válido por ${dias_certificado} días"
fi

printf '\nRevisión terminada: %s\n' "$(date '+%Y-%m-%d %H:%M %Z')"
