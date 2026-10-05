<?php

declare(strict_types=1);

namespace App\Formularios;

use App\Dominio\ErrorDeNegocio;
use App\Core\Peticion;
use App\Core\Validador;
use App\Dominio\Catalogo;
use App\Repositorios\EquipoRepositorio;

/**
 * Lee y valida el formulario de alta/edición de equipos.
 */
final class EquipoFormulario
{
    /**
     * @param array|null $equipoActual Al editar, sus valores guardados se
     *        aceptan aunque ya no estén en el catálogo (datos históricos).
     * @throws ErrorDeNegocio con el primer error de validación.
     */
    public static function desdePeticion(?array $equipoActual = null): array
    {
        $datos = [];
        foreach (EquipoRepositorio::CAMPOS as $campo) {
            $datos[$campo] = Peticion::formulario($campo);
        }

        $permitidos = static fn (array $catalogo, string $campo): array =>
            ($equipoActual[$campo] ?? null) !== null ? [...$catalogo, $equipoActual[$campo]] : $catalogo;

        $esAlta = $equipoActual === null;

        $validador = (new Validador())
            ->requerido($datos['tipo'], 'Tipo')
            ->enLista($datos['tipo'], $permitidos(array_keys(Catalogo::TIPOS_EQUIPO), 'tipo'), 'Tipo')
            ->requerido($datos['marca'], 'Marca')
            ->enLista($datos['marca'], $permitidos(array_keys(Catalogo::MARCAS), 'marca'), 'Marca')
            ->requerido($datos['identificador'], 'Identificador')
            ->longitudMaxima($datos['identificador'], 50, 'Identificador')
            ->longitudMaxima($datos['asignado_a'], 100, 'Asignado a')
            ->longitudMaxima($datos['serial'], 100, 'Serial')
            ->longitudMaxima($datos['procesador'], 100, 'Procesador')
            ->enLista($datos['ram'], $permitidos(Catalogo::RAM, 'ram'), 'RAM')
            ->enLista($datos['disco'], $permitidos(Catalogo::valores(Catalogo::DISCOS_C), 'disco'), 'Disco C:')
            ->enLista($datos['disco2'], $permitidos(Catalogo::valores(Catalogo::DISCOS_D), 'disco2'), 'Disco D:')
            ->requerido($datos['estado'], 'Estado')
            ->enLista($datos['estado'], Catalogo::ESTADOS_EQUIPO, 'Estado')
            ->requerido($datos['ubicacion'], 'Ubicación')
            ->enLista($datos['ubicacion'], $permitidos(Catalogo::UBICACIONES, 'ubicacion'), 'Ubicación')
            ->requerido($datos['codigo_barras'], 'Código de Barras')
            ->longitudMaxima($datos['codigo_barras'], 100, 'Código de Barras')
            ->patron($datos['codigo_barras'], '/^[^\p{C}]+$/u', 'El código de barras contiene caracteres no válidos.');

        // En registros antiguos estos campos pueden estar vacíos: solo se
        // exigen al dar de alta para no bloquear la edición de esos equipos.
        if ($esAlta) {
            $validador->requerido($datos['asignado_a'], 'Asignado a')->requerido($datos['serial'], 'Serial');
        }

        $validador->exigirValido();

        return $datos;
    }
}
