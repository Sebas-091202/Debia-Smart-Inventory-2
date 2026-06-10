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

/*====================================
FILTROS
====================================*/

$tipo = $_GET['tipo'] ?? '';
$marca = $_GET['marca'] ?? '';
$identificador = $_GET['identificador'] ?? '';
$ubicacion = $_GET['ubicacion'] ?? '';

$where = " WHERE e.tipo IN ('P','TU','E') ";
$params = [];

if ($tipo != '') {
    $where .= " AND e.tipo=? ";
    $params[] = $tipo;
}

if ($marca != '') {
    $where .= " AND e.marca=? ";
    $params[] = $marca;
}

if ($identificador != '') {
    $where .= " AND e.identificador=? ";
    $params[] = $identificador;
}

if ($ubicacion != '') {
    $where .= " AND e.ubicacion=? ";
    $params[] = $ubicacion;
}


/*====================================
CATALOGO EQUIPOS + TOTALES
====================================*/

$sqlEquipos = "

SELECT
e.*,

/* Preventivos hechos */
(
SELECT COUNT(*)
FROM mantenimientos m
WHERE m.equipo_id=e.id
AND LOWER(m.tipo_mantenimiento)='preventivo'
) total_preventivos,

/* Correctivos hechos */
(
SELECT COUNT(*)
FROM mantenimientos m
WHERE m.equipo_id=e.id
AND LOWER(m.tipo_mantenimiento)='correctivo'
) total_correctivos,

/* ultima fecha mantenimiento */
(
SELECT MAX(m.fecha)
FROM mantenimientos m
WHERE m.equipo_id=e.id
) ultimo_mantenimiento,

/* tipo ultimo mantenimiento */
(
SELECT m2.tipo_mantenimiento
FROM mantenimientos m2
WHERE m2.equipo_id=e.id
ORDER BY m2.fecha DESC
LIMIT 1
) ultimo_tipo

FROM equipos e

$where

ORDER BY e.tipo,e.marca

";

/* =====================
PAGINACIÓN EQUIPOS
===================== */

$porPagina = 6;
$pagina = $_GET['pagina'] ?? 1;

if ($pagina < 1) $pagina = 1;

$offset = ($pagina - 1) * $porPagina;

/* TOTAL */
$sqlTotal = "SELECT COUNT(*) FROM equipos e $where";
$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalEquipos = $stmtTotal->fetchColumn();

$totalPaginas = ceil($totalEquipos / $porPagina);

/* CONSULTA PAGINADA */
$sqlEquipos .= " LIMIT $porPagina OFFSET $offset";

$stmt = $conn->prepare($sqlEquipos);
$stmt->execute($params);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);


$sqlGrafico = "

SELECT
DATE_FORMAT(fecha,'%Y-%m') mes,

SUM(
CASE
WHEN LOWER(tipo_mantenimiento)='preventivo'
THEN 1 ELSE 0
END
) preventivos,

SUM(
CASE
WHEN LOWER(tipo_mantenimiento)='correctivo'
THEN 1 ELSE 0
END
) correctivos

FROM mantenimientos m
JOIN equipos e
ON e.id=m.equipo_id

$where

GROUP BY DATE_FORMAT(fecha,'%Y-%m')
ORDER BY mes

";

$stmt = $conn->prepare($sqlGrafico);
$stmt->execute($params);

$datosGrafico = $stmt->fetchAll(PDO::FETCH_ASSOC);
/* AGRUPAR EN BLOQUES DE 3 MESES */

$bloques = array_chunk($datosGrafico, 3);


$labels = [];
$seriePreventivos = [];
$serieCorrectivos = [];

foreach ($datosGrafico as $fila) {

    $labels[] = $fila['mes'];

    $seriePreventivos[] =
        (int)$fila['preventivos'];

    $serieCorrectivos[] =
        (int)$fila['correctivos'];
}



/*====================================
KPIS
====================================*/

$totalPrevGeneral = array_sum(
    array_column($equipos, 'total_preventivos')
);

$totalCorrGeneral = array_sum(
    array_column($equipos, 'total_correctivos')
);


/*====================================
SELECTS FILTRO
====================================*/

