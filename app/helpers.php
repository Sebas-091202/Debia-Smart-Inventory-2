<?php

declare(strict_types=1);

/**
 * Funciones globales de apoyo para las plantillas.
 * Se mantienen cortas a propósito: la lógica vive en las clases de App\.
 */

use App\Core\Csrf;
use App\Dominio\Catalogo;

/** Escapa un valor para imprimirlo de forma segura en HTML (evita XSS). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Campo oculto con el token CSRF para incluir en todo formulario POST. */
function campo_csrf(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(Csrf::token()) . '">';
}

/** Atributo "selected" cuando el valor coincide con el seleccionado. */
function seleccionado(string $valor, ?string $actual): string
{
    return $valor === (string) $actual ? 'selected' : '';
}

/**
 * Etiquetas <option> de un catálogo. Una lista simple usa el mismo texto
 * como valor; un arreglo asociativo usa la clave como valor.
 */
function opciones_select(array $opciones, ?string $actual): string
{
    $html = '';

    foreach ($opciones as $clave => $texto) {
        $valor = array_is_list($opciones) ? (string) $texto : (string) $clave;
        $html .= '<option value="' . e($valor) . '" ' . seleccionado($valor, $actual) . '>' . e($texto) . '</option>';
    }

    return $html;
}

/** Badge de color según el estado del equipo o del mantenimiento. */
function badge_estado(?string $estado, string $claseBase = 'badge'): string
{
    return '<span class="' . e($claseBase . ' ' . Catalogo::claseEstado($estado)) . '">' . e($estado) . '</span>';
}

/** Texto alternativo cuando el valor está vacío. */
function o_si_vacio(mixed $valor, string $alternativa): string
{
    $texto = trim((string) ($valor ?? ''));

    return $texto === '' ? $alternativa : $texto;
}

/**
 * Serializa datos para un bloque <script type="application/json">.
 * Los flags JSON_HEX_* impiden cerrar la etiqueta o romper el HTML.
 */
function json_para_html(mixed $datos): string
{
    return json_encode(
        $datos,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
}
