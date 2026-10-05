<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Dominio\ErrorDeNegocio;
use App\Repositorios\AuditoriaRepositorio;
use App\Repositorios\EquipoRepositorio;

/**
 * Altas, cambios y bajas de equipos, siempre con registro en la bitácora.
 */
final class EquipoServicio
{
    public function __construct(
        private readonly EquipoRepositorio $equipos = new EquipoRepositorio(),
        private readonly AuditoriaRepositorio $auditoria = new AuditoriaRepositorio(),
    ) {
    }

    public function crear(array $datos, int $usuarioId): int
    {
        $this->exigirCodigoLibre($datos['codigo_barras']);

        $id = $this->equipos->crear($datos);
        $this->auditoria->registrar($usuarioId, 'CREAR EQUIPO', "Equipo creado con código: {$datos['codigo_barras']}");

        return $id;
    }

    public function actualizar(int $id, array $datos, int $usuarioId): void
    {
        $this->exigirCodigoLibre($datos['codigo_barras'], $id);

        $this->equipos->actualizar($id, $datos);
        $this->auditoria->registrar($usuarioId, 'ACTUALIZAR EQUIPO', "Equipo {$id} actualizado, código: {$datos['codigo_barras']}");
    }

    /**
     * Elimina el equipo. Por las claves foráneas (ON DELETE CASCADE) se
     * borran también sus mantenimientos.
     */
    public function eliminar(int $id, int $usuarioId): void
    {
        $equipo = $this->equipos->buscarPorId($id) ?? throw new ErrorDeNegocio('El equipo ya no existe.');

        $this->equipos->eliminar($id);
        $this->auditoria->registrar(
            $usuarioId,
            'ELIMINAR EQUIPO',
            "Equipo eliminado: {$equipo['identificador']} (código {$equipo['codigo_barras']})"
        );
    }

    private function exigirCodigoLibre(string $codigo, ?int $exceptoId = null): void
    {
        if ($this->equipos->codigoEnUso($codigo, $exceptoId)) {
            throw new ErrorDeNegocio("El código de barras «{$codigo}» ya está asignado a otro equipo.");
        }
    }
}
