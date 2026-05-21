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

$preventivoSeleccionado = null;


/*========================================
SI VIENE SELECCIONADO DESDE PROGRAMAR
=========================================*/

if (isset($_GET['preventivo_id'])) {

    $id = intval($_GET['preventivo_id']);

    $stmt = $conn->prepare("
SELECT
p.*,
e.tipo,
e.marca,
e.identificador,
e.asignado_a

FROM preventivos_programados p
INNER JOIN equipos e
ON e.id=p.equipo_id

WHERE p.id=?
");

    $stmt->execute([$id]);

    $preventivoSeleccionado =
        $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =====================
PAGINACIÓN
===================== */

$porPagina = 6;
$pagina = $_GET['pagina'] ?? 1;

if ($pagina < 1) $pagina = 1;

$offset = ($pagina - 1) * $porPagina;

/* =====================
TOTAL REGISTROS
===================== */

$totalSQL = "
SELECT COUNT(*) 
FROM (
    SELECT MAX(id)
    FROM preventivos_programados
    GROUP BY equipo_id
) AS sub
";

$totalRegistros = $conn->query($totalSQL)->fetchColumn();

$totalPaginas = ceil($totalRegistros / $porPagina);

/* =====================
CONSULTA PAGINADA
===================== */

$preventivos = $conn->query("
SELECT
p.*,
e.tipo,
e.marca,
e.identificador,
e.asignado_a
FROM preventivos_programados p
INNER JOIN equipos e ON e.id = p.equipo_id

WHERE p.id IN (
    SELECT MAX(id)
    FROM preventivos_programados
    GROUP BY equipo_id
)

AND p.estado IN (
'Programado',
'Vencido',
'Reprogramado',
'Ejecutado'
)

ORDER BY p.fecha_programada ASC
LIMIT $porPagina OFFSET $offset
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <title>Reprogramar Preventivos</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/reprogramar_preventivos.css">

</head>

<body>
    <!-- Boton Hamburguesa -->
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
    <div class="main">

        <div class="card">
            <h2>Reprogramación de Mantenimiento Preventivo</h2>
        </div>



        <div class="card">

            <h3>Filtrar Equipo</h3>

            <div class="filtro-box">

                <div>
                    <label>Tipo</label>
                    <select id="filtroTipo">
                        <option value="">Todos</option>
                    </select>
                </div>

                <div>
                    <label>Marca</label>
                    <select id="filtroMarca">
                        <option value="">Todas</option>
                    </select>
                </div>

                <div>
                    <label>Identificador</label>
                    <select id="filtroIdentificador">
                        <option value="">Todos</option>
                    </select>
                </div>

                <div>
                    <button
                        id="btnBuscar"
                        type="button"
                        class="btn-search">
                        Buscar
                    </button>
                </div>

            </div>

        </div>




        <div class="card">

            <h3>Preventivos Disponibles</h3>

            <table id="tablaEquipos">

                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Frecuencia</th>
                        <th>Fecha Actual</th>
                        <th>Próximo Preventivo</th>
                        <th>Asignado</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($preventivos as $p): ?>

                        <tr>

                            <td>
                                <?= $p['tipo']; ?> -
                                <?= $p['marca']; ?> -
                                <?= $p['identificador']; ?>
                            </td>

                            <td>
                                <?= $p['tipo_frecuencia']; ?>
                            </td>

                            <td>
                                <?= $p['fecha_programada']; ?>
                            </td>

                            <td>
                                <?= $p['proxima_fecha_programada']; ?>
                            </td>
                            <td>
                                <?= $p['asignado_a']; ?>
                            </td>

                            <td>

                                <?php
                                $clase = 'programado';
                                if ($p['estado'] == 'Ejecutado') {
                                    $clase = 'ejecutado';
                                }

                                if ($p['estado'] == 'Programado') {
                                    $clase = 'programado';
                                }

                                if ($p['estado'] == 'Vencido') {
                                    $clase = 'vencido';
                                }

                                if ($p['estado'] == 'Reprogramado') {
                                    $clase = 'reprogramado';
                                }
                                ?>

                                <span class="estado <?= $clase ?>">
                                    <?= $p['estado']; ?>
                                </span>

                            </td>

                            <td>

                                <a href="reprogramar_preventivo.php?preventivo_id=<?= $p['id'] ?>">
                                    <button type="button" class="btn-action">
                                        Seleccionar
                                    </button>
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>
            <div class="paginacion">

                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?= $pagina - 1 ?>">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a class="<?= ($pagina == $i) ? 'activo-pagina' : '' ?>"
                    href="?pagina=<?= $i ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina < $totalPaginas): ?>
                    <a href="?pagina=<?= $pagina + 1 ?>">Siguiente</a>
                <?php endif; ?>

            </div>

        </div>



        <div class="card">

            <h3>Formulario de Reprogramación</h3>

            <div id="alertaEquipo" class="alerta">
                Debe seleccionar un equipo antes de reprogramar.
            </div>


            <form
                action="../controller/procesar_reprogramar_preventivo.php"
                method="POST"
                onsubmit="return validarEquipo()">

                <input
                    type="hidden"
                    name="preventivo_id"
                    id="preventivo_id"
                    value="<?= $preventivoSeleccionado['id'] ?? '' ?>">


                <p>

                    Equipo seleccionado:

                    <strong id="equipoSeleccionado">

                        <?php
                        if ($preventivoSeleccionado) {
                            echo
                            $preventivoSeleccionado['tipo']
                                . ' - ' .
                                $preventivoSeleccionado['marca']
                                . ' - ' .
                                $preventivoSeleccionado['identificador'];
                        } else {
                            echo "Ninguno";
                        }
                        ?>

                    </strong>

                </p>


                <label>Nueva Fecha</label>

                <input
                    type="date"
                    name="nueva_fecha"
                    required
                    value="<?= $preventivoSeleccionado['proxima_fecha_programada'] ?? '' ?>">


                <label>Motivo</label>

                <textarea
                    name="motivo"
                    required></textarea>


                <label>Responsable</label>

                <input
                    name="responsable"
                    required>


                <button
                    name="reprogramar"
                    class="btn">

                    Guardar Reprogramación

                </button>

            </form>

        </div>

    </div>



    <script>
        function toggleSidebar() {
            document.body.classList.toggle(
                'sidebar-collapsed'
            );
        }



        function validarEquipo() {

            let id =
                document.getElementById(
                    'preventivo_id'
                ).value;

            if (id === "") {

                document.getElementById(
                    'alertaEquipo'
                ).style.display = 'block';

                return false;
            }

            return true;

        }



        /*==========================
        FILTROS
        ==========================*/

        const filas =
            document.querySelectorAll(
                '#tablaEquipos tbody tr'
            );

        const filtroTipo =
            document.getElementById('filtroTipo');

        const filtroMarca =
            document.getElementById('filtroMarca');

        const filtroId =
            document.getElementById(
                'filtroIdentificador'
            );

        let tipos = new Set();

        filas.forEach(fila => {

            let partes =
                fila.cells[0].innerText.split('-');

            tipos.add(
                partes[0].trim()
            );

        });

        tipos.forEach(tipo => {

            let op = document.createElement('option');

            op.value = tipo;
            op.text = tipo;

            filtroTipo.appendChild(op);

        });



        filtroTipo.addEventListener(
            'change',
            function() {

                filtroMarca.innerHTML =
                    '<option value="">Todas</option>';

                let marcas = new Set();

                filas.forEach(fila => {

                    let partes =
                        fila.cells[0].innerText.split('-');

                    if (
                        this.value == '' ||
                        partes[0].trim() === this.value
                    ) {
                        marcas.add(
                            partes[1].trim()
                        );
                    }

                });

                marcas.forEach(m => {

                    let op = document.createElement('option');
                    op.value = m;
                    op.text = m;

                    filtroMarca.appendChild(op);

                });

            });



        filtroMarca.addEventListener(
            'change',
            function() {

                filtroId.innerHTML =
                    '<option value="">Todos</option>';

                let ids = new Set();

                filas.forEach(fila => {

                    let partes =
                        fila.cells[0].innerText.split('-');

                    if (
                        (!filtroTipo.value ||
                            partes[0].trim() === filtroTipo.value) &&
                        (!this.value ||
                            partes[1].trim() === this.value)
                    ) {
                        ids.add(
                            partes[2].trim()
                        );
                    }

                });

                ids.forEach(i => {

                    let op = document.createElement('option');

                    op.value = i;
                    op.text = i;

                    filtroId.appendChild(op);

                });

            });



        document.getElementById(
            'btnBuscar'
        ).addEventListener(
            'click',
            filtrarTabla
        );



        function filtrarTabla() {

            let tipo = filtroTipo.value;
            let marca = filtroMarca.value;
            let iden = filtroId.value;

            filas.forEach(fila => {

                let partes =
                    fila.cells[0].innerText.split('-');

                let mostrar = true;

                if (tipo && partes[0].trim() != tipo) {
                    mostrar = false;
                }

                if (marca && partes[1].trim() != marca) {
                    mostrar = false;
                }

                if (iden && partes[2].trim() != iden) {
                    mostrar = false;
                }

                fila.style.display =
                    mostrar ? '' : 'none';

            });

        }
    </script>

</body>

</html>