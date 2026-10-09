<?php

declare(strict_types=1);

namespace App\Repositorios;

use App\Dominio\Rol;

final class UsuarioRepositorio extends Repositorio
{
    /**
     * Columnas visibles en la gestión de usuarios (nunca el hash).
     * Los permisos llegan como texto "A,B" (ver Cuenta::valoresDePermisos).
     */
    private const COLUMNAS_PUBLICAS = "id, nombre, usuario, correo, numero_identificacion, rol, activo, fecha_creacion,
        array_to_string(permisos, ',') AS permisos";

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

    public function existeCorreo(string $correo): bool
    {
        return (bool) $this->valor('SELECT EXISTS (SELECT 1 FROM usuarios WHERE lower(correo) = lower(?))', [$correo]);
    }

    public function contar(): int
    {
        return (int) $this->valor('SELECT COUNT(*) FROM usuarios');
    }

    public function listar(int $limite, int $desplazamiento): array
    {
        return $this->filas(
            'SELECT ' . self::COLUMNAS_PUBLICAS . ' FROM usuarios
             ORDER BY activo DESC, rol, usuario_normalizado LIMIT :limite OFFSET :desplazamiento',
            ['limite' => $limite, 'desplazamiento' => $desplazamiento]
        );
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

    /** @param string[] $datos['permisos'] */
    public function crear(array $datos): int
    {
        return $this->insertar(
            'INSERT INTO usuarios (nombre, usuario, correo, numero_identificacion, contrasena, rol, permisos)
             VALUES (?, ?, ?, ?, ?, ?, ?::text[])',
            [
                $datos['nombre'], $datos['usuario'], $datos['correo'], $datos['numero_identificacion'],
                $datos['contrasena'], $datos['rol'], self::arregloPostgres($datos['permisos']),
            ]
        );
    }

    /** Guarda los datos ya resueltos por UsuarioServicio (qué puede cambiar cada quién). */
    public function actualizar(int $id, array $datos): void
    {
        $this->ejecutar(
            'UPDATE usuarios SET nombre = ?, usuario = ?, correo = ?, numero_identificacion = ?, rol = ?, activo = ?,
                permisos = ?::text[]
             WHERE id = ?',
            [
                $datos['nombre'], $datos['usuario'], $datos['correo'], $datos['numero_identificacion'],
                $datos['rol'], $datos['activo'], self::arregloPostgres($datos['permisos']), $id,
            ]
        );
    }

    public function actualizarContrasena(int $id, string $hash): void
    {
        $this->ejecutar('UPDATE usuarios SET contrasena = ? WHERE id = ?', [$hash, $id]);
    }

    /**
     * Literal de arreglo de PostgreSQL ("{A,B}"). Los valores vienen del
     * enum Permiso (mayúsculas y guion bajo), así que no requieren comillas;
     * igualmente viaja como parámetro, nunca concatenado en el SQL.
     *
     * @param string[] $valores
     */
    private static function arregloPostgres(array $valores): string
    {
        return '{' . implode(',', $valores) . '}';
    }
}
