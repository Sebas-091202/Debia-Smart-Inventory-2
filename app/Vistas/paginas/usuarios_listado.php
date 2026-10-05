<?php
/**
 * Gestión de usuarios (solo administrador).
 *
 * @var App\Dominio\Rol $rol
 * @var array[]         $usuarios
 * @var int             $actualId  Id del administrador que está viendo la página.
 */

use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Rol;

Vista::mostrar('layout/inicio', ['titulo' => 'Usuarios', 'estilos' => ['usuarios.css'], 'rol' => $rol]);
?>
<main class="container">
    <section class="card">
        <div class="encabezado-usuarios">
            <h2>Usuarios del Sistema</h2>
            <a href="<?= e(Url::vista('agregar_usuario.php')) ?>" class="btn-nuevo">
                <i class='bx bx-user-plus' aria-hidden="true"></i> Nuevo Usuario
            </a>
        </div>
        <p class="nota-usuarios">
            Las cuentas solo se crean aquí. Un usuario desactivado no puede iniciar sesión
            y, si tenía una sesión abierta, se cierra en su siguiente acción.
        </p>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th scope="col">Usuario</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Identificación</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td data-label="Usuario">
                                <?= e($usuario['usuario']) ?>
                                <?php if ((int) $usuario['id'] === $actualId): ?><span class="tu-cuenta">(tú)</span><?php endif; ?>
                            </td>
                            <td data-label="Nombre"><?= e($usuario['nombre']) ?></td>
                            <td data-label="Correo"><?= e(o_si_vacio($usuario['correo'], '—')) ?></td>
                            <td data-label="Identificación"><?= e(o_si_vacio($usuario['numero_identificacion'], '—')) ?></td>
                            <td data-label="Rol"><?= Rol::from($usuario['rol'])->esAdmin() ? 'Administrador' : 'Usuario' ?></td>
                            <td data-label="Estado">
                                <span class="badge <?= $usuario['activo'] ? 'estado-activo' : 'estado-baja' ?>">
                                    <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td data-label="Acción">
                                <a href="<?= e(Url::vista('editar_usuario.php', ['id' => $usuario['id']])) ?>" class="btn-editar">
                                    <i class='bx bx-edit-alt' aria-hidden="true"></i> Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php Vista::mostrar('layout/fin'); ?>
