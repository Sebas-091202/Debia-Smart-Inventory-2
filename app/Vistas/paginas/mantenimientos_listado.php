<?php
/**
 * Historial de mantenimientos correctivos o preventivos.
 *
 * @var App\Dominio\Rol                  $rol
 * @var string                           $tipoMantenimiento
 * @var array                            $presentacion  Textos y estilos según el tipo.
 * @var App\Dominio\FiltrosMantenimiento $filtros
 * @var App\Core\Paginador               $paginador
 * @var array[]                          $mantenimientos
 * @var string[]                         $identificadores
 */

use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Catalogo;

Vista::mostrar('layout/inicio', ['titulo' => $presentacion['titulo'], 'estilos' => [$presentacion['estilo']], 'rol' => $rol]);
?>
<?php foreach ($presentacion['contenedores'] as $claseContenedor): ?>
    <div class="<?= e($claseContenedor) ?>">
<?php endforeach; ?>

    <div class="card">
        <h2><?= e($presentacion['titulo']) ?></h2>
        <p class="subtitulo">
            Mantenimientos registrados como <strong><?= e($tipoMantenimiento) ?></strong> desde la Hoja de Vida de cada equipo.
        </p>

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

            <select name="identificador" aria-label="Identificador">
                <option value="">Todos los identificadores</option>
                <?= opciones_select($identificadores, $filtros->identificador) ?>
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
    </div>

    <?php Vista::mostrar('parciales/glosario', ['claveMemoria' => 'glosario' . $tipoMantenimiento]); ?>

    <div class="card">
        <p class="tabla-scroll-hint">
            <i class='bx bx-move-horizontal' aria-hidden="true"></i>
            Desliza horizontalmente para ver todas las columnas
        </p>
        <div class="tabla-scroll">
            <table class="<?= e($presentacion['claseTabla']) ?>">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Identificador</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Ubicación</th>
                        <th>Responsable</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th>Hoja de Vida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($mantenimientos === []): ?>
                        <tr>
                            <td colspan="10" class="celda-vacia">
                                No se encontraron mantenimientos <?= e($presentacion['plural']) ?> con los filtros seleccionados.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($mantenimientos as $mantenimiento): ?>
                        <tr>
                            <td data-label="Fecha"><?= e($mantenimiento['fecha']) ?></td>
                            <td data-label="Identificador"><?= e($mantenimiento['identificador']) ?></td>
                            <td data-label="Tipo"><?= e($mantenimiento['equipo_tipo']) ?></td>
                            <td data-label="Marca"><?= e($mantenimiento['equipo_marca']) ?></td>
                            <td data-label="Ubicación"><?= e($mantenimiento['ubicacion']) ?></td>
                            <td data-label="Responsable"><?= e($mantenimiento['responsable']) ?></td>
                            <td data-label="Descripción"><?= e($mantenimiento['descripcion']) ?></td>
                            <td data-label="Estado"><?= badge_estado($mantenimiento['estado']) ?></td>
                            <td data-label="Observaciones"><?= e(o_si_vacio($mantenimiento['observaciones'], '—')) ?></td>
                            <td data-label="Hoja de Vida">
                                <a href="<?= e(Url::vista($rol->vista('hoja_vida_equipos'), ['id' => $mantenimiento['equipo_id']])) ?>" class="btn-hoja-vida">
                                    <i class='bx bx-barcode' aria-hidden="true"></i> <?= e($presentacion['textoHojaVida']) ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php Vista::mostrar('parciales/paginacion', [
            'paginador'   => $paginador,
            'consulta'    => $filtros->comoConsulta(),
            'descripcion' => 'mantenimientos ' . $presentacion['plural'],
        ]); ?>
    </div>

<?= str_repeat('</div>', count($presentacion['contenedores'])) ?>
<?php Vista::mostrar('layout/fin'); ?>
