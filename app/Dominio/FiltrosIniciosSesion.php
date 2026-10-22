<?php

declare(strict_types=1);

namespace App\Dominio;

use App\Core\Peticion;

/**
 * Filtros del historial de inicios de sesión.
 */
final class FiltrosIniciosSesion
{
    public function __construct(
        public readonly ?int $usuarioId = null,
        public readonly string $resultado = '',
        public readonly string $fechaDesde = '',
        public readonly string $fechaHasta = '',
    ) {
    }

    public static function desdeConsulta(): self
    {
        [$desde, $hasta] = Peticion::consultaRangoFechas('fecha_desde', 'fecha_hasta');

        return new self(
            usuarioId: Peticion::consultaEntero('usuario'),
            resultado: Peticion::consultaEnLista('resultado', array_column(ResultadoInicioSesion::cases(), 'value')),
            fechaDesde: $desde,
            fechaHasta: $hasta,
        );
    }

    /** Parámetros para reconstruir la URL (paginación). */
    public function comoConsulta(): array
    {
        return [
            'usuario'     => $this->usuarioId,
            'resultado'   => $this->resultado,
            'fecha_desde' => $this->fechaDesde,
            'fecha_hasta' => $this->fechaHasta,
        ];
    }
}
