<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Constructor de cláusulas WHERE parametrizadas.
 *
 * Los nombres de columna son siempre literales del código; los valores
 * del usuario solo viajan como parámetros enlazados (sin inyección SQL).
 * Los métodos ignoran los valores vacíos, así un filtro no seleccionado
 * no restringe la consulta.
 */
final class FiltroSql
{
    /** @var string[] */
    private array $condiciones = [];
    private array $parametros = [];

    /** Condición fija sin parámetros, p. ej. "e.tipo IN ('PO','TO')". */
    public function siempre(string $condicion): self
    {
        $this->condiciones[] = $condicion;

        return $this;
    }

    public function igual(string $columna, string $valor): self
    {
        return $this->agregar($columna, '=', $valor);
    }

    /** Búsqueda parcial sin distinguir mayúsculas ni minúsculas. */
    public function contiene(string $columna, string $valor): self
    {
        return $valor === '' ? $this : $this->agregar($columna, 'ILIKE', '%' . self::escaparLike($valor) . '%');
    }

    public function desde(string $columna, string $valor): self
    {
        return $this->agregar($columna, '>=', $valor);
    }

    public function hasta(string $columna, string $valor): self
    {
        return $this->agregar($columna, '<=', $valor);
    }

    /** Condición fija con parámetros nombrados propios. */
    public function condicion(string $sql, array $parametros): self
    {
        $this->condiciones[] = $sql;
        $this->parametros += $parametros;

        return $this;
    }

    /** "WHERE a AND b" o "WHERE 1=1" si no hay condiciones. */
    public function where(): string
    {
        return 'WHERE ' . ($this->condiciones === [] ? '1=1' : implode(' AND ', $this->condiciones));
    }

    public function parametros(): array
    {
        return $this->parametros;
    }

    private function agregar(string $columna, string $operador, string $valor): self
    {
        if ($valor === '') {
            return $this;
        }

        $parametro = ':p' . count($this->parametros);
        $this->condiciones[] = "{$columna} {$operador} {$parametro}";
        $this->parametros[$parametro] = $valor;

        return $this;
    }

    private static function escaparLike(string $valor): string
    {
        return addcslashes($valor, '%_\\');
    }
}
