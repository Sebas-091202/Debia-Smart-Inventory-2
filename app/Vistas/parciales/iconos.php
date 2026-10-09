<?php
/**
 * Ícono de la pestaña del navegador y de acceso directo en móviles:
 * el logo recortado en círculo (public/img/favicon-*.png).
 */

use App\Core\Url;

?>
<link rel="icon" href="<?= e(Url::recurso('favicon.ico')) ?>" sizes="48x48">
<link rel="icon" type="image/png" href="<?= e(Url::recurso('img/favicon-32.png')) ?>" sizes="32x32">
<link rel="icon" type="image/png" href="<?= e(Url::recurso('img/favicon-192.png')) ?>" sizes="192x192">
<link rel="apple-touch-icon" href="<?= e(Url::recurso('img/apple-touch-icon.png')) ?>">
