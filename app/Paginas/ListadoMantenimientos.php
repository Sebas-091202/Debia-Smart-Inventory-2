<?php

declare(strict_types=1);

namespace App\Paginas;

use App\Core\Paginador;
use App\Core\Peticion;
use App\Core\Vista;
use App\Dominio\FiltrosMantenimiento;
use App\Dominio\Rol;
use App\Repositorios\MantenimientoRepositorio;

/**
 * Historial de mantenimientos de un tipo (Correctivo o Preventivo).
 * Ambas vistas comparten estructura; solo cambian textos y hoja de estilos.
 */
final class ListadoMantenimientos
{
    private const POR_PAGINA = 10;

    private const PRESENTACION = [
        'Correctivo' => [
            'titulo'        => 'Historial de Mantenimientos Correctivos',
            'estilo'        => 'ver_correctivos.css',
            'claseTabla'    => 'tabla-correctivos',
            'plural'        => 'correctivos',
            'contenedores'  => ['main-content', 'container'],
            'textoHojaVida' => 'Ver Hoja',
        ],
        'Preventivo' => [
            'titulo'        => 'Historial de Mantenimientos Preventivos',
            'estilo'        => 'ver_preventivos.css',
            'claseTabla'    => 'tabla-preventivos',
            'plural'        => 'preventivos',
            'contenedores'  => ['container'],
            'textoHojaVida' => 'Ver Hoja de Vida',
        ],
    ];

    public static function mostrar(Rol $rol, string $tipoMantenimiento): void
    {
        $filtros = FiltrosMantenimiento::desdeConsulta($tipoMantenimiento);
        $mantenimientos = new MantenimientoRepositorio();
        $paginador = new Paginador($mantenimientos->contar($filtros), self::POR_PAGINA, Peticion::consultaEntero('pagina'));

        Vista::mostrar('paginas/mantenimientos_listado', [
            'rol'               => $rol,
            'tipoMantenimiento' => $tipoMantenimiento,
            'presentacion'      => self::PRESENTACION[$tipoMantenimiento],
            'filtros'           => $filtros,
            'paginador'         => $paginador,
            'mantenimientos'    => $mantenimientos->listar($filtros, self::POR_PAGINA, $paginador->desplazamiento()),
            'identificadores'   => $mantenimientos->identificadores($filtros),
        ]);
    }
}
