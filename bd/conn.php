<?php
/**
 * conn.php
 * ---------------------------------------------------------------
 * Conexión PDO endurecida.
 * Las credenciales NUNCA deben ir hardcodeadas en el repositorio.
 * Configúralas como variables de entorno del servidor (Apache/Nginx-PHP-FPM)
 * o en un archivo .env fuera del webroot y cargado con una librería
 * como vlucas/phpdotenv.
 * ---------------------------------------------------------------
 */

declare(strict_types=1);

$host    = getenv('DB_HOST') ?: '127.0.0.1';
$db      = getenv('DB_NAME') ?: 'debia_smart_inventory';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // lanza excepciones, no expone errores en pantalla
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,                  // usa prepared statements REALES del driver (mitiga inyección SQL)
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
];

try {
    $conn = new PDO($dsn, $user, $pass, $opciones);
} catch (PDOException $e) {
    // Nunca mostrar $e->getMessage() al usuario final: puede filtrar
    // estructura de BD, credenciales parciales, rutas del servidor, etc.
    error_log('Error de conexión a BD: ' . $e->getMessage());
    http_response_code(500);
    die('Error interno del servidor. Intenta más tarde.');
}