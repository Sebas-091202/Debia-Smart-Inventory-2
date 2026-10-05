<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

/**
 * Lectura tipada de la entrada del usuario ($_GET / $_POST).
 * Ningún controlador ni página lee los superglobales directamente:
 * todo valor llega como string recortado, entero válido o null.
 */
final class Peticion
{
    public static function exigirPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            Respuesta::error(405, 'Método no permitido.');
        }
    }

    /** Texto de la URL, recortado y limitado (los filtros nunca fallan). */
    public static function consulta(string $campo, int $longitudMaxima = 100): string
    {
        return mb_substr(self::texto($_GET, $campo), 0, $longitudMaxima);
    }

    /** Valor de la URL solo si pertenece a la lista permitida; si no, "". */
    public static function consultaEnLista(string $campo, array $permitidos): string
    {
        $valor = self::texto($_GET, $campo);

        return in_array($valor, $permitidos, true) ? $valor : '';
    }

    public static function consultaEntero(string $campo): ?int
    {
        return self::enteroPositivo($_GET[$campo] ?? null);
    }

    /** Fecha YYYY-MM-DD válida y razonable, o "" si no lo es. */
    public static function consultaFecha(string $campo): string
    {
        return self::fechaValida(self::texto($_GET, $campo)) ?? '';
    }

    /** Texto del formulario recortado; la validación de longitud la hace el Validador. */
    public static function formulario(string $campo): string
    {
        return self::texto($_POST, $campo);
    }

    /**
     * Texto del formulario tal cual se escribió, sin recortar espacios.
     * Para credenciales: deben coincidir exactamente con lo registrado.
     */
    public static function formularioExacto(string $campo): string
    {
        $valor = $_POST[$campo] ?? '';

        return is_string($valor) ? $valor : '';
    }

    public static function formularioEntero(string $campo): ?int
    {
        return self::enteroPositivo($_POST[$campo] ?? null);
    }

    /** Lista de textos (campos con nombre "campo[]"). */
    public static function formularioLista(string $campo): array
    {
        $valores = $_POST[$campo] ?? [];

        if (!is_array($valores)) {
            return [];
        }

        return array_map(static fn ($valor) => is_string($valor) ? trim($valor) : '', array_values($valores));
    }

    public static function fechaValida(string $valor): ?string
    {
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
            return null;
        }

        $anio = (int) $fecha->format('Y');

        return ($anio >= 2000 && $anio <= 2100) ? $valor : null;
    }

    private static function texto(array $origen, string $campo): string
    {
        $valor = $origen[$campo] ?? '';

        // Un array (campo[]=x) o cualquier otro tipo se trata como vacío.
        return is_string($valor) ? trim($valor) : '';
    }

    private static function enteroPositivo(mixed $valor): ?int
    {
        $entero = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $entero === false ? null : $entero;
    }
}
