<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Core\BaseDatos;
use PDO;
use PDOStatement;

/**
 * Base de los repositorios: acceso a la conexión y atajos de consulta.
 * Todo valor externo se enlaza como parámetro; jamás se concatena.
 */
abstract class Repositorio
{
    protected PDO $bd;

    public function __construct(?PDO $bd = null)
    {
        $this->bd = $bd ?? BaseDatos::conexion();
    }

    protected function ejecutar(string $sql, array $parametros = []): PDOStatement
    {
        $sentencia = $this->bd->prepare($sql);

        foreach ($parametros as $nombre => $valor) {
            $sentencia->bindValue(is_int($nombre) ? $nombre + 1 : $nombre, $valor, self::tipo($valor));
        }

        $sentencia->execute();

        return $sentencia;
    }

    /** Ejecuta un INSERT ... RETURNING id y devuelve el id generado. */
    protected function insertar(string $sql, array $parametros = []): int
    {
        return (int) $this->valor($sql . ' RETURNING id', $parametros);
    }

    protected function filas(string $sql, array $parametros = []): array
    {
        return $this->ejecutar($sql, $parametros)->fetchAll();
    }

    protected function fila(string $sql, array $parametros = []): ?array
    {
        $fila = $this->ejecutar($sql, $parametros)->fetch();

        return $fila === false ? null : $fila;
    }

    protected function columna(string $sql, array $parametros = []): array
    {
        return $this->ejecutar($sql, $parametros)->fetchAll(PDO::FETCH_COLUMN);
    }

    protected function valor(string $sql, array $parametros = []): mixed
    {
        return $this->ejecutar($sql, $parametros)->fetchColumn();
    }

    private static function tipo(mixed $valor): int
    {
        return match (true) {
            is_int($valor)  => PDO::PARAM_INT,
            is_bool($valor) => PDO::PARAM_BOOL,
            is_null($valor) => PDO::PARAM_NULL,
            default         => PDO::PARAM_STR,
        };
    }
}
