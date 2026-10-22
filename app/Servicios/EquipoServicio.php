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
    /** Nombre de cada columna en la bitácora (mismo orden que EquipoRepositorio::CAMPOS). */
    private const ETIQUETAS = [
        'tipo'          => 'Tipo',
        'marca'         => 'Marca',
        'identificador' => 'Identificador',
        'asignado_a'    => 'Asignado a',
        'simcard'       => 'SIMCARD',
        'simcard2'      => 'SIMCARD 2',
        'serial'        => 'Serial',
        'procesador'    => 'Procesador',
        'ram'           => 'RAM',
        'disco'         => 'Disco C:',
        'disco2'        => 'Disco D:',
        'estado'        => 'Estado',
        'ubicacion'     => 'Ubicación',
        'codigo_barras' => 'Código de barras',
    ];

    public function __construct(
        private readonly EquipoRepositorio $equipos = new EquipoRepositorio(),
        private readonly AuditoriaRepositorio $auditoria = new AuditoriaRepositorio(),
    ) {
    }

    public function crear(array $datos, int $usuarioId): int
    {
        $this->exigirCodigoLibre($datos['codigo_barras']);

        $id = $this->equipos->crear($datos);
        $this->auditoria->registrar(
            $usuarioId,
            'CREAR EQUIPO',
            "Equipo {$datos['identificador']} creado con código: {$datos['codigo_barras']}"
        );

        return $id;
    }

    /** La bitácora guarda qué campos cambiaron, con el valor anterior y el nuevo. */
    public function actualizar(int $id, array $datos, int $usuarioId): void
    {
        $this->exigirCodigoLibre($datos['codigo_barras'], $id);
        $antes = $this->equipos->buscarPorId($id) ?? throw new ErrorDeNegocio('El equipo ya no existe.');

        $this->equipos->actualizar($id, $datos);
        $this->auditoria->registrar(
            $usuarioId,
            'ACTUALIZAR EQUIPO',
            "Equipo {$antes['identificador']} (código {$antes['codigo_barras']}): " . self::describirCambios($antes, $datos)
        );
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

    /** "Asignado a: «Juan» → «Pedro»; RAM: «8GB» → «16GB»", o "sin cambios". */
    private static function describirCambios(array $antes, array $despues): string
    {
        $cambios = [];

        foreach (self::ETIQUETAS as $campo => $etiqueta) {
            $anterior = (string) ($antes[$campo] ?? '');
            $nuevo = (string) ($despues[$campo] ?? '');

            if ($anterior !== $nuevo) {
                $cambios[] = sprintf('%s: «%s» → «%s»', $etiqueta, $anterior === '' ? 'vacío' : $anterior, $nuevo === '' ? 'vacío' : $nuevo);
            }
        }

        return $cambios === [] ? 'sin cambios' : implode('; ', $cambios);
    }

    private function exigirCodigoLibre(string $codigo, ?int $exceptoId = null): void
    {
        if ($this->equipos->codigoEnUso($codigo, $exceptoId)) {
            throw new ErrorDeNegocio("El código de barras «{$codigo}» ya está asignado a otro equipo.");
        }
    }
}
