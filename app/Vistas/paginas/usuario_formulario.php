<?php
/**
 * Alta y edición de usuarios.
 *
 * Los campos se adaptan a quien edita (ver App\Dominio\Cuenta): solo la
 * cuenta principal cambia nombres de usuario y asigna permisos. El
 * servidor vuelve a aplicar estas reglas en UsuarioServicio.
 *
 * @var App\Dominio\Rol    $rol
 * @var App\Dominio\Cuenta $cuenta   Cuenta que está editando.
 * @var array|null         $usuario  Usuario guardado (edición) o null (alta).
 * @var array              $valores  Entrada anterior o datos del usuario.
 */

use App\Config;
use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Cuenta;
use App\Dominio\Permiso;
use App\Dominio\Rol;

$esEdicion = $usuario !== null;
$objetivo = $esEdicion ? new Cuenta($usuario) : null;
$esPropio = $objetivo?->id() === $cuenta->id();
$objetivoEsPrincipal = $objetivo?->esPrincipal() ?? false;
$cambiaUsuario = !$esEdicion || $cuenta->esPrincipal();
$asignaPermisos = $cuenta->esPrincipal() && !$objetivoEsPrincipal;

$titulo = $esEdicion ? 'Editar Usuario' : 'Nuevo Usuario';
$valor = static fn (string $campo): string => (string) ($valores[$campo] ?? '');
$roles = [];
foreach ($cuenta->rolesAsignables() as $asignable) {
    $roles[$asignable->value] = $asignable->esAdmin() ? 'Administrador' : 'Usuario (solo consulta)';
}
$activo = !$esEdicion || in_array($valores['activo'] ?? false, [true, '1'], true);

// Alta sin errores previos: se proponen los permisos predeterminados.
$permisosMarcados = array_key_exists('permisos', $valores) || $esEdicion || $valores !== []
    ? Cuenta::valoresDePermisos($valores['permisos'] ?? [])
    : Permiso::valores(Permiso::PREDETERMINADOS);

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
            <?php if ($cambiaUsuario): ?>
                <input type="text" name="usuario" id="usuario-usuario" minlength="3" maxlength="50"
                    pattern="[A-Za-z0-9._\-]+" title="Letras, números, punto, guion y guion bajo"
                    autocomplete="off" autocapitalize="none" spellcheck="false" value="<?= e($valor('usuario')) ?>" required>
                <p class="ayuda-campo">
                    Distingue mayúsculas: el usuario debe escribirlo exactamente igual al ingresar.
                    <?php if ($esEdicion): ?>Si lo cambias, comunícale el nuevo nombre de usuario.<?php endif; ?>
                </p>
            <?php else: ?>
                <input type="text" id="usuario-usuario" value="<?= e($usuario['usuario']) ?>" readonly>
                <p class="ayuda-campo">Solo la cuenta principal puede cambiar el nombre de usuario.</p>
            <?php endif; ?>

            <label for="usuario-nombre">Nombre completo</label>
            <input type="text" name="nombre" id="usuario-nombre" maxlength="100"
                autocomplete="off" value="<?= e($valor('nombre')) ?>" required>

            <label for="usuario-correo">Correo electrónico</label>
            <?php if ($objetivoEsPrincipal): ?>
                <input type="email" name="correo" id="usuario-correo" value="<?= e($usuario['correo']) ?>" readonly>
                <p class="ayuda-campo">Este correo identifica a la cuenta principal y no se puede cambiar.</p>
            <?php else: ?>
                <input type="email" name="correo" id="usuario-correo" maxlength="100"
                    autocomplete="off" value="<?= e($valor('correo')) ?>" required>
            <?php endif; ?>

            <label for="usuario-identificacion">Identificación</label>
            <input type="text" inputmode="numeric" name="numero_identificacion" id="usuario-identificacion"
                pattern="\d{5,20}" title="Entre 5 y 20 dígitos" value="<?= e($valor('numero_identificacion')) ?>" required>

            <label for="usuario-rol">Rol</label>
            <?php if ($esPropio): ?>
                <input type="hidden" name="rol" value="<?= e($usuario['rol']) ?>">
                <input type="text" id="usuario-rol" value="<?= e(Rol::from($usuario['rol'])->etiqueta()) ?>" readonly>
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

            <fieldset class="grupo-permisos">
                <legend>Permisos en el sistema</legend>
                <?php if ($objetivoEsPrincipal): ?>
                    <p class="ayuda-campo">La cuenta principal tiene todos los permisos.</p>
                <?php else: ?>
                    <?php if (!$asignaPermisos): ?>
                        <p class="ayuda-campo">
                            Solo la cuenta principal asigna permisos.
                            <?php if (!$esEdicion): ?>La cuenta nueva tendrá los permisos marcados.<?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php foreach (Permiso::cases() as $permiso): ?>
                        <label class="casilla casilla-permiso">
                            <input type="checkbox" name="permisos[]" value="<?= e($permiso->value) ?>"
                                <?= in_array($permiso->value, $permisosMarcados, true) ? 'checked' : '' ?>
                                <?= $asignaPermisos ? '' : 'disabled' ?>>
                            <span>
                                <strong><?= e($permiso->etiqueta()) ?></strong>
                                <small><?= e($permiso->descripcion()) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </fieldset>

            <?php if (!$esPropio): ?>
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
            <?php else: ?>
                <p class="ayuda-campo">Para cambiar tu propia contraseña usa la opción «Cambiar Contraseña» del menú.</p>
            <?php endif; ?>

            <button type="submit" class="submit-btn"><?= $esEdicion ? 'Guardar Cambios' : 'Crear Usuario' ?></button>
        </form>

        <div class="texto-centro">
            <a href="<?= e(Url::vista('usuarios.php')) ?>" class="volver">Volver a Usuarios</a>
        </div>
    </div>
</main>
<?php Vista::mostrar('layout/fin'); ?>
