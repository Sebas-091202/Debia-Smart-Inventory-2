<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cálculo de paginación: página actual ajustada al rango válido,
 * desplazamiento SQL y qué números mostrar en escritorio y en móvil.
 */
final class Paginador
{
    /** Números de página visibles por bloque en escritorio. */
    private const PAGINAS_POR_BLOQUE = 10;

    /** Números de página visibles en móvil (alrededor de la actual). */
    private const PAGINAS_EN_MOVIL = 5;

    public readonly int $pagina;
    public readonly int $totalPaginas;

    public function __construct(
        public readonly int $totalRegistros,
        public readonly int $porPagina,
        ?int $paginaSolicitada,
    ) {
        $this->totalPaginas = (int) ceil($totalRegistros / $porPagina);
        $this->pagina = max(1, min($paginaSolicitada ?? 1, max(1, $this->totalPaginas)));
    }

    public function desplazamiento(): int
    {
        return ($this->pagina - 1) * $this->porPagina;
    }

    public function primeraDelBloque(): int
    {
        $bloque = intdiv($this->pagina - 1, self::PAGINAS_POR_BLOQUE);

        return $bloque * self::PAGINAS_POR_BLOQUE + 1;
    }

    public function ultimaDelBloque(): int
    {
        return min($this->primeraDelBloque() + self::PAGINAS_POR_BLOQUE - 1, $this->totalPaginas);
    }

    /** true si el número debe ocultarse en pantallas pequeñas. */
    public function ocultaEnMovil(int $numero): bool
    {
        $mitad = intdiv(self::PAGINAS_EN_MOVIL, 2);
        $inicio = max($this->primeraDelBloque(), $this->pagina - $mitad);
        $fin = min($this->ultimaDelBloque(), $inicio + self::PAGINAS_EN_MOVIL - 1);
        $inicio = max($this->primeraDelBloque(), $fin - self::PAGINAS_EN_MOVIL + 1);

        return $numero < $inicio || $numero > $fin;
    }

    public function tieneAnterior(): bool
    {
        return $this->pagina > 1;
    }

    public function tieneSiguiente(): bool
    {
        return $this->pagina < $this->totalPaginas;
    }
}
