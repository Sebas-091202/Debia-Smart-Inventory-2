<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Core\FiltroSql;
use App\Dominio\FiltrosEquipo;

final class EquipoRepositorio extends Repositorio
{
    /** Columnas editables desde el formulario, en orden. */
    public const CAMPOS = [
        'tipo', 'marca', 'identificador', 'asignado_a', 'serial', 'procesador',
        'ram', 'disco', 'disco2', 'estado', 'ubicacion', 'codigo_barras',
    ];

    public function contar(FiltrosEquipo $filtros): int
    {
        $filtro = $this->filtro($filtros);

        return (int) $this->valor("SELECT COUNT(*) FROM equipos {$filtro->where()}", $filtro->parametros());
    }

    public function listar(FiltrosEquipo $filtros, int $limite, int $desplazamiento): array
    {
        $filtro = $this->filtro($filtros);

        return $this->filas(
            "SELECT * FROM equipos {$filtro->where()} ORDER BY id ASC LIMIT :limite OFFSET :desplazamiento",
            $filtro->parametros() + [':limite' => $limite, ':desplazamiento' => $desplazamiento]
        );
    }

    /**
     * Identificadores existentes dentro de los demás filtros elegidos,
     * para que el select muestre solo opciones con resultados.
     */
    public function identificadores(FiltrosEquipo $filtros): array
    {
        $filtro = $this->filtro($filtros, incluirIdentificador: false)
            ->siempre("identificador IS NOT NULL AND identificador <> ''");

        return $this->columna(
            "SELECT DISTINCT identificador FROM equipos {$filtro->where()} ORDER BY identificador",
            $filtro->parametros()
        );
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->fila('SELECT * FROM equipos WHERE id = ?', [$id]);
    }

    public function buscarPorCodigo(string $codigo): ?array
    {
        return $this->fila('SELECT * FROM equipos WHERE upper(codigo_barras) = upper(?)', [$codigo]);
    }

    public function codigoEnUso(string $codigo, ?int $exceptoId = null): bool
    {
        return (bool) $this->valor(
            'SELECT COUNT(*) FROM equipos WHERE upper(codigo_barras) = upper(?) AND id <> ?',
            [$codigo, $exceptoId ?? 0]
        );
    }

    public function crear(array $datos): int
    {
        $columnas = implode(', ', self::CAMPOS);
        $marcadores = implode(', ', array_fill(0, count(self::CAMPOS), '?'));

        return $this->insertar("INSERT INTO equipos ({$columnas}) VALUES ({$marcadores})", $this->valoresEnOrden($datos));
    }

    public function actualizar(int $id, array $datos): void
    {
        $asignaciones = implode(', ', array_map(static fn ($campo) => "{$campo} = ?", self::CAMPOS));

        $this->ejecutar("UPDATE equipos SET {$asignaciones} WHERE id = ?", [...$this->valoresEnOrden($datos), $id]);
    }

    public function eliminar(int $id): bool
    {
        return $this->ejecutar('DELETE FROM equipos WHERE id = ?', [$id])->rowCount() > 0;
    }

    private function filtro(FiltrosEquipo $filtros, bool $incluirIdentificador = true): FiltroSql
    {
        $filtro = (new FiltroSql())
            ->igual('tipo', $filtros->tipo)
            ->igual('marca', $filtros->marca)
            ->contiene('ubicacion', $filtros->ubicacion)
            ->igual('estado', $filtros->estado)
            ->igual('upper(codigo_barras)', mb_strtoupper($filtros->codigo));

        return $incluirIdentificador ? $filtro->igual('identificador', $filtros->identificador) : $filtro;
    }

    /** Valores vacíos se guardan como NULL (columnas opcionales). */
    private function valoresEnOrden(array $datos): array
    {
        return array_map(
            static fn ($campo) => ($datos[$campo] ?? '') === '' ? null : $datos[$campo],
            self::CAMPOS
        );
    }
}
