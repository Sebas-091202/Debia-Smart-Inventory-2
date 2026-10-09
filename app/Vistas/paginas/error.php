<?php
/**
 * Página de error autónoma: no depende de sesión, rol ni base de datos.
 *
 * @var int    $codigo
 * @var string $mensaje
 */

use App\Core\Url;
use App\Core\Vista;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= (int) $codigo ?> | Debia Smart Inventory</title>
    <?php Vista::mostrar('parciales/iconos'); ?>
    <link rel="stylesheet" href="<?= e(Url::recurso('css/componentes.css')) ?>">
</head>

<body class="pagina-error">
    <main class="pagina-error-tarjeta">
        <h1><?= (int) $codigo ?></h1>
        <p><?= e($mensaje) ?></p>
        <a href="<?= e(Url::vista('index_Login.php')) ?>">Volver al inicio</a>
    </main>
</body>

</html>
