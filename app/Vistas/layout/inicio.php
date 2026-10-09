<?php
/**
 * Apertura del documento: <head> común, mensajes y menú lateral.
 *
 * @var string                $titulo
 * @var string[]              $estilos  Hojas de estilo propias de la página (en css/).
 * @var string[]|null         $scripts  Scripts propios de la página (en js/).
 * @var App\Dominio\Rol|null  $rol      Si se indica, se muestra el menú lateral.
 * @var array|null            $mensaje  Mensaje flash ya extraído (opcional).
 */

use App\Core\Flash;
use App\Core\Url;
use App\Core\Vista;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | Debia Smart Inventory</title>
    <?php Vista::mostrar('parciales/iconos'); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
        integrity="sha384-42kyIPf7HDYLkGffmxDhSx/3Z/53wGBs3nD6wEFxsbeDc7rMO6mkYbkAcpRsnMU2" crossorigin="anonymous">
    <?php if ($rol !== null): ?>
        <link rel="stylesheet" href="<?= e(Url::recurso('css/sidebar.css')) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(Url::recurso('css/componentes.css')) ?>">
    <?php foreach ($estilos as $estilo): ?>
        <link rel="stylesheet" href="<?= e(Url::recurso('css/' . $estilo)) ?>">
    <?php endforeach; ?>
    <script src="<?= e(Url::recurso('js/app.js')) ?>" defer></script>
    <?php foreach ($scripts ?? [] as $script): ?>
        <script src="<?= e(Url::recurso('js/' . $script)) ?>" defer></script>
    <?php endforeach; ?>
</head>

<body>
    <?php Vista::mostrar('parciales/mensajes', ['mensaje' => $mensaje ?? Flash::extraer()]); ?>
    <?php if ($rol !== null): ?>
        <?php Vista::mostrar('parciales/menu_lateral', ['rol' => $rol]); ?>
    <?php endif; ?>
