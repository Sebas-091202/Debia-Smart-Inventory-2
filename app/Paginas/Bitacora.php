<?php

declare(strict_types=1);

namespace App\Paginas;

use App\Core\Paginador;
use App\Core\Peticion;
use App\Core\Vista;
use App\Dominio\FiltrosBitacora;
use App\Dominio\Rol;
use App\Repositorios\AuditoriaRepositorio;
use App\Repositorios\UsuarioRepositorio;

/**
 * Bitácora de cambios (permiso "Ver bitácora de cambios"): quién creó,
 * editó o eliminó información, y cuándo. Incluye a los administradores.
 */
final class Bitacora
{
    private const POR_PAGINA = 20;

    public static function mostrar(Rol $rol): void
    {
        $auditoria = new AuditoriaRepositorio();
        $acciones = $auditoria->acciones();
        $filtros = FiltrosBitacora::desdeConsulta($acciones);
        $paginador = new Paginador($auditoria->contar($filtros), self::POR_PAGINA, Peticion::consultaEntero('pagina'));

        Vista::mostrar('paginas/bitacora', [
            'rol'       => $rol,
            'filtros'   => $filtros,
            'paginador' => $paginador,
            'registros' => $auditoria->listar($filtros, self::POR_PAGINA, $paginador->desplazamiento()),
            'acciones'  => $acciones,
            'usuarios'  => (new UsuarioRepositorio())->opciones(),
        ]);
    }
}
