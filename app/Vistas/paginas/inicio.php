<?php
/**
 * Pantalla de bienvenida con accesos a cada módulo.
 *
 * @var App\Dominio\Rol $rol
 */

use App\Core\Url;
use App\Core\Vista;

$titulo = $rol->esAdmin() ? 'Panel Administrador' : 'Panel Usuario';

Vista::mostrar('layout/inicio', ['titulo' => $titulo, 'estilos' => ['inicio.css'], 'rol' => null]);
?>
<div class="admin-container">
    <header>
        <img src="<?= e(Url::recurso('img/logo.png')) ?>" alt="Logo Debia">
    </header>
    <h1>Bienvenido <?= $rol->esAdmin() ? 'Administrador' : 'Usuario' ?></h1>
    <nav aria-label="Módulos">
        <ul>
            <?php foreach ($rol->menu() as $opcion): ?>
                <li>
                    <a href="<?= e(Url::vista($opcion['archivo'])) ?>">
                        <i class='bx <?= e($opcion['icono']) ?>' aria-hidden="true"></i> <?= e($opcion['texto']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <li>
                <form method="POST" action="<?= e(Url::controlador('logout.php')) ?>">
                    <?= campo_csrf() ?>
                    <button type="submit" class="logout"><i class='bx bx-log-out' aria-hidden="true"></i> Cerrar Sesión</button>
                </form>
            </li>
        </ul>
    </nav>
</div>
<?php Vista::mostrar('layout/fin'); ?>
