<?php
/**
 * Botón hamburguesa y menú lateral según el rol.
 *
 * @var App\Dominio\Rol $rol
 */

use App\Core\Auth;
use App\Core\Url;

$paginaActual = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$opciones = [
    ['archivo' => $rol->paginaInicio(), 'icono' => 'bx-home', 'texto' => 'Inicio'],
    ...Auth::cuenta()->menu(),
];
?>
<button class="toggle-btn" id="btnToggleSidebar" type="button" aria-label="Abrir o cerrar el menú" aria-controls="sidebar">
    <i class='bx bx-menu' aria-hidden="true"></i>
</button>

<nav class="sidebar" id="sidebar" aria-label="Menú principal">
    <div class="sidebar-header">
        <h4 class="logo-title">
            <span class="logo-full">Debia Smart Inventory</span>
            <span class="logo-short">DSI</span>
        </h4>
    </div>

    <?php foreach ($opciones as $opcion): ?>
        <a href="<?= e(Url::vista($opcion['archivo'])) ?>" class="sidebar-item" <?= $opcion['archivo'] === $paginaActual ? 'aria-current="page"' : '' ?>>
            <i class='bx <?= e($opcion['icono']) ?>' aria-hidden="true"></i> <span><?= e($opcion['texto']) ?></span>
        </a>
    <?php endforeach; ?>

    <form method="POST" action="<?= e(Url::controlador('logout.php')) ?>" class="sidebar-salir">
        <?= campo_csrf() ?>
        <button type="submit" class="sidebar-item"><i class='bx bx-log-out' aria-hidden="true"></i> <span>Cerrar Sesión</span></button>
    </form>
</nav>
