<?php
/**
 * bootstrap_seguro.php
 * ---------------------------------------------------------------
 * Carga esto ANTES de session_start() en cada controlador
 * (login.php, register.php, y cualquier vista protegida).
 * Centraliza toda la configuración de sesión/cookies segura para
 * que no se te olvide aplicarla en algún archivo nuevo.
 * ---------------------------------------------------------------
 */

declare(strict_types=1);

$hostActual  = $_SERVER['HTTP_HOST'] ?? '';
$esEntornoLocal = in_array(
    strtolower(explode(':', $hostActual)[0]), // quita el puerto si lo hay, ej: localhost:8080
    ['localhost', '127.0.0.1', '::1'],
    true
);

// En local mostramos los errores de PHP en pantalla para poder depurar.
// En producción esto SIEMPRE debe estar apagado (nunca mostrar detalles
// internos a un usuario final: filtran rutas del servidor, estructura de
// BD, versiones de librerías, etc.). Se registran igual en el log del
// servidor sin importar este ajuste, gracias a log_errors.
ini_set('display_errors', $esEntornoLocal ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// --- 1. Forzar HTTPS solo fuera de entornos locales de desarrollo ---
// Detecta automáticamente localhost/127.0.0.1 para no romper el flujo
// de pruebas en XAMPP/Laragon (evita el bug de POST -> GET en redirects).

$forzarHttps = !$esEntornoLocal; // true en producción, false en local automáticamente

if ($forzarHttps && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
    // Si estás detrás de un proxy/load balancer, valida X-Forwarded-Proto también
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if ($proto !== 'https') {
        // 307 preserva el método original (POST sigue siendo POST tras la redirección),
        // a diferencia del 302 por defecto que los navegadores convierten en GET.
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 307);
        exit;
    }
}

// --- 2. Cabeceras de seguridad básicas ---
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (!$esEntornoLocal) {
    // Nunca envíes HSTS en local: el navegador recuerda forzar HTTPS para ese
    // host durante meses (max-age), y te puede dejar bloqueado de tu propio
    // entorno de pruebas si luego apagas HTTPS ahí.
    header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
}
// Ajustado a los recursos reales del proyecto: Google Fonts, Boxicons (unpkg)
// y el bloque <style> inline que usan las vistas. Si más adelante quitas el
// <style> inline y usas solo .css externos, puedes retirar 'unsafe-inline'
// de style-src para endurecer aún más.
header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self'; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; " .
    "font-src 'self' https://fonts.gstatic.com https://unpkg.com; " .
    "img-src 'self' data:; " .
    "object-src 'none'; " .
    "base-uri 'self';"
);

// --- 3. Configurar cookie de sesión ANTES de session_start() ---
session_set_cookie_params([
    'lifetime' => 0,                // expira al cerrar el navegador
    'path'     => '/',
    'domain'   => '',               // pon tu dominio en producción, ej: '.tudominio.com'
    'secure'   => !$esEntornoLocal, // solo exige HTTPS fuera de local (si no, la cookie no viajaría por http:// y perderías la sesión)
    'httponly' => true,             // no accesible desde JavaScript (mitiga XSS -> robo de sesión)
    'samesite' => 'Strict',         // mitiga CSRF a nivel de navegador
]);

session_name('SID_APP'); // evita el nombre por defecto PHPSESSID (oscurece la tecnología usada)
session_start();

// --- 4. Regenerar el ID de sesión periódicamente (mitiga secuestro de sesión) ---
if (empty($_SESSION['creado_en'])) {
    $_SESSION['creado_en'] = time();
} elseif (time() - $_SESSION['creado_en'] > 900) { // cada 15 min
    session_regenerate_id(true);
    $_SESSION['creado_en'] = time();
}

// --- 5. Generar token CSRF de un solo uso si no existe ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Verifica y ROTA el token CSRF (uso único real).
 * Llamar al inicio de cada POST protegido.
 */
function verificarCsrfORenovar(): void
{
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']))
    ) {
        http_response_code(403);
        die('Acceso denegado: token CSRF inválido o expirado.');
    }
    // Token de un solo uso: se regenera tras cada verificación exitosa
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Redirige mostrando un mensaje flash seguro (evita eval de <script> con datos dinámicos).
 * @param string $formulario 'login' o 'register': qué panel debe quedar visible al recargar.
 */
function redirigirConMensaje(string $url, string $tipo, string $mensaje, string $formulario = 'login'): never
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje, 'formulario' => $formulario];
    header('Location: ' . $url);
    exit;
}