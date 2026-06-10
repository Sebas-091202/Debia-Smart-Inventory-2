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

$tipo   = $_GET['tipo'] ?? '';
$marca  = $_GET['marca'] ?? '';
$estado = $_GET['estado'] ?? '';

/* =====================
FILTROS BASE
===================== */

$where = " WHERE m.tipo_mantenimiento='Correctivo' ";
$params = [];

if ($tipo != '') {
    $where .= " AND e.tipo=?";
    $params[] = $tipo;
}

if ($marca != '') {
    $where .= " AND e.marca LIKE ?";
    $params[] = "%$marca%";
}

if ($estado != '') {
    $where .= " AND m.estado=?";
    $params[] = $estado;
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

$sqlTotal = "
SELECT COUNT(*)
FROM mantenimientos m
INNER JOIN equipos e ON m.equipo_id = e.id
$where
";

$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalRegistros = $stmtTotal->fetchColumn();

$totalPaginas = ceil($totalRegistros / $porPagina);

/* =====================
CONSULTA PRINCIPAL
===================== */

$sql = "
SELECT 
m.id,
m.equipo_id,
m.fecha,
m.descripcion,
m.estado,
m.observaciones,
e.tipo,
e.marca,
e.identificador,
e.ubicacion
FROM mantenimientos m
INNER JOIN equipos e ON m.equipo_id=e.id
$where
ORDER BY m.fecha DESC
LIMIT $porPagina OFFSET $offset
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$correctivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ver Correctivos</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/ver_correctivos.css">
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

    <!-- CONTENIDO -->
    <div class="main-content">
        <div class="card">

            <h2>Correctivos Registrados</h2>
            <form method="GET">
                <div class="filtros">

                    <select name="tipo">
                        <option value="">Tipo</option>
                        <option value="P">Portátil</option>
                        <option value="TU">Todo en Uno</option>
                        <option value="E">Escritorio</option>
                        <option value="I">Impresora</option>
                    </select>

                    <select name="marca">
                        <option value="">Marca</option>
                        <option value="A">Asus</option>
                        <option value="HP">Hewlett-Packard</option>
                        <option value="L">Lenovo</option>
                        <option value="EP">EPSON</option>
                        <option value="G">Genius</option>
                    </select>

                    <select name="estado">
                        <option value="">Estado</option>
                        <option value="Activo">Activo</option>
                        <option value="En reparación">En reparación</option>
                        <option value="Dado de baja">Dado de baja</option>
                    </select>

                    <button class="btn-search">
                        Buscar
                    </button>

                </div>

            </form>

        </div>



        <div class="card">

            <table>

                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th>Acción</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($correctivos as $c): ?>

                        <tr>

                            <td><?= $c['identificador'] ?></td>

                            <td><?= $c['tipo'] ?></td>

                            <td><?= $c['marca'] ?></td>

                            <td><?= $c['fecha'] ?></td>

                            <td><?= $c['descripcion'] ?></td>

                            <td>

                                <?php
                                $clase = 'activo';

                                if ($c['estado'] == 'En reparación') {
                                    $clase = 'reparacion';
                                }

                                if ($c['estado'] == 'Dado de baja') {
                                    $clase = 'baja';
                                }
                                ?>

                                <span class="badge <?= $clase ?>">
                                    <?= $c['estado'] ?>
                                </span>

                            </td>

                            <td>
                                <?= $c['observaciones'] ?>
                            </td>

                            <td>

                                <a href="hoja_vida_equipos.php?id=<?= $c['equipo_id'] ?>">
                                    <button
                                        type="button"
                                        class="btn-hv">
                                        Ver Hoja de Vida
                                    </button>
                                </a>
                            </td>



                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="paginacion">

                <?php
                $queryFiltros =
                    "&tipo=" . urlencode($tipo) .
                    "&marca=" . urlencode($marca) .
                    "&estado=" . urlencode($estado);
                ?>

                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?= $pagina - 1 . $queryFiltros ?>">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a
                        class="<?= ($i == $pagina) ? 'activo-pagina' : '' ?>"
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
    </script>
</body>
</html>