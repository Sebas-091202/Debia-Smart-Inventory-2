<?php

declare(strict_types=1);

namespace App\Formularios;

use App\Config;
use App\Core\Peticion;
use App\Core\Validador;
use App\Dominio\ErrorDeNegocio;
use App\Dominio\Rol;

/**
 * Lee y valida los formularios de usuarios: alta y edición (administrador)
 * y cambio de la propia contraseña (cualquier usuario).
 *
 * Las contraseñas se leen sin recortar: los espacios son parte de ellas.
 */
final class UsuarioFormulario
{
    /** @throws ErrorDeNegocio */
    public static function alta(): array
    {
        $datos = self::datosPersonales() + [
            'usuario'    => Peticion::formulario('usuario'),
            'contrasena' => Peticion::formularioExacto('contrasena'),
        ];

        $validador = self::validarDatosPersonales($datos)
            ->requerido($datos['usuario'], 'Usuario')
            ->longitudMinima($datos['usuario'], 3, 'Usuario')
            ->longitudMaxima($datos['usuario'], 50, 'Usuario')
            ->patron($datos['usuario'], '/^[A-Za-z0-9._-]+$/', 'El usuario solo puede tener letras, números, punto, guion y guion bajo.');

        self::validarContrasenaNueva($validador, $datos['contrasena'], Peticion::formularioExacto('contrasena_confirmacion'));
        $validador->exigirValido();

        return $datos;
    }

    /**
     * La contraseña es opcional al editar: vacía significa "no cambiarla".
     *
     * @throws ErrorDeNegocio
     */
    public static function edicion(): array
    {
        $datos = self::datosPersonales() + [
            'activo'     => Peticion::formulario('activo') === '1',
            'contrasena' => Peticion::formularioExacto('contrasena'),
        ];

        $validador = self::validarDatosPersonales($datos);

        if ($datos['contrasena'] !== '') {
            self::validarContrasenaNueva($validador, $datos['contrasena'], Peticion::formularioExacto('contrasena_confirmacion'));
        }

        $validador->exigirValido();

        return $datos;
    }

    /**
     * @return array{actual: string, nueva: string}
     * @throws ErrorDeNegocio
     */
    public static function cambioContrasena(): array
    {
        $datos = [
            'actual' => Peticion::formularioExacto('contrasena_actual'),
            'nueva'  => Peticion::formularioExacto('contrasena'),
        ];

        $validador = (new Validador())->requerido($datos['actual'], 'Contraseña actual');
        self::validarContrasenaNueva($validador, $datos['nueva'], Peticion::formularioExacto('contrasena_confirmacion'));
        $validador
            ->verdadero($datos['nueva'] !== $datos['actual'], 'La contraseña nueva debe ser distinta de la actual.')
            ->exigirValido();

        return $datos;
    }

    private static function datosPersonales(): array
    {
        return [
            'nombre'                => Peticion::formulario('nombre'),
            'correo'                => Peticion::formulario('correo'),
            'numero_identificacion' => Peticion::formulario('numero_identificacion'),
            'rol'                   => Peticion::formulario('rol'),
        ];
    }

    private static function validarDatosPersonales(array $datos): Validador
    {
        return (new Validador())
            ->requerido($datos['nombre'], 'Nombre completo')
            ->longitudMaxima($datos['nombre'], 100, 'Nombre completo')
            ->requerido($datos['correo'], 'Correo electrónico')
            ->longitudMaxima($datos['correo'], 100, 'Correo electrónico')
            ->correo($datos['correo'], 'Correo electrónico')
            ->requerido($datos['numero_identificacion'], 'Identificación')
            ->patron($datos['numero_identificacion'], '/^\d{5,20}$/', 'La identificación debe tener entre 5 y 20 dígitos.')
            ->requerido($datos['rol'], 'Rol')
            ->enLista($datos['rol'], array_column(Rol::cases(), 'value'), 'Rol');
    }

    private static function validarContrasenaNueva(Validador $validador, string $contrasena, string $confirmacion): void
    {
        $validador
            ->requerido($contrasena, 'Contraseña')
            ->longitudMinima($contrasena, Config::CONTRASENA_LONGITUD_MINIMA, 'Contraseña')
            ->verdadero(strlen($contrasena) <= Config::CONTRASENA_LONGITUD_MAXIMA, 'La contraseña es demasiado larga.')
            ->verdadero($contrasena === $confirmacion, 'La confirmación no coincide con la contraseña.');
    }
}
