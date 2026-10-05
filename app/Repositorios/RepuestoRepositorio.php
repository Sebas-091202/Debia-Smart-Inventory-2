<?php

declare(strict_types=1);

namespace App\Repositorios;

final class RepuestoRepositorio extends Repositorio
{
    /**
     * Devuelve el id del repuesto (mismo nombre, tipo y capacidad) sumándole
     * la cantidad al stock, o lo crea si aún no existe.
     */
    public function sumarOCrear(array $repuesto): int
    {
        $existente = $this->valor(
            'SELECT id FROM repuestos WHERE nombre = ? AND tipo = ? AND capacidad IS NOT DISTINCT FROM ? FOR UPDATE',
            [$repuesto['nombre'], $repuesto['tipo'], $repuesto['capacidad']]
        );

        if ($existente !== false) {
            $this->ejecutar('UPDATE repuestos SET stock = stock + ? WHERE id = ?', [$repuesto['cantidad'], (int) $existente]);

            return (int) $existente;
        }

        return $this->insertar(
            "INSERT INTO repuestos (nombre, serial, capacidad, valor, tipo, descripcion, stock, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Disponible')",
            [
                $repuesto['nombre'], $repuesto['serial'], $repuesto['capacidad'], $repuesto['valor'],
                $repuesto['tipo'], $repuesto['descripcion'], $repuesto['cantidad'],
            ]
        );
    }

    public function asociarAMantenimiento(int $mantenimientoId, int $repuestoId, int $cantidad, string $valorUnitario): void
    {
        $this->ejecutar(
            'INSERT INTO mantenimiento_repuestos (mantenimiento_id, repuesto_id, cantidad, valor_unitario) VALUES (?, ?, ?, ?)',
            [$mantenimientoId, $repuestoId, $cantidad, $valorUnitario]
        );
    }
}
