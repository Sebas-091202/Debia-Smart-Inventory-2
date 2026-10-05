<?php

declare(strict_types=1);

namespace App\Core;

use App\Config;
use PDO;
use PDOException;
use Throwable;

/**
 * Conexión PDO única por petición a PostgreSQL, creada solo cuando se necesita.
 */
final class BaseDatos
{
    /** SQLSTATE de PostgreSQL para una clave única repetida. */
    public const VIOLACION_UNICIDAD = '23505';

    private static ?PDO $conexion = null;

    public static function conexion(): PDO
    {
        return self::$conexion ??= self::conectar(Config::baseDatos());
    }

    /**
     * Ejecuta $operacion dentro de una transacción: confirma si termina
     * bien y revierte ante cualquier excepción (que se vuelve a lanzar).
     */
    public static function transaccion(callable $operacion): mixed
    {
        $bd = self::conexion();
        $bd->beginTransaction();

        try {
            $resultado = $operacion();
            $bd->commit();

            return $resultado;
        } catch (Throwable $excepcion) {
            $bd->rollBack();
            throw $excepcion;
        }
    }

    /**
     * Abre la conexión con la configuración indicada. Público para los
     * scripts de consola (bin/), que no pasan por una petición web.
     *
     * @param array{host: string, puerto: string, nombre: string, usuario: string,
     *              clave: string, esquema: string, ssl: string, certificado: string} $config
     */
    public static function conectar(array $config): PDO
    {
        try {
            $bd = new PDO(self::dsn($config), $config['usuario'], $config['clave'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Sentencias preparadas reales del motor (no emuladas).
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $excepcion) {
            // El detalle (host, usuario, motivo) solo va al log del servidor.
            error_log('Error de conexión a la base de datos: ' . $excepcion->getMessage());

            if (PHP_SAPI === 'cli') {
                throw $excepcion;
            }

            Respuesta::error(500, 'Error interno del servidor. Intenta más tarde.');
        }

        // Las tablas viven en un esquema propio (no expuesto por la API REST
        // de Supabase). Se valida el nombre porque SET no admite parámetros.
        if (preg_match('/^[a-z_][a-z0-9_]*$/', $config['esquema']) !== 1) {
            throw new \InvalidArgumentException('DB_SCHEMA no es un nombre de esquema válido.');
        }

        $bd->exec("SET search_path TO {$config['esquema']}");
        $bd->prepare('SELECT set_config(\'TimeZone\', ?, false)')->execute([Config::zonaHoraria()]);

        return $bd;
    }

    private static function dsn(array $config): string
    {
        $partes = [
            'host'    => $config['host'],
            'port'    => $config['puerto'],
            'dbname'  => $config['nombre'],
            'sslmode' => $config['certificado'] !== '' ? 'verify-full' : $config['ssl'],
        ];

        if ($config['certificado'] !== '') {
            $partes['sslrootcert'] = $config['certificado'];
        }

        $pares = array_map(static fn ($clave, $valor) => "{$clave}={$valor}", array_keys($partes), $partes);

        return 'pgsql:' . implode(';', $pares);
    }
}
