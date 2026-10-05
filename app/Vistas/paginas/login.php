<?php
/**
 * Inicio de sesión. No hay registro público: las cuentas las crea el
 * administrador desde "Usuarios".
 *
 * @var array|null $mensaje          Mensaje flash del intento anterior.
 * @var array      $entradaAnterior  Lo escrito antes de un error (sin contraseña).
 */

use App\Config;
use App\Core\Url;
use App\Core\Vista;

Vista::mostrar('layout/inicio', [
    'titulo'  => 'Acceso',
    'estilos' => ['login.css'],
    'rol'     => null,
    'mensaje' => $mensaje,
]);
?>
<div class="container-form">
    <div class="information">
        <div class="info-childs">
            <h2>¡Bienvenido!</h2>
            <p>Ingresa con el usuario y la contraseña que te asignó el administrador.</p>
            <p class="info-nota">¿No tienes cuenta u olvidaste tu contraseña? Solicítalo al administrador del sistema.</p>
        </div>
    </div>
    <div class="form-information">
        <div class="form-information-childs">
            <img src="<?= e(Url::recurso('img/logo.png')) ?>" alt="Logo Debia" class="form-logo">
            <h2>Iniciar Sesión</h2>
            <form class="form" action="<?= e(Url::controlador('login.php')) ?>" method="POST">
                <?= campo_csrf() ?>
                <div class="input-box">
                    <i class='bx bx-user' aria-hidden="true"></i>
                    <input type="text" placeholder="Usuario" name="usuario" maxlength="50"
                        aria-label="Usuario" autocomplete="username" autocapitalize="none" spellcheck="false"
                        value="<?= e((string) ($entradaAnterior['usuario'] ?? '')) ?>" required>
                </div>
                <div class="input-box">
                    <i class='bx bx-lock-alt' aria-hidden="true"></i>
                    <input type="password" placeholder="Contraseña" name="contrasena" maxlength="<?= Config::CONTRASENA_LONGITUD_MAXIMA ?>"
                        aria-label="Contraseña" autocomplete="current-password" required>
                </div>
                <input type="submit" value="Iniciar Sesión">
            </form>
        </div>
    </div>
</div>
<?php Vista::mostrar('layout/fin'); ?>
