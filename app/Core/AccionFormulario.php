<?php

declare(strict_types=1);

namespace App\Core;

use App\Dominio\ErrorDeNegocio;
use App\Dominio\Rol;

/**
 * Plantilla común de los controladores que reciben un formulario POST:
 *
 *   1. exige el rol (si se indica), el método POST y un token CSRF válido;
 *   2. ejecuta la acción, que devuelve la URL a la que redirigir;
 *   3. si la acción lanza ErrorDeNegocio, muestra el mensaje, conserva lo
 *      escrito en el formulario y vuelve a $urlSiFalla.
 */
final class AccionFormulario
{
    /** Campos que nunca se guardan para volver a rellenar el formulario. */
    private const CAMPOS_NO_RECORDABLES = ['csrf_token', 'contrasena', 'contrasena_confirmacion', 'contrasena_actual'];

    /** @param callable(): string $accion */
    public static function ejecutar(?Rol $rolRequerido, string $urlSiFalla, callable $accion, string $contextoError = ''): never
    {
        if ($rolRequerido !== null) {
            Auth::exigirRol($rolRequerido);
        }

        Peticion::exigirPost();
        Csrf::verificar();

        try {
            Respuesta::redirigir($accion());
        } catch (ErrorDeNegocio $error) {
            Flash::guardar('error', $error->getMessage(), $contextoError);
            Flash::guardarEntrada(array_diff_key($_POST, array_flip(self::CAMPOS_NO_RECORDABLES)));
            Respuesta::redirigir($urlSiFalla);
        }
    }
}