$tipos = $conn->query("
SELECT DISTINCT tipo
FROM equipos
WHERE tipo IN ('P','TU','E')
ORDER BY tipo
")->fetchAll(PDO::FETCH_COLUMN);


$marcas = $conn->query("
SELECT DISTINCT marca
FROM equipos
WHERE tipo IN ('P','TU','E')
ORDER BY marca
")->fetchAll(PDO::FETCH_COLUMN);

$ids = $conn->query("
SELECT DISTINCT identificador
FROM equipos
WHERE tipo IN ('P','TU','E')
ORDER BY identificador
")->fetchAll(PDO::FETCH_COLUMN);

$ubicaciones = $conn->query("
SELECT DISTINCT ubicacion
FROM equipos
WHERE tipo IN ('P','TU','E')
ORDER BY ubicacion
")->fetchAll(PDO::FETCH_COLUMN);

?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Indicadores de Mantenimiento</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/indicadores_mantenimiento.css">
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
            <h2>Indicadores de Mantenimiento</h2>
        </div>


        <form method="GET" class="card filtros">

            <select name="tipo">
                <option value="">Todos tipos</option>

                <?php foreach ($tipos as $t): ?>

                    <option value="<?= $t ?>" <?= ($tipo == $t) ? 'selected' : '' ?>>
                        <?= $t ?>
                    </option>

                <?php endforeach; ?>

            </select>



            <select name="marca">
                <option value="">Todas marcas</option>

                <?php foreach ($marcas as $m): ?>

                    <option value="<?= $m ?>" <?= ($marca == $m) ? 'selected' : '' ?>>
                        <?= $m ?>
                    </option>

                <?php endforeach; ?>

            </select>



            <select name="identificador">
                <option value="">Todos identificadores</option>

                <?php foreach ($ids as $i): ?>

                    <option value="<?= $i ?>" <?= ($identificador == $i) ? 'selected' : '' ?>>
                        <?= $i ?>
                    </option>

                <?php endforeach; ?>

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


            <button type="submit">
                Filtrar
            </button>

        </form>




        <div class="card kpis">

            <div class="kpi">
                <h1><?= $totalPrevGeneral ?></h1>
                Preventivos
            </div>

            <div class="kpi">
                <h1><?= $totalCorrGeneral ?></h1>
                Correctivos
            </div>

            <div class="kpi">
                <h1><?= count($equipos) ?></h1>
                Equipos
            </div>

        </div>




        <div class="dashboard">

            <div class="card">

                <h3>Mantenimientos por Mes</h3>

            <?php foreach ($bloques as $index => $bloque): ?>

                <div style="margin-bottom:30px;">
                    <canvas id="grafico<?= $index ?>"></canvas>
                </div>

            <?php endforeach; ?>

            </div>

            <div class="card">

                <h3>Matriz de Equipos</h3>

                <table>

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

                    <?php foreach ($equipos as $e): ?>

                        <tr>

                            <td><?= $e['tipo'] ?></td>
                            <td><?= $e['marca'] ?></td>
                            <td><?= $e['identificador'] ?></td>
                            <td><?= $e['ubicacion'] ?></td>
                            <td><?= $e['asignado_a'] ?></td>

                            <td>
                                <span class="badge-blue">
                                    <?= $e['total_preventivos'] ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge-red">
                                    <?= $e['total_correctivos'] ?>
                                </span>
                            </td>

                            <td>
                                <?= $e['ultimo_mantenimiento'] ?: 'Sin registros' ?>
                            </td>

                            <td>

                                <?php
                                if (!$e['ultimo_tipo']) {
                                    echo '-';
                                } elseif (strtolower($e['ultimo_tipo']) == 'preventivo') {
                                    echo '<span class="badge-blue">Preventivo</span>';
                                } else {
                                    echo '<span class="badge-red">Correctivo</span>';
                                }
                                ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </table>
                <div class="paginacion">

                    <?php
                    $queryFiltros =
                        "&tipo=" . urlencode($tipo) .
                        "&marca=" . urlencode($marca) .
                        "&identificador=" . urlencode($identificador) .
                        "&ubicacion=" . urlencode($ubicacion);
                    ?>

                    <?php if ($pagina > 1): ?>
                        <a href="?pagina=<?= $pagina - 1 . $queryFiltros ?>">Anterior</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <a class="<?= ($pagina == $i) ? 'activo-pagina' : '' ?>"
                        href="?pagina=<?= $i . $queryFiltros ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagina < $totalPaginas): ?>
                        <a href="?pagina=<?= $pagina + 1 . $queryFiltros ?>">Siguiente</a>
                    <?php endif; ?>

                </div>

            </div>

        </div>

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

const bloques = <?= json_encode($bloques); ?>;

bloques.forEach((bloque, index) => {

    let labels = bloque.map(item => item.mes);
    let preventivos = bloque.map(item => item.preventivos);
    let correctivos = bloque.map(item => item.correctivos);

    const ctx = document.getElementById(
        'grafico' + index
    );

    new Chart(ctx, {

        type: 'bar',

        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Preventivos',
                    data: preventivos,
                    backgroundColor: '#3b67c7'
                },
                {
                    label: 'Correctivos',
                    data: correctivos,
                    backgroundColor: '#b42a2a'
                }
            ]
        },

        options: {
            responsive: true,
            plugins: {
                legend: { display: true }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }

    });

});

    </script>

</body>

</html>