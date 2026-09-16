<?php

/* =====================================================
   SEGURIDAD DE SESIÓN
   Estas directivas deben fijarse ANTES de session_start()
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
    'secure'   => $esHttps,   // Solo viaja por HTTPS cuando el sitio esté en HTTPS
    'httponly' => true,       // JS no puede leer la cookie de sesión
    'samesite' => 'Lax',      // Mitiga CSRF cross-site básico
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_start();

require '../bd/conn.php';

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

/* =====================================================
   CABECERAS DE SEGURIDAD (defensa en profundidad)
   ===================================================== */

// Evitar cache para que no se pueda volver atrás después de cerrar sesión
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

/* Nonce único por petición: permite un CSP estricto (sin 'unsafe-inline')
   tanto para <style> como para <script>. */
$cspNonce = base64_encode(random_bytes(16));

header(
    "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'nonce-$cspNonce'; " .
        "style-src 'self' 'nonce-$cspNonce' https://fonts.googleapis.com https://unpkg.com; " .
        "font-src 'self' https://fonts.gstatic.com https://unpkg.com data:; " .
        "img-src 'self' data:; " .
        "connect-src 'self'; " .
        "frame-ancestors 'none'; " .
        "base-uri 'self'; " .
        "form-action 'self'; " .
        "object-src 'none'"
);

/* =====================================================
   HELPERS
   ===================================================== */

/** Escapa cualquier valor para salida segura en HTML (evita XSS). */
function e($valor): string
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/* Token CSRF de sesión, para proteger acciones que modifican datos (eliminar). */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* =====================================================
   CATÁLOGOS (fuente única de verdad)
   Estos mismos arreglos alimentan:
     1) Los <select> de los filtros
     2) La tabla de la Guía de Abreviaciones
   Así ambos quedan siempre sincronizados.
   ===================================================== */

$tiposEquipo = [
    'PO' => 'Portátil',
    'TO' => 'Todo en Uno',
    'ES' => 'Escritorio',
    'IM' => 'Impresora',
    'VI' => 'Videobeam',
    'BR' => 'Bases de Refrigeración',
    'MS' => 'Mouse',
    'TE' => 'Teclado',
    'DI' => 'Diademas',
    'AU' => 'Auriculares',
    'CE' => 'Celulares',
    'MO' => 'Monitor',
];

$marcasEquipo = [
    'DELL'  => 'Dell',
    'INP'   => 'INPOWER',
    'AS'    => 'Asus',
    'HP'    => 'Hewlett-Packard',
    'LE'    => 'Lenovo',
    'EP'    => 'EPSON',
    'CA'    => 'CANON',
    'GE'    => 'Genius',
    'OP'    => 'OPPO',
    'KA'    => 'KALLEY',
    'SAM'   => 'SAMSUNG',
    'MAX'   => 'MAXELL',
    'PAN'   => 'PANASONIC',
    'ARC'   => 'ARCHTEX',
    'XKIM'  => 'XKIM',
    'HAVIT' => 'HAVIT',
    'LOG'   => 'Logitech',
    'WIT'   => 'WIT',
    'S'     => 'Samsung',
    'H'     => 'Huawei',
    'MOT'   => 'Motorola',
    'X'     => 'Xiaomi',
    'GEN'   => 'Genérico',
];

$ubicacionesEquipo = [
    'AXA Mortales',
    'AXA Gastos Medicos',
    'AXA IPS',
    'Generales',
    'Consultas',
    'HDI',
    'SURA',
    'SURA IPS',
    'SURA Gastos Medicos',
    'Sistemas',
    'Financiera',
    'Talento Humano',
    'Dirección Operativa',
    'En Casa',
    'Renting',
    'Camaras',
    'Bodega',
];

/* =====================================================
   ELIMINAR (con protección CSRF + validación de ID)
   ===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_equipo'])) {

    $tokenRecibido = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $tokenRecibido)) {
        http_response_code(403);
        exit('Solicitud inválida o expirada. Recarga la página e inténtalo de nuevo.');
    }

    $id_equipo = filter_input(INPUT_POST, 'id_equipo', FILTER_VALIDATE_INT);

    if ($id_equipo === false || $id_equipo === null || $id_equipo <= 0) {
        http_response_code(400);
        exit('Identificador de equipo inválido.');
    }

    $stmt = $conn->prepare("DELETE FROM equipos WHERE id = ?");
    $stmt->execute([$id_equipo]);

    header("Location: ver_equipos.php");
    exit();
}


/* =====================================================
   FILTROS
   Se validan contra los catálogos anteriores (whitelist):
   así solo se aceptan valores que realmente existen en los
   <select>, sin importar qué se manipule en la URL.
   ===================================================== */

