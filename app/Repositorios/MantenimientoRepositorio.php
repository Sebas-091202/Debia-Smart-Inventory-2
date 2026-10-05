<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Core\FiltroSql;
use App\Dominio\FiltrosMantenimiento;

final class MantenimientoRepositorio extends Repositorio
{
    private const DESDE = 'FROM mantenimientos m JOIN equipos e ON e.id = m.equipo_id';

    public function contar(FiltrosMantenimiento $filtros): int
    {
        $filtro = $this->filtro($filtros);

        return (int) $this->valor('SELECT COUNT(*) ' . self::DESDE . " {$filtro->where()}", $filtro->parametros());
    }

    public function listar(FiltrosMantenimiento $filtros, int $limite, int $desplazamiento): array
    {
        $filtro = $this->filtro($filtros);

        return $this->filas(
            'SELECT m.id, m.fecha, m.responsable, m.descripcion, m.estado, m.observaciones,
                    e.id AS equipo_id, e.tipo AS equipo_tipo, e.marca AS equipo_marca,
                    e.identificador, e.ubicacion '
            . self::DESDE . " {$filtro->where()}
             ORDER BY m.fecha DESC, m.id DESC
             LIMIT :limite OFFSET :desplazamiento",
            $filtro->parametros() + [':limite' => $limite, ':desplazamiento' => $desplazamiento]
        );
    }

    public function identificadores(FiltrosMantenimiento $filtros): array
    {
        $filtro = $this->filtro($filtros, incluirIdentificador: false)
            ->siempre("e.identificador IS NOT NULL AND e.identificador <> ''");

        return $this->columna(
            'SELECT DISTINCT e.identificador ' . self::DESDE . " {$filtro->where()} ORDER BY e.identificador",
            $filtro->parametros()
        );
    }

    public function historialDeEquipo(int $equipoId): array
    {
        return $this->filas(
            'SELECT fecha, responsable, tipo_mantenimiento, descripcion, estado, observaciones
             FROM mantenimientos WHERE equipo_id = ? ORDER BY fecha ASC, id ASC',
            [$equipoId]
        );
    }

    public function crear(array $datos): int
    {
        return $this->insertar(
            'INSERT INTO mantenimientos
                (equipo_id, fecha, responsable, tipo_mantenimiento, descripcion, estado, observaciones)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $datos['equipo_id'], $datos['fecha'], $datos['responsable'], $datos['tipo_mantenimiento'],
                $datos['descripcion'], $datos['estado'], $datos['observaciones'] === '' ? null : $datos['observaciones'],
            ]
        );
    }

    private function filtro(FiltrosMantenimiento $filtros, bool $incluirIdentificador = true): FiltroSql
    {
        $filtro = (new FiltroSql())
            ->condicion('m.tipo_mantenimiento = :tipo_mantenimiento', [':tipo_mantenimiento' => $filtros->tipoMantenimiento])
            ->igual('e.tipo', $filtros->tipo)
            ->igual('e.marca', $filtros->marca)
            ->contiene('e.ubicacion', $filtros->ubicacion)
            ->desde('m.fecha', $filtros->fechaDesde)
            ->hasta('m.fecha', $filtros->fechaHasta);

        return $incluirIdentificador ? $filtro->igual('e.identificador', $filtros->identificador) : $filtro;
    }
}
