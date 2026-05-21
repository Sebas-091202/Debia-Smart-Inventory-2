<?php
require '../bd/conn.php';

session_start();

/* VALIDAR SESIÓN PRIMERO */
if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/index_Login.php");
    exit;
}

/* VALIDAR ROL DESPUÉS */
if ($_SESSION['rol'] !== 'ADMIN') {
    header("Location: ../views/index_Login.php");
    exit;
}

// Evitar cache para que no se pueda volver atrás después de cerrar sesión
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$tipo = $_GET['tipo'] ?? '';
$marca = $_GET['marca'] ?? '';
$ubicacion = $_GET['ubicacion'] ?? '';
$id_equipo = $_GET['id'] ?? '';

/* ==========================
FILTROS DE EQUIPOS
==========================*/
$sql = "SELECT * FROM equipos WHERE 1=1";
$params = [];

if ($tipo != '') {
    $sql .= " AND tipo=?";
    $params[] = $tipo;
}

if ($marca != '') {
    $sql .= " AND marca LIKE ?";
    $params[] = "%$marca%";
}

if ($ubicacion != '') {
    $sql .= " AND ubicacion LIKE ?";
    $params[] = "%$ubicacion%";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
DETALLE EQUIPO + HOJA VIDA POR ID Y SELECCION DESDE FILTROS
==========================*/
$equipo = null;
$historial = [];

if ($id_equipo) {

    $eq = $conn->prepare("SELECT * FROM equipos WHERE id=?");
    $eq->execute([$id_equipo]);
    $equipo = $eq->fetch(PDO::FETCH_ASSOC);

    $mant = $conn->prepare("SELECT *
FROM mantenimientos
WHERE equipo_id=?
ORDER BY fecha ASC");

    $mant->execute([$id_equipo]);
    $historial = $mant->fetchAll(PDO::FETCH_ASSOC);
}


if (!empty($_POST['nombre_repuesto'])) {

    foreach ($_POST['nombre_repuesto'] as $i => $nombre) {

        $tipo = $_POST['tipo_repuesto'][$i];
        $serial = $_POST['serial_repuesto'][$i];
        $capacidad = $_POST['capacidad_repuesto'][$i];
        $descripcion = $_POST['descripcion_repuesto'][$i];
        $valor = $_POST['valor_repuesto'][$i];
        $cantidad = $_POST['cantidad'][$i];

        if (!empty($nombre) && !empty($tipo) && !empty($cantidad)) {

            // BUSCAR SI YA EXISTE (MISMO NOMBRE + TIPO)
            $buscar = $conn->prepare("
                SELECT id, stock FROM repuestos 
                WHERE nombre = ? AND tipo = ? AND capacidad = ?
            ");
            $buscar->execute([$nombre, $tipo, $capacidad]);
            $existe = $buscar->fetch(PDO::FETCH_ASSOC);

            if ($existe) {

                // AUMENTAR STOCK
                $update = $conn->prepare("
                    UPDATE repuestos 
                    SET stock = stock + ? 
                    WHERE id = ?
                ");
                $update->execute([$cantidad, $existe['id']]);

                $repuesto_id = $existe['id'];
            } else {

                // CREAR NUEVO
                $insert = $conn->prepare("
                    INSERT INTO repuestos (nombre, serial, capacidad, tipo, descripcion, valor, stock, estado)
                    VALUES (?, ?, ?, ?, ?, ?, 'Disponible')
                ");
                $insert->execute([$nombre, $serial, $capacidad, $tipo, $descripcion, $valor, $cantidad]);

                $repuesto_id = $conn->lastInsertId();
            }

            // RELACIONAR CON MANTENIMIENTO
            $id_mantenimiento = $conn->lastInsertId();
            $rel = $conn->prepare("
                INSERT INTO mantenimiento_repuestos (mantenimiento_id, repuesto_id, cantidad)
                VALUES (?, ?, ?)
            ");
            $rel->execute([$id_mantenimiento, $repuesto_id, $cantidad]);
        }
    }
}
// FIN REGISTRO DE REPUESTOS
// ESTO PERMITE QUE AL REGISTRAR UN MANTENIMIENTO CON REPUESTOS, 
//  SE VAYA ACTUALIZANDO EL STOCK DE LOS REPUESTOS USADOS Y SE VAYA CREANDO NUEVOS REPUESTOS SI ES NECESARIO, 
//  ASÍ COMO LA RELACIÓN ENTRE MANTENIMIENTO Y REPUESTO PARA LLEVAR UN CONTROL DE QUÉ REPUESTOS SE USARON EN CADA MANTENIMIENTO.

/* ==========================
ESTO ES PARA CUANDO SE INGRESA DESDE EL QR, 
QUE MUESTRE DIRECTAMENTE LA HOJA DE VIDA DE ESE EQUIPO
DETALLE EQUIPO + HOJA VIDA POR CÓDIGO DE BARRAS
==========================*/

$id_equipo = $_GET['id'] ?? null;
$codigo = $_GET['codigo'] ?? null;

if ($codigo) {
    $stmt = $conn->prepare("SELECT * FROM equipos WHERE codigo_barras = ?");
    $stmt->execute([$codigo]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_equipo = $equipo['id'] ?? null;
}

if ($id_equipo) {

    $mant = $conn->prepare("
        SELECT *
        FROM mantenimientos
        WHERE equipo_id = ?
        ORDER BY fecha ASC
        ");

    $mant->execute([$id_equipo]);
    $historial = $mant->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Hoja de Vida Equipos</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/hoja_vida_equipos.css">
</head>

<body>
    <!-- Botón hamburguesa -->
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class='bx bx-menu'></i>
    </button>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4 class="logo-title">
                <span class="logo-full">Debia Smart Inventory</span>
                <span class="logo-short">DSI</span>
            </h4>
        </div>
        <a href="index_Admin.php"><i class='bx bx-home'></i> <span> Inicio</span></a>
        <a href="ver_equipos.php"><i class='bx bx-list-ul'></i> <span> Ver Equipos</span></a>
        <a href="ver_qr.php"><i class='bx bx-barcode'></i> <span> Ver QR</span></a>
        <a href="agregar_equipos.php"><i class='bx bx-plus-circle'> </i><span> Agregar Equipo</span></a>
        <a href="editar_equipos.php"><i class='bx bx-edit-alt'></i> <span> Editar Equipo</span></a>
        <a href="ver_correctivos.php"><i class='bx bx-check-square'></i> <span> Ver Correctivos</span></a>
        <a href="programar_preventivos.php"><i class='bx bx-calendar'></i> <span> Programación de Preventivos</span></a>
        <a href="reprogramar_preventivo.php"><i class='bx bx-refresh'></i> <span> Reprogramación de Preventivos</span></a>
        <a href="indicadores_mantenimiento.php"><i class='bx bx-bar-chart'></i> <span> Indicadores de Mantenimiento</span></a>
        <a href="repuestos.php"><i class='bx bx-cog'></i> <span> Gestión de Repuestos</span></a>
        <a href="hoja_vida_equipos.php"><i class='bx bx-file'></i> <span> Hoja de Vida General</span></a>
    </div>

    <div class="main-content">

        <div class="card">
            <h2>Consulta Hoja de Vida</h2>

            <form method="GET">
                <div class="filtros">

                    <select name="tipo">
                        <option value="">Tipo</option>
                        <option value="P">Portátil</option>
                        <option value="TU">Todo en Uno</option>
                        <option value="E">Escritorio</option>
                        <option value="I">Impresora</option>
                        <option value="M">Mouse</option>
                        <option value="T">Teclado</option>
                        <option value="C">Celulares</option>
                    </select>

                    <select name="marca" id="">
                        <option value="">Marca</option>
                        <option value="A">Asus</option>
                        <option value="HP">Hewlett-Packard</option>
                        <option value="L">Lenovo</option>
                        <option value="EP">EPSON</option>
                        <option value="G">Genius</option>
                        <option value="S">Samsung</option>
                        <option value="H">Huawei</option>
                        <option value="M">Motorola</option>
                        <option value="X">Xiaomi</option>
                    </select>

                    <!-- UBICACION -->
                    <select name="ubicacion">
                        <!-- SURA, Generales, Consultas, HDI(Preguntar si es un area) -->
                        <option value="">Ubicación</option>
                        <option value="AXA">AXA</option>
                        <option value="Sistemas">Sistemas</option>
                        <option value="Financiera">Financiera</option>
                        <option value="Talento Humano">Talento Humano</option>
                        <option value="Directores Operativos">Directores Operativos</option>
                    </select>

                    <button class="btn btn-search">Buscar</button>

                </div>
            </form>
        </div>

        <?php
        $hayFiltros = ($tipo != '' || $marca != '' || $ubicacion != '');
        ?>

        <?php if ($hayFiltros && !$id_equipo): ?>

            <div class="card">
                <h2>Equipo Encontrado</h2>

                <?php if (count($equipos) > 0): ?>

                    <table>
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

                            <?php foreach ($equipos as $e): ?>
                                <tr>
                                    <td><?= $e['tipo'] ?></td>
                                    <td><?= $e['marca'] ?></td>
                                    <td><?= $e['identificador'] ?></td>
                                    <td><?= $e['ubicacion'] ?></td>
                                    <td>
                                        <a href="hoja_vida_equipos.php?id=<?= $e['id'] ?>">
                                            <button type="button" style=" background:linear-gradient(135deg,#2563eb,#1d4ed8); color:white; border:none; padding:8px 16px; border-radius:8px; cursor:pointer;">
                                                Visualizar
                                            </button>
                                        </a>
                                    </td>

                                </tr>
                            <?php endforeach; ?>

                        </tbody>
                    </table>

                <?php else: ?>

                    <p style="text-align:center;">
                        No se encontraron equipos con esos filtros.
                    </p>

                <?php endif; ?>
            </div>

        <?php endif; ?>

        <?php if ($equipo): ?>

            <div class="card">
                <h2>Hoja de Vida Equipo <?= $equipo['tipo'] ?> - <?= $equipo['marca'] ?> - <?= $equipo['identificador'] ?></h2>
                <h3>Ficha Técnica del Equipo</h3>
                <table style="margin-bottom:25px;">
                    <tr>
                        <th>Campo</th>
                        <th>Información</th>
                    </tr>

                    <tr>
                        <td>Tipo</td>
                        <td><?= $equipo['tipo'] ?></td>
                    </tr>

                    <tr>
                        <td>Marca</td>
                        <td><?= $equipo['marca'] ?></td>
                    </tr>

                    <tr>
                        <td>Identificador</td>
                        <td><?= $equipo['identificador'] ?></td>
                    </tr>

                    <tr>
                        <td>Ubicación</td>
                        <td><?= $equipo['ubicacion'] ?></td>
                    </tr>

                    <tr>
                        <td>Asignado a</td>
                        <td><?= $equipo['asignado_a'] ?></td>
                    </tr>

                    <tr>
                        <td>Serial</td>
                        <td><?= $equipo['serial'] ?: 'No registrado' ?></td>
                    </tr>

                    <tr>
                        <td>Procesador</td>
                        <td><?= $equipo['procesador'] ?: 'No registrado' ?></td>
                    </tr>

                    <tr>
                        <td>Memoria RAM</td>
                        <td><?= $equipo['ram'] ?: 'No registrada' ?></td>
                    </tr>

                    <tr>
                        <td>Disco</td>
                        <td><?= $equipo['disco'] ?: 'No registrado' ?></td>
                    </tr>

                    <tr>
                        <td>Estado Actual</td>
                        <td>
                            <?php
                            $badgeEstado = 'activo';

                            if ($equipo['estado'] == 'En reparación') {
                                $badgeEstado = 'reparacion';
                            }

                            if ($equipo['estado'] == 'Dado de baja') {
                                $badgeEstado = 'baja';
                            }
                            ?>

                            <span class="badgeEstado <?= $badgeEstado ?>">
                                <?= $equipo['estado'] ?>
                            </span>

                        </td>
                    </tr>
                </table>

                <h3>Matriz de Observaciones y Mantenimientos</h3>
                <table id="tablaHV">
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
                        <?php foreach ($historial as $h): ?>
                            <tr>
                                <td><?= $h['fecha'] ?></td>
                                <td><?= $h['responsable'] ?></td>
                                <td><?= $h['tipo_mantenimiento'] ?></td>
                                <td><?= $h['descripcion'] ?></td>
                                <td>

                                    <?php
                                    $badgeEstado = 'activo';

                                    if ($h['estado'] == 'En reparación') {
                                        $badgeEstado = 'reparacion';
                                    }

                                    if ($h['estado'] == 'Dado de baja') {
                                        $badgeEstado = 'baja';
                                    }
                                    ?>

                                    <span class="badgeEstado <?= $badgeEstado ?>">
                                        <?= $h['estado'] ?>
                                    </span>

                                </td>
                                <td><?= $h['observaciones'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button onclick="descargarPDF()" class="btn btn-pdf">
                    Descargar Hoja de Vida PDF
                </button>
            </div>

            <div class="card">
                <h3>Registrar Nuevo Mantenimiento</h3>
                <form action="../controller/procesar_mantenimiento.php" method="POST">
                    <input
                        type="hidden"
                        name="equipo_id"
                        value="<?= $equipo['id']; ?>">

                    <label>Fecha del mantenimiento</label>
                    <input
                        type="date"
                        name="fecha"
                        required>

                    <label>Responsable</label>
                    <input
                        type="text"
                        name="responsable"
                        required>

                    <label>Tipo de Mantenimiento</label>
                    <select name="tipo_mantenimiento" id="tipo_mantenimiento" required>
                        <option value="">Seleccione</option>
                        <option value="Preventivo">Preventivo</option>
                        <option value="Correctivo">Correctivo</option>
                    </select>

                    <label>Descripción del mantenimiento</label>
                    <textarea name="descripcion" required placeholder="Detalle del mantenimiento realizado"></textarea>

                    <label>Estado del equipo posterior al mantenimiento</label>
                    <select name="estado" required>
                        <option value="">Seleccione</option>
                        <option value="Activo">Activo</option>
                        <option value="En reparación">En reparación</option>
                        <option value="Dado de baja">Dado de baja</option>
                    </select>

                    <label>Observaciones</label>
                    <textarea name="observaciones" placeholder="Hallazgos, recomendaciones, novedades..."></textarea>

                    <?php
                    // CONSULTAR REPUESTOS
                    $rep = $conn->prepare("SELECT id, nombre, stock FROM repuestos");
                    $rep->execute();
                    $repuestos = $rep->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <h4>Registrar / Usar Repuestos</h4>

                    <div id="contenedor-repuestos">

                        <div class="item-repuesto">

                            <label>Nombre</label>
                            <select name="nombre_repuesto[]" onchange="actualizarOpciones(this)">
                                <option value="">Seleccione</option>
                                <option value="Almacenamiento">Almacenamiento</option>
                                <option value="RAM">RAM</option>
                                <option value="Fuente">Fuente de alimentación</option>
                            </select>

                            <label>Capacidad</label>
                            <select name="capacidad_repuesto[]">
                                <option value="">Seleccione</option>
                            </select>

                            <label>Tipo</label>
                            <select name="tipo_repuesto[]">
                                <option value="">Seleccione</option>
                            </select>
                            <label>Serial</label>
                            <input type="text" name="serial_repuesto[]" placeholder="Serial (opcional)">
                            <label>Descripción</label>
                            <textarea name="descripcion_repuesto[]" placeholder="Descripción"></textarea>
                            <label>Valor</label>
                            <input type="number" name="valor_repuesto[]" placeholder="Valor" step="0.01" min="0">
                            <label>Cantidad</label>
                            <input type="number" name="cantidad[]" placeholder="Cantidad" min="1">

                            <button type="button" onclick="eliminarRepuesto(this)" class="btn-delete">
                                Eliminar
                            </button>

                            <hr>
                        </div>

                    </div>

                    <button type="button" onclick="agregarRepuesto()" class="btn btn-add">
                        + Agregar otro repuesto
                    </button>
                    <button
                        type="submit"
                        class="btn btn-save">
                        Guardar mantenimiento
                    </button>
                </form>
            </div>

        <?php endif; ?>

    </div>
    <script>
        function toggleSidebar() {

            if (window.innerWidth <= 768) {
                // MÓVIL
                document.body.classList.toggle('sidebar-open');
            } else {
                // ESCRITORIO
                document.body.classList.toggle('sidebar-collapsed');
            }
        }

        function descargarPDF() {

            const {
                jsPDF
            } = window.jspdf;
            let doc = new jsPDF();

            // DATOS DEL EQUIPO (YA RENDERIZADOS)
            let identificador = "<?= $equipo['identificador'] ?>";
            let tipo = "<?= $equipo['tipo'] ?>";
            let marca = "<?= $equipo['marca'] ?>";
            let ubicacion = "<?= $equipo['ubicacion'] ?>";
            let asignado = "<?= $equipo['asignado_a'] ?>";
            let serial = "<?= $equipo['serial'] ?>";
            let procesador = "<?= $equipo['procesador'] ?>";
            let ram = "<?= $equipo['ram'] ?>";
            let disco = "<?= $equipo['disco'] ?>";
            let estado = "<?= $equipo['estado'] ?>";

            // TÍTULO
            doc.setFontSize(16);
            doc.text("HOJA DE VIDA DEL EQUIPO", 50, 15);

            doc.setFontSize(10);

            // INFORMACIÓN ORGANIZADA
            let y = 30;

            doc.text(`Identificador: ${identificador}`, 10, y);
            y += 7;
            doc.text(`Tipo: ${tipo}`, 10, y);
            y += 7;
            doc.text(`Marca: ${marca}`, 10, y);
            y += 7;
            doc.text(`Ubicación: ${ubicacion}`, 10, y);
            y += 7;
            doc.text(`Asignado a: ${asignado}`, 10, y);
            y += 7;
            doc.text(`Serial: ${serial}`, 10, y);
            y += 7;
            doc.text(`Procesador: ${procesador}`, 10, y);
            y += 7;
            doc.text(`RAM: ${ram}`, 10, y);
            y += 7;
            doc.text(`Disco: ${disco}`, 10, y);
            y += 7;
            doc.text(`Estado: ${estado}`, 10, y);
            y += 10;

            // TABLA DE MANTENIMIENTO
            let filas = [];

            document.querySelectorAll("#tablaHV tbody tr").forEach(tr => {
                let row = [];
                tr.querySelectorAll("td").forEach(td => {
                    row.push(td.innerText.trim());
                });
                filas.push(row);
            });

            doc.autoTable({
                startY: y,
                head: [
                    ['Fecha', 'Responsable', 'Tipo', 'Descripción', 'Estado', 'Observaciones']
                ],
                body: filas
            });

            doc.save("Hoja_Vida_" +  $tipo + "_" + $marca + "_"  + identificador + ".pdf");
        }



        function agregarRepuesto() {
            let cont = document.getElementById('contenedor-repuestos');

            let nuevo = cont.children[0].cloneNode(true);

            // limpiar valores
            nuevo.querySelectorAll('input, textarea, select').forEach(e => e.value = '');

            cont.appendChild(nuevo);

            nuevo.querySelector('select[name="nombre_repuesto[]"]')
                .setAttribute('onchange', 'actualizarOpciones(this)');

        }

        function eliminarRepuesto(btn) {
            let cont = document.getElementById('contenedor-repuestos');

            // ✅ NO dejar eliminar el último
            if (cont.children.length > 1) {
                btn.parentElement.remove();
            } else {
                alert("Debe existir al menos un formulario de repuesto");

            }
        }

        function actualizarOpciones(selectNombre) {

            let contenedor = selectNombre.parentElement;

            let selectTipo = contenedor.querySelector('select[name="tipo_repuesto[]"]');
            let selectCap = contenedor.querySelector('select[name="capacidad_repuesto[]"]');

            let valor = selectNombre.value;

            // limpiar
            selectTipo.innerHTML = '<option value="">Seleccione</option>';
            selectCap.innerHTML = '<option value="">Seleccione</option>';

            if (valor === "Almacenamiento") {

                // TIPOS
                selectTipo.innerHTML += `<option value="SSD">SSD</option>`;
                selectTipo.innerHTML += `<option value="HDD">HDD</option>`;

                // CAPACIDAD
                selectCap.innerHTML += `<option value="256GB">256GB</option>`;
                selectCap.innerHTML += `<option value="512GB">512GB</option>`;
                selectCap.innerHTML += `<option value="1TB">1TB</option>`;
                selectCap.innerHTML += `<option value="2TB">2TB</option>`;

            }

            if (valor === "RAM") {

                // TIPOS
                selectTipo.innerHTML += `<option value="SO-DIMM DDR3">SO-DIMM DDR3</option>`;
                selectTipo.innerHTML += `<option value="SO-DIMM DDR4">SO-DIMM DDR4</option>`;
                selectTipo.innerHTML += `<option value="SO-DIMM DDR5">SO-DIMM DDR5</option>`;
                selectTipo.innerHTML += `<option value="DIMM DDR3">DIMM DDR3</option>`;
                selectTipo.innerHTML += `<option value="DIMM DDR4">DIMM DDR4</option>`;
                selectTipo.innerHTML += `<option value="DIMM DDR5">DIMM DDR5</option>`;

                // CAPACIDAD
                selectCap.innerHTML += `<option value="4GB">4GB</option>`;
                selectCap.innerHTML += `<option value="8GB">8GB</option>`;
                selectCap.innerHTML += `<option value="16GB">16GB</option>`;
                selectCap.innerHTML += `<option value="32GB">32GB</option>`;

            }

            if (valor === "Fuente") {

                // TIPOS
                selectTipo.innerHTML += `<option value="Cargador">Cargador</option>`;
                selectTipo.innerHTML += `<option value="Cable de poder">Cable de poder</option>`;

                // CAPACIDAD (no aplica)
                selectCap.innerHTML = `<option value="N/A">No aplica</option>`;
            }
        }
    </script>
</body>

</html>