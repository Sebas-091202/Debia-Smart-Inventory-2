<?php
/**
 * Bitácora de cambios (permiso "Ver bitácora de cambios").
 *
 * @var App\Dominio\Rol             $rol
 * @var App\Dominio\FiltrosBitacora $filtros
 * @var App\Core\Paginador          $paginador
 * @var array[]                     $registros
 * @var string[]                    $acciones  Acciones registradas, para el filtro.
 * @var array<int, string>          $usuarios  id => usuario, para el filtro.
 */

use App\Core\Vista;

Vista::mostrar('layout/inicio', ['titulo' => 'Bitácora de Cambios', 'estilos' => ['registros.css'], 'rol' => $rol]);
?>
<main class="container">
    <section class="card">
        <h2>Bitácora de Cambios</h2>
        <p class="nota-registros">
            Quién creó, editó o eliminó información del sistema, con la fecha y la hora, incluidos los
            administradores. En la edición de equipos se ve cada campo que cambió, con su valor anterior y el nuevo.
        </p>

        <form method="GET" class="filtros">
            <select name="usuario" aria-label="Usuario">
                <option value="">Todos los usuarios</option>
                <?= opciones_select($usuarios, (string) $filtros->usuarioId) ?>
            </select>

            <select name="accion" aria-label="Acción">
                <option value="">Todas las acciones</option>
                <?= opciones_select($acciones, $filtros->accion) ?>
            </select>

            <label class="campo-fecha">
                <span>Desde</span>
                <input type="date" name="fecha_desde" value="<?= e($filtros->fechaDesde) ?>">
            </label>

            <label class="campo-fecha">
                <span>Hasta</span>
                <input type="date" name="fecha_hasta" value="<?= e($filtros->fechaHasta) ?>">
            </label>

            <button class="btn-search" type="submit">Buscar</button>
        </form>
    </section>

    <section class="card">
        <div class="tabla-scroll">
            <table class="tabla-registros">
                <thead>
                    <tr>
                        <th scope="col">Fecha y hora</th>
                        <th scope="col">Usuario</th>
                        <th scope="col">Acción</th>
                        <th scope="col">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($registros === []): ?>
                        <tr>
                            <td colspan="4" class="celda-vacia">No hay registros con los filtros seleccionados.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($registros as $registro): ?>
                        <tr>
                            <td data-label="Fecha y hora"><?= e($registro['fecha']) ?></td>
                            <td data-label="Usuario">
                                <?php if ($registro['usuario'] !== null): ?>
                                    <div>
                                        <?= e($registro['usuario']) ?>
                                        <span class="nombre-completo"><?= e($registro['nombre']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="sin-dato">Consola del servidor</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Acción"><span class="accion"><?= e($registro['accion']) ?></span></td>
                            <td data-label="Detalle" class="celda-detalle"><?= e(o_si_vacio($registro['detalle'], '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php Vista::mostrar('parciales/paginacion', [
            'paginador'   => $paginador,
            'consulta'    => $filtros->comoConsulta(),
            'descripcion' => 'registros',
        ]); ?>
    </section>
</main>
<?php Vista::mostrar('layout/fin'); ?>
