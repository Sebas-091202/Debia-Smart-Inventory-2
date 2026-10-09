<?php
/**
 * Inventario de equipos.
 *
 * @var App\Dominio\Rol           $rol
 * @var bool                      $puedeEditar  Permiso "Editar equipo".
 * @var App\Dominio\FiltrosEquipo $filtros
 * @var App\Core\Paginador        $paginador
 * @var array[]                   $equipos
 * @var string[]                  $identificadores
 */

use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Catalogo;

$esAdmin = $rol->esAdmin();
$conAcciones = $esAdmin || $puedeEditar;
$totalColumnas = $conAcciones ? 13 : 12;

Vista::mostrar('layout/inicio', ['titulo' => 'Inventario de Equipos', 'estilos' => ['ver_equipos.css'], 'rol' => $rol]);
?>
<main class="container">
    <div class="card">
        <h2>Inventario de Equipos</h2>

        <form method="GET" class="filtros">
            <select name="tipo" aria-label="Tipo">
                <option value="">Seleccione Tipo</option>
                <?= opciones_select(Catalogo::TIPOS_EQUIPO, $filtros->tipo) ?>
            </select>

            <select name="marca" aria-label="Marca">
                <option value="">Seleccione Marca</option>
                <?= opciones_select(Catalogo::MARCAS, $filtros->marca) ?>
            </select>

            <select name="ubicacion" aria-label="Ubicación">
                <option value="">Seleccione Ubicación</option>
                <?= opciones_select(Catalogo::UBICACIONES, $filtros->ubicacion) ?>
            </select>

            <select name="estado" aria-label="Estado">
                <option value="">Seleccione Estado</option>
                <?= opciones_select(Catalogo::ESTADOS_EQUIPO, $filtros->estado) ?>
            </select>

            <!-- Solo lista los identificadores que existen dentro de los demás filtros -->
            <select name="identificador" aria-label="Identificador">
                <option value="">Todos los identificadores</option>
                <?= opciones_select($identificadores, $filtros->identificador) ?>
            </select>

            <button class="btn-search" type="submit">Buscar</button>
        </form>
    </div>

    <?php Vista::mostrar('parciales/glosario', ['claveMemoria' => 'glosarioEquipos']); ?>

    <div class="card">
        <div class="tabla-scroll">
            <table class="tabla-equipos">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>ID</th>
                        <th>Asignado</th>
                        <th>Serial</th>
                        <th>Procesador</th>
                        <th>RAM</th>
                        <th>Disco C:</th>
                        <th>Disco D:</th>
                        <th>Estado</th>
                        <th>Ubicación</th>
                        <?php if ($conAcciones): ?>
                            <th>Acción</th>
                        <?php endif; ?>
                        <th>Hoja de Vida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($equipos === []): ?>
                        <tr>
                            <td colspan="<?= $totalColumnas ?>" class="celda-vacia">
                                No se encontraron equipos con los filtros seleccionados.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($equipos as $equipo): ?>
                        <tr>
                            <td data-label="Tipo"><?= e($equipo['tipo']) ?></td>
                            <td data-label="Marca"><?= e($equipo['marca']) ?></td>
                            <td data-label="ID"><?= e($equipo['identificador']) ?></td>
                            <td data-label="Asignado"><?= e($equipo['asignado_a']) ?></td>
                            <td data-label="Serial"><?= e($equipo['serial']) ?></td>
                            <td data-label="Procesador"><?= e($equipo['procesador']) ?></td>
                            <td data-label="RAM"><?= e($equipo['ram']) ?></td>
                            <td data-label="Disco C:"><?= e($equipo['disco']) ?></td>
                            <td data-label="Disco D:"><?= e($equipo['disco2']) ?></td>
                            <td data-label="Estado"><?= badge_estado($equipo['estado']) ?></td>
                            <td data-label="Ubicación"><?= e($equipo['ubicacion']) ?></td>
                            <?php if ($conAcciones): ?>
                                <td data-label="Acción">
                                    <div class="acciones-fila">
                                        <?php if ($puedeEditar): ?>
                                            <a href="<?= e(Url::vista('editar_equipos.php', ['id' => $equipo['id']])) ?>" class="btn-update">Editar</a>
                                        <?php endif; ?>
                                        <?php if ($esAdmin): ?>
                                            <form method="POST" action="<?= e(Url::controlador('eliminar_equipo.php')) ?>"
                                                data-confirmar="¿Eliminar el equipo <?= e($equipo['identificador']) ?>? También se borrará todo su historial de mantenimientos.">
                                                <?= campo_csrf() ?>
                                                <input type="hidden" name="id_equipo" value="<?= (int) $equipo['id'] ?>">
                                                <button type="submit" class="btn-delete">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td data-label="Hoja de Vida">
                                <a href="<?= e(Url::vista($rol->vista('hoja_vida_equipos'), ['id' => $equipo['id']])) ?>" class="btn-hoja-vida">
                                    <i class='bx bx-barcode' aria-hidden="true"></i> Ver Hoja de Vida
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php Vista::mostrar('parciales/paginacion', ['paginador' => $paginador, 'consulta' => $filtros->comoConsulta()]); ?>
    </div>
</main>
<?php Vista::mostrar('layout/fin'); ?>
