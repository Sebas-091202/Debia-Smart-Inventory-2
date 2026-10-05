<?php

declare(strict_types=1);

namespace App\Dominio;

use App\Core\Peticion;

/**
 * Filtros del historial de mantenimientos (correctivos o preventivos).
 */
final class FiltrosMantenimiento
{
    public function __construct(
        public readonly string $tipoMantenimiento,
        public readonly string $tipo = '',
        public readonly string $marca = '',
        public readonly string $ubicacion = '',
        public readonly string $identificador = '',
        public readonly string $fechaDesde = '',
        public readonly string $fechaHasta = '',
    ) {
    }

    public static function desdeConsulta(string $tipoMantenimiento): self
    {
        $desde = Peticion::consultaFecha('fecha_desde');
        $hasta = Peticion::consultaFecha('fecha_hasta');

        // Un rango invertido se corrige en lugar de devolver cero resultados.
        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return new self(
            tipoMantenimiento: $tipoMantenimiento,
            tipo: Peticion::consultaEnLista('tipo', array_keys(Catalogo::TIPOS_EQUIPO)),
            marca: Peticion::consultaEnLista('marca', array_keys(Catalogo::MARCAS)),
            ubicacion: Peticion::consultaEnLista('ubicacion', Catalogo::UBICACIONES),
            identificador: Peticion::consulta('identificador'),
            fechaDesde: $desde,
            fechaHasta: $hasta,
        );
    }

    public function comoConsulta(): array
    {
        return [
            'tipo'          => $this->tipo,
            'marca'         => $this->marca,
            'ubicacion'     => $this->ubicacion,
            'identificador' => $this->identificador,
            'fecha_desde'   => $this->fechaDesde,
            'fecha_hasta'   => $this->fechaHasta,
        ];
    }
}
