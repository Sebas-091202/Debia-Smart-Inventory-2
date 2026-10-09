<?php
/**
 * Cambio de la propia contraseña (permiso "Cambio de contraseña").
 *
 * @var App\Dominio\Rol $rol
 */

use App\Config;
use App\Core\Url;
use App\Core\Vista;

$minimo = Config::CONTRASENA_LONGITUD_MINIMA;
$maximo = Config::CONTRASENA_LONGITUD_MAXIMA;

Vista::mostrar('layout/inicio', ['titulo' => 'Cambiar Contraseña', 'estilos' => ['formulario.css'], 'rol' => $rol]);
?>
<main class="main-content">
    <div class="form-container form-angosto">
        <h2>Cambiar Contraseña</h2>

        <form action="<?= e(Url::controlador('cambiar_contrasena.php')) ?>" method="POST">
            <?= campo_csrf() ?>

            <label for="contrasena-actual">Contraseña actual</label>
            <input type="password" name="contrasena_actual" id="contrasena-actual" maxlength="<?= $maximo ?>"
                autocomplete="current-password" required>

            <label for="contrasena-nueva">Contraseña nueva</label>
            <input type="password" name="contrasena" id="contrasena-nueva" minlength="<?= $minimo ?>" maxlength="<?= $maximo ?>"
                placeholder="Mínimo <?= $minimo ?> caracteres" autocomplete="new-password" required>

            <label for="contrasena-confirmacion">Confirmar contraseña nueva</label>
            <input type="password" name="contrasena_confirmacion" id="contrasena-confirmacion" minlength="<?= $minimo ?>" maxlength="<?= $maximo ?>"
                autocomplete="new-password" required>

            <button type="submit" class="submit-btn">Actualizar Contraseña</button>
        </form>
    </div>
</main>
<?php Vista::mostrar('layout/fin'); ?>
