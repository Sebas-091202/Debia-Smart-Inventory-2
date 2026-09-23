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

// Evitar cache
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

// CORRECCIÓN CLAVE: Acrónimos correctos de la base de datos (PO, TO, ES)
$where = " WHERE e.tipo IN ('PO','TO','ES') ";
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
(
    SELECT COUNT(*) FROM mantenimientos m 
    WHERE m.equipo_id=e.id AND LOWER(m.tipo_mantenimiento)='preventivo'
) AS total_preventivos,
(
    SELECT COUNT(*) FROM mantenimientos m 
    WHERE m.equipo_id=e.id AND LOWER(m.tipo_mantenimiento)='correctivo'
) AS total_correctivos,
(
    SELECT MAX(m.fecha) FROM mantenimientos m 
    WHERE m.equipo_id=e.id
) AS ultimo_mantenimiento,
(
    SELECT m2.tipo_mantenimiento FROM mantenimientos m2 
    WHERE m2.equipo_id=e.id ORDER BY m2.fecha DESC LIMIT 1
) AS ultimo_tipo

FROM equipos e
$where
ORDER BY e.identificador ASC
";

/* =====================
PAGINACIÓN EQUIPOS
===================== */
$porPagina = 6;
$pagina = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?: 1;

if ($pagina < 1) $pagina = 1;

$offset = ($pagina - 1) * $porPagina;

$sqlTotal = "SELECT COUNT(*) FROM equipos e $where";
$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalEquipos = $stmtTotal->fetchColumn();

$totalPaginas = ceil($totalEquipos / $porPagina);

if ($totalPaginas > 0 && $pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $porPagina;
}

$sqlEquipos .= " LIMIT $porPagina OFFSET $offset";

$stmt = $conn->prepare($sqlEquipos);
$stmt->execute($params);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*====================================
GRÁFICOS
====================================*/
$sqlGrafico = "
SELECT
DATE_FORMAT(fecha,'%Y-%m') mes,
SUM(CASE WHEN LOWER(tipo_mantenimiento)='preventivo' THEN 1 ELSE 0 END) preventivos,
SUM(CASE WHEN LOWER(tipo_mantenimiento)='correctivo' THEN 1 ELSE 0 END) correctivos
FROM mantenimientos m
JOIN equipos e ON e.id=m.equipo_id
$where
GROUP BY DATE_FORMAT(fecha,'%Y-%m')
ORDER BY mes
";

$stmt = $conn->prepare($sqlGrafico);
$stmt->execute($params);
$datosGrafico = $stmt->fetchAll(PDO::FETCH_ASSOC);
$bloques = array_chunk($datosGrafico, 3);

/*====================================
KPIS GLOBALES
====================================*/
// Usamos consultas directas para los KPIs globales para que reflejen el total real y no solo el de la página actual
$sqlKPIs = "
SELECT 
    SUM(CASE WHEN LOWER(m.tipo_mantenimiento)='preventivo' THEN 1 ELSE 0 END) AS total_prev,
    SUM(CASE WHEN LOWER(m.tipo_mantenimiento)='correctivo' THEN 1 ELSE 0 END) AS total_corr
FROM mantenimientos m
JOIN equipos e ON e.id = m.equipo_id
$where
";
$stmtKPIs = $conn->prepare($sqlKPIs);
$stmtKPIs->execute($params);
$kpisGenerales = $stmtKPIs->fetch(PDO::FETCH_ASSOC);

$totalPrevGeneral = $kpisGenerales['total_prev'] ?? 0;
$totalCorrGeneral = $kpisGenerales['total_corr'] ?? 0;

/*====================================
SELECTS FILTRO
====================================*/
$tipos = $conn->query("SELECT DISTINCT tipo FROM equipos WHERE tipo IN ('PO','TO','ES') ORDER BY tipo")->fetchAll(PDO::FETCH_COLUMN);
$marcas = $conn->query("SELECT DISTINCT marca FROM equipos WHERE tipo IN ('PO','TO','ES') ORDER BY marca")->fetchAll(PDO::FETCH_COLUMN);
$ids = $conn->query("SELECT DISTINCT identificador FROM equipos WHERE tipo IN ('PO','TO','ES') ORDER BY identificador")->fetchAll(PDO::FETCH_COLUMN);
$ubicaciones = $conn->query("SELECT DISTINCT ubicacion FROM equipos WHERE tipo IN ('PO','TO','ES') ORDER BY ubicacion")->fetchAll(PDO::FETCH_COLUMN);

