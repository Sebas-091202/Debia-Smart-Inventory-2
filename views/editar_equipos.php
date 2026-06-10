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

if (!isset($_GET['id'])) {
    header("Location: ver_equipos.php");
    exit();
}

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM equipos WHERE id=?");
$stmt->execute([$id]);

$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    die("Equipo no encontrado");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Equipo</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/editar_equipos.css">
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

        <div class="form-container">

            <h2>Editar Equipo</h2>

            <form action="../controller/actualizar_equipos.php"
                method="POST">

                <input type="hidden" name="id" value="<?= $equipo['id'] ?>">

                <label>Tipo</label>
                <select name="tipo">
                    <option value="P" <?= ($equipo['tipo'] == 'P') ? 'selected' : ''; ?>>Portátil</option>

                    <option value="TU" <?= ($equipo['tipo'] == 'TU') ? 'selected' : ''; ?>>Todo en Uno</option>

                    <option value="E" <?= ($equipo['tipo'] == 'E') ? 'selected' : ''; ?>>Escritorio</option>

                    <option value="I" <?= ($equipo['tipo'] == 'I') ? 'selected' : ''; ?>>Impresora</option>

                    <option value="M" <?= ($equipo['tipo'] == 'M') ? 'selected' : ''; ?>>Mouse</option>

                    <option value="T" <?= ($equipo['tipo'] == 'T') ? 'selected' : ''; ?>>Teclado</option>

                    <option value="C" <?= ($equipo['tipo'] == 'C') ? 'selected' : ''; ?>>Celulares</option>

                    <option value="DI" <?= ($equipo['tipo'] == 'DI') ? 'selected' : ''; ?>>Diademas</option>

                    <option value="MON" <?= ($equipo['tipo'] == 'MON') ? 'selected' : ''; ?>>Monitor</option>
                </select>

                <label>Marca</label>
                <select name="marca">
                    <option value="A" <?= ($equipo['marca'] == 'A') ? 'selected' : ''; ?>>Asus</option>

                    <option value="HP" <?= ($equipo['marca'] == 'HP') ? 'selected' : ''; ?>>Hewlett-Packard</option>

                    <option value="L" <?= ($equipo['marca'] == 'L') ? 'selected' : ''; ?>>Lenovo</option>

                    <option value="EP" <?= ($equipo['marca'] == 'EP') ? 'selected' : ''; ?>>EPSON</option>

                    <option value="G" <?= ($equipo['marca'] == 'G') ? 'selected' : ''; ?>>Genius</option>

                    <option value="S" <?= ($equipo['marca'] == 'S') ? 'selected' : ''; ?>>Samsung</option>

                    <option value="H" <?= ($equipo['marca'] == 'H') ? 'selected' : ''; ?>>Huawei</option>

                    <option value="MOT" <?= ($equipo['marca'] == 'MOT') ? 'selected' : ''; ?>>Motorola</option>

                    <option value="X" <?= ($equipo['marca'] == 'X') ? 'selected' : ''; ?>>Xiaomi</option>

                    <option value="CA" <?= ($equipo['marca'] == 'CA') ? 'selected' : ''; ?>>CANON</option>
                </select>



                <label>Identificador</label>
                <input type="text"
                    name="identificador"
                    value="<?= $equipo['identificador'] ?>">


                <label>Asignado a</label>
                <input type="text"
                    name="asignado_a"
                    value="<?= $equipo['asignado_a'] ?>">


                <label>Serial</label>
                <input type="text"
                    name="serial"
                    value="<?= $equipo['serial'] ?>">


                <label>Procesador</label>
                <input type="text"
                    name="procesador"
                    value="<?= $equipo['procesador'] ?>">

                <label>RAM</label>
                <select name="ram" required>
                    <option value="">Seleccione RAM</option>

                    <option value="4GB" <?= ($equipo['ram'] == '4GB') ? 'selected' : '' ?>>4GB</option>
                    <option value="8GB" <?= ($equipo['ram'] == '8GB') ? 'selected' : '' ?>>8GB</option>
                    <option value="12GB" <?= ($equipo['ram'] == '12GB') ? 'selected' : '' ?>>12GB</option>
                    <option value="16GB" <?= ($equipo['ram'] == '16GB') ? 'selected' : '' ?>>16GB</option>
                    <option value="32GB" <?= ($equipo['ram'] == '32GB') ? 'selected' : '' ?>>32GB</option>
                    <option value="64GB" <?= ($equipo['ram'] == '64GB') ? 'selected' : '' ?>>64GB</option>
                </select>


                <label>Disco C:</label>
                <select name="disco" required>
                    <option value="">Seleccione Disco</option>

                    <option value="256GB SSD" <?= ($equipo['disco'] == '256GB SSD') ? 'selected' : '' ?>>256GB SSD</option>
                    <option value="512GB SSD" <?= ($equipo['disco'] == '512GB SSD') ? 'selected' : '' ?>>512GB SSD</option>
                    <option value="1TB SSD" <?= ($equipo['disco'] == '1TB SSD') ? 'selected' : '' ?>>1TB SSD</option>

                    <option value="500GB HDD" <?= ($equipo['disco'] == '500GB HDD') ? 'selected' : '' ?>>500GB HDD</option>
                    <option value="700GB HDD" <?= ($equipo['disco'] == '700GB HDD') ? 'selected' : '' ?>>700GB HDD</option>
                    <option value="1TB HDD" <?= ($equipo['disco'] == '1TB HDD') ? 'selected' : '' ?>>1TB HDD</option>
                    <option value="2TB HDD" <?= ($equipo['disco'] == '2TB HDD') ? 'selected' : '' ?>>2TB HDD</option>
                </select>



                <label>Disco D:</label>
                <select name="disco2" required>
                    <option value="">Seleccione Disco D:</option>

                    <option value="Ninguno" <?= ($equipo['disco2'] == 'Ninguno') ? 'selected' : '' ?>>Ninguno</option>

                    <option value="256GB SSD" <?= ($equipo['disco2'] == '256GB SSD') ? 'selected' : '' ?>>256GB SSD</option>
                    <option value="512GB SSD" <?= ($equipo['disco2'] == '512GB SSD') ? 'selected' : '' ?>>512GB SSD</option>
                    <option value="1TB SSD" <?= ($equipo['disco2'] == '1TB SSD') ? 'selected' : '' ?>>1TB SSD</option>

                    <option value="500GB HDD" <?= ($equipo['disco2'] == '500GB HDD') ? 'selected' : '' ?>>500GB HDD</option>
                    <option value="700GB HDD" <?= ($equipo['disco2'] == '700GB HDD') ? 'selected' : '' ?>>700GB HDD</option>
                    <option value="1TB HDD" <?= ($equipo['disco2'] == '1TB HDD') ? 'selected' : '' ?>>1TB HDD</option>
                    <option value="2TB HDD" <?= ($equipo['disco2'] == '2TB HDD') ? 'selected' : '' ?>>2TB HDD</option>
                </select>


                <label>Estado</label>
                <select name="estado">
                    <option value="Seleccione Estado" disabled
                        <?= ($equipo['estado'] == '') ? 'selected' : ''; ?>>
                        Seleccione Estado
                    <option value="Activo"
                        <?= ($equipo['estado'] == 'Activo') ? 'selected' : ''; ?>>
                        Activo
                    </option>

                    <option value="Inactivo"
                        <?= ($equipo['estado'] == 'Inactivo') ? 'selected' : ''; ?>>
                        Inactivo
                    </option>

                    <option value="En reparación"
                        <?= ($equipo['estado'] == 'En reparación') ? 'selected' : ''; ?>>
                        En reparación
                    </option>

                    <option value="Dado de baja"
                        <?= ($equipo['estado'] == 'Dado de baja') ? 'selected' : ''; ?>>
                        Dado de baja
                    </option>
                </select>

                <label>Ubicación</label>
                <select name="ubicacion">
                    <option value="Seleccione Ubicación" disabled
                        <?= ($equipo['ubicacion'] == '') ? 'selected' : ''; ?>>
                        Seleccione Ubicación

                    <option value="AXA Mortales">
                        <?= ($equipo['ubicacion'] == 'AXA Mortales') ? 'selected' : ''; ?>
                        AXA Mortales
                    </option>
                    <option value="AXA Gastos Medicos">
                        <?= ($equipo['ubicacion'] == 'AXA Gastos Medicos') ? 'selected' : ''; ?>
                        AXA Gastos Medicos
                    </option>
                    <option value="AXA IPS">
                        <?= ($equipo['ubicacion'] == 'AXA IPS') ? 'selected' : ''; ?>
                        AXA IPS
                    </option>
                    <option value="Generales">
                        <?= ($equipo['ubicacion'] == 'Generales') ? 'selected' : ''; ?>
                        Generales
                    </option>
                    <option value="Consultas">
                        <?= ($equipo['ubicacion'] == 'Consultas') ? 'selected' : ''; ?>
                        Consultas
                    </option>
                    <option value="HDI">
                        <?= ($equipo['ubicacion'] == 'HDI') ? 'selected' : ''; ?>
                        HDI
                    </option>
                    <option value="SURA">
                        <?= ($equipo['ubicacion'] == 'SURA') ? 'selected' : ''; ?>
                        SURA
                    </option>
                    <option value="SURA IPS">
                        <?= ($equipo['ubicacion'] == 'SURA IPS') ? 'selected' : ''; ?>
                        SURA IPS
                    </option>
                    <option value="SURA Gastos Medicos">
                        <?= ($equipo['ubicacion'] == 'SURA Gastos Medicos') ? 'selected' : ''; ?>
                        SURA Gastos Medicos
                    </option>
                    <option value="Sistemas">
                        <?= ($equipo['ubicacion'] == 'Sistemas') ? 'selected' : ''; ?>
                        Sistemas
                    </option>
                    <option value="Financiera">
                        <?= ($equipo['ubicacion'] == 'Financiera') ? 'selected' : ''; ?>
                        Financiera
                    </option>
                    <option value="Talento Humano">
                        <?= ($equipo['ubicacion'] == 'Talento Humano') ? 'selected' : ''; ?>
                        Talento Humano
                    </option>
                    <option value="Dirección Operativa">
                        <?= ($equipo['ubicacion'] == 'Dirección Operativa') ? 'selected' : ''; ?>
                        Dirección Operativa
                    </option>
                    <option value="Bodega">
                        <?= ($equipo['ubicacion'] == 'Bodega') ? 'selected' : ''; ?>
                        Bodega
                    </option>
                </select>


                <label>Código de Barras</label>
                <input type="text"
                    name="codigo_barras"
                    value="<?= $equipo['codigo_barras'] ?>"
                    placeholder="Escanear o escribir">


                <button
                    type="submit"
                    class="submit-btn">
                    Actualizar Equipo
                </button>

            </form>

            <div style="text-align:center;">
                <a href="ver_equipos.php" class="volver">
                    Volver al Inventario
                </a>
            </div>

        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
        }
    </script>

</body>

</html>