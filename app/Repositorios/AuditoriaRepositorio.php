<?php

declare(strict_types=1);

namespace App\Repositorios;

/**
 * Bitácora de acciones de los usuarios (tabla logs_sistema).
 */
final class AuditoriaRepositorio extends Repositorio
{
    public function registrar(?int $usuarioId, string $accion, string $detalle): void
    {
        $this->ejecutar(
            'INSERT INTO logs_sistema (usuario_id, accion, detalle) VALUES (?, ?, ?)',
            [$usuarioId, $accion, $detalle]
        );
    }
}
