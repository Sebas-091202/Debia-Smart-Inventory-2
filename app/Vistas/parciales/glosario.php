<?php
/**
 * Guía de Abreviaciones colapsable (tipos y marcas del Catálogo).
 * El estado abierto/cerrado se recuerda por página con $claveMemoria.
 *
 * @var string $claveMemoria
 */

use App\Dominio\Catalogo;

$grupos = [
    'Tipos de equipo' => Catalogo::TIPOS_EQUIPO,
    'Marcas'          => Catalogo::MARCAS,
];
?>
<section class="card glosario" id="glosario" data-colapsable="<?= e($claveMemoria) ?>">
    <button type="button" class="glosario-toggle" id="glosarioToggleBtn" data-colapsable-boton
        aria-expanded="false" aria-controls="glosario-content">
        <h3>Guía de Abreviaciones <span class="glosario-hint">(clic para ver)</span></h3>
        <i class='bx bx-chevron-down' aria-hidden="true"></i>
    </button>

    <div class="glosario-content" id="glosario-content">
        <div class="glosario-inner">
            <div class="glosario-body">
                <?php foreach ($grupos as $tituloGrupo => $abreviaciones): ?>
                    <div class="glosario-grupo">
                        <h4><?= e($tituloGrupo) ?></h4>
                        <div class="glosario-chips">
                            <?php foreach ($abreviaciones as $codigo => $nombre): ?>
                                <span class="glosario-chip"><b><?= e($codigo) ?></b> <?= e($nombre) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
