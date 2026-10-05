<?php
/**
 * Enlaces de paginación conservando los filtros activos.
 *
 * @var App\Core\Paginador $paginador
 * @var array              $consulta     Filtros actuales (se repiten en cada enlace).
 * @var string|null        $descripcion  Ej.: "mantenimientos correctivos" para mostrar el total.
 */

use App\Core\Url;

if ($paginador->totalPaginas === 0) {
    return;
}

$enlace = static fn (int $pagina): string => Url::cadenaConsulta($consulta + ['pagina' => $pagina]);
?>
<nav class="paginacion" aria-label="Paginación">
    <?php if ($paginador->tieneAnterior()): ?>
        <a href="<?= e($enlace($paginador->pagina - 1)) ?>" class="pag-anterior">Anterior</a>
    <?php endif; ?>

    <?php for ($numero = $paginador->primeraDelBloque(); $numero <= $paginador->ultimaDelBloque(); $numero++): ?>
        <?php
        $clases = trim(
            ($numero === $paginador->pagina ? 'activo-pagina ' : '')
            . ($paginador->ocultaEnMovil($numero) ? 'd-mobile-none' : '')
        );
        ?>
        <a href="<?= e($enlace($numero)) ?>" class="<?= $clases ?>" <?= $numero === $paginador->pagina ? 'aria-current="page"' : '' ?>><?= $numero ?></a>
    <?php endfor; ?>

    <?php if ($paginador->tieneSiguiente()): ?>
        <a href="<?= e($enlace($paginador->pagina + 1)) ?>" class="pag-siguiente">Siguiente</a>
    <?php endif; ?>

    <div class="indicador-paginacion">
        Páginas <?= $paginador->primeraDelBloque() ?> - <?= $paginador->ultimaDelBloque() ?> de <?= $paginador->totalPaginas ?>
        <?php if (!empty($descripcion)): ?>
            (<?= $paginador->totalRegistros ?> <?= e($descripcion) ?>)
        <?php endif; ?>
    </div>
</nav>
