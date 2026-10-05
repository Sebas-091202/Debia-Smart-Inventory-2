<?php

declare(strict_types=1);

namespace App;

/**
 * Configuración central de la aplicación.
 *
 * Los valores sensibles (credenciales) nunca van en el código. Se leen,
 * en este orden, de:
 *   1. variables de entorno del servidor (Apache: SetEnv DB_PASS "secreto");
 *   2. el archivo .env de la raíz del proyecto (fuera de public/ y del
 *      repositorio; ver .env.example).
 */
final class Config
{
    /** Segundos de inactividad tras los cuales se cierra la sesión. */
    public const SESION_INACTIVIDAD_MAXIMA = 3600;

    /** Cada cuántos segundos se renueva el ID de sesión (mitiga secuestro). */
    public const SESION_REGENERAR_CADA = 900;

    /** Intentos fallidos permitidos antes de bloquear el login. */
    public const LOGIN_INTENTOS_MAXIMOS = 5;

    /** Ventana (segundos) en la que se cuentan los intentos fallidos. */
    public const LOGIN_VENTANA_INTENTOS = 900;

    /** Duración (segundos) del bloqueo tras superar los intentos. */
    public const LOGIN_DURACION_BLOQUEO = 900;

    /** Longitud mínima exigida para contraseñas nuevas. */
    public const CONTRASENA_LONGITUD_MINIMA = 8;

    /** bcrypt solo considera los primeros 72 bytes de la contraseña. */
    public const CONTRASENA_LONGITUD_MAXIMA = 72;

    /** @var array<string, string>|null Contenido del .env, leído una sola vez. */
    private static ?array $archivoEntorno = null;

    /**
     * Conexión a PostgreSQL (Supabase en producción).
     *
     * @return array{host: string, puerto: string, nombre: string, usuario: string,
     *               clave: string, esquema: string, ssl: string, certificado: string}
     */
    public static function baseDatos(): array
    {
        return [
            'host'        => self::entorno('DB_HOST', '127.0.0.1'),
            'puerto'      => self::entorno('DB_PORT', '5432'),
            'nombre'      => self::entorno('DB_NAME', 'postgres'),
            'usuario'     => self::entorno('DB_USER', 'postgres'),
            'clave'       => self::entorno('DB_PASS', ''),
            'esquema'     => self::entorno('DB_SCHEMA', 'inventario'),
            // require: cifrado obligatorio. Con DB_SSLROOTCERT se pasa a
            // verify-full (además valida el certificado del servidor).
            'ssl'         => self::entorno('DB_SSLMODE', 'require'),
            'certificado' => self::entorno('DB_SSLROOTCERT', ''),
        ];
    }

    /** Zona horaria con la que se guardan y muestran fechas y horas. */
    public static function zonaHoraria(): string
    {
        return self::entorno('APP_TIMEZONE', 'America/Bogota');
    }

    /** true solo si se definió APP_ENV=local: muestra errores en pantalla. */
    public static function esDesarrollo(): bool
    {
        return self::entorno('APP_ENV', 'production') === 'local';
    }

    /** true si la app está detrás de un proxy que termina HTTPS. */
    public static function confiarEnProxy(): bool
    {
        return self::entorno('APP_TRUST_PROXY', '0') === '1';
    }

    private static function entorno(string $nombre, string $defecto): string
    {
        $valor = getenv($nombre);

        if ($valor !== false) {
            return $valor;
        }

        return self::archivoEntorno()[$nombre] ?? $defecto;
    }

    /**
     * Lee el .env (líneas CLAVE=valor; # inicia un comentario; el valor
     * puede ir entre comillas). Si no existe, no hay valores.
     *
     * @return array<string, string>
     */
    private static function archivoEntorno(): array
    {
        if (self::$archivoEntorno !== null) {
            return self::$archivoEntorno;
        }

        $ruta = dirname(__DIR__) . '/.env';
        $lineas = is_readable($ruta) ? file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        self::$archivoEntorno = [];

        foreach ($lineas ?: [] as $linea) {
            if (preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*=\s*(.*?)\s*$/', $linea, $partes) !== 1) {
                continue;
            }

            self::$archivoEntorno[$partes[1]] = self::sinComillas($partes[2]);
        }

        return self::$archivoEntorno;
    }

    private static function sinComillas(string $valor): string
    {
        if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && str_ends_with($valor, $valor[0])) {
            return substr($valor, 1, -1);
        }

        return $valor;
    }
}
