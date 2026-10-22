<?php

declare(strict_types=1);

namespace App\Servicios;

use App\Config;
use App\Dominio\ErrorDeNegocio;
use App\Dominio\ResultadoInicioSesion;
use App\Repositorios\InicioSesionRepositorio;
use App\Repositorios\IntentosLoginRepositorio;
use App\Repositorios\UsuarioRepositorio;

/**
 * Verificación de credenciales con protección contra fuerza bruta y
 * registro de cada intento en el historial de inicios de sesión.
 *
 * Dos contadores de fallos: por usuario + IP (5) y por IP sola (30), que
 * frena a quien prueba una misma contraseña contra muchas cuentas.
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
        private readonly InicioSesionRepositorio $inicios = new InicioSesionRepositorio(),
    ) {
    }

    /**
     * @return array{id: int, usuario: string, rol: string}
     * @throws ErrorDeNegocio si las credenciales fallan o el acceso está bloqueado.
     */
    public function autenticar(string $usuario, string $contrasena, string $ip, string $navegador): array
    {
        // Los intentos se cuentan sobre la forma normalizada: así no se puede
        // esquivar el bloqueo alternando mayúsculas ("Admin", "ADMIN"...).
        // Un usuario que no es UTF-8 válido no puede existir (y la base lo
        // rechazaría con un error): se trata como un usuario inexistente.
        $usuarioNormalizado = mb_check_encoding($usuario, 'UTF-8') ? self::normalizar($usuario) : '';
        $porUsuario = hash('sha256', $usuarioNormalizado . '|' . $ip);
        // "ip:" no puede coincidir con la clave de arriba, que siempre lleva "|".
        $porIp = hash('sha256', 'ip:' . $ip);

        // Mientras dura un bloqueo los intentos no se registran en el
        // historial: ya quedó el que lo provocó y así un ataque no lo llena.
        $this->rechazarSiBloqueado($porUsuario, $porIp);

        $registro = $usuarioNormalizado === '' ? null : $this->usuarios->buscarPorUsuario($usuarioNormalizado);
        $usuarioId = $registro === null ? null : (int) $registro['id'];

        // El usuario debe escribirse exactamente como está registrado
        // (distingue mayúsculas, minúsculas y espacios).
        $usuarioCoincide = $registro !== null && hash_equals($registro['usuario'], $usuario);

        // La contraseña se verifica siempre (con el hash señuelo si no hay
        // registro) para que el tiempo de respuesta no revele nada.
        $contrasenaValida = password_verify($contrasena, $registro['contrasena'] ?? self::HASH_SENUELO);

        if (!$usuarioCoincide || !$contrasenaValida) {
            $this->registrarFallo($porUsuario, Config::LOGIN_INTENTOS_MAXIMOS);
            $this->registrarFallo($porIp, Config::LOGIN_INTENTOS_MAXIMOS_IP);

            $quedoBloqueado = $this->segundosDeBloqueo($porUsuario, $porIp) > 0;
            $this->inicios->registrar(
                $usuarioId,
                $quedoBloqueado ? ResultadoInicioSesion::Bloqueado : ResultadoInicioSesion::Credenciales,
                $ip,
                $navegador
            );

            throw new ErrorDeNegocio(self::MENSAJE_CREDENCIALES);
        }

        // El contador por IP no se limpia: caduca solo, así un acierto no
        // borra los fallos que alguien acumuló contra otras cuentas.
        $this->intentos->limpiar($porUsuario);

        // Solo se informa tras verificar la contraseña: a quien no la conoce
        // no se le revela que la cuenta existe.
        if (!$registro['activo']) {
            $this->inicios->registrar($usuarioId, ResultadoInicioSesion::Desactivada, $ip, $navegador);
            throw new ErrorDeNegocio('Tu cuenta está desactivada. Contacta al administrador.');
        }

        $this->actualizarHashSiEsNecesario($usuarioId, $contrasena, $registro['contrasena']);
        $this->inicios->registrar($usuarioId, ResultadoInicioSesion::Exitoso, $ip, $navegador);

        return ['id' => $usuarioId, 'usuario' => $registro['usuario'], 'rol' => $registro['rol']];
    }

    public static function normalizar(string $usuario): string
    {
        return mb_strtolower(trim($usuario));
    }

    private function rechazarSiBloqueado(string ...$identificadores): void
    {
        $segundos = $this->segundosDeBloqueo(...$identificadores);

        if ($segundos > 0) {
            $minutos = (int) ceil($segundos / 60);
            throw new ErrorDeNegocio("Demasiados intentos fallidos. Intenta de nuevo en {$minutos} minuto(s).");
        }
    }

    /** El bloqueo más largo entre los contadores indicados (0 = ninguno). */
    private function segundosDeBloqueo(string ...$identificadores): int
    {
        return max(array_map(fn (string $identificador) => $this->intentos->segundosDeBloqueo($identificador), $identificadores));
    }

    private function registrarFallo(string $identificador, int $maximo): void
    {
        $this->intentos->registrarFallo($identificador, $maximo, Config::LOGIN_VENTANA_INTENTOS, Config::LOGIN_DURACION_BLOQUEO);
    }

    /** Migra hashes antiguos (p. ej. bcrypt de menor costo) al algoritmo actual. */
    private function actualizarHashSiEsNecesario(int $id, string $contrasena, string $hashActual): void
    {
        if (password_needs_rehash($hashActual, PASSWORD_DEFAULT)) {
            $this->usuarios->actualizarContrasena($id, password_hash($contrasena, PASSWORD_DEFAULT));
        }
    }
}
