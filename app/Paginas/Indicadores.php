<?php

declare(strict_types=1);

namespace App\Paginas;

use App\Core\Paginador;
use App\Core\Peticion;
use App\Core\Vista;
use App\Dominio\FiltrosIndicadores;
use App\Dominio\Rol;
use App\Repositorios\IndicadoresRepositorio;

/**
 * Tablero de mantenimiento de los equipos de cómputo: KPIs, gráfico
 * mensual y matriz de equipos con sus totales.
 */
final class Indicadores
{
    private const POR_PAGINA = 6;

    /** Meses por gráfico, para que las barras no queden diminutas. */
    private const MESES_POR_GRAFICO = 3;

    public static function mostrar(Rol $rol): void
    {
        $filtros = FiltrosIndicadores::desdeConsulta();
        $indicadores = new IndicadoresRepositorio();
        $paginador = new Paginador($indicadores->contarEquipos($filtros), self::POR_PAGINA, Peticion::consultaEntero('pagina'));

        Vista::mostrar('paginas/indicadores', [
            'rol'       => $rol,
            'filtros'   => $filtros,
            'paginador' => $paginador,
            'totales'   => $indicadores->totales($filtros),
            'equipos'   => $indicadores->equiposConTotales($filtros, self::POR_PAGINA, $paginador->desplazamiento()),
            'graficos'  => array_chunk(self::seriesMensuales($indicadores->mantenimientosPorMes($filtros)), self::MESES_POR_GRAFICO),
            'opciones'  => [
                'marca'         => $indicadores->opciones('marca'),
                'identificador' => $indicadores->opciones('identificador'),
                'ubicacion'     => $indicadores->opciones('ubicacion'),
            ],
        ]);
    }

    private static function seriesMensuales(array $filas): array
    {
        return array_map(static fn (array $fila) => [
            'mes'         => $fila['mes'],
            'preventivos' => (int) $fila['preventivos'],
            'correctivos' => (int) $fila['correctivos'],
        ], $filas);
    }
}
