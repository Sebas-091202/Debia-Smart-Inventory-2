<?php

declare(strict_types=1);

namespace App\Dominio;

use App\Core\Peticion;

/**
 * Filtros de búsqueda de equipos, ya validados contra el Catálogo.
 * Un valor fuera de catálogo se descarta (queda como "sin filtro").
 */
final class FiltrosEquipo
{
    public function __construct(
        public readonly string $tipo = '',
        public readonly string $marca = '',
        public readonly string $ubicacion = '',
        public readonly string $estado = '',
        public readonly string $identificador = '',
        public readonly string $codigo = '',
    ) {
    }

    public static function desdeConsulta(): self
    {
        return new self(
            tipo: Peticion::consultaEnLista('tipo', array_keys(Catalogo::TIPOS_EQUIPO)),
            marca: Peticion::consultaEnLista('marca', array_keys(Catalogo::MARCAS)),
            ubicacion: Peticion::consultaEnLista('ubicacion', Catalogo::UBICACIONES),
            estado: Peticion::consultaEnLista('estado', Catalogo::ESTADOS_EQUIPO),
            identificador: Peticion::consulta('identificador'),
            codigo: Peticion::consulta('codigo'),
        );
    }

    public function hayFiltros(): bool
    {
        return array_filter($this->comoConsulta()) !== [];
    }

    /** Parámetros para reconstruir la URL (paginación). */
    public function comoConsulta(): array
    {
        return [
            'tipo'          => $this->tipo,
            'marca'         => $this->marca,
            'ubicacion'     => $this->ubicacion,
            'estado'        => $this->estado,
            'identificador' => $this->identificador,
            'codigo'        => $this->codigo,
        ];
    }
}
