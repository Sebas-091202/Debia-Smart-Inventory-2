<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Dominio\Rol;

final class UsuarioRepositorio extends Repositorio
{
    /** Columnas visibles en la gestión de usuarios (nunca el hash). */
    private const COLUMNAS_PUBLICAS = 'id, nombre, usuario, correo, numero_identificacion, rol, activo, fecha_creacion';

    /** Para el login: incluye el hash de la contraseña. */
    public function buscarPorUsuario(string $usuarioNormalizado): ?array
    {
        return $this->fila(
            'SELECT id, usuario, contrasena, rol, activo FROM usuarios WHERE usuario_normalizado = ? LIMIT 1',
            [$usuarioNormalizado]
        );
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->fila('SELECT ' . self::COLUMNAS_PUBLICAS . ' FROM usuarios WHERE id = ?', [$id]);
    }

    /** Hash de la contraseña actual (para verificarla antes de cambiarla). */
    public function hashContrasena(int $id): ?string
    {
        $hash = $this->valor('SELECT contrasena FROM usuarios WHERE id = ?', [$id]);

        return $hash === false ? null : $hash;
    }

    public function listar(): array
    {
        return $this->filas('SELECT ' . self::COLUMNAS_PUBLICAS . ' FROM usuarios ORDER BY activo DESC, rol, usuario');
    }

    /**
     * Ids de los administradores activos, bloqueando esas filas hasta el
     * fin de la transacción: dos cambios simultáneos no pueden dejar el
     * sistema sin ningún administrador.
     *
     * @return int[]
     */
    public function adminsActivosBloqueando(): array
    {
        return array_map('intval', $this->columna(
            'SELECT id FROM usuarios WHERE rol = ? AND activo FOR UPDATE',
            [Rol::Admin->value]
        ));
    }

    public function crear(array $datos): int
    {
        return $this->insertar(
            'INSERT INTO usuarios (nombre, usuario, correo, numero_identificacion, contrasena, rol)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $datos['nombre'], $datos['usuario'], $datos['correo'],
                $datos['numero_identificacion'], $datos['contrasena'], $datos['rol'],
            ]
        );
    }

    /** Datos editables por el administrador (el nombre de usuario no cambia). */
    public function actualizar(int $id, array $datos): void
    {
        $this->ejecutar(
            'UPDATE usuarios SET nombre = ?, correo = ?, numero_identificacion = ?, rol = ?, activo = ? WHERE id = ?',
            [$datos['nombre'], $datos['correo'], $datos['numero_identificacion'], $datos['rol'], $datos['activo'], $id]
        );
    }

    public function actualizarContrasena(int $id, string $hash): void
    {
        $this->ejecutar('UPDATE usuarios SET contrasena = ? WHERE id = ?', [$hash, $id]);
    }
}
