<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Core\FiltroSql;
use App\Dominio\FiltrosBitacora;

/**
 * Bitácora de acciones de los usuarios (tabla logs_sistema).
 */
final class AuditoriaRepositorio extends Repositorio
{
    private const DESDE = 'FROM logs_sistema l LEFT JOIN usuarios u ON u.id = l.usuario_id';

    public function registrar(?int $usuarioId, string $accion, string $detalle): void
    {
        $this->ejecutar(
            'INSERT INTO logs_sistema (usuario_id, accion, detalle) VALUES (?, ?, ?)',
            [$usuarioId, $accion, $detalle]
        );
    }

    /** Acciones que existen en la bitácora (opciones del filtro). */
    public function acciones(): array
    {
        return $this->columna("SELECT DISTINCT accion FROM logs_sistema WHERE accion IS NOT NULL AND accion <> '' ORDER BY accion");
    }

    public function contar(FiltrosBitacora $filtros): int
    {
        $filtro = $this->filtro($filtros);

        return (int) $this->valor('SELECT COUNT(*) ' . self::DESDE . " {$filtro->where()}", $filtro->parametros());
    }

    public function listar(FiltrosBitacora $filtros, int $limite, int $desplazamiento): array
    {
        $filtro = $this->filtro($filtros);

        return $this->filas(
            "SELECT to_char(l.fecha, 'YYYY-MM-DD HH24:MI:SS') AS fecha, l.accion, l.detalle, u.usuario, u.nombre "
            . self::DESDE . " {$filtro->where()}
             ORDER BY l.fecha DESC, l.id DESC
             LIMIT :limite OFFSET :desplazamiento",
            $filtro->parametros() + [':limite' => $limite, ':desplazamiento' => $desplazamiento]
        );
    }

    private function filtro(FiltrosBitacora $filtros): FiltroSql
    {
        $filtro = (new FiltroSql())
            ->igual('l.accion', $filtros->accion)
            ->desde('l.fecha::date', $filtros->fechaDesde)
            ->hasta('l.fecha::date', $filtros->fechaHasta);

        return $filtros->usuarioId === null
            ? $filtro
            : $filtro->condicion('l.usuario_id = :usuario', [':usuario' => $filtros->usuarioId]);
    }
}
