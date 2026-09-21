<?php
require '../bd/conn.php';

/* =====================================================
   SEGURIDAD DE SESIÓN
   ===================================================== */
$esHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ||
    (($_SERVER['SERVER_PORT'] ?? '') == 443)
);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $esHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

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

/* =====================================================
   HELPERS
   ===================================================== */
function e($valor): string {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =====================================================
   CATÁLOGOS (Arrays para los select automáticos)
   ===================================================== */
$tiposEquipo = [
    'PO' => 'Portátil', 'TO' => 'Todo en Uno', 'ES' => 'Escritorio',
    'IM' => 'Impresora', 'VI' => 'Videobeam', 'BR' => 'Bases de Refrigeración',
    'MS' => 'Mouse', 'TE' => 'Teclado', 'DI' => 'Diademas',
    'AU' => 'Auriculares', 'CE' => 'Celulares', 'MO' => 'Monitor',
];

$marcasEquipo = [
    'DE' => 'DELL', 'IN' => 'INPOWER', 'AS' => 'ASUS', 'HP' => 'HEWLETT-PACKARD', 
    'LE' => 'LENOVO', 'EP' => 'EPSON', 'CA' => 'CANON', 'GE' => 'Genius', 
    'OP' => 'OPPO', 'KA' => 'KALLEY', 'SA' => 'SAMSUNG', 'MA' => 'MAXELL', 
    'PA' => 'PANASONIC', 'AR' => 'ARCHTEX', 'XK' => 'XKIM', 'HA' => 'HAVIT', 
    'LO' => 'LOGITECH', 'WI' => 'WIT', 'HU' => 'HUAWEI', 'MT' => 'MOTOROLA', 
    'XI' => 'XIAOMI', 'SM' => 'SIN MARCA',
];

$ubicacionesEquipo = [
    'AXA Mortales', 'AXA Gastos Medicos', 'AXA IPS', 'Generales', 'Consultas', 
    'HDI', 'SURA', 'SURA IPS', 'SURA Gastos Medicos', 'Sistemas', 'Financiera', 
    'Talento Humano', 'Dirección Operativa', 'En Casa', 'Renting', 'Camaras', 'Bodega',
];

/* =====================================================
   CAPTURAR FILTROS Y VALIDAR
   ===================================================== */
$tipo          = trim((string)($_GET['tipo'] ?? ''));
$marca         = trim((string)($_GET['marca'] ?? ''));
$ubicacion     = trim((string)($_GET['ubicacion'] ?? ''));
$identificador = trim((string)($_GET['identificador'] ?? ''));

if ($tipo !== '' && !array_key_exists($tipo, $tiposEquipo)) { $tipo = ''; }
if ($marca !== '' && !array_key_exists($marca, $marcasEquipo)) { $marca = ''; }
if ($ubicacion !== '' && !in_array($ubicacion, $ubicacionesEquipo, true)) { $ubicacion = ''; }

$identificador = mb_substr($identificador, 0, 100);

/* =====================================================
   CONSTRUIR SQL DE BÚSQUEDA GENERAL
   ===================================================== */
$sqlWhere = " WHERE 1=1 ";
$params = [];

if ($tipo !== '') {
    $sqlWhere .= " AND tipo = :tipo";
    $params[':tipo'] = $tipo;
}
if ($marca !== '') {
    $sqlWhere .= " AND marca LIKE :marca";
    $params[':marca'] = "%$marca%";
}
if ($ubicacion !== '') {
    $sqlWhere .= " AND ubicacion LIKE :ubicacion";
    $params[':ubicacion'] = "%$ubicacion%";
}
if ($identificador !== '') {
    $sqlWhere .= " AND identificador = :identificador";
    $params[':identificador'] = $identificador;
}

/* =====================================================
   PAGINACIÓN
   ===================================================== */
$porPagina = 10;
$pagina = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?: 1;

if ($pagina < 1) {
    $pagina = 1;
}

$inicio = ($pagina - 1) * $porPagina;

// Contar total de registros
$sqlTotal = "SELECT COUNT(*) FROM equipos " . $sqlWhere;
$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalRegistros = (int) $stmtTotal->fetchColumn();
$totalPaginas = (int) ceil($totalRegistros / $porPagina);

if ($totalPaginas > 0 && $pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $inicio = ($pagina - 1) * $porPagina;
}

// Búsqueda de la matriz de equipos CON LÍMITES DE PAGINACIÓN
$sqlEquipos = "SELECT * FROM equipos " . $sqlWhere . " ORDER BY id ASC LIMIT :inicio, :porPagina";
$stmtEquipos = $conn->prepare($sqlEquipos);

foreach ($params as $key => $val) {
    $stmtEquipos->bindValue($key, $val);
}
$stmtEquipos->bindValue(':inicio', (int)$inicio, PDO::PARAM_INT);
$stmtEquipos->bindValue(':porPagina', (int)$porPagina, PDO::PARAM_INT);
$stmtEquipos->execute();
$equipos = $stmtEquipos->fetchAll(PDO::FETCH_ASSOC);

// Query string reutilizable para los enlaces de paginación
$queryFiltros = http_build_query([
    'tipo'          => $tipo,
    'marca'         => $marca,
    'ubicacion'     => $ubicacion,
    'identificador' => $identificador,
]);

/* =====================================================
   IDENTIFICADORES DISPONIBLES PARA EL SELECT DINÁMICO
   ===================================================== */
$sqlWhereIdentificador = " WHERE 1=1 ";
$paramsIdentificador = [];

if ($tipo !== '') {
    $sqlWhereIdentificador .= " AND tipo = :tipo";
    $paramsIdentificador[':tipo'] = $tipo;
}
if ($marca !== '') {
    $sqlWhereIdentificador .= " AND marca LIKE :marca";
    $paramsIdentificador[':marca'] = "%$marca%";
}
if ($ubicacion !== '') {
    $sqlWhereIdentificador .= " AND ubicacion LIKE :ubicacion";
    $paramsIdentificador[':ubicacion'] = "%$ubicacion%";
}

$stmtIdentificadores = $conn->prepare("
    SELECT DISTINCT identificador
    FROM equipos
    $sqlWhereIdentificador
    AND identificador IS NOT NULL AND identificador <> ''
    ORDER BY identificador
");
$stmtIdentificadores->execute($paramsIdentificador);
$identificadoresEquipo = $stmtIdentificadores->fetchAll(PDO::FETCH_COLUMN);

/* ==========================
DETALLE EQUIPO + HOJA VIDA POR ID Y SELECCION DESDE FILTROS
==========================*/
$id_equipo = $_GET['id'] ?? null;
$codigo = $_GET['codigo'] ?? null;
$equipo = null;
$historial = [];

if ($codigo) {
    $stmt = $conn->prepare("SELECT * FROM equipos WHERE codigo_barras = ?");
    $stmt->execute([$codigo]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_equipo = $equipo['id'] ?? null;
} elseif ($id_equipo) {
    $eq = $conn->prepare("SELECT * FROM equipos WHERE id=?");
    $eq->execute([$id_equipo]);
    $equipo = $eq->fetch(PDO::FETCH_ASSOC);
}

if ($id_equipo) {
    $mant = $conn->prepare("SELECT * FROM mantenimientos WHERE equipo_id=? ORDER BY fecha ASC");
    $mant->execute([$id_equipo]);
    $historial = $mant->fetchAll(PDO::FETCH_ASSOC);
}

// REGISTRO DE MANTENIMIENTOS Y REPUESTOS POSTERIORES
if (!empty($_POST['nombre_repuesto'])) {
    foreach ($_POST['nombre_repuesto'] as $i => $nombre) {
        $tipoRep = $_POST['tipo_repuesto'][$i];
        $serial = $_POST['serial_repuesto'][$i];
        $capacidad = $_POST['capacidad_repuesto'][$i];
        $descripcion = $_POST['descripcion_repuesto'][$i];
        $valor = $_POST['valor_repuesto'][$i];
        $cantidad = $_POST['cantidad'][$i];

        if (!empty($nombre) && !empty($tipoRep) && !empty($cantidad)) {
            $buscar = $conn->prepare("SELECT id, stock FROM repuestos WHERE nombre = ? AND tipo = ? AND capacidad = ?");
            $buscar->execute([$nombre, $tipoRep, $capacidad]);
            $existe = $buscar->fetch(PDO::FETCH_ASSOC);

            if ($existe) {
                $update = $conn->prepare("UPDATE repuestos SET stock = stock + ? WHERE id = ?");
                $update->execute([$cantidad, $existe['id']]);
                $repuesto_id = $existe['id'];
            } else {
                $insert = $conn->prepare("INSERT INTO repuestos (nombre, serial, capacidad, tipo, descripcion, valor, stock, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'Disponible')");
                $insert->execute([$nombre, $serial, $capacidad, $tipoRep, $descripcion, $valor, $cantidad]);
                $repuesto_id = $conn->lastInsertId();
            }

            $id_mantenimiento = $conn->lastInsertId();
            $rel = $conn->prepare("INSERT INTO mantenimiento_repuestos (mantenimiento_id, repuesto_id, cantidad) VALUES (?, ?, ?)");
            $rel->execute([$id_mantenimiento, $repuesto_id, $cantidad]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Hoja de Vida Equipos</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/hoja_vida_equipos.css">
    <style>
        * { box-sizing: border-box; }
        
        .Bodega { background: #415885; color: white; }

        /* =====================================================
           DISEÑO DE FILTROS RESPONSIVOS
           ===================================================== */
        .filtros {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            gap: 12px;
            margin-top: 14px;
        }

        .filtros select {
            flex: 1 1 190px;
            min-width: 0;
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #d7dce3;
            font-size: 14px;
            background: #fff;
        }

        .filtros .btn-search {
            flex: 1 1 190px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #60a5fa, #2563eb);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            padding: 10px 16px;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .filtros .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, .35);
        }

        @media (max-width: 560px) {
            .filtros select, .filtros .btn-search {
                flex: 1 1 100%;
            }
        }

        /* =====================================================
           TABLAS RESPONSIVAS
           ===================================================== */
        .tabla-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #415885 #e9edf5;
        }
        .tabla-scroll::-webkit-scrollbar { height: 8px; }
        .tabla-scroll::-webkit-scrollbar-track { background: #e9edf5; border-radius: 10px; }
        .tabla-scroll::-webkit-scrollbar-thumb { background: #415885; border-radius: 10px; }

        .tabla-equipos {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .tabla-equipos th,
        .tabla-equipos td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .tabla-equipos th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
        }

        @media (max-width: 680px) {
            .tabla-scroll { overflow: visible; }
            .tabla-equipos { min-width: 0; width: 100%; display: block; }
            .tabla-equipos thead {
                position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap;
            }
            .tabla-equipos tbody { display: block; }
            .tabla-equipos tbody tr {
                display: block; margin-bottom: 14px; padding: 12px 14px;
                border: 1px solid #e5e9f0; border-radius: 12px; background: #fff;
                box-shadow: 0 2px 6px rgba(15, 23, 42, .06);
            }
            .tabla-equipos tbody td {
                display: flex; align-items: center; justify-content: space-between;
                gap: 12px; white-space: normal; text-align: right;
                padding: 8px 0; border-bottom: 1px dashed #eef1f5; min-width: 0;
            }
            .tabla-equipos tbody td:last-child { border-bottom: none; }
            .tabla-equipos tbody td::before {
                content: attr(data-label); font-weight: 700; font-size: 12px;
                text-transform: uppercase; color: #64748b; text-align: left; flex-shrink: 0;
            }
        }
        
        .btn-visualizar {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-visualizar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }
/* =========================================================
   PAGINACIÓN
   ========================================================= */
.paginacion {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 18px;
    padding: 10px 5px;
}

.paginacion a {
    min-width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 9px;
    background: #fff;
    color: #111827;
    border-radius: 7px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: transform .2s ease, background .2s ease;
}

.paginacion a:hover {
    transform: translateY(-1px);
    background: #e5e7eb;
}

.paginacion .activo-pagina {
    background: #2563eb;
    color: #fff;
}

.paginacion .indicador-paginacion {
    width: 100%;
    text-align: center;
    margin-top: 5px;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    opacity: .85;
}

/* En pantallas angostas, la paginación se "transforma":
   solo se muestran Anterior / Siguiente y la página activa,
   ocultando el resto de números para no saturar la pantalla. */
@media (max-width: 680px) {
    .paginacion a:not(.pag-anterior):not(.pag-siguiente):not(.activo-pagina) {
        display: none;
    }

    .paginacion a {
        min-width: 40px;
        height: 40px;
        font-size: 14px;
    }
}

/* =========================================================
   RESPONSIVE GENERAL
   ========================================================= */
@media (max-width: 768px) {
    .container {
        padding: 20px 15px;
    }

    .card {
        padding: 16px;
    }
}

@media (min-width: 1600px) {
    .container {
        max-width: 1680px;
    }

    .tabla-equipos th,
    .tabla-equipos td {
        font-size: 15px;


    }
}
    </style>
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

    <div class="main-content">
        <div class="card">
            <h2>Consulta Hoja de Vida</h2>
            
            <form method="GET">
                <div class="filtros">
                    <select name="tipo">
                        <option value="" <?= $tipo === '' ? 'selected' : '' ?>>Seleccione Tipo</option>
                        <?php foreach ($tiposEquipo as $cod => $nom): ?>
                            <option value="<?= e($cod) ?>" <?= $tipo === $cod ? 'selected' : '' ?>><?= e($nom) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="marca">
                        <option value="" <?= $marca === '' ? 'selected' : '' ?>>Seleccione Marca</option>
                        <?php foreach ($marcasEquipo as $cod => $nom): ?>
                            <option value="<?= e($cod) ?>" <?= $marca === $cod ? 'selected' : '' ?>><?= e($nom) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="ubicacion">
                        <option value="" <?= $ubicacion === '' ? 'selected' : '' ?>>Seleccione Ubicación</option>
                        <?php foreach ($ubicacionesEquipo as $ubi): ?>
                            <option value="<?= e($ubi) ?>" <?= $ubicacion === $ubi ? 'selected' : '' ?>><?= e($ubi) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="identificador">
                        <option value="" <?= $identificador === '' ? 'selected' : '' ?>>Todos los identificadores</option>
                        <?php foreach ($identificadoresEquipo as $ident): ?>
                            <option value="<?= e($ident) ?>" <?= $identificador === $ident ? 'selected' : '' ?>><?= e($ident) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button class="btn btn-search">Buscar</button>
                </div>
            </form>
        </div>

        <?php $hayFiltros = ($tipo != '' || $marca != '' || $ubicacion != '' || $identificador != ''); ?>

        <?php if ($hayFiltros && !$id_equipo): ?>
            <div class="card">
                <h2>Equipo(s) Encontrado(s)</h2>
                <?php if (count($equipos) > 0): ?>
                    <div class="tabla-scroll">
                        <table class="tabla-equipos">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Marca</th>
                                    <th>ID</th>
                                    <th>Ubicación</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipos as$e): ?>
                                    <tr>
                                        <td data-label="Tipo"><?= e($e['tipo']) ?></td>
                                        <td data-label="Marca"><?= e($e['marca']) ?></td>
                                        <td data-label="ID"><?= e($e['identificador']) ?></td>
                                        <td data-label="Ubicación"><?= e($e['ubicacion']) ?></td>
                                        <td data-label="Acción">
                                            <a href="hoja_vida_equipos.php?id=<?= $e['id'] ?>" class="btn-visualizar">
                                                Visualizar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- AQUÍ SE RENDERIZA LA PAGINACIÓN -->
                    <div class="paginacion">
                        <?php
                        $paginasPorBloque = 10;
                        $bloqueActual = (int) ceil($pagina / $paginasPorBloque);$primeraPagina = (($bloqueActual - 1) *$paginasPorBloque) + 1;
                        $ultimaPagina = min($primeraPagina + $paginasPorBloque - 1,$totalPaginas);
                        ?>

                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=<?= ($pagina - 1) ?>&<?= e($queryFiltros) ?>">Anterior</a>
                        <?php endif; ?>

                        <?php for ($i = $primeraPagina; $i <= $ultimaPagina; $i++): ?>
                            <a class="<?= ($pagina == $i) ? 'activo-pagina' : ''; ?>" href="?pagina=<?= $i ?>&<?= e($queryFiltros) ?>"><?= $i ?></a>
                        <?php endfor; ?>

                        <?php if ($pagina <$totalPaginas): ?>
                            <a href="?pagina=<?= ($pagina + 1) ?>&<?= e($queryFiltros) ?>">Siguiente</a>
                        <?php endif; ?>

                        <?php if ($totalPaginas > 0): ?>
                            <span class="indicador-paginacion">
                                Páginas <?= $primeraPagina ?> - <?= $ultimaPagina ?> de <?= $totalPaginas ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align:center; padding: 20px;">
                        No se encontraron equipos con los filtros seleccionados.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($equipo): ?>
            <div class="card">
                <h2>Hoja de Vida Equipo <?= e($equipo['identificador']) ?></h2>
                <h3>Ficha Técnica del Equipo</h3>

                <div class="tabla-scroll">
                    <table class="tabla-equipos" style="margin-bottom:25px;">
                        <tbody>
                            <tr><td data-label="Campo" style="font-weight:600; width:30%;">Tipo</td><td data-label="Información"><?= e($equipo['tipo']) ?></td></tr>
                            <tr><td data-label="Campo" style="font-weight:600;">Marca</td><td data-label="Información"><?= e($equipo['marca']) ?></td></tr>
                            <tr><td data-label="Campo" style="font-weight:600;">Identificador</td><td data-label="Información"><?= e($equipo['identificador']) ?></td></tr>
                            <tr><td data-label="Campo" style="font-weight:600;">Ubicación</td><td data-label="Información"><?= e($equipo['ubicacion']) ?></td></tr>
                            <tr><td data-label="Campo" style="font-weight:600;">Asignado a</td><td data-label="Información"><?= e($equipo['asignado_a']) ?></td></tr>
                            <tr><td data-label="Campo" style="font-weight:600;">Serial</td><td data-label="Información"><?= e($equipo['serial']) ?: 'No registrado' ?></td></tr>
                            <tr><td data-label="Campo" style="font-weight:600;">Procesador</td><td data-label="Información"><?= e($equipo['procesador']) ?: 'No registrado' ?></td></tr>
                            <tr><td data-label="Campo" style="font-weight:600;">Memoria RAM</td><td data-label="Información"><?= e($equipo['ram']) ?: 'No registrada' ?></td></tr>
                            <tr><td data-label="Campo" style="font-weight:600;">Disco C:</td><td data-label="Información"><?= e($equipo['disco']) ?: 'No registrado' ?></td></tr>
                            <tr><td data-label="Campo" style="font-weight:600;">Disco D:</td><td data-label="Información"><?= e($equipo['disco2']) ?: 'No registrado' ?></td></tr>
                            <tr>
                                <td data-label="Campo" style="font-weight:600;">Estado Actual</td>
                                <td data-label="Información">
                                    <?php
                                    $badgeEstado = 'activo';
                                    if ($equipo['estado'] == 'En reparación') $badgeEstado = 'reparacion';
                                    if ($equipo['estado'] == 'Dado de baja')$badgeEstado = 'baja';
                                    if ($equipo['estado'] == 'Bodega')$badgeEstado = 'Bodega';
                                    ?>
                                    <span class="badgeEstado <?= $badgeEstado ?>"><?= e($equipo['estado']) ?></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3>Matriz de Observaciones y Mantenimientos</h3>
                <div class="tabla-scroll">
                    <table class="tabla-equipos" id="tablaHV">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Responsable</th>
                                <th>Tipo de Mantenimiento</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial as$h): ?>
                                <tr>
                                    <td data-label="Fecha"><?= e($h['fecha']) ?></td>
                                    <td data-label="Responsable"><?= e($h['responsable']) ?></td>
                                    <td data-label="Tipo Mantenimiento"><?= e($h['tipo_mantenimiento']) ?></td>
                                    <td data-label="Descripción"><?= e($h['descripcion']) ?></td>
                                    <td data-label="Estado">
                                        <?php
                                        $bEst = 'activo';
                                        if ($h['estado'] == 'Bodega')$bEst = 'Bodega';
                                        if ($h['estado'] == 'En reparación') $bEst = 'reparacion';
                                        if ($h['estado'] == 'Dado de baja')$bEst = 'baja';
                                        ?>
                                        <span class="badgeEstado <?= $bEst ?>"><?= e($h['estado']) ?></span>
                                    </td>
                                    <td data-label="Observaciones"><?= e($h['observaciones']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button onclick="descargarPDF()" class="btn btn-pdf" style="margin-top: 15px;">
                    Descargar Hoja de Vida PDF
                </button>
            </div>

            <!-- FORMULARIO DE MANTENIMIENTO INTACTO -->
            <div class="card">
                <h3>Registrar Nuevo Mantenimiento</h3>
                <form action="../controller/procesar_mantenimiento.php" method="POST">
                    <input type="hidden" name="equipo_id" value="<?= $equipo['id']; ?>">
                    <label>Fecha del mantenimiento</label>
                    <input type="date" name="fecha" required>
                    <label>Responsable</label>
                    <input type="text" name="responsable" required>
                    <label>Tipo de Mantenimiento</label>
                    <select name="tipo_mantenimiento" id="tipo_mantenimiento" required>
                        <option value="">Seleccione</option>
                        <option value="Preventivo">Preventivo</option>
                        <option value="Correctivo">Correctivo</option>
                    </select>
                    <label>Descripción del mantenimiento</label>
                    <textarea name="descripcion" required placeholder="Detalle del mantenimiento realizado"></textarea>
                    <label>Estado del equipo posterior al mantenimiento</label>
                    <select name="estado" required>
                        <option value="">Seleccione</option>
                        <option value="Activo">Activo</option>
                        <option value="En reparación">En reparación</option>
                        <option value="Dado de baja">Dado de baja</option>
                    </select>
                    <label>Observaciones</label>
                    <textarea name="observaciones" placeholder="Hallazgos, recomendaciones, novedades..."></textarea>

                    <button type="submit" class="btn btn-save">Guardar mantenimiento</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
    <script>
        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                document.body.classList.toggle('sidebar-open');
            } else {
                document.body.classList.toggle('sidebar-collapsed');
            }
        }

        function descargarPDF() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let identificador = "<?= e($equipo['identificador'] ?? '') ?>";
            let tipo = "<?= e($equipo['tipo'] ?? '') ?>";
            let marca = "<?= e($equipo['marca'] ?? '') ?>";
            let ubicacion = "<?= e($equipo['ubicacion'] ?? '') ?>";
            let asignado = "<?= e($equipo['asignado_a'] ?? '') ?>";
            let serial = "<?= e($equipo['serial'] ?? '') ?>";
            let procesador = "<?= e($equipo['procesador'] ?? '') ?>";
            let ram = "<?= e($equipo['ram'] ?? '') ?>";
            let disco = "<?= e($equipo['disco'] ?? '') ?>";
            let estado = "<?= e($equipo['estado'] ?? '') ?>";

            doc.setFontSize(16);
            doc.text("HOJA DE VIDA DEL EQUIPO", 50, 15);
            doc.setFontSize(10);
            
            let y = 30;
            doc.text(`Identificador: ${identificador}`, 10, y); y += 7;
            doc.text(`Tipo: ${tipo}`, 10, y); y += 7;
            doc.text(`Marca: ${marca}`, 10, y); y += 7;
            doc.text(`Ubicación: ${ubicacion}`, 10, y); y += 7;
            doc.text(`Asignado a: ${asignado}`, 10, y); y += 7;
            doc.text(`Serial: ${serial}`, 10, y); y += 7;
            doc.text(`Procesador: ${procesador}`, 10, y); y += 7;
            doc.text(`RAM: ${ram}`, 10, y); y += 7;
            doc.text(`Disco: ${disco}`, 10, y); y += 7;
            doc.text(`Estado: ${estado}`, 10, y); y += 10;

            let filas = [];
            document.querySelectorAll("#tablaHV tbody tr").forEach(tr => {
                let row = [];
                tr.querySelectorAll("td").forEach(td => {
                    let text = td.innerText.trim();
                    row.push(text);
                });
                if(row.length > 0) filas.push(row);
            });

            doc.autoTable({
                startY: y,
                head: [['Fecha', 'Responsable', 'Tipo', 'Descripción', 'Estado', 'Observaciones']],
                body: filas
            });

            doc.save("Hoja_Vida_" + identificador + ".pdf");
        }


    </script>
</body>
</html>