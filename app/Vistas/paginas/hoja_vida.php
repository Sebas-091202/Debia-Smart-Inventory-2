<?php
/**
 * Hoja de vida de equipos.
 *
 * @var App\Dominio\Rol           $rol
 * @var App\Dominio\FiltrosEquipo $filtros
 * @var string[]                  $identificadores
 * @var array|null                $equipo
 * @var bool                      $equipoNoEncontrado
 * @var array[]                   $historial
 * @var array[]                   $resultados
 * @var App\Core\Paginador|null   $paginador
 * @var array                     $entradaAnterior  Lo escrito antes de un error de validación.
 */

use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Catalogo;

$fichaTecnica = $equipo === null ? [] : [
    'Tipo'          => $equipo['tipo'],
    'Marca'         => $equipo['marca'],
    'Identificador' => $equipo['identificador'],
    'Ubicación'     => $equipo['ubicacion'],
    'Asignado a'    => $equipo['asignado_a'],
    'Serial'        => o_si_vacio($equipo['serial'], 'No registrado'),
    'Procesador'    => o_si_vacio($equipo['procesador'], 'No registrado'),
    'Memoria RAM'   => o_si_vacio($equipo['ram'], 'No registrada'),
    'Disco C:'      => o_si_vacio($equipo['disco'], 'No registrado'),
    'Disco D:'      => o_si_vacio($equipo['disco2'], 'No registrado'),
];
$anterior = static fn (string $campo): string => (string) ($entradaAnterior[$campo] ?? '');

Vista::mostrar('layout/inicio', [
    'titulo'  => 'Hoja de Vida Equipos',
    'estilos' => ['hoja_vida_equipos.css'],
    'scripts' => ['vendor/jspdf.umd.min.js', 'vendor/jspdf.plugin.autotable.min.js', 'hoja_vida.js'],
    'rol'     => $rol,
]);
?>
<main class="main-content">
    <div class="card">
        <h2>Consulta Hoja de Vida</h2>

        <form method="GET" action="<?= e(Url::vista($rol->vista('hoja_vida_equipos'))) ?>">
            <div class="filtros">
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

                <button class="btn btn-search" type="submit">Buscar</button>
            </div>
        </form>
    </div>

    <?php if ($equipoNoEncontrado): ?>
        <div class="card">
            <p class="mensaje-vacio">No se encontró el equipo solicitado.</p>
        </div>
    <?php endif; ?>

    <?php if ($paginador !== null): ?>
        <div class="card">
            <h2>Equipo(s) Encontrado(s)</h2>

            <?php if ($resultados === []): ?>
                <p class="mensaje-vacio">No se encontraron equipos con los filtros seleccionados.</p>
            <?php else: ?>
                <div class="tabla-scroll">
                    <table class="tabla-equipos">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Marca</th>
                                <th>ID</th>
                                <th>Ubicación</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultados as $resultado): ?>
                                <tr>
                                    <td data-label="Tipo"><?= e($resultado['tipo']) ?></td>
                                    <td data-label="Marca"><?= e($resultado['marca']) ?></td>
                                    <td data-label="ID"><?= e($resultado['identificador']) ?></td>
                                    <td data-label="Ubicación"><?= e($resultado['ubicacion']) ?></td>
                                    <td data-label="Acción">
                                        <a href="<?= e(Url::vista($rol->vista('hoja_vida_equipos'), ['id' => $resultado['id']])) ?>" class="btn-visualizar">
                                            Visualizar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php Vista::mostrar('parciales/paginacion', ['paginador' => $paginador, 'consulta' => $filtros->comoConsulta()]); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($equipo !== null): ?>
        <div class="card">
            <h2>Hoja de Vida Equipo <?= e($equipo['identificador']) ?></h2>
            <h3>Ficha Técnica del Equipo</h3>

            <div class="tabla-scroll">
                <table class="tabla-equipos tabla-ficha">
                    <tbody>
                        <?php foreach ($fichaTecnica as $campo => $valor): ?>
                            <tr>
                                <td data-label="Campo" class="celda-campo"><?= e($campo) ?></td>
                                <td data-label="Información"><?= e($valor) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td data-label="Campo" class="celda-campo">Estado Actual</td>
                            <td data-label="Información"><?= badge_estado($equipo['estado'], 'badgeEstado') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Matriz de Observaciones y Mantenimientos</h3>
            <div class="tabla-scroll">
                <table class="tabla-equipos">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Responsable</th>
                            <th>Tipo de Mantenimiento</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($historial === []): ?>
                            <tr>
                                <td colspan="6" class="celda-vacia">Este equipo aún no tiene mantenimientos registrados.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($historial as $mantenimiento): ?>
                            <tr>
                                <td data-label="Fecha"><?= e($mantenimiento['fecha']) ?></td>
                                <td data-label="Responsable"><?= e($mantenimiento['responsable']) ?></td>
                                <td data-label="Tipo Mantenimiento"><?= e($mantenimiento['tipo_mantenimiento']) ?></td>
                                <td data-label="Descripción"><?= e($mantenimiento['descripcion']) ?></td>
                                <td data-label="Estado"><?= badge_estado($mantenimiento['estado'], 'badgeEstado') ?></td>
                                <td data-label="Observaciones"><?= e($mantenimiento['observaciones']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Datos para el PDF: JSON inerte (no se ejecuta), leído por js/hoja_vida.js -->
            <script type="application/json" id="datos-hoja-vida"><?= json_para_html(['equipo' => $equipo, 'historial' => $historial]) ?></script>
            <button type="button" class="btn btn-pdf" id="btnDescargarPdf">Descargar Hoja de Vida PDF</button>
        </div>

        <?php if ($rol->esAdmin()): ?>
            <div class="card">
                <h3>Registrar Nuevo Mantenimiento</h3>
                <form action="<?= e(Url::controlador('procesar_mantenimiento.php')) ?>" method="POST">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="equipo_id" value="<?= (int) $equipo['id'] ?>">

                    <label for="mant-fecha">Fecha del mantenimiento</label>
                    <input type="date" id="mant-fecha" name="fecha" value="<?= e($anterior('fecha')) ?>" required>

                    <label for="mant-responsable">Responsable</label>
                    <input type="text" id="mant-responsable" name="responsable" maxlength="100" value="<?= e($anterior('responsable')) ?>" required>

                    <label for="mant-tipo">Tipo de Mantenimiento</label>
                    <select name="tipo_mantenimiento" id="mant-tipo" required>
                        <option value="">Seleccione</option>
                        <?= opciones_select(Catalogo::TIPOS_MANTENIMIENTO, $anterior('tipo_mantenimiento')) ?>
                    </select>

                    <label for="mant-descripcion">Descripción del mantenimiento</label>
                    <textarea id="mant-descripcion" name="descripcion" maxlength="2000" required placeholder="Detalle del mantenimiento realizado"><?= e($anterior('descripcion')) ?></textarea>

                    <label for="mant-estado">Estado del mantenimiento</label>
                    <select name="estado" id="mant-estado" required>
                        <option value="">Seleccione</option>
                        <?= opciones_select(Catalogo::ESTADOS_MANTENIMIENTO, $anterior('estado')) ?>
                    </select>

                    <label for="mant-observaciones">Observaciones</label>
                    <textarea id="mant-observaciones" name="observaciones" maxlength="2000" placeholder="Hallazgos, recomendaciones, novedades..."><?= e($anterior('observaciones')) ?></textarea>

                    <button type="submit" class="btn btn-save">Guardar mantenimiento</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php Vista::mostrar('layout/fin'); ?>
