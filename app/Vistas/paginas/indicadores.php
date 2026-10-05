<?php
/**
 * Tablero de indicadores de mantenimiento.
 *
 * @var App\Dominio\Rol                $rol
 * @var App\Dominio\FiltrosIndicadores $filtros
 * @var App\Core\Paginador             $paginador
 * @var array{preventivos: int, correctivos: int} $totales
 * @var array[]                        $equipos
 * @var array[]                        $graficos  Series mensuales en bloques de 3 meses.
 * @var array<string, string[]>        $opciones
 */

use App\Core\Vista;
use App\Dominio\Catalogo;

$tiposComputo = array_intersect_key(Catalogo::TIPOS_EQUIPO, array_flip(Catalogo::TIPOS_COMPUTO));
$kpis = [
    'Preventivos' => $totales['preventivos'],
    'Correctivos' => $totales['correctivos'],
    'Equipos'     => $paginador->totalRegistros,
];

Vista::mostrar('layout/inicio', [
    'titulo'  => 'Indicadores de Mantenimiento',
    'estilos' => ['indicadores_mantenimiento.css'],
    'scripts' => ['vendor/chart.umd.min.js', 'indicadores.js'],
    'rol'     => $rol,
]);
?>
<main class="main-content">
    <div class="container">
        <div class="card">
            <h2>Indicadores de Mantenimiento</h2>
        </div>

        <form method="GET" class="card filtros">
            <select name="tipo" aria-label="Tipo">
                <option value="">Todos los Tipos</option>
                <?= opciones_select($tiposComputo, $filtros->tipo) ?>
            </select>

            <select name="marca" aria-label="Marca">
                <option value="">Todas las Marcas</option>
                <?= opciones_select($opciones['marca'], $filtros->marca) ?>
            </select>

            <select name="identificador" aria-label="Identificador">
                <option value="">Todos los IDs</option>
                <?= opciones_select($opciones['identificador'], $filtros->identificador) ?>
            </select>

            <select name="ubicacion" aria-label="Ubicación">
                <option value="">Todas las Ubicaciones</option>
                <?= opciones_select($opciones['ubicacion'], $filtros->ubicacion) ?>
            </select>

            <button type="submit">Filtrar</button>
        </form>

        <div class="kpis">
            <?php foreach ($kpis as $etiqueta => $valor): ?>
                <div class="kpi">
                    <h1><?= (int) $valor ?></h1>
                    <span><?= e($etiqueta) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="dashboard">
            <div class="card">
                <h3>Mantenimientos por Mes</h3>
                <?php if ($graficos === []): ?>
                    <p class="mensaje-vacio">No hay datos para graficar.</p>
                <?php endif; ?>

                <?php foreach (array_keys($graficos) as $indice): ?>
                    <div class="grafico-bloque">
                        <canvas data-grafico="<?= (int) $indice ?>" aria-label="Gráfico de mantenimientos por mes" role="img"></canvas>
                    </div>
                <?php endforeach; ?>

                <!-- Series para Chart.js: JSON inerte leído por js/indicadores.js -->
                <script type="application/json" id="datos-graficos"><?= json_para_html($graficos) ?></script>
            </div>

            <div class="card">
                <h3>Matriz de Equipos</h3>

                <?php if ($equipos === []): ?>
                    <p class="mensaje-vacio">No se encontraron registros.</p>
                <?php else: ?>
                    <div class="tabla-scroll">
                        <table class="tabla-equipos">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Marca</th>
                                    <th>ID</th>
                                    <th>Ubicación</th>
                                    <th>Asignado</th>
                                    <th>Preventivos</th>
                                    <th>Correctivos</th>
                                    <th>Último Mant.</th>
                                    <th>Tipo Último</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipos as $equipo): ?>
                                    <tr>
                                        <td data-label="Tipo"><?= e($equipo['tipo']) ?></td>
                                        <td data-label="Marca"><?= e($equipo['marca']) ?></td>
                                        <td data-label="ID"><?= e($equipo['identificador']) ?></td>
                                        <td data-label="Ubicación"><?= e($equipo['ubicacion']) ?></td>
                                        <td data-label="Asignado"><?= e($equipo['asignado_a']) ?></td>
                                        <td data-label="Preventivos"><span class="badge-blue"><?= (int) $equipo['total_preventivos'] ?></span></td>
                                        <td data-label="Correctivos"><span class="badge-red"><?= (int) $equipo['total_correctivos'] ?></span></td>
                                        <td data-label="Último Mant."><?= e(o_si_vacio($equipo['ultimo_mantenimiento'], 'Sin registros')) ?></td>
                                        <td data-label="Tipo Último">
                                            <?php if ($equipo['ultimo_tipo'] === null): ?>
                                                -
                                            <?php else: ?>
                                                <span class="<?= $equipo['ultimo_tipo'] === 'Preventivo' ? 'badge-blue' : 'badge-red' ?>"><?= e($equipo['ultimo_tipo']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php Vista::mostrar('parciales/paginacion', ['paginador' => $paginador, 'consulta' => $filtros->comoConsulta()]); ?>
            </div>
        </div>
    </div>
</main>
<?php Vista::mostrar('layout/fin'); ?>
