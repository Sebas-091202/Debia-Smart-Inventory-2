<?php

declare(strict_types=1);

namespace App\Dominio;

use App\Core\Peticion;

/**
 * Filtros de la bitácora de cambios (tabla logs_sistema).
 */
final class FiltrosBitacora
{
    public function __construct(
        public readonly ?int $usuarioId = null,
        public readonly string $accion = '',
        public readonly string $fechaDesde = '',
        public readonly string $fechaHasta = '',
    ) {
    }

    /** @param string[] $acciones Acciones registradas: las únicas aceptadas en el filtro. */
    public static function desdeConsulta(array $acciones): self
    {
        [$desde, $hasta] = Peticion::consultaRangoFechas('fecha_desde', 'fecha_hasta');

        return new self(
            usuarioId: Peticion::consultaEntero('usuario'),
            accion: Peticion::consultaEnLista('accion', $acciones),
            fechaDesde: $desde,
            fechaHasta: $hasta,
        );
    }

    /** Parámetros para reconstruir la URL (paginación). */
    public function comoConsulta(): array
    {
        return [
            'usuario'     => $this->usuarioId,
            'accion'      => $this->accion,
            'fecha_desde' => $this->fechaDesde,
            'fecha_hasta' => $this->fechaHasta,
        ];
    }
}
