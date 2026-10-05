<?php

declare(strict_types=1);

/**
 * Copia los datos de la base MySQL/MariaDB actual (XAMPP) a PostgreSQL
 * (Supabase), conservando los ids.
 *
 * Uso (desde la raíz del proyecto, con database/esquema.sql ya ejecutado
 * en Supabase y el .env apuntando a Supabase):
 *
 *     php bin/migrar_desde_mysql.php
 *
 * Origen MySQL (opcional, estos son los valores por defecto de XAMPP):
 *     MYSQL_HOST=127.0.0.1  MYSQL_DB=debia_smart_inventory  MYSQL_USER=root  MYSQL_PASS=
 *
 * Seguridad: solo LEE de MySQL. En PostgreSQL escribe todo dentro de una
 * transacción y se niega a ejecutarse si alguna tabla destino ya tiene
 * filas, así que no puede duplicar ni mezclar datos.
 *
 * No se migran: intentos_login (datos temporales), preventivos_programados
 * e historial_equipos (funciones retiradas de la aplicación).
 */

use App\Config;
use App\Core\BaseDatos;

if (PHP_SAPI !== 'cli') {
    exit('Solo se ejecuta desde la consola.');
}

require __DIR__ . '/../app/autoload.php';

/** Tablas en orden de dependencias (las referenciadas primero) y sus columnas. */
const TABLAS = [
    'usuarios'                => ['id', 'nombre', 'usuario', 'correo', 'numero_identificacion', 'contrasena', 'rol', 'fecha_creacion'],
    'equipos'                 => ['id', 'tipo', 'marca', 'identificador', 'asignado_a', 'serial', 'procesador', 'ram', 'disco', 'disco2', 'estado', 'ubicacion', 'codigo_barras', 'fecha_registro'],
    'repuestos'               => ['id', 'nombre', 'tipo', 'capacidad', 'serial', 'descripcion', 'valor', 'stock', 'estado', 'fecha_registro'],
    'mantenimientos'          => ['id', 'equipo_id', 'fecha', 'tipo_mantenimiento', 'responsable', 'descripcion', 'estado', 'observaciones', 'fecha_creacion'],
    'mantenimiento_repuestos' => ['id', 'mantenimiento_id', 'repuesto_id', 'cantidad', 'valor_unitario', 'fecha_registro'],
    'logs_sistema'            => ['id', 'usuario_id', 'accion', 'detalle', 'fecha'],
];

$entorno = static fn (string $nombre, string $defecto): string => getenv($nombre) === false ? $defecto : (string) getenv($nombre);

try {
    $mysql = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $entorno('MYSQL_HOST', '127.0.0.1'), $entorno('MYSQL_DB', 'debia_smart_inventory')),
        $entorno('MYSQL_USER', 'root'),
        $entorno('MYSQL_PASS', ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    // Las fechas de MySQL están en hora local; PostgreSQL las interpreta
    // con la misma zona (BaseDatos fija APP_TIMEZONE en la sesión).
    $postgres = BaseDatos::conectar(Config::baseDatos());

    foreach (array_keys(TABLAS) as $tabla) {
        if ((int) $postgres->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn() > 0) {
            throw new RuntimeException("La tabla destino «{$tabla}» ya tiene datos. La migración solo se hace sobre una base vacía.");
        }
    }

    $postgres->beginTransaction();

    foreach (TABLAS as $tabla => $columnas) {
        $lista = implode(', ', $columnas);
        $marcadores = implode(', ', array_fill(0, count($columnas), '?'));
        $insertar = $postgres->prepare("INSERT INTO {$tabla} ({$lista}) VALUES ({$marcadores})");
        $copiadas = 0;

        foreach ($mysql->query("SELECT {$lista} FROM {$tabla} ORDER BY id") as $fila) {
            $insertar->execute(array_values($fila));
            $copiadas++;
        }

        // Los ids se insertaron a mano: la secuencia debe continuar después del mayor.
        $postgres->exec(
            "SELECT setval(pg_get_serial_sequence('{$tabla}', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM {$tabla}"
        );

        printf("  %-25s %6d filas\n", $tabla, $copiadas);
    }

    $postgres->commit();
    echo "Migración completada.\n";
} catch (Throwable $error) {
    if (isset($postgres) && $postgres->inTransaction()) {
        $postgres->rollBack();
    }

    fwrite(STDERR, 'Error: ' . $error->getMessage() . "\nNo se guardó ningún cambio en PostgreSQL.\n");
    exit(1);
}
