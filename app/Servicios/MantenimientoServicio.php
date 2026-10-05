<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Dominio\ErrorDeNegocio;
use App\Core\BaseDatos;
use App\Repositorios\AuditoriaRepositorio;
use App\Repositorios\EquipoRepositorio;
use App\Repositorios\MantenimientoRepositorio;
use App\Repositorios\RepuestoRepositorio;

/**
 * Registro de un mantenimiento junto con los repuestos usados.
 * Todo ocurre en una sola transacción: o se guarda completo o nada.
 */
final class MantenimientoServicio
{
    public function __construct(
        private readonly EquipoRepositorio $equipos = new EquipoRepositorio(),
        private readonly MantenimientoRepositorio $mantenimientos = new MantenimientoRepositorio(),
        private readonly RepuestoRepositorio $repuestos = new RepuestoRepositorio(),
        private readonly AuditoriaRepositorio $auditoria = new AuditoriaRepositorio(),
    ) {
    }

    public function registrar(array $mantenimiento, array $repuestos, int $usuarioId): int
    {
        $equipo = $this->equipos->buscarPorId($mantenimiento['equipo_id'])
            ?? throw new ErrorDeNegocio('El equipo seleccionado no existe.');

        return BaseDatos::transaccion(function () use ($mantenimiento, $repuestos, $equipo, $usuarioId) {
            $mantenimientoId = $this->mantenimientos->crear($mantenimiento);

            foreach ($repuestos as $repuesto) {
                $repuestoId = $this->repuestos->sumarOCrear($repuesto);
                $this->repuestos->asociarAMantenimiento($mantenimientoId, $repuestoId, $repuesto['cantidad'], $repuesto['valor']);
            }

            $this->auditoria->registrar(
                $usuarioId,
                'REGISTRAR MANTENIMIENTO',
                "{$mantenimiento['tipo_mantenimiento']} registrado al equipo {$equipo['identificador']}"
            );

            return $mantenimientoId;
        });
    }
}
