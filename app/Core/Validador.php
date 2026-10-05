<?php

declare(strict_types=1);

namespace App\Core;

use App\Dominio\ErrorDeNegocio;

/**
 * Validación declarativa de formularios.
 *
 *   $v = (new Validador())
 *       ->requerido($nombre, 'Nombre')
 *       ->longitudMaxima($nombre, 100, 'Nombre');
 *   if (!$v->esValido()) { ... $v->primerError() ... }
 */
final class Validador
{
    /** @var string[] */
    private array $errores = [];

    public function requerido(string $valor, string $etiqueta): self
    {
        if ($valor === '') {
            $this->errores[] = "El campo «{$etiqueta}» es obligatorio.";
        }

        return $this;
    }

    public function longitudMaxima(string $valor, int $maximo, string $etiqueta): self
    {
        if (mb_strlen($valor) > $maximo) {
            $this->errores[] = "El campo «{$etiqueta}» admite máximo {$maximo} caracteres.";
        }

        return $this;
    }

    public function longitudMinima(string $valor, int $minimo, string $etiqueta): self
    {
        if (mb_strlen($valor) < $minimo) {
            $this->errores[] = "El campo «{$etiqueta}» debe tener al menos {$minimo} caracteres.";
        }

        return $this;
    }

    /** Valor vacío permitido salvo que se marque como requerido aparte. */
    public function enLista(string $valor, array $permitidos, string $etiqueta): self
    {
        if ($valor !== '' && !in_array($valor, $permitidos, true)) {
            $this->errores[] = "El valor de «{$etiqueta}» no es válido.";
        }

        return $this;
    }

    public function patron(string $valor, string $expresion, string $mensaje): self
    {
        if ($valor !== '' && preg_match($expresion, $valor) !== 1) {
            $this->errores[] = $mensaje;
        }

        return $this;
    }

    public function correo(string $valor, string $etiqueta): self
    {
        if ($valor !== '' && filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
            $this->errores[] = "El campo «{$etiqueta}» no es un correo válido.";
        }

        return $this;
    }

    public function fecha(string $valor, string $etiqueta): self
    {
        if ($valor !== '' && Peticion::fechaValida($valor) === null) {
            $this->errores[] = "El campo «{$etiqueta}» no es una fecha válida.";
        }

        return $this;
    }

    public function verdadero(bool $condicion, string $mensaje): self
    {
        if (!$condicion) {
            $this->errores[] = $mensaje;
        }

        return $this;
    }

    public function esValido(): bool
    {
        return $this->errores === [];
    }

    public function primerError(): string
    {
        return $this->errores[0] ?? '';
    }

    /** @throws ErrorDeNegocio con el primer error encontrado. */
    public function exigirValido(): void
    {
        if (!$this->esValido()) {
            throw new ErrorDeNegocio($this->primerError());
        }
    }
}
