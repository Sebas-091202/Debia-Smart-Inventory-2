<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Config;
use App\Dominio\ErrorDeNegocio;
use App\Repositorios\IntentosLoginRepositorio;
use App\Repositorios\UsuarioRepositorio;

/**
 * Verificación de credenciales con protección contra fuerza bruta.
 */
final class AutenticacionServicio
{
    /**
     * Hash señuelo: si el usuario no existe se verifica contra él igualmente,
     * así el tiempo de respuesta no revela qué usuarios existen.
     */
    private const HASH_SENUELO = '$2y$10$sUBF4ODaDOKmIZLnjzmBvu.jjYPilqdQmMCsxv6nQFY3iFjQgGZPa';

    private const MENSAJE_CREDENCIALES = 'Usuario o contraseña incorrectos.';

    public function __construct(
        private readonly UsuarioRepositorio $usuarios = new UsuarioRepositorio(),
        private readonly IntentosLoginRepositorio $intentos = new IntentosLoginRepositorio(),
    ) {
    }

    /**
     * @return array{id: int, usuario: string, rol: string}
     * @throws ErrorDeNegocio si las credenciales fallan o el acceso está bloqueado.
     */
    public function autenticar(string $usuario, string $contrasena, string $ip): array
    {
        // Los intentos se cuentan sobre la forma normalizada: así no se puede
        // esquivar el bloqueo alternando mayúsculas ("Admin", "ADMIN"...).
        $usuarioNormalizado = self::normalizar($usuario);
        $identificador = hash('sha256', $usuarioNormalizado . '|' . $ip);

        $this->rechazarSiBloqueado($identificador);

        $registro = $usuarioNormalizado === '' ? null : $this->usuarios->buscarPorUsuario($usuarioNormalizado);

        // El usuario debe escribirse exactamente como está registrado
        // (distingue mayúsculas, minúsculas y espacios).
        $usuarioCoincide = $registro !== null && hash_equals($registro['usuario'], $usuario);

        // La contraseña se verifica siempre (con el hash señuelo si no hay
        // registro) para que el tiempo de respuesta no revele nada.
        $contrasenaValida = password_verify($contrasena, $registro['contrasena'] ?? self::HASH_SENUELO);

        if (!$usuarioCoincide || !$contrasenaValida) {
            $this->intentos->registrarFallo(
                $identificador,
                Config::LOGIN_INTENTOS_MAXIMOS,
                Config::LOGIN_VENTANA_INTENTOS,
                Config::LOGIN_DURACION_BLOQUEO
            );
            throw new ErrorDeNegocio(self::MENSAJE_CREDENCIALES);
        }

        $this->intentos->limpiar($identificador);

        // Solo se informa tras verificar la contraseña: a quien no la conoce
        // no se le revela que la cuenta existe.
        if (!$registro['activo']) {
            throw new ErrorDeNegocio('Tu cuenta está desactivada. Contacta al administrador.');
        }

        $this->actualizarHashSiEsNecesario((int) $registro['id'], $contrasena, $registro['contrasena']);

        return ['id' => (int) $registro['id'], 'usuario' => $registro['usuario'], 'rol' => $registro['rol']];
    }

    public static function normalizar(string $usuario): string
    {
        return mb_strtolower(trim($usuario));
    }

    private function rechazarSiBloqueado(string $identificador): void
    {
        $segundos = $this->intentos->segundosDeBloqueo($identificador);

        if ($segundos > 0) {
            $minutos = (int) ceil($segundos / 60);
            throw new ErrorDeNegocio("Demasiados intentos fallidos. Intenta de nuevo en {$minutos} minuto(s).");
        }
    }

    /** Migra hashes antiguos (p. ej. bcrypt de menor costo) al algoritmo actual. */
    private function actualizarHashSiEsNecesario(int $id, string $contrasena, string $hashActual): void
    {
        if (password_needs_rehash($hashActual, PASSWORD_DEFAULT)) {
            $this->usuarios->actualizarContrasena($id, password_hash($contrasena, PASSWORD_DEFAULT));
        }
    }
}
