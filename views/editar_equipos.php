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
        <a href="agregar_equipos.php"><i class='bx bx-plus-circle'> </i><span> Agregar Equipo</span></a>
        <a href="editar_equipos.php"><i class='bx bx-edit-alt'></i> <span> Editar Equipo</span></a>
        <a href="ver_correctivos.php"><i class='bx bx-check-square'></i> <span> Ver Correctivos</span></a>
        <a href="ver_preventivos.php"><i class='bx bx-calendar'></i> <span> Ver Preventivos</span></a>
        <a href="indicadores_mantenimiento.php"><i class='bx bx-bar-chart'></i> <span> Indicadores de Mantenimiento</span></a>
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
                    <option value="" disabled>
                        Seleccione Tipo
                    </option>
                    <option value="PO" <?= ($equipo['tipo'] == 'PO') ? 'selected' : ''; ?>>Portátil</option>
                    <option value="TO" <?= ($equipo['tipo'] == 'TO') ? 'selected' : ''; ?>>Todo en Uno</option>
                    <option value="ES" <?= ($equipo['tipo'] == 'ES') ? 'selected' : ''; ?>>Escritorio</option>
                    <option value="IM" <?= ($equipo['tipo'] == 'IM') ? 'selected' : ''; ?>>Impresora</option>
                    <option value="VI" <?= ($equipo['tipo'] == 'VI') ? 'selected' : ''; ?>>Videobeam</option>
                    <option value="BR" <?= ($equipo['tipo'] == 'BR') ? 'selected' : ''; ?>>Bases de Refrigeración</option>
                    <option value="MS" <?= ($equipo['tipo'] == 'MS') ? 'selected' : ''; ?>>Mouse</option>
                    <option value="TE" <?= ($equipo['tipo'] == 'TE') ? 'selected' : ''; ?>>Teclado</option>
                    <option value="DI" <?= ($equipo['tipo'] == 'DI') ? 'selected' : ''; ?>>Diademas</option>
                    <option value="AU" <?= ($equipo['tipo'] == 'AU') ? 'selected' : ''; ?>>Auriculares</option>
                    <option value="CE" <?= ($equipo['tipo'] == 'CE') ? 'selected' : ''; ?>>Celulares</option>
                    <option value="MO" <?= ($equipo['tipo'] == 'MO') ? 'selected' : ''; ?>>Monitor</option>
                </select>

                <label>Marca</label>
                <select name="marca" required>
                    <option value="" disabled <?= (empty($equipo['marca'])) ? 'selected' : ''; ?>>
                        Seleccione Marca
                    </option>
                    <option value="DELL" <?= ($equipo['marca'] == 'DELL') ? 'selected' : ''; ?>>Dell</option>
                    <option value="INP" <?= ($equipo['marca'] == 'INP') ? 'selected' : ''; ?>>INPOWER</option>
                    <option value="AS" <?= ($equipo['marca'] == 'AS') ? 'selected' : ''; ?>>Asus</option>
                    <option value="HP" <?= ($equipo['marca'] == 'HP') ? 'selected' : ''; ?>>Hewlett-Packard</option>
                    <option value="LE" <?= ($equipo['marca'] == 'LE') ? 'selected' : ''; ?>>Lenovo</option>
                    <option value="EP" <?= ($equipo['marca'] == 'EP') ? 'selected' : ''; ?>>EPSON</option>
                    <option value="CA" <?= ($equipo['marca'] == 'CA') ? 'selected' : ''; ?>>CANON</option>
                    <option value="GE" <?= ($equipo['marca'] == 'GE') ? 'selected' : ''; ?>>Genius</option>
                    <option value="OP" <?= ($equipo['marca'] == 'OP') ? 'selected' : ''; ?>>OPPO</option>
                    <option value="KA" <?= ($equipo['marca'] == 'KA') ? 'selected' : ''; ?>>KALLEY</option>
                    <option value="SAM" <?= ($equipo['marca'] == 'SAM') ? 'selected' : ''; ?>>SAMSUNG</option>
                    <option value="CHA" <?= ($equipo['marca'] == 'CHA') ? 'selected' : ''; ?>>CHALLENGER</option>
                    <option value="LG" <?= ($equipo['marca'] == 'LG') ? 'selected' : ''; ?>>LG</option>
                    <option value="MA" <?= ($equipo['marca'] == 'MA') ? 'selected' : ''; ?>>MAXELL</option>
                    <option value="PA" <?= ($equipo['marca'] == 'PA') ? 'selected' : ''; ?>>PANASONIC</option>
                    <option value="AR" <?= ($equipo['marca'] == 'AR') ? 'selected' : ''; ?>>ARCHTEX</option>
                    <option value="XK" <?= ($equipo['marca'] == 'XK') ? 'selected' : ''; ?>>XKIM</option>
                    <option value="HA" <?= ($equipo['marca'] == 'HA') ? 'selected' : ''; ?>>HAVIT</option>
                    <option value="LO" <?= ($equipo['marca'] == 'LO') ? 'selected' : ''; ?>>Logitech</option>
                    <option value="WI" <?= ($equipo['marca'] == 'WI') ? 'selected' : ''; ?>>WIT</option>
                    <option value="HU" <?= ($equipo['marca'] == 'HU') ? 'selected' : ''; ?>>Huawei</option>
                    <option value="MO" <?= ($equipo['marca'] == 'MO') ? 'selected' : ''; ?>>Motorola</option>
                    <option value="XI" <?= ($equipo['marca'] == 'XI') ? 'selected' : ''; ?>>Xiaomi</option>
                    <option value="N/A" <?= ($equipo['marca'] == 'N/A') ? 'selected' : ''; ?>>SIN MARCA</option>
                </select>



                <label>Identificador</label>
                <input type="text" name="identificador" value="<?= $equipo['identificador'] ?>">

                <label>Asignado a</label>
                <input type="text" name="asignado_a" value="<?= $equipo['asignado_a'] ?>">

                <label>Serial</label>
                <input type="text" name="serial" value="<?= $equipo['serial'] ?>">

                <label>Procesador</label>
                <input type="text" name="procesador" value="<?= $equipo['procesador'] ?>">

                <label>RAM</label>
                <select name="ram">
                    <option value="" disabled selected>Seleccione RAM</option>

                    <option value="4GB" <?= ($equipo['ram'] == '4GB') ? 'selected' : '' ?>>4GB</option>
                    <option value="8GB" <?= ($equipo['ram'] == '8GB') ? 'selected' : '' ?>>8GB</option>
                    <option value="12GB" <?= ($equipo['ram'] == '12GB') ? 'selected' : '' ?>>12GB</option>
                    <option value="16GB" <?= ($equipo['ram'] == '16GB') ? 'selected' : '' ?>>16GB</option>
                    <option value="32GB" <?= ($equipo['ram'] == '32GB') ? 'selected' : '' ?>>32GB</option>
                    <option value="64GB" <?= ($equipo['ram'] == '64GB') ? 'selected' : '' ?>>64GB</option>
                </select>


                <label>Disco C:</label>
                <select name="disco">
                    <option value="" disabled selected>Seleccione Disco C:</option>
                    <option value="" disabled>----Tipo de Disco SSD----</option>
                    <!-- Ninguno -->
                    <option value="Ninguno SSD" <?= ($equipo['disco'] == 'Ninguno SSD') ? 'selected' : '' ?>>Ninguno SSD</option>
                    <option value="16GB" <?= ($equipo['disco'] == '16GB') ? 'selected' : '' ?>>16GB</option>
                    <option value="32GB" <?= ($equipo['disco'] == '32GB') ? 'selected' : '' ?>>32GB</option>
                    <option value="64GB" <?= ($equipo['disco'] == '64GB') ? 'selected' : '' ?>>64GB</option>
                    <option value="128GB SSD" <?= ($equipo['disco'] == '128GB SSD') ? 'selected' : '' ?>>128GB SSD</option>
                    <option value="256GB SSD" <?= ($equipo['disco'] == '256GB SSD') ? 'selected' : '' ?>>256GB SSD</option>
                    <option value="512GB SSD" <?= ($equipo['disco'] == '512GB SSD') ? 'selected' : '' ?>>512GB SSD</option>
                    <option value="1TB SSD" <?= ($equipo['disco'] == '1TB SSD') ? 'selected' : '' ?>>1TB SSD</option>
                    <option value="2TB SSD" <?= ($equipo['disco'] == '2TB SSD') ? 'selected' : '' ?>>2TB SSD</option>

                    <option value="" disabled>----Tipo de Disco HDD----</option>
                    <!-- Ninguno -->
                    <option value="Ninguno HDD" <?= ($equipo['disco'] == 'Ninguno HDD') ? 'selected' : '' ?>>Ninguno HDD</option>
                    <option value="500GB HDD" <?= ($equipo['disco'] == '500GB HDD') ? 'selected' : '' ?>>500GB HDD</option>
                    <option value="700GB HDD" <?= ($equipo['disco'] == '700GB HDD') ? 'selected' : '' ?>>700GB HDD</option>
                    <option value="1TB HDD" <?= ($equipo['disco'] == '1TB HDD') ? 'selected' : '' ?>>1TB HDD</option>
                    <option value="2TB HDD" <?= ($equipo['disco'] == '2TB HDD') ? 'selected' : '' ?>>2TB HDD</option>
                </select>



                <label>Disco D:</label>
                <select name="disco2">
                    <option value="" disabled>Seleccione Disco D:</option>
                    <option value="" disabled>----Tipo de Disco SSD----</option>
                    <!-- Ninguno -->
                    <option value="Ninguno SSD" <?= ($equipo['disco2'] == 'Ninguno SSD') ? 'selected' : '' ?>>Ninguno SSD</option>
                    <option value="128GB SSD" <?= ($equipo['disco2'] == '128GB SSD') ? 'selected' : '' ?>>128GB SSD</option>
                    <option value="256GB SSD" <?= ($equipo['disco2'] == '256GB SSD') ? 'selected' : '' ?>>256GB SSD</option>
                    <option value="512GB SSD" <?= ($equipo['disco2'] == '512GB SSD') ? 'selected' : '' ?>>512GB SSD</option>
                    <option value="1TB SSD" <?= ($equipo['disco2'] == '1TB SSD') ? 'selected' : '' ?>>1TB SSD</option>
                    <option value="2TB SSD" <?= ($equipo['disco2'] == '2TB SSD') ? 'selected' : '' ?>>2TB SSD</option>

                    <option value="" disabled>----Tipo de Disco HDD----</option>
                    <!-- Ninguno -->
                    <option value="Ninguno HDD" <?= ($equipo['disco2'] == 'Ninguno HDD') ? 'selected' : '' ?>>Ninguno HDD</option>
                    <option value="500GB HDD" <?= ($equipo['disco2'] == '500GB HDD') ? 'selected' : '' ?>>500GB HDD</option>
                    <option value="700GB HDD" <?= ($equipo['disco2'] == '700GB HDD') ? 'selected' : '' ?>>700GB HDD</option>
                    <option value="1TB HDD" <?= ($equipo['disco2'] == '1TB HDD') ? 'selected' : '' ?>>1TB HDD</option>
                    <option value="2TB HDD" <?= ($equipo['disco2'] == '2TB HDD') ? 'selected' : '' ?>>2TB HDD</option>
                </select>


                <label>Estado</label>
                <select name="estado">
                    <option value="" disabled selected>
                        Seleccione Estado
                    </option>

                    <option value="Activo" <?= ($equipo['estado'] == 'Activo') ? 'selected' : ''; ?>>
                        Activo
                    </option>

                    <option value="Inactivo" <?= ($equipo['estado'] == 'Inactivo') ? 'selected' : ''; ?>>
                        Inactivo
                    </option>

                    <option value="En reparación" <?= ($equipo['estado'] == 'En reparación') ? 'selected' : ''; ?>>
                        En reparación
                    </option>

                    <option value="Dado de baja" <?= ($equipo['estado'] == 'Dado de baja') ? 'selected' : ''; ?>>
                        Dado de baja
                    </option>
                </select>

                <label>Ubicación</label>
                <select name="ubicacion">
                    <option value="" disabled>
                        Seleccione Ubicación
                    </option>
                    <option value="AXA Mortales" <?= ($equipo['ubicacion'] == 'AXA Mortales') ? 'selected' : ''; ?>>
                        AXA Mortales
                    </option>
                    <option value="AXA Gastos Medicos" <?= ($equipo['ubicacion'] == 'AXA Gastos Medicos') ? 'selected' : ''; ?>>
                        AXA Gastos Medicos
                    </option>
                    <option value="AXA IPS" <?= ($equipo['ubicacion'] == 'AXA IPS') ? 'selected' : ''; ?>>
                        AXA IPS
                    </option>
                    <option value="Generales" <?= ($equipo['ubicacion'] == 'Generales') ? 'selected' : ''; ?>>
                        Generales
                    </option>
                    <option value="Consultas" <?= ($equipo['ubicacion'] == 'Consultas') ? 'selected' : ''; ?>>
                        Consultas
                    </option>
                    <option value="HDI" <?= ($equipo['ubicacion'] == 'HDI') ? 'selected' : ''; ?>>
                        HDI
                    </option>
                    <option value="SURA" <?= ($equipo['ubicacion'] == 'SURA') ? 'selected' : ''; ?>>
                        SURA
                    </option>
                    <option value="SURA IPS" <?= ($equipo['ubicacion'] == 'SURA IPS') ? 'selected' : ''; ?>>
                        SURA IPS
                    </option>
                    <option value="SURA Gastos Medicos" <?= ($equipo['ubicacion'] == 'SURA Gastos Medicos') ? 'selected' : ''; ?>>
                        SURA Gastos Medicos
                    </option>
                    <option value="Sistemas" <?= ($equipo['ubicacion'] == 'Sistemas') ? 'selected' : ''; ?>>
                        Sistemas
                    </option>
                    <option value="Financiera" <?= ($equipo['ubicacion'] == 'Financiera') ? 'selected' : ''; ?>>
                        Financiera
                    </option>
                    <option value="Talento Humano" <?= ($equipo['ubicacion'] == 'Talento Humano') ? 'selected' : ''; ?>>
                        Talento Humano
                    </option>
                    <option value="Dirección Operativa" <?= ($equipo['ubicacion'] == 'Dirección Operativa') ? 'selected' : ''; ?>>
                        Dirección Operativa
                    </option>
                    <option value="En Casa" <?= ($equipo['ubicacion'] == 'En Casa') ? 'selected' : ''; ?>>
                        En Casa
                    </option>
                    <option value="Renting" <?= ($equipo['ubicacion'] == 'Renting') ? 'selected' : ''; ?>>
                        Renting
                    </option>
                    <option value="Camaras" <?= ($equipo['ubicacion'] == 'Camaras') ? 'selected' : ''; ?>>
                        Camaras
                    </option>
                    <option value="Bodega" <?= ($equipo['ubicacion'] == 'Bodega') ? 'selected' : ''; ?>>
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