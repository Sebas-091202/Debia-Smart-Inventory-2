<?php
/**
 * Formulario de alta y edición de equipos.
 *
 * @var App\Dominio\Rol $rol
 * @var array|null      $equipo   Equipo guardado (edición) o null (alta).
 * @var array           $valores  Valores a mostrar: entrada anterior o datos del equipo.
 */

use App\Core\Url;
use App\Core\Vista;
use App\Dominio\Catalogo;

$esEdicion = $equipo !== null;
$titulo = $esEdicion ? 'Editar Equipo' : 'Registrar Nuevo Equipo';
$valor = static fn (string $campo): string => (string) ($valores[$campo] ?? '');

/**
 * Al editar, un valor guardado que ya no está en el catálogo se ofrece
 * igualmente para no perderlo al guardar.
 */
$conValorActual = static function (array $catalogo, string $campo) use ($valor): array {
    $actual = $valor($campo);
    $existe = $actual === '' || in_array($actual, array_is_list($catalogo) ? $catalogo : array_keys($catalogo), true);

    if ($existe) {
        return $catalogo;
    }

    return array_is_list($catalogo) ? [...$catalogo, $actual] : $catalogo + [$actual => $actual];
};

$opcionesDisco = static function (array $agrupado, string $campo) use ($valor): string {
    $html = '';
    $actual = $valor($campo);
    $enCatalogo = in_array($actual, Catalogo::valores($agrupado), true);

    if ($actual !== '' && !$enCatalogo) {
        $html .= opciones_select([$actual], $actual);
    }

    foreach ($agrupado as $tecnologia => $opciones) {
        $html .= '<optgroup label="Tipo de Disco ' . e($tecnologia) . '">' . opciones_select($opciones, $actual) . '</optgroup>';
    }

    return $html;
};

Vista::mostrar('layout/inicio', ['titulo' => $titulo, 'estilos' => ['formulario.css'], 'rol' => $rol]);
?>
<main class="main-content">
    <div class="form-container">
        <h2><?= e($titulo) ?></h2>

        <form action="<?= e(Url::controlador($esEdicion ? 'actualizar_equipos.php' : 'procesar_agregar_equipos.php')) ?>" method="POST">
            <?= campo_csrf() ?>
            <?php if ($esEdicion): ?>
                <input type="hidden" name="id" value="<?= (int) $equipo['id'] ?>">
            <?php endif; ?>

            <label for="equipo-tipo">Tipo</label>
            <select name="tipo" id="equipo-tipo" required>
                <option value="">Seleccione Tipo</option>
                <?= opciones_select($conValorActual(Catalogo::TIPOS_EQUIPO, 'tipo'), $valor('tipo')) ?>
            </select>

            <label for="equipo-marca">Marca</label>
            <select name="marca" id="equipo-marca" required>
                <option value="">Seleccione Marca</option>
                <?= opciones_select($conValorActual(Catalogo::MARCAS, 'marca'), $valor('marca')) ?>
            </select>

            <label for="equipo-identificador">Identificador</label>
            <input type="text" name="identificador" id="equipo-identificador" maxlength="50"
                placeholder="Ej: TIPO-MARCA-001" value="<?= e($valor('identificador')) ?>" required>

            <label for="equipo-asignado">Asignado a</label>
            <input type="text" name="asignado_a" id="equipo-asignado" maxlength="100"
                placeholder="Usuario o área" value="<?= e($valor('asignado_a')) ?>" <?= $esEdicion ? '' : 'required' ?>>

            <label for="equipo-serial">Serial</label>
            <input type="text" name="serial" id="equipo-serial" maxlength="100"
                placeholder="Número de serie" value="<?= e($valor('serial')) ?>" <?= $esEdicion ? '' : 'required' ?>>

            <label for="equipo-procesador">Procesador</label>
            <input type="text" name="procesador" id="equipo-procesador" maxlength="100"
                placeholder="Ej: INTEL I7-10750H CPU" value="<?= e($valor('procesador')) ?>">

            <label for="equipo-ram">RAM</label>
            <select name="ram" id="equipo-ram">
                <option value="">Seleccione RAM</option>
                <?= opciones_select($conValorActual(Catalogo::RAM, 'ram'), $valor('ram')) ?>
            </select>

            <label for="equipo-disco">Disco C:</label>
            <select name="disco" id="equipo-disco">
                <option value="">Seleccione Disco C:</option>
                <?= $opcionesDisco(Catalogo::DISCOS_C, 'disco') ?>
            </select>

            <label for="equipo-disco2">Disco D:</label>
            <select name="disco2" id="equipo-disco2">
                <option value="">Seleccione Disco D:</option>
                <?= $opcionesDisco(Catalogo::DISCOS_D, 'disco2') ?>
            </select>

            <label for="equipo-estado">Estado</label>
            <select name="estado" id="equipo-estado" required>
                <option value="">Seleccione Estado</option>
                <?= opciones_select(Catalogo::ESTADOS_EQUIPO, $valor('estado')) ?>
            </select>

            <label for="equipo-ubicacion">Ubicación</label>
            <select name="ubicacion" id="equipo-ubicacion" required>
                <option value="">Seleccione Ubicación</option>
                <?= opciones_select($conValorActual(Catalogo::UBICACIONES, 'ubicacion'), $valor('ubicacion')) ?>
            </select>

            <label for="equipo-codigo">Código de Barras</label>
            <input type="text" name="codigo_barras" id="equipo-codigo" maxlength="100"
                placeholder="Escanear o escribir" value="<?= e($valor('codigo_barras')) ?>" required>

            <button type="submit" class="submit-btn"><?= $esEdicion ? 'Actualizar Equipo' : 'Guardar Equipo' ?></button>
        </form>

        <?php if ($esEdicion): ?>
            <div class="texto-centro">
                <a href="<?= e(Url::vista('ver_equipos.php')) ?>" class="volver">Volver al Inventario</a>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php Vista::mostrar('layout/fin'); ?>