// URL de filtros para paginación
$queryFiltros = http_build_query([
    'tipo' => $tipo,
    'marca' => $marca,
    'identificador' => $identificador,
    'ubicacion' => $ubicacion
]);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Indicadores de Mantenimiento</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <a href="agregar_equipos.php"><i class='bx bx-plus-circle'> </i><span> Agregar Equipo</span></a>
        <a href="editar_equipos.php"><i class='bx bx-edit-alt'></i> <span> Editar Equipo</span></a>
        <a href="ver_correctivos.php"><i class='bx bx-check-square'></i> <span> Ver Correctivos</span></a>
        <a href="ver_preventivos.php"><i class='bx bx-calendar'></i> <span> Ver Preventivos</span></a>
        <a href="indicadores_mantenimiento.php"><i class='bx bx-bar-chart'></i> <span> Indicadores de Mantenimiento</span></a>
        <a href="hoja_vida_equipos.php"><i class='bx bx-file'></i> <span> Hoja de Vida General</span></a>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <div class="container">
            <div class="card">
                <h2>Indicadores de Mantenimiento</h2>
            </div>

            <!-- FILTROS -->
            <form method="GET" class="card filtros">
                <select name="tipo">
                    <option value="">Todos los Tipos</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= ($tipo == $t) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="marca">
                    <option value="">Todas las Marcas</option>
                    <?php foreach ($marcas as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>" <?= ($marca == $m) ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="identificador">
                    <option value="">Todos los IDs</option>
                    <?php foreach ($ids as $i): ?>
                        <option value="<?= htmlspecialchars($i) ?>" <?= ($identificador == $i) ? 'selected' : '' ?>><?= htmlspecialchars($i) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="ubicacion">
                    <option value="">Todas las Ubicaciones</option>
                    <?php foreach ($ubicaciones as $u): ?>
                        <option value="<?= htmlspecialchars($u) ?>" <?= ($ubicacion == $u) ? 'selected' : '' ?>><?= htmlspecialchars($u) ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Filtrar</button>
            </form>

            <!-- KPIs -->
            <div class="kpis">
                <div class="kpi">
                    <h1><?= $totalPrevGeneral ?></h1>
                    <span>Preventivos</span>
                </div>
                <div class="kpi">
                    <h1><?= $totalCorrGeneral ?></h1>
                    <span>Correctivos</span>
                </div>
                <div class="kpi">
                    <h1><?= $totalEquipos ?></h1>
                    <span>Equipos</span>
                </div>
            </div>

            <!-- DASHBOARD GRID -->
            <div class="dashboard">
                <!-- GRÁFICO -->
                <div class="card">
                    <h3>Mantenimientos por Mes</h3>
                    <?php if (empty($bloques)): ?>
                        <p style="text-align:center; color:#e2e8f0;">No hay datos para graficar.</p>
                    <?php else: ?>
                        <?php foreach ($bloques as $index => $bloque): ?>
                            <div style="margin-bottom:30px;">
                                <canvas id="grafico<?= $index ?>"></canvas>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- TABLA -->
                <div class="card">
                    <h3>Matriz de Equipos</h3>

                    <?php if (empty($equipos)): ?>
                        <p style="text-align:center; color:#e2e8f0;">No se encontraron registros.</p>
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
                                    <?php foreach ($equipos as $e): ?>
                                        <tr>
                                            <td data-label="Tipo"><?= htmlspecialchars($e['tipo']) ?></td>
                                            <td data-label="Marca"><?= htmlspecialchars($e['marca']) ?></td>
                                            <td data-label="ID"><?= htmlspecialchars($e['identificador']) ?></td>
                                            <td data-label="Ubicación"><?= htmlspecialchars($e['ubicacion']) ?></td>
                                            <td data-label="Asignado"><?= htmlspecialchars($e['asignado_a']) ?></td>

                                            <td data-label="Preventivos">
                                                <span class="badge-blue"><?= $e['total_preventivos'] ?></span>
                                            </td>
                                            <td data-label="Correctivos">
                                                <span class="badge-red"><?= $e['total_correctivos'] ?></span>
                                            </td>
                                            <td data-label="Último Mant.">
                                                <?= $e['ultimo_mantenimiento'] ?: 'Sin registros' ?>
                                            </td>
                                            <td data-label="Tipo Último">
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
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- PAGINACIÓN -->
                    <div class="paginacion">
                        <?php
                        $paginasPorBloque = 10;
                        $bloqueActual = (int) ceil($pagina / $paginasPorBloque);
                        $primeraPagina = (($bloqueActual - 1) * $paginasPorBloque) + 1;
                        $ultimaPagina = min($primeraPagina + $paginasPorBloque - 1, $totalPaginas);

                        // LÓGICA MÓVIL: Calcular las 5 páginas más cercanas a la actual
                        $rangoMobileInicio = max($primeraPagina, $pagina - 9);
                        $rangoMobileFin = min($ultimaPagina, $pagina + 9);

                        // Si estamos en los bordes, asegurar de mostrar siempre 5 opciones (si existen)
                        if ($rangoMobileFin - $rangoMobileInicio < 9) {
                            if ($rangoMobileInicio == $primeraPagina) {
                                $rangoMobileFin = min($ultimaPagina, $primeraPagina + 9);
                            } elseif ($rangoMobileFin == $ultimaPagina) {
                                $rangoMobileInicio = max($primeraPagina, $ultimaPagina - 9);
                            }
                        }
                        ?>

                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=<?= ($pagina - 1) ?>&<?= $queryFiltros ?>" class="pag-anterior">Anterior</a>
                        <?php endif; ?>

                        <?php for ($i = $primeraPagina; $i <= $ultimaPagina; $i++): ?>
                            <?php
                            $esActivo = ($pagina == $i) ? 'activo-pagina' : '';
                            $ocultarEnMovil = ($i < $rangoMobileInicio || $i > $rangoMobileFin) ? 'd-mobile-none' : '';
                            ?>
                            <a class="<?= $esActivo ?> <?= $ocultarEnMovil ?>" href="?pagina=<?= $i ?>&<?= $queryFiltros ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($pagina < $totalPaginas): ?>
                            <a href="?pagina=<?= ($pagina + 1) ?>&<?= $queryFiltros ?>" class="pag-siguiente">Siguiente</a>
                        <?php endif; ?>

                        <?php if ($totalPaginas > 0): ?>
                            <div class="indicador-paginacion">Páginas <?= $primeraPagina ?> - <?= $ultimaPagina ?> de <?= $totalPaginas ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                document.body.classList.toggle('sidebar-open');
            } else {
                document.body.classList.toggle('sidebar-collapsed');
            }
        }

        const bloques = <?= json_encode($bloques); ?>;

        if (bloques && bloques.length > 0) {
            bloques.forEach((bloque, index) => {
                let labels = bloque.map(item => item.mes);
                let preventivos = bloque.map(item => item.preventivos);
                let correctivos = bloque.map(item => item.correctivos);

                const canvas = document.getElementById('grafico' + index);
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                    label: 'Preventivos',
                                    data: preventivos,
                                    backgroundColor: '#3b82f6',
                                    borderRadius: 6
                                },
                                {
                                    label: 'Correctivos',
                                    data: correctivos,
                                    backgroundColor: '#ec4141',
                                    borderRadius: 6
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    labels: {
                                        color: '#ffffff'
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: '#ffffff'
                                    },
                                    grid: {
                                        color: 'rgba(255,255,255,0.1)'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: '#ffffff',
                                        precision: 0
                                    },
                                    grid: {
                                        color: 'rgba(255,255,255,0.1)'
                                    }
                                }
                            }
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>