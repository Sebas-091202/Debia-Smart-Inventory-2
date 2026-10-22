<?php
/**
 * Historial de inicios de sesión (permiso "Ver inicios de sesión").
 *
 * @var App\Dominio\Rol                  $rol
 * @var App\Dominio\FiltrosIniciosSesion $filtros
 * @var App\Core\Paginador               $paginador
 * @var array[]                          $inicios
 * @var array<int, string>               $usuarios  id => usuario, para el filtro.
 */

use App\Core\Vista;
use App\Dominio\ResultadoInicioSesion;

Vista::mostrar('layout/inicio', ['titulo' => 'Inicios de Sesión', 'estilos' => ['registros.css'], 'rol' => $rol]);
?>
<main class="container">
    <section class="card">
        <h2>Inicios de Sesión</h2>
        <p class="nota-registros">
            Cada intento de entrar al sistema: quién, cuándo, desde qué IP y con qué resultado.
            Si alguien escribe un usuario que no existe, aparece como «Usuario inexistente».
        </p>

        <form method="GET" class="filtros">
            <select name="usuario" aria-label="Usuario">
                <option value="">Todos los usuarios</option>
                <?= opciones_select($usuarios, (string) $filtros->usuarioId) ?>
            </select>

            <select name="resultado" aria-label="Resultado">
                <option value="">Todos los resultados</option>
                <?= opciones_select(ResultadoInicioSesion::opciones(), $filtros->resultado) ?>
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
                        <th scope="col">Resultado</th>
                        <th scope="col">IP</th>
                        <th scope="col">Navegador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($inicios === []): ?>
                        <tr>
                            <td colspan="5" class="celda-vacia">No hay inicios de sesión con los filtros seleccionados.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($inicios as $inicio): ?>
                        <?php $resultado = ResultadoInicioSesion::from($inicio['resultado']); ?>
                        <tr>
                            <td data-label="Fecha y hora"><?= e($inicio['fecha']) ?></td>
                            <td data-label="Usuario">
                                <?php if ($inicio['usuario'] === null): ?>
                                    <span class="sin-dato">Usuario inexistente</span>
                                <?php else: ?>
                                    <div>
                                        <?= e($inicio['usuario']) ?>
                                        <span class="nombre-completo"><?= e($inicio['nombre']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Resultado">
                                <span class="badge <?= e($resultado->claseEstado()) ?>"><?= e($resultado->etiqueta()) ?></span>
                            </td>
                            <td data-label="IP"><?= e($inicio['ip']) ?></td>
                            <td data-label="Navegador" title="<?= e($inicio['navegador']) ?>"><?= e(navegador_legible($inicio['navegador'])) ?></td>
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
