<?php

declare(strict_types=1);

namespace App\Dominio;

/**
 * Resultado de un intento de inicio de sesión (coincide con la
 * restricción CHECK de inicios_sesion.resultado).
 */
enum ResultadoInicioSesion: string
{
    case Exitoso = 'EXITOSO';
    case Credenciales = 'CREDENCIALES';
    case Bloqueado = 'BLOQUEADO';
    case Desactivada = 'DESACTIVADA';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Exitoso      => 'Exitoso',
            self::Credenciales => 'Contraseña o usuario incorrecto',
            self::Bloqueado    => 'Bloqueado por intentos fallidos',
            self::Desactivada  => 'Cuenta desactivada',
        };
    }

    /** Clase de color compartida (componentes.css). */
    public function claseEstado(): string
    {
        return match ($this) {
            self::Exitoso      => 'estado-activo',
            self::Credenciales => 'estado-reparacion',
            self::Bloqueado    => 'estado-baja',
            self::Desactivada  => 'estado-bodega',
        };
    }

    /** @return array<string, string> valor => etiqueta, para el filtro. */
    public static function opciones(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(static fn (self $resultado) => $resultado->etiqueta(), self::cases())
        );
    }
}
