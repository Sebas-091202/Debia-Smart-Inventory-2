<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Core\FiltroSql;
use App\Dominio\FiltrosIniciosSesion;
use App\Dominio\ResultadoInicioSesion;

/**
 * Historial de inicios de sesión (tabla inicios_sesion): cada intento con
 * su resultado, IP y navegador.
 */
final class InicioSesionRepositorio extends Repositorio
{
    private const DESDE = 'FROM inicios_sesion i LEFT JOIN usuarios u ON u.id = i.usuario_id';

    /** @param int|null $usuarioId null si el usuario escrito no existe. */
    public function registrar(?int $usuarioId, ResultadoInicioSesion $resultado, string $ip, string $navegador): void
    {
        $this->ejecutar(
            'INSERT INTO inicios_sesion (usuario_id, resultado, ip, navegador) VALUES (?, ?, ?, ?)',
            [$usuarioId, $resultado->value, $ip, $navegador === '' ? null : $navegador]
        );
    }

    public function contar(FiltrosIniciosSesion $filtros): int
    {
        $filtro = $this->filtro($filtros);

        return (int) $this->valor('SELECT COUNT(*) ' . self::DESDE . " {$filtro->where()}", $filtro->parametros());
    }

    public function listar(FiltrosIniciosSesion $filtros, int $limite, int $desplazamiento): array
    {
        $filtro = $this->filtro($filtros);

        return $this->filas(
            "SELECT to_char(i.fecha, 'YYYY-MM-DD HH24:MI:SS') AS fecha, i.resultado, i.ip, i.navegador,
                    u.usuario, u.nombre "
            . self::DESDE . " {$filtro->where()}
             ORDER BY i.fecha DESC, i.id DESC
             LIMIT :limite OFFSET :desplazamiento",
            $filtro->parametros() + [':limite' => $limite, ':desplazamiento' => $desplazamiento]
        );
    }

    private function filtro(FiltrosIniciosSesion $filtros): FiltroSql
    {
        $filtro = (new FiltroSql())
            ->igual('i.resultado', $filtros->resultado)
            ->desde('i.fecha::date', $filtros->fechaDesde)
            ->hasta('i.fecha::date', $filtros->fechaHasta);

        return $filtros->usuarioId === null
            ? $filtro
            : $filtro->condicion('i.usuario_id = :usuario', [':usuario' => $filtros->usuarioId]);
    }
}
