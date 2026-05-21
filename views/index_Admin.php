<?php
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

?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Panel Administrador</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="../css/index_Admin.css">
</head>

<body>
  <div class="admin-container">
    <header>

      <img src="../img/logo.png" alt="Logo">
    </header>
    <h1>Bienvenido Administrador</h1>
    <nav>
      <ul>
        <li><a href="ver_equipos.php"><i class='bx bx-list-ul'></i> Visualizar Equipos</a></li>
        <li><a href="ver_qr.php"><i class='bx bx-barcode'></i> Ver QR</a></li>
        <li><a href="agregar_equipos.php"><i class='bx bx-plus-circle'></i> Agregar Equipo</a></li>
        <li><a href="editar_equipos.php"><i class='bx bx-edit-alt'></i> Editar Equipo</a></li>
        <li><a href="ver_correctivos.php"><i class='bx bx-check-square'></i><span>Ver Correctivos</span></a></li>
        <li><a href="programar_preventivos.php"><i class='bx bx-calendar'></i> Programación Automática</a></li>
        <li><a href="reprogramar_preventivo.php"><i class='bx bx-calendar-edit'></i> Reprogramar Preventivos</a></li>
        <li><a href="indicadores_mantenimiento.php"><i class='bx bx-bar-chart'></i> Indicadores de Mantenimiento</a></li>
        <li><a href="repuestos.php"><i class='bx bx-cog'></i> Gestión de Repuestos</a></li>
        <li><a href="hoja_vida_equipos.php"><i class='bx bx-file'></i><span>Hoja de Vida General</span></a></li>
        <li><a href="../controller/logout.php" class="logout"><i class='bx bx-log-out'></i> Cerrar Sesión</a></li>
      </ul>
    </nav>
  </div>
  <script>
    history.pushState(null, null, location.href);
    window.onpopstate = function() {
      history.go(1);
    };
  </script>
</body>

</html>