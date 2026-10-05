<?php

declare(strict_types=1);

namespace App\Formularios;

use App\Dominio\ErrorDeNegocio;
use App\Core\Peticion;
use App\Core\Validador;
use App\Dominio\Catalogo;

/**
 * Lee y valida el registro de un mantenimiento y sus repuestos opcionales.
 */
final class MantenimientoFormulario
{
    private const CANTIDAD_MAXIMA = 10000;

    /** @throws ErrorDeNegocio */
    public static function mantenimiento(): array
    {
        $datos = [
            'equipo_id'          => Peticion::formularioEntero('equipo_id'),
            'fecha'              => Peticion::formulario('fecha'),
            'responsable'        => Peticion::formulario('responsable'),
            'tipo_mantenimiento' => Peticion::formulario('tipo_mantenimiento'),
            'descripcion'        => Peticion::formulario('descripcion'),
            'estado'             => Peticion::formulario('estado'),
            'observaciones'      => Peticion::formulario('observaciones'),
        ];

        $validador = (new Validador())
            ->verdadero($datos['equipo_id'] !== null, 'No se indicó el equipo.')
            ->requerido($datos['fecha'], 'Fecha del mantenimiento')
            ->fecha($datos['fecha'], 'Fecha del mantenimiento')
            ->requerido($datos['responsable'], 'Responsable')
            ->longitudMaxima($datos['responsable'], 100, 'Responsable')
            ->requerido($datos['tipo_mantenimiento'], 'Tipo de Mantenimiento')
            ->enLista($datos['tipo_mantenimiento'], Catalogo::TIPOS_MANTENIMIENTO, 'Tipo de Mantenimiento')
            ->requerido($datos['descripcion'], 'Descripción')
            ->longitudMaxima($datos['descripcion'], 2000, 'Descripción')
            ->requerido($datos['estado'], 'Estado')
            ->enLista($datos['estado'], Catalogo::ESTADOS_MANTENIMIENTO, 'Estado')
            ->longitudMaxima($datos['observaciones'], 2000, 'Observaciones');

        $validador->exigirValido();

        return $datos;
    }

    /**
     * Filas de repuestos (campos "nombre_repuesto[]", etc.). Las filas
     * sin nombre, tipo ni cantidad se ignoran, como antes.
     *
     * @throws ErrorDeNegocio
     */
    public static function repuestos(): array
    {
        $nombres = Peticion::formularioLista('nombre_repuesto');
        $columnas = [
            'tipo'        => Peticion::formularioLista('tipo_repuesto'),
            'serial'      => Peticion::formularioLista('serial_repuesto'),
            'capacidad'   => Peticion::formularioLista('capacidad_repuesto'),
            'descripcion' => Peticion::formularioLista('descripcion_repuesto'),
            'valor'       => Peticion::formularioLista('valor_repuesto'),
            'cantidad'    => Peticion::formularioLista('cantidad'),
        ];

        $repuestos = [];

        foreach ($nombres as $i => $nombre) {
            $fila = ['nombre' => $nombre] + array_map(static fn (array $columna) => $columna[$i] ?? '', $columnas);

            if ($fila['nombre'] === '' || $fila['tipo'] === '' || $fila['cantidad'] === '') {
                continue;
            }

            $repuestos[] = self::repuestoValidado($fila);
        }

        return $repuestos;
    }

    private static function repuestoValidado(array $fila): array
    {
        $cantidad = filter_var($fila['cantidad'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => self::CANTIDAD_MAXIMA],
        ]);
        $valor = $fila['valor'] === '' ? '0' : $fila['valor'];

        (new Validador())
            ->longitudMaxima($fila['nombre'], 100, 'Nombre del repuesto')
            ->longitudMaxima($fila['tipo'], 50, 'Tipo de repuesto')
            ->longitudMaxima($fila['serial'], 100, 'Serial del repuesto')
            ->longitudMaxima($fila['capacidad'], 50, 'Capacidad del repuesto')
            ->longitudMaxima($fila['descripcion'], 1000, 'Descripción del repuesto')
            ->verdadero($cantidad !== false, 'La cantidad de cada repuesto debe ser un número entre 1 y ' . self::CANTIDAD_MAXIMA . '.')
            ->patron($valor, '/^\d{1,8}(\.\d{1,2})?$/', 'El valor del repuesto debe ser un número positivo (hasta 2 decimales).')
            ->exigirValido();

        return [
            'nombre'      => $fila['nombre'],
            'tipo'        => $fila['tipo'],
            // El serial es UNIQUE: vacío se guarda como NULL para no chocar.
            'serial'      => $fila['serial'] === '' ? null : $fila['serial'],
            'capacidad'   => $fila['capacidad'] === '' ? null : $fila['capacidad'],
            'descripcion' => $fila['descripcion'] === '' ? null : $fila['descripcion'],
            'valor'       => $valor,
            'cantidad'    => (int) $cantidad,
        ];
    }
}
