<?php

declare(strict_types=1);

namespace App\Formularios;

use App\Config;
use App\Core\Peticion;
use App\Core\Validador;
use App\Dominio\ErrorDeNegocio;
use App\Dominio\Permiso;
use App\Dominio\Rol;

/**
 * Lee y valida los formularios de usuarios: alta y edición (gestión de
 * usuarios) y cambio de la propia contraseña (cualquier usuario).
 *
 * Qué campos aplica cada quién (permisos, nombre de usuario...) lo decide
 * UsuarioServicio; aquí solo se valida el formato.
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

        $validador = self::validarDatosPersonales($datos);
        self::validarUsuario($validador, $datos['usuario']);
        self::validarContrasenaNueva($validador, $datos['contrasena'], Peticion::formularioExacto('contrasena_confirmacion'));
        $validador->exigirValido();

        return $datos;
    }

    /**
     * La contraseña es opcional al editar: vacía significa "no cambiarla".
     *
     * @param bool $conUsuario true si quien edita puede cambiar el nombre de usuario.
     * @throws ErrorDeNegocio
     */
    public static function edicion(bool $conUsuario): array
    {
        $datos = self::datosPersonales() + [
            'usuario'    => $conUsuario ? Peticion::formulario('usuario') : '',
            'activo'     => Peticion::formulario('activo') === '1',
            'contrasena' => Peticion::formularioExacto('contrasena'),
        ];

        $validador = self::validarDatosPersonales($datos);

        if ($conUsuario) {
            self::validarUsuario($validador, $datos['usuario']);
        }

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
            'permisos'              => array_values(array_unique(Peticion::formularioLista('permisos'))),
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
            ->enLista($datos['rol'], array_column(Rol::cases(), 'value'), 'Rol')
            ->verdadero(
                count(Permiso::desdeValores($datos['permisos'])) === count($datos['permisos']),
                'Uno de los permisos seleccionados no es válido.'
            );
    }

    private static function validarUsuario(Validador $validador, string $usuario): void
    {
        $validador
            ->requerido($usuario, 'Usuario')
            ->longitudMinima($usuario, 3, 'Usuario')
            ->longitudMaxima($usuario, 50, 'Usuario')
            ->patron($usuario, '/^[A-Za-z0-9._-]+$/', 'El usuario solo puede tener letras, números, punto, guion y guion bajo.');
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
