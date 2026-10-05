<?php

declare(strict_types=1);

namespace App\Dominio;

use RuntimeException;

/**
 * Error esperado (validación, regla de negocio) cuyo mensaje es seguro
 * de mostrar al usuario. Cualquier otra excepción se registra en el log
 * y el usuario solo ve un mensaje genérico.
 */
final class ErrorDeNegocio extends RuntimeException
{
}
