<?php
/**
 * Alta y edición de usuarios (solo administrador).
 *
 * @var App\Dominio\Rol $rol
 * @var array|null      $usuario    Usuario guardado (edición) o null (alta).
 * @var array           $valores    Entrada anterior o datos del usuario.
 * @var bool            $esPropio   true si el administrador edita su propia cuenta.
 */

use App\Config;
use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Rol;

$esEdicion = $usuario !== null;
$titulo = $esEdicion ? 'Editar Usuario' : 'Nuevo Usuario';
$valor = static fn (string $campo): string => (string) ($valores[$campo] ?? '');
$roles = [Rol::Usuario->value => 'Usuario (solo consulta)', Rol::Admin->value => 'Administrador'];
$activo = !$esEdicion || in_array($valores['activo'] ?? false, [true, '1'], true);
$minimo = Config::CONTRASENA_LONGITUD_MINIMA;
$maximo = Config::CONTRASENA_LONGITUD_MAXIMA;

Vista::mostrar('layout/inicio', ['titulo' => $titulo, 'estilos' => ['formulario.css'], 'rol' => $rol]);
?>
<main class="main-content">
    <div class="form-container">
        <h2><?= e($titulo) ?></h2>

        <form action="<?= e(Url::controlador($esEdicion ? 'actualizar_usuario.php' : 'crear_usuario.php')) ?>" method="POST">
            <?= campo_csrf() ?>
            <?php if ($esEdicion): ?>
                <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
            <?php endif; ?>

            <label for="usuario-usuario">Usuario</label>
            <?php if ($esEdicion): ?>
                <input type="text" id="usuario-usuario" value="<?= e($usuario['usuario']) ?>" readonly>
                <p class="ayuda-campo">El nombre de usuario no se puede cambiar.</p>
            <?php else: ?>
                <input type="text" name="usuario" id="usuario-usuario" minlength="3" maxlength="50"
                    pattern="[A-Za-z0-9._\-]+" title="Letras, números, punto, guion y guion bajo"
                    autocomplete="off" autocapitalize="none" spellcheck="false" value="<?= e($valor('usuario')) ?>" required>
                <p class="ayuda-campo">Distingue mayúsculas: el usuario debe escribirlo exactamente igual al ingresar.</p>
            <?php endif; ?>

            <label for="usuario-nombre">Nombre completo</label>
            <input type="text" name="nombre" id="usuario-nombre" maxlength="100"
                autocomplete="off" value="<?= e($valor('nombre')) ?>" required>

            <label for="usuario-correo">Correo electrónico</label>
            <input type="email" name="correo" id="usuario-correo" maxlength="100"
                autocomplete="off" value="<?= e($valor('correo')) ?>" required>

            <label for="usuario-identificacion">Identificación</label>
            <input type="text" inputmode="numeric" name="numero_identificacion" id="usuario-identificacion"
                pattern="\d{5,20}" title="Entre 5 y 20 dígitos" value="<?= e($valor('numero_identificacion')) ?>" required>

            <label for="usuario-rol">Rol</label>
            <?php if ($esPropio): ?>
                <input type="hidden" name="rol" value="<?= e(Rol::Admin->value) ?>">
                <input type="text" id="usuario-rol" value="Administrador" readonly>
            <?php else: ?>
                <select name="rol" id="usuario-rol" required>
                    <?= opciones_select($roles, $valor('rol') === '' ? Rol::Usuario->value : $valor('rol')) ?>
                </select>
            <?php endif; ?>

            <?php if ($esEdicion): ?>
                <?php if ($esPropio): ?>
                    <input type="hidden" name="activo" value="1">
                    <p class="ayuda-campo">No puedes cambiar tu propio rol ni desactivar tu cuenta.</p>
                <?php else: ?>
                    <label class="casilla">
                        <input type="checkbox" name="activo" value="1" <?= $activo ? 'checked' : '' ?>>
                        Cuenta activa (puede iniciar sesión)
                    </label>
                <?php endif; ?>
            <?php endif; ?>

            <fieldset class="grupo-contrasena">
                <legend><?= $esEdicion ? 'Restablecer contraseña (opcional)' : 'Contraseña inicial' ?></legend>
                <?php if ($esEdicion): ?>
                    <p class="ayuda-campo">Déjala vacía para conservar la contraseña actual.</p>
                <?php endif; ?>

                <label for="usuario-contrasena">Contraseña</label>
                <input type="password" name="contrasena" id="usuario-contrasena" minlength="<?= $minimo ?>" maxlength="<?= $maximo ?>"
                    placeholder="Mínimo <?= $minimo ?> caracteres" autocomplete="new-password" <?= $esEdicion ? '' : 'required' ?>>

                <label for="usuario-confirmacion">Confirmar contraseña</label>
                <input type="password" name="contrasena_confirmacion" id="usuario-confirmacion" minlength="<?= $minimo ?>" maxlength="<?= $maximo ?>"
                    autocomplete="new-password" <?= $esEdicion ? '' : 'required' ?>>
            </fieldset>

            <button type="submit" class="submit-btn"><?= $esEdicion ? 'Guardar Cambios' : 'Crear Usuario' ?></button>
        </form>

        <div class="texto-centro">
            <a href="<?= e(Url::vista('usuarios.php')) ?>" class="volver">Volver a Usuarios</a>
        </div>
    </div>
</main>
<?php Vista::mostrar('layout/fin'); ?>
