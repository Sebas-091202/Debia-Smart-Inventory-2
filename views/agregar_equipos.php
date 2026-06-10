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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Agregar Equipo</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/agregar_equipos.css">
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
    <!-- CONTENIDO -->
    <div class="main-content">
        <div class="form-container">

            <h2>Registrar Nuevo Equipo</h2>

            <form action="../controller/procesar_agregar_equipos.php" method="POST">

                <!-- TIPO -->
                <label>Tipo</label>
                <select name="tipo" required>
                    <option value="">Tipo</option>
                    <option value="P">Portátil</option>
                    <option value="TU">Todo en Uno</option>
                    <option value="E">Escritorio</option>
                    <option value="I">Impresora</option>
                    <option value="M">Mouse</option>
                    <option value="T">Teclado</option>
                    <option value="DI">Diademas</option>
                    <option value="C">Celulares</option>
                    <option value="MON">Monitor</option>
                </select>

                <!-- MARCA -->
                <label>Marca</label>
                <select name="marca" required>
                    <option value="">Marca</option>
                    <option value="A">Asus</option>
                    <option value="HP">Hewlett-Packard</option>
                    <option value="L">Lenovo</option>
                    <option value="EP">EPSON</option>
                    <option value="G">Genius</option>
                    <option value="S">Samsung</option>
                    <option value="H">Huawei</option>
                    <option value="MOT">Motorola</option>
                    <option value="X">Xiaomi</option>
                    <option value="CA">CANON</option>
                </select>


                <!-- IDENTIFICADOR -->
                <label>Identificador</label>
                <input type="text" name="identificador" placeholder="Ej: 001" required>

                <!-- ASIGNADO -->
                <label>Asignado a</label>
                <input type="text" name="asignado_a" placeholder="Usuario o área">

                <!-- SERIAL -->
                <label>Serial</label>
                <input type="text" name="serial" placeholder="Número de serie">

                <!-- HARDWARE -->
                <label>Procesador</label>
                <input type="text" name="procesador">

                <label>RAM</label>
                <select name="ram" required>
                    <option value="">Seleccione RAM</option>
                    <option value="4GB">4GB</option>
                    <option value="8GB">8GB</option>
                    <option value="12GB">12GB</option>
                    <option value="16GB">16GB</option>
                    <option value="32GB">32GB</option>
                    <option value="64GB">64GB</option>
                </select>



                <label>Disco C:</label>

                <select name="disco" required>
                    <option value="">Seleccione Disco</option>

                    <!-- SSD -->
                    <option value="128GB">128GB SSD</option>
                    <option value="256GB SSD">256GB SSD</option>
                    <option value="512GB SSD">512GB SSD</option>
                    <option value="1TB SSD">1TB SSD</option>

                    <!-- HDD -->
                    <option value="500GB HDD">500GB HDD</option>
                    <option value="700GB HDD">700GB HDD</option>    
                    <option value="1TB HDD">1TB HDD</option>
                    <option value="2TB HDD">2TB HDD</option>
                </select>


                <label>Disco D:</label>
                <select name="disco2" required>
                    <option value="">Seleccione Disco</option>
                    <!-- Ninguno -->
                    <option value="Ninguno">Ninguno</option>
                    <!-- SSD -->
                    <option value="256GB SSD">256GB SSD</option>
                    <option value="512GB SSD">512GB SSD</option>
                    <option value="1TB SSD">1TB SSD</option>

                    <!-- HDD -->
                    <option value="500GB HDD">500GB HDD</option>
                    <option value="1TB HDD">1TB HDD</option>
                    <option value="2TB HDD">2TB HDD</option>
                </select>


                <!-- ESTADO -->
                <label>Estado</label>
                <select name="estado">
                    <option value="">Seleccione Estado</option>
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                    <option value="En reparación">En reparación</option>
                    <option value="Dado de baja">Dado de baja</option>
                </select>

                <!-- UBICACION -->
                <label>Ubicación</label>
                <select name="ubicacion">
                    <!-- SURA, Generales, Consultas, HDI(Preguntar si es un area) -->
                    <option value="">Ubicación</option>
                    
                    <option value="AXA Mortales">AXA Mortales</option>
                    <option value="AXA Gastos Medicos">AXA Gastos Medicos</option>
                    <option value="AXA IPS">AXA IPS</option>
                    <option value="Generales">Generales</option>
                    <option value="Consultas">Consultas</option>
                    <option value="HDI">HDI</option>
                    <option value="SURA">SURA</option>
                    <option value="SURA IPS">SURA IPS</option>
                    <option value="SURA Gastos Medicos">SURA Gastos Medicos</option>
                    <option value="Sistemas">Sistemas</option>
                    <option value="Financiera">Financiera</option>
                    <option value="Talento Humano">Talento Humano</option>
                    <option value="Dirección Operativa">Dirección Operativa</option>
                    <option value="Bodega">Bodega</option>
                </select>

                <label>Código de Barras</label>
                <input type="text" name="codigo_barras" id="codigo_barras" placeholder="Escanear o escribir">

                <div class="submit-btn">
                    <button type="submit" class="submit-btn">Guardar Equipo</button>
                </div>


            </form>
        </div>
    </div>
    <script>
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
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