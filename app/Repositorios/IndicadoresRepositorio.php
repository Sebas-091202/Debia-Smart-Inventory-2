<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Core\FiltroSql;
use App\Dominio\Catalogo;
use App\Dominio\FiltrosIndicadores;

/**
 * Consultas agregadas del tablero de mantenimiento (solo equipos de cómputo).
 */
final class IndicadoresRepositorio extends Repositorio
{
    public function contarEquipos(FiltrosIndicadores $filtros): int
    {
        $filtro = $this->filtro($filtros);

        return (int) $this->valor("SELECT COUNT(*) FROM equipos e {$filtro->where()}", $filtro->parametros());
    }

    /** Equipos con sus totales de preventivos/correctivos y último mantenimiento. */
    public function equiposConTotales(FiltrosIndicadores $filtros, int $limite, int $desplazamiento): array
    {
        $filtro = $this->filtro($filtros);

        return $this->filas(
            "SELECT e.tipo, e.marca, e.identificador, e.ubicacion, e.asignado_a,
                    COALESCE(t.preventivos, 0) AS total_preventivos,
                    COALESCE(t.correctivos, 0) AS total_correctivos,
                    t.ultimo_mantenimiento,
                    (SELECT m2.tipo_mantenimiento FROM mantenimientos m2
                      WHERE m2.equipo_id = e.id ORDER BY m2.fecha DESC, m2.id DESC LIMIT 1) AS ultimo_tipo
             FROM equipos e
             LEFT JOIN (
                 SELECT equipo_id,
                        COUNT(*) FILTER (WHERE tipo_mantenimiento = 'Preventivo') AS preventivos,
                        COUNT(*) FILTER (WHERE tipo_mantenimiento = 'Correctivo') AS correctivos,
                        MAX(fecha) AS ultimo_mantenimiento
                 FROM mantenimientos GROUP BY equipo_id
             ) t ON t.equipo_id = e.id
             {$filtro->where()}
             ORDER BY e.identificador ASC
             LIMIT :limite OFFSET :desplazamiento",
            $filtro->parametros() + [':limite' => $limite, ':desplazamiento' => $desplazamiento]
        );
    }

    /** @return array{preventivos: int, correctivos: int} */
    public function totales(FiltrosIndicadores $filtros): array
    {
        $filtro = $this->filtro($filtros);
        $fila = $this->fila(
            "SELECT COUNT(*) FILTER (WHERE m.tipo_mantenimiento = 'Preventivo') AS preventivos,
                    COUNT(*) FILTER (WHERE m.tipo_mantenimiento = 'Correctivo') AS correctivos
             FROM mantenimientos m JOIN equipos e ON e.id = m.equipo_id
             {$filtro->where()}",
            $filtro->parametros()
        );

        return ['preventivos' => (int) ($fila['preventivos'] ?? 0), 'correctivos' => (int) ($fila['correctivos'] ?? 0)];
    }

    public function mantenimientosPorMes(FiltrosIndicadores $filtros): array
    {
        $filtro = $this->filtro($filtros);

        return $this->filas(
            "SELECT to_char(m.fecha, 'YYYY-MM') AS mes,
                    COUNT(*) FILTER (WHERE m.tipo_mantenimiento = 'Preventivo') AS preventivos,
                    COUNT(*) FILTER (WHERE m.tipo_mantenimiento = 'Correctivo') AS correctivos
             FROM mantenimientos m JOIN equipos e ON e.id = m.equipo_id
             {$filtro->where()}
             GROUP BY mes ORDER BY mes",
            $filtro->parametros()
        );
    }

    /** Valores distintos de una columna de equipos (para los select). */
    public function opciones(string $columna): array
    {
        $columnasPermitidas = ['marca', 'identificador', 'ubicacion'];

        if (!in_array($columna, $columnasPermitidas, true)) {
            throw new \InvalidArgumentException("Columna no permitida: {$columna}");
        }

        $filtro = $this->filtro(new FiltrosIndicadores())->siempre("e.{$columna} IS NOT NULL AND e.{$columna} <> ''");

        return $this->columna(
            "SELECT DISTINCT e.{$columna} FROM equipos e {$filtro->where()} ORDER BY e.{$columna}",
            $filtro->parametros()
        );
    }

    private function filtro(FiltrosIndicadores $filtros): FiltroSql
    {
        $tiposComputo = "'" . implode("','", Catalogo::TIPOS_COMPUTO) . "'";

        return (new FiltroSql())
            ->siempre("e.tipo IN ({$tiposComputo})")
            ->igual('e.tipo', $filtros->tipo)
            ->igual('e.marca', $filtros->marca)
            ->igual('e.identificador', $filtros->identificador)
            ->contiene('e.ubicacion', $filtros->ubicacion);
    }
}
