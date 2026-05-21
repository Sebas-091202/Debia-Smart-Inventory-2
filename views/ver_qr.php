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

if (!isset($_GET['codigo'])) {
    header("Location: ver_equipos.php");
    exit();
}

$codigo = $_GET['codigo'] ?? '';
$equipo = null;

if ($codigo) {
    $stmt = $conn->prepare("SELECT * FROM equipos WHERE codigo_barras=?");
    $stmt->execute([$codigo]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>QR del Equipo</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

<link rel="stylesheet" href="../css/sidebar.css">
<link rel="stylesheet" href="../css/ver_equipos.css">

<style>
.container{
    display:flex;
    justify-content:center;

}

/* CARD */
.card{
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(18px);
    padding:30px;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,.4);
    color:white;
    text-align:center;
    max-width:400px;
    width:100%;
}

/* TITULOS */
h2{
    text-align:center;
    margin-bottom:20px;
}

/* QR */
.qr-img{
    background:white;
    padding:15px;
    border-radius:15px;
    margin:20px 0;
}


/* BOTONES */
.btn {
    display:inline-flex;
    align-items:center;
    gap:6px;
    margin:8px;
    padding:10px 15px;
    border-radius:10px;
    text-decoration:none;
    color:white;
    font-weight:600;
}

.btn-descargar {
    background: linear-gradient(135deg,#16a34a,#15803d);
}

.btn-hoja {
    background: linear-gradient(135deg,#2563eb,#1d4ed8);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow:0 6px 15px rgba(0,0,0,.3);
}

</style>
</head>

<body>

<!-- BOTÓN HAMBURGUESA -->
<button class="toggle-btn" onclick="toggleSidebar()">
    <i class='bx bx-menu'></i>
</button>

<!-- SIDEBAR COMPLETO -->
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

<!-- ✅ CONTENIDO -->
<div class="container">

    <div class="card">

        <h2>QR del Equipo</h2>

        <?php if($equipo): ?>

            <div class="qr-container">

                <div>

                    <h3><?= htmlspecialchars($equipo['tipo'] . ' - ' . $equipo['marca'] . ' - ' . $equipo['identificador']) ?></h3>

                    <div class="qr-img">
                        <img src="../qrs/<?= htmlspecialchars($codigo) ?>.png" width="200">
                    </div>

                    <p><strong>Código: </strong> <?= htmlspecialchars($codigo) ?></p>

                    <!-- DESCARGAR -->
                    <a href="../qrs/<?= urlencode($codigo) ?>.png"
                       download
                       class="btn btn-descargar">
                        <i class='bx bx-download'></i> Descargar
                    </a>

                    <!-- HOJA DE VIDA -->
                    <a href="hoja_vida_equipos.php?codigo=<?= urlencode($codigo) ?>"
                       class="btn btn-hoja">
                        <i class='bx bx-file'></i> Hoja de Vida
                    </a>

                </div>

            </div>

        <?php else: ?>
            <p>No se encontró el equipo</p>
        <?php endif; ?>

    </div>

</div>

<!-- ✅ SCRIPT FUNCIONAL -->
<script>
function toggleSidebar() {

    if (window.innerWidth <= 768) {
        // MODO MÓVIL
        document.body.classList.toggle('sidebar-open');
    } else {
        // MODO ESCRITORIO
        document.body.classList.toggle('sidebar-collapsed');
    }

}
</script>

</body>
</html>