$tipo          = trim((string)($_GET['tipo'] ?? ''));
$marca         = trim((string)($_GET['marca'] ?? ''));
$ubicacion     = trim((string)($_GET['ubicacion'] ?? ''));
$identificador = trim((string)($_GET['identificador'] ?? ''));
$codigo        = trim((string)($_GET['codigo'] ?? ''));

if ($tipo !== '' && !array_key_exists($tipo, $tiposEquipo)) {
    $tipo = '';
}

if ($marca !== '' && !array_key_exists($marca, $marcasEquipo)) {
    $marca = '';
}

if ($ubicacion !== '' && !in_array($ubicacion, $ubicacionesEquipo, true)) {
    $ubicacion = '';
}

// Límite razonable de longitud para campos libres (defensa en profundidad)
$identificador = mb_substr($identificador, 0, 100);
$codigo        = mb_substr($codigo, 0, 100);

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
    $sqlWhere .= " AND identificador LIKE :identificador";
    $params[':identificador'] = "%$identificador%";
}

if ($codigo !== '') {
    $sqlWhere .= " AND codigo_barras = :codigo";
    $params[':codigo'] = $codigo;
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


/* CONTAR TOTAL REGISTROS FILTRADOS */

$sqlTotal = "SELECT COUNT(*) FROM equipos " . $sqlWhere;

$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->execute($params);

$totalRegistros = (int) $stmtTotal->fetchColumn();

$totalPaginas = (int) ceil($totalRegistros / $porPagina);

// Si piden una página fuera de rango, la ajustamos al máximo válido
if ($totalPaginas > 0 && $pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $inicio = ($pagina - 1) * $porPagina;
}

/* =====================================================
   CÓDIGOS DE BARRAS DISPONIBLES (para el select de búsqueda)
   ===================================================== */

$codigos = $conn->query("
    SELECT codigo_barras
    FROM equipos
    WHERE codigo_barras IS NOT NULL
    AND codigo_barras <> ''
    ORDER BY codigo_barras
")->fetchAll(PDO::FETCH_COLUMN);


/* CONSULTA CON LIMIT (siempre parametrizada, sin concatenar valores del usuario) */

$sql = "
SELECT *
FROM equipos
$sqlWhere
ORDER BY id ASC
LIMIT :inicio, :porPagina
";

$stmt = $conn->prepare($sql);

// BIND FILTROS
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

// BIND PAGINACIÓN
$stmt->bindValue(':inicio', (int)$inicio, PDO::PARAM_INT);
$stmt->bindValue(':porPagina', (int)$porPagina, PDO::PARAM_INT);

$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Query string reutilizable para los enlaces de paginación,
   construida de forma segura con http_build_query(). */
$queryFiltros = http_build_query([
    'tipo'          => $tipo,
    'marca'         => $marca,
    'ubicacion'     => $ubicacion,
    'identificador' => $identificador,
    'codigo'        => $codigo,
]);

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
<style nonce="<?= e($cspNonce) ?>">
    * {
        box-sizing: border-box;
    }

    .btn-hoja-vida {
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

    .btn-hoja-vida:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, .25);
    }

    /* FORZAR ESTILO DEL BOTÓN HOJA DE VIDA */
    td a.btn-hoja-vida {
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
        white-space: nowrap;
    }

    /* HOVER */
    td a.btn-hoja-vida:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, .25);
    }

    .Inactivo {
        background: #415885;
        color: white;
    }

    /* =====================================================
       LAYOUT GENERAL RESPONSIVO
       ===================================================== */

    .container {
        width: 100%;
        max-width: 1500px;   /* que no se estire infinito en monitores 4K */
        margin: 0 auto;
        padding: 20px clamp(12px, 3vw, 32px);
    }

    .card {
        max-width: 100%;
        box-sizing: border-box;
        padding: clamp(14px, 2.5vw, 24px);
        margin-bottom: 20px;
    }

    .card h2 {
        font-size: clamp(1.15rem, 1.6vw + 0.7rem, 1.6rem);
    }

    /* =====================================================
       FILTROS
       ===================================================== */

    .filtros {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 12px;
        margin-top: 14px;
    }

    .filtros select,
    .filtros input[type="text"] {
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
        .filtros select,
        .filtros input[type="text"],
        .filtros .btn-search {
            flex: 1 1 100%;
        }
    }

    /* =====================================================
       GLOSARIO COLAPSABLE
       ===================================================== */

    .glosario {
        padding: 16px 20px;
    }

    .glosario-toggle {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;

        background: none;
        border: none;
        margin: 0;
        padding: 0;

        font: inherit;
        color: inherit;
        text-align: left;
        cursor: pointer;
    }

    .glosario-toggle h3 {
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 8px;
    }

    .glosario-toggle .bx-chevron-down {
        font-size: 22px;
        flex-shrink: 0;
        transition: transform 0.3s ease;
    }

    .glosario.abierto .glosario-toggle .bx-chevron-down {
        transform: rotate(180deg);
    }

    .glosario-hint {
        font-size: 12px;
        opacity: .65;
        font-weight: 400;
    }

    .glosario-content {
        display: grid;
        grid-template-rows: 0fr;
        transition: grid-template-rows 0.35s ease;
    }

    .glosario.abierto .glosario-content {
        grid-template-rows: 1fr;
    }

    .glosario-inner {
        overflow: hidden;
        min-height: 0;
    }

    .glosario-body {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        padding-top: 16px;
    }

    .glosario-grupo h4 {
        margin: 0 0 10px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: .04em;
        opacity: .75;
    }

    .glosario-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .glosario-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #eef2f9;
        color: #000000;
        font-size: 12.5px;
        line-height: 1.2;
        white-space: nowrap;
    }

    .glosario-chip b {
        color: #2563eb;
        font-weight: 700;
    }

    @media (max-width: 600px) {
        .glosario-body {
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .glosario-toggle h3 {
            font-size: 15px;
        }
    }

    /* =====================================================
       TABLA DE EQUIPOS
       Escritorio / tablet: scroll horizontal contenido.
       Móvil angosto: se transforma en tarjetas apiladas.
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

    .tabla-scroll::-webkit-scrollbar {
        height: 8px;
    }

    .tabla-scroll::-webkit-scrollbar-track {
        background: #e9edf5;
        border-radius: 10px;
    }

    .tabla-scroll::-webkit-scrollbar-thumb {
        background: #415885;
        border-radius: 10px;
    }

    .tabla-equipos {
        width: 100%;
        min-width: 1100px;
        border-collapse: collapse;
    }

    .tabla-equipos th,
    .tabla-equipos td {
        white-space: nowrap;
        padding: 10px 12px;
    }

    .tabla-equipos td:nth-child(4),
    .tabla-equipos td:nth-child(5) {
        white-space: normal;
        min-width: 130px;
    }

    @media (max-width: 900px) {
        .tabla-equipos {
            min-width: 900px;
        }
    }

    /* ----- Vista en tarjetas para pantallas angostas ----- */
    @media (max-width: 680px) {
        .tabla-scroll {
            overflow: visible;
        }

        .tabla-equipos {
            min-width: 0;
            width: 100%;
            display: block;
        }

        .tabla-equipos thead {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
        }

        .tabla-equipos tbody {
            display: block;
        }

        .tabla-equipos tbody tr {
            display: block;
            margin-bottom: 14px;
            padding: 12px 14px;
            border: 1px solid #e5e9f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(15, 23, 42, .06);
        }

        .tabla-equipos tbody td {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            white-space: normal;
            text-align: right;
            padding: 8px 0;
            border-bottom: 1px dashed #eef1f5;
            min-width: 0;
        }

        .tabla-equipos tbody td:last-child {
            border-bottom: none;
        }

        .tabla-equipos tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: #64748b;
            text-align: left;
            flex-shrink: 0;
        }

        .tabla-equipos tbody td[data-label="Acción"] {
            justify-content: flex-end;
        }
    }

    /* =====================================================
       PAGINACIÓN: que envuelva bien en pantallas pequeñas
       ===================================================== */

    .paginacion {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 16px;
    }

    /* =====================================================
       MONITORES GRANDES: un poco más de aire y tipografía
       ===================================================== */
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

