<?php

declare(strict_types=1);

namespace App\Dominio;

use App\Core\Peticion;

/**
 * Filtros del tablero de indicadores (solo equipos de cómputo).
 */
final class FiltrosIndicadores
{
    public function __construct(
        public readonly string $tipo = '',
        public readonly string $marca = '',
        public readonly string $identificador = '',
        public readonly string $ubicacion = '',
    ) {
    }

    public static function desdeConsulta(): self
    {
        return new self(
            tipo: Peticion::consultaEnLista('tipo', Catalogo::TIPOS_COMPUTO),
            marca: Peticion::consultaEnLista('marca', array_keys(Catalogo::MARCAS)),
            identificador: Peticion::consulta('identificador'),
            ubicacion: Peticion::consultaEnLista('ubicacion', Catalogo::UBICACIONES),
        );
    }

    public function comoConsulta(): array
    {
        return [
            'tipo'          => $this->tipo,
            'marca'         => $this->marca,
            'identificador' => $this->identificador,
            'ubicacion'     => $this->ubicacion,
        ];
    }
}
