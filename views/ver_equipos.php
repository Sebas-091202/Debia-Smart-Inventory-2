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

/* ELIMINAR */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_equipo'])) {

    $id_equipo = $_POST['id_equipo'];

    $stmt = $conn->prepare("DELETE FROM equipos WHERE id=?");
    $stmt->execute([$id_equipo]);

    header("Location: ver_equipos.php");
    exit();
}


/* =====================
FILTROS
===================== */

$tipo = $_GET['tipo'] ?? '';
$marca = $_GET['marca'] ?? '';
$serial = $_GET['serial'] ?? '';
$ubicacion = $_GET['ubicacion'] ?? '';
$identificador = $_GET['identificador'] ?? '';
$codigo = $_GET['codigo'] ?? '';

$sqlWhere = " WHERE 1=1 ";
$params = [];

if (!empty($tipo)) {
    $sqlWhere .= " AND tipo=:tipo";
    $params[':tipo'] = $tipo;
}

if (!empty($marca)) {
    $sqlWhere .= " AND marca LIKE :marca";
    $params[':marca'] = "%$marca%";
}

if (!empty($ubicacion)) {
    $sqlWhere .= " AND ubicacion LIKE :ubicacion";
    $params[':ubicacion'] = "%$ubicacion%";
}

if (!empty($identificador)) {
    $sqlWhere .= " AND identificador LIKE :identificador";
    $params[':identificador'] = "%$identificador%";
}


if (!empty($codigo)) {
    $sqlWhere .= " AND codigo_barras = :codigo";
    $params[':codigo'] = $codigo;
}


/* =====================
PAGINACION
===================== */

$porPagina = 6;

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$inicio = ($pagina - 1) * $porPagina;


/* CONTAR TOTAL REGISTROS FILTRADOS */

$sqlTotal = "SELECT COUNT(*) FROM equipos " . $sqlWhere;

$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->execute($params);

$totalRegistros = $stmtTotal->fetchColumn();

$totalPaginas = ceil($totalRegistros / $porPagina);

/* =====================
CÓDIGOS DE BARRAS DISPONIBLES
===================== */

$codigos = $conn->query("
SELECT codigo_barras 
FROM equipos 
WHERE codigo_barras IS NOT NULL 
AND codigo_barras <> ''
ORDER BY codigo_barras
")->fetchAll(PDO::FETCH_COLUMN);


/* CONSULTA CON LIMIT */

$sql = "
SELECT *
FROM equipos
$sqlWhere
ORDER BY identificador ASC
LIMIT :inicio, :porPagina
";

$stmt = $conn->prepare($sql);

/* parametros filtros */
// BIND FILTROS
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

// BIND PAGINACIÓN
$stmt->bindValue(':inicio', (int)$inicio, PDO::PARAM_INT);
$stmt->bindValue(':porPagina', (int)$porPagina, PDO::PARAM_INT);

// EJECUTAR SIN PARAMS
$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lista de Equipos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/ver_equipos.css">
</head>
<style>
    .btn-qr {
        display: inline-flex;
        align-items: center;
        gap: 6px;

        padding: 6px 10px;

        background: linear-gradient(135deg, #60a5fa, #2563eb);
        color: #fff !important;

        border-radius: 8px;

        font-weight: 600;
        font-size: 13px;

        text-decoration: none !important;

        cursor: pointer;

        transition: all 0.3s ease;
    }

    .btn-qr:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, .25);
    }

    /* FORZAR ESTILO DEL BOTÓN QR */
    td a.btn-qr {
        display: inline-flex !important;
        align-items: center;
        gap: 6px;

        padding: 6px 10px;

        background: linear-gradient(135deg, #60a5fa, #2563eb) !important;
        color: white !important;

        border-radius: 8px;

        font-weight: 600;
        font-size: 13px;

        text-decoration: none !important;

        cursor: pointer;

        border: none;
    }

    /* HOVER */
    td a.btn-qr:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, .25);
    }

    .Inactivo {
        background: #415885;
        color: white;
    }
