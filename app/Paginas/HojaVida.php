<?php

declare(strict_types=1);

namespace App\Paginas;

use App\Core\Flash;
use App\Core\Paginador;
use App\Core\Peticion;
use App\Core\Vista;
use App\Dominio\FiltrosEquipo;
use App\Dominio\Rol;
use App\Repositorios\EquipoRepositorio;
use App\Repositorios\MantenimientoRepositorio;

/**
 * Hoja de vida de un equipo: búsqueda, ficha técnica, historial de
 * mantenimientos y (solo administrador) registro de un mantenimiento.
 *
 * El equipo llega por ?codigo= (código de barras) o por ?id=.
 */
final class HojaVida
{
    private const POR_PAGINA = 10;

    public static function mostrar(Rol $rol): void
    {
        $filtros = self::filtrosDeBusqueda();
        $equipos = new EquipoRepositorio();
        [$equipo, $seBuscoEquipo] = self::equipoSolicitado($equipos);

        $datos = [
            'rol'                => $rol,
            'filtros'            => $filtros,
            'identificadores'    => $equipos->identificadores($filtros),
            'equipo'             => $equipo,
            'equipoNoEncontrado' => $seBuscoEquipo && $equipo === null,
            'historial'          => $equipo ? (new MantenimientoRepositorio())->historialDeEquipo((int) $equipo['id']) : [],
            'resultados'         => [],
            'paginador'          => null,
            'entradaAnterior'    => Flash::extraerEntrada(),
        ];

        if ($equipo === null && $filtros->hayFiltros()) {
            $datos['paginador'] = new Paginador($equipos->contar($filtros), self::POR_PAGINA, Peticion::consultaEntero('pagina'));
            $datos['resultados'] = $equipos->listar($filtros, self::POR_PAGINA, $datos['paginador']->desplazamiento());
        }

        Vista::mostrar('paginas/hoja_vida', $datos);
    }

    /** En esta vista solo se filtra por tipo, marca, ubicación e identificador. */
    private static function filtrosDeBusqueda(): FiltrosEquipo
    {
        $consulta = FiltrosEquipo::desdeConsulta();

        return new FiltrosEquipo(
            tipo: $consulta->tipo,
            marca: $consulta->marca,
            ubicacion: $consulta->ubicacion,
            identificador: $consulta->identificador,
        );
    }

    /** @return array{0: ?array, 1: bool} Equipo encontrado y si se pidió alguno. */
    private static function equipoSolicitado(EquipoRepositorio $equipos): array
    {
        $codigo = Peticion::consulta('codigo');

        if ($codigo !== '') {
            return [$equipos->buscarPorCodigo($codigo), true];
        }

        $id = Peticion::consultaEntero('id');

        return $id === null ? [null, false] : [$equipos->buscarPorId($id), true];
    }
}
