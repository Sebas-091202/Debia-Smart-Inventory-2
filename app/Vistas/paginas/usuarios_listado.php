<?php
/**
 * Gestión de usuarios (permiso "Crear nuevo usuario" o "Editar usuario").
 *
 * @var App\Dominio\Rol    $rol
 * @var App\Dominio\Cuenta $cuenta     Cuenta que está viendo la página.
 * @var App\Core\Paginador $paginador
 * @var array[]            $usuarios   Página actual.
 */

use App\Config;
use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Cuenta;
use App\Dominio\Permiso;

Vista::mostrar('layout/inicio', ['titulo' => 'Usuarios', 'estilos' => ['usuarios.css'], 'rol' => $rol]);
?>
<main class="container">
    <section class="card">
        <div class="encabezado-usuarios">
            <h2>Usuarios del Sistema</h2>
            <?php if ($cuenta->puede(Permiso::CrearUsuario)): ?>
                <a href="<?= e(Url::vista('agregar_usuario.php')) ?>" class="btn-nuevo">
                    <i class='bx bx-user-plus' aria-hidden="true"></i> Nuevo Usuario
                </a>
            <?php endif; ?>
        </div>
        <p class="nota-usuarios">
            Las cuentas solo se crean aquí. Un usuario desactivado no puede iniciar sesión
            y, si tenía una sesión abierta, se cierra en su siguiente acción. Solo la cuenta
            principal (<?= e(Config::CORREO_CUENTA_PRINCIPAL) ?>) asigna permisos y cambia nombres de usuario.
        </p>

        <div class="tabla-scroll">
            <table class="tabla-usuarios">
                <thead>
                    <tr>
                        <th scope="col">Usuario</th>
                        <th scope="col">Correo</th>
                        <th scope="col">Identificación</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Permisos</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php $fila = new Cuenta($usuario); ?>
                        <tr>
                            <td data-label="Usuario">
                                <div>
                                    <?= e($usuario['usuario']) ?>
                                    <?php if ($fila->id() === $cuenta->id()): ?><span class="tu-cuenta">(tú)</span><?php endif; ?>
                                    <span class="nombre-completo"><?= e($usuario['nombre']) ?></span>
                                </div>
                            </td>
                            <td data-label="Correo"><?= e(o_si_vacio($usuario['correo'], '—')) ?></td>
                            <td data-label="Identificación"><?= e(o_si_vacio($usuario['numero_identificacion'], '—')) ?></td>
                            <td data-label="Rol"><?= e($fila->rol()->etiqueta()) ?></td>
                            <td data-label="Permisos">
                                <div class="lista-permisos">
                                    <?php if ($fila->esPrincipal()): ?>
                                        <span class="permiso permiso-principal">Cuenta principal · todos</span>
                                    <?php else: ?>
                                        <?php foreach ($fila->permisos() as $permiso): ?>
                                            <span class="permiso"><?= e($permiso->etiqueta()) ?></span>
                                        <?php endforeach; ?>
                                        <?php if ($fila->permisos() === []): ?>
                                            <span class="sin-permisos">Ninguno</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="Estado">
                                <span class="badge <?= $usuario['activo'] ? 'estado-activo' : 'estado-baja' ?>">
                                    <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td data-label="Acción">
                                <?php if ($cuenta->puedeGestionar($fila)): ?>
                                    <a href="<?= e(Url::vista('editar_usuario.php', ['id' => $usuario['id']])) ?>" class="btn-editar">
                                        <i class='bx bx-edit-alt' aria-hidden="true"></i> Editar
                                    </a>
                                <?php else: ?>
                                    <span class="sin-permisos" title="No tienes permiso para modificar esta cuenta">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php Vista::mostrar('parciales/paginacion', ['paginador' => $paginador, 'consulta' => [], 'descripcion' => 'usuarios']); ?>
    </section>
</main>
<?php Vista::mostrar('layout/fin'); ?>