<body>
    <!-- Boton Hamburguesa -->
    <button class="toggle-btn" id="btnToggleSidebar" type="button" aria-label="Abrir/cerrar menú">
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
    <div class="container">
        <div class="card">
            <h2>Inventario de Equipos</h2>
            <!--  FILTROS -->
            <form method="GET" class="filtros">
                <select name="tipo">
                    <option value="" <?= $tipo === '' ? 'selected' : '' ?>>Seleccione Tipo</option>
                    <?php foreach ($tiposEquipo as $codigo_ => $nombre_): ?>
                        <option value="<?= e($codigo_) ?>" <?= $tipo === $codigo_ ? 'selected' : '' ?>>
                            <?= e($nombre_) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- MARCA -->
                <select name="marca">
                    <option value="" <?= $marca === '' ? 'selected' : '' ?>>Seleccione Marca</option>
                    <?php foreach ($marcasEquipo as $codigoMarca_ => $nombreMarca_): ?>
                        <option value="<?= e($codigoMarca_) ?>" <?= $marca === $codigoMarca_ ? 'selected' : '' ?>>
                            <?= e($nombreMarca_) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- UBICACION -->
                <select name="ubicacion">
                    <option value="" <?= $ubicacion === '' ? 'selected' : '' ?>>Seleccione Ubicación</option>
                    <?php foreach ($ubicacionesEquipo as $ubicacion_): ?>
                        <option value="<?= e($ubicacion_) ?>" <?= $ubicacion === $ubicacion_ ? 'selected' : '' ?>>
                            <?= e($ubicacion_) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="identificador" placeholder="Identificador" value="<?= e($identificador) ?>" maxlength="100">

                <button class="btn-search" type="submit">Buscar</button>

            </form>
        </div>

        <!-- GLOSARIO -->
        <div class="card glosario" id="glosario">
            <button type="button" class="glosario-toggle" id="glosarioToggleBtn" aria-expanded="false" aria-controls="glosario-content">
                <h3>Guía de Abreviaciones <span class="glosario-hint">(clic para ver)</span></h3>
                <i class='bx bx-chevron-down'></i>
            </button>

            <div class="glosario-content" id="glosario-content">
                <div class="glosario-inner">
                    <div class="glosario-body">
                        <div class="glosario-grupo">
                            <h4>Tipos de equipo</h4>
                            <div class="glosario-chips">
                                <?php foreach ($tiposEquipo as $codigoTipo_ => $nombreTipo_): ?>
                                    <span class="glosario-chip"><b><?= e($codigoTipo_) ?></b> <?= e($nombreTipo_) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="glosario-grupo">
                            <h4>Marcas</h4>
                            <div class="glosario-chips">
                                <?php foreach ($marcasEquipo as $codigoMarca2_ => $nombreMarca2_): ?>
                                    <span class="glosario-chip"><b><?= e($codigoMarca2_) ?></b> <?= e($nombreMarca2_) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA -->
        <div class="card">
            <div class="tabla-scroll">
                <table class="tabla-equipos">
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
                            <th>Hoja de Vida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($result)): ?>
                            <tr>
                                <td colspan="13" style="text-align:center; padding: 24px;">
                                    No se encontraron equipos con los filtros seleccionados.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($result as $equipo): ?>
                            <?php
                                $idEquipo = (int)$equipo['id'];

                                $clase = 'activo';
                                if ($equipo['estado'] == 'Inactivo') {
                                    $clase = 'Inactivo';
                                } elseif ($equipo['estado'] == 'En reparación') {
                                    $clase = 'reparacion';
                                } elseif ($equipo['estado'] == 'Dado de baja') {
                                    $clase = 'baja';
                                }
                            ?>
                            <tr>
                                <td data-label="Tipo"><?= e($equipo['tipo']) ?></td>
                                <td data-label="Marca"><?= e($equipo['marca']) ?></td>
                                <td data-label="ID"><?= e($equipo['identificador']) ?></td>
                                <td data-label="Asignado"><?= e($equipo['asignado_a']) ?></td>
                                <td data-label="Serial"><?= e($equipo['serial']) ?></td>
                                <td data-label="Procesador"><?= e($equipo['procesador']) ?></td>
                                <td data-label="RAM"><?= e($equipo['ram']) ?></td>
                                <td data-label="Disco C:"><?= e($equipo['disco']) ?></td>
                                <td data-label="Disco D:"><?= e($equipo['disco2']) ?></td>
                                <td data-label="Estado">
                                    <span class="badge <?= e($clase) ?>">
                                        <?= e($equipo['estado']) ?>
                                    </span>
                                </td>
                                <td data-label="Ubicación"><?= e($equipo['ubicacion']) ?></td>
                                <td data-label="Acción" style="display:flex; gap:8px; justify-content:center;">
                                    <!-- EDITAR REDIRIGE -->
                                    <a href="editar_equipos.php?id=<?= $idEquipo ?>">
                                        <button type="button" class="btn-update">
                                            Editar
                                        </button>
                                    </a>
                                    <!-- ELIMINAR -->
                                    <form method="POST" class="form-eliminar">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="id_equipo" value="<?= $idEquipo ?>">
                                        <button type="submit" name="eliminar_equipo" class="btn-delete">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                                <td data-label="Hoja de Vida">
                                    <a href="hoja_vida_equipos.php?codigo=<?= urlencode($equipo['codigo_barras'] ?? '') ?>" class="btn-hoja-vida">
                                        <i class='bx bx-barcode'></i> Ver Hoja de Vida
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="paginacion">

                <?php
                /*
                ==========================================
                PAGINACIÓN POR BLOQUES DE 10
                ==========================================
                */

                $paginasPorBloque = 10;

                $bloqueActual = (int) ceil($pagina / $paginasPorBloque);

                $primeraPagina = (($bloqueActual - 1) * $paginasPorBloque) + 1;

                $ultimaPagina = min(
                    $primeraPagina + $paginasPorBloque - 1,
                    $totalPaginas
                );
                ?>

                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?= ($pagina - 1) ?>&<?= e($queryFiltros) ?>">
                        Anterior
                    </a>
                <?php endif; ?>

                <?php for ($i = $primeraPagina; $i <= $ultimaPagina; $i++): ?>
                    <a
                        class="<?= ($pagina == $i) ? 'activo-pagina' : ''; ?>"
                        href="?pagina=<?= $i ?>&<?= e($queryFiltros) ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagina < $totalPaginas): ?>
                    <a href="?pagina=<?= ($pagina + 1) ?>&<?= e($queryFiltros) ?>">
                        Siguiente
                    </a>
                <?php endif; ?>

                <?php if ($totalPaginas > 0): ?>
                    <span class="indicador-paginacion">
                        Páginas <?= $primeraPagina ?> - <?= $ultimaPagina ?>
                        de <?= $totalPaginas ?>
                    </span>
                <?php endif; ?>

            </div>
        </div>

    </div>

    <script nonce="<?= e($cspNonce) ?>">
        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                // MÓVIL
                document.body.classList.toggle('sidebar-open');
            } else {
                // ESCRITORIO
                document.body.classList.toggle('sidebar-collapsed');
            }
        }

        document.getElementById('btnToggleSidebar').addEventListener('click', toggleSidebar);

        /* ===== GLOSARIO COLAPSABLE ===== */
        function toggleGlosario() {
            const glosario = document.getElementById('glosario');
            const boton = document.getElementById('glosarioToggleBtn');

            const abierto = glosario.classList.toggle('abierto');

            boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');

            try {
                localStorage.setItem('glosarioAbierto', abierto ? '1' : '0');
            } catch (err) {
                /* localStorage puede no estar disponible (modo privado, etc.) */
            }
        }

        document.getElementById('glosarioToggleBtn').addEventListener('click', toggleGlosario);

        /* Confirmación antes de eliminar un equipo */
        document.querySelectorAll('.form-eliminar').forEach((form) => {
            form.addEventListener('submit', (evento) => {
                if (!confirm('¿Desea eliminar este equipo?')) {
                    evento.preventDefault();
                }
            });
        });

        // Restaurar el último estado guardado del glosario (por defecto: cerrado)
        document.addEventListener('DOMContentLoaded', () => {
            const glosario = document.getElementById('glosario');
            if (!glosario) return;

            let guardado = null;
            try {
                guardado = localStorage.getItem('glosarioAbierto');
            } catch (err) {
                /* ignorar */
            }

            if (guardado === '1') {
                glosario.classList.add('abierto');
                document.getElementById('glosarioToggleBtn')
                    .setAttribute('aria-expanded', 'true');
            }
        });
    </script>

</body>

</html>