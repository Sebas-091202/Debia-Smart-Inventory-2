<?php
/**
 * Mensaje de resultado de la última acción (si lo hay).
 *
 * @var array{tipo: string, mensaje: string}|null $mensaje
 */

if ($mensaje === null) {
    return;
}

$esError = $mensaje['tipo'] === 'error';
?>
<div class="alerta <?= $esError ? 'alerta-error' : 'alerta-exito' ?>" role="<?= $esError ? 'alert' : 'status' ?>" data-alerta>
    <i class='bx <?= $esError ? 'bx-error-circle' : 'bx-check-circle' ?>' aria-hidden="true"></i>
    <span><?= e($mensaje['mensaje']) ?></span>
    <button type="button" class="alerta-cerrar" data-alerta-cerrar aria-label="Cerrar mensaje">&times;</button>
</div>