</style>

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
    <!-- Contenido principal -->
    <div class="container">
        <div class="card">
            <h2>Inventario de Equipos</h2>
            <!--  FILTROS -->
            <form method="GET" class="filtros">
                <select name="tipo">
                    <option value="" disabled selected>Seleccione Tipo</option>
                    <option value="P">Portátil</option>
                    <option value="TU">Todo en Uno</option>
                    <option value="E">Escritorio</option>
                    <option value="I">Impresora</option>
                    <option value="M">Mouse</option>
                    <option value="T">Teclado</option>
                    <option value="C">Celulares</option>
                    <option value="DI">Diademas</option>
                    <option value="MON">Monitor</option>
                </select>

                <select name="marca">
                    <option value="" disabled selected>Seleccione Marca</option>
                    <option value="A">Asus</option>
                    <option value="HP">Hewlett-Packard</option>
                    <option value="L">Lenovo</option>
                    <option value="LOG">Logitech</option>
                    <option value="CA">CANON</option>
                    <option value="EP">EPSON</option>
                    <option value="G">Genius</option>
                    <option value="INP">INPOWER</option>
                    <option value="S">Samsung</option>
                    <option value="H">Huawei</option>
                    <option value="MO">Motorola</option>
                    <option value="X">Xiaomi</option>
                    <option value="GEN">Genérico</option>
                </select>


                <!-- UBICACION -->
                <select name="ubicacion">
                    <!-- SURA, Generales, Consultas, HDI(Preguntar si es un area) -->
                    <option value="" disabled selected>Seleccione Ubicación</option>
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

                <input type="text" name="identificador" placeholder="Identificador">

                <select name="codigo">
                    <option value="" disabled selected>Código de Barras</option>

                    <?php foreach ($codigos as $cod): ?>
                        <option value="<?= $cod ?>"
                            <?= (isset($_GET['codigo']) && $_GET['codigo'] == $cod) ? 'selected' : '' ?>>
                            <?= $cod ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button class="btn-search" type="submit">Buscar</button>

            </form>
        </div>

        <!-- GLOSARIO -->
        <div class="card glosario">
            <h3>Guía de Abreviaciones</h3>
            <table>
                <p>Tipos de Equipos</p>
                <tr>
                    <td>P</td>
                    <td>Portátil</td>
                    <td>TU</td>
                    <td>Todo en Uno</td>
                </tr>
                <tr>
                    <td>T</td>
                    <td>Teclado</td>
                    <td>M</td>
                    <td>Mouse</td>
                </tr>
                <tr>
                    <td>E</td>
                    <td>Escritorio</td>
                    <td>I</td>
                    <td>Impresora</td>
                </tr>
                <tr>
                    <td>C</td>
                    <td>Celulares</td>
                    <td>DI</td>
                    <td>Diademas</td>
                </tr>
                <tr>
                    <td>MON</td>
                    <td>Monitor</td>
                </tr>
            </table>
            <table>
                <p>Marcas</p>
                <tr>
                    <td>A</td>
                    <td>Asus</td>
                    <td>L</td>
                    <td>Lenovo</td>
                </tr>
                <tr>
                    <td>EP</td>
                    <td>EPSON</td>
                    <td>CA</td>
                    <td>CANON</td>
                </tr>
                <tr>
                    <td>HP</td>
                    <td>Hewlett-Packard</td>
                    <td>MOT</td>
                    <td>Motorola</td>
                </tr>
                <tr>
                    <td>G</td>
                    <td>Genius</td>
                    <td>X</td>
                    <td>Xiaomi</td>
                </tr>
                <tr>
                    <td>S</td>
                    <td>Samsung</td>
                    <td>H</td>
                    <td>Huawei</td>
                </tr>
                <tr>
                    <td>GEN</td>
                    <td>Genérico</td>
                    <td>LOG</td>
                    <td>Logitech</td>
                </tr>
                <tr>
                    <td>INP</td>
                    <td>INPOWER</td>
                </tr>
            </table>
        </div>

        <!-- TABLA -->
        <div class="card">
            <table>
                <thead>
                    <tr>

                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>ID</th>
                        <th>Asignado</th>
                        <th>Serial</th>
                        <th>Procesador</th>
                        <th>RAM</th>
                        <th>Disco C:</th>
                        <th>Disco D:</th>
                        <th>Estado</th>
                        <th>Ubicación</th>
                        <th>Acción</th>
                        <th>QR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result as $equipo): ?>
                        <tr>
                            <td><?= $equipo['tipo'] ?></td>
                            <td><?= $equipo['marca'] ?></td>
                            <td><?= $equipo['identificador'] ?></td>
                            <td><?= $equipo['asignado_a'] ?></td>
                            <td><?= $equipo['serial'] ?></td>
                            <td><?= $equipo['procesador'] ?></td>
                            <td><?= $equipo['ram'] ?></td>
                            <td><?= $equipo['disco'] ?></td>
                            <td><?= $equipo['disco2'] ?></td>
                            <td>
                                <?php
                                $clase = 'activo';

                                if ($equipo['estado'] == 'Inactivo') {
                                    $clase = 'Inactivo';
                                }

                                if ($equipo['estado'] == 'En reparación') {
                                    $clase = 'reparacion';
                                }

                                if ($equipo['estado'] == 'Dado de baja') {
                                    $clase = 'baja';
                                }
                                ?>

                                <span class="badge <?= $clase ?>">
                                    <?= $equipo['estado'] ?>
                                </span>
                            </td>
                            <td><?= $equipo['ubicacion'] ?></td>
                            <td style="display:flex; gap:8px; justify-content:center;">
                                <!-- EDITAR REDIRIGE -->
                                <a href="editar_equipos.php?id=<?= $equipo['id'] ?>">
                                    <button type="button" class="btn-update">
                                        Editar
                                    </button>
                                </a>
                                <!-- ELIMINAR -->
                                <form method="POST"
                                    onsubmit="return confirm('¿Desea eliminar este equipo?');">
                                    <input type="hidden"
                                        name="id_equipo"
                                        value="<?= $equipo['id'] ?>">
                                    <button type="submit"
                                        name="eliminar_equipo"
                                        class="btn-delete">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                            <td>

                                <a href="ver_qr.php?codigo=<?= $equipo['codigo_barras'] ?>" class="btn-qr">
                                    <i class='bx bx-barcode'></i> Ver QR
                                </a>


                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="paginacion">

                <?php
                $queryFiltros =
                    "&tipo=" . urlencode($tipo) .
                    "&marca=" . urlencode($marca) .
                    "&ubicacion=" . urlencode($ubicacion) .
                    "&identificador=" . urlencode($identificador);
                ?>

                <?php if ($pagina > 1): ?>

                    <a href="?pagina=<?= $pagina - 1 . $queryFiltros ?>">
                        Anterior
                    </a>

                <?php endif; ?>


                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>

                    <a
                        class="<?= ($pagina == $i) ? 'activo-pagina' : ''; ?>"
                        href="?pagina=<?= $i . $queryFiltros ?>">
                        <?= $i ?>
                    </a>

                <?php endfor; ?>


                <?php if ($pagina < $totalPaginas): ?>

                    <a href="?pagina=<?= $pagina + 1 . $queryFiltros ?>">
                        Siguiente
                    </a>

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