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
    'secure'   => $esHttps,
    'httponly' => true,
    'samesite' => 'Lax',
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

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

/* Nonce único por petición: permite un CSP estricto (sin
   'unsafe-inline') para los pocos <script> de esta página.
   No hace falta nonce en style-src porque todo el diseño
   vive en el archivo externo historial_preventivos.css. */
$cspNonce = base64_encode(random_bytes(16));

header(
    "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'nonce-$cspNonce'; " .
        "style-src 'self' https://fonts.googleapis.com https://unpkg.com; " .
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

/** Valida una fecha en formato YYYY-MM-DD; devuelve '' si no es válida. */
function fechaValida(string $valor): string
{
    if ($valor === '') {
        return '';
    }

    $d = DateTime::createFromFormat('Y-m-d', $valor);

    if ($d === false || $d->format('Y-m-d') !== $valor) {
        return '';
    }

    // Rango razonable para evitar valores absurdos (defensa en profundidad)
    $anio = (int) $d->format('Y');
    if ($anio < 2000 || $anio > 2100) {
        return '';
    }

    return $valor;
}

/* =====================================================
   CATÁLOGOS (misma fuente que usa ver_equipos.php,
   para que tipo/marca/ubicación se vean igual en toda la app)
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
    'DE'   => 'Dell',
    'IN'   => 'INPOWER',
    'AS'   => 'Asus',
    'HP'   => 'Hewlett-Packard',
    'LE'   => 'Lenovo',
    'EP'   => 'EPSON',
    'CA'   => 'CANON',
    'GE'   => 'Genius',
    'OP'   => 'OPPO',
    'KA'   => 'KALLEY',
    'SA'   => 'SAMSUNG',
    'MA'   => 'MAXELL',
    'PA'   => 'PANASONIC',
    'AR'   => 'ARCHTEX',
    'XK'   => 'XKIM',
    'HA'   => 'HAVIT',
    'LO'   => 'LOGITECH',
    'WI'   => 'WIT',
    'HU'   => 'HUAWEI',
    'MT'   => 'MOTOROLA',
    'XI'   => 'XIAOMI',
    'SM'   => 'SIN MARCA',
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

/* Estados posibles que puede dejar registrado un mantenimiento,
   tal como los ofrece el formulario "Registrar Nuevo Mantenimiento"
   de la hoja de vida. */
$estadosMantenimiento = [
    'Activo',
    'En reparación',
    'Dado de baja',
];

/* =====================================================
   FILTROS
   Todo se valida contra catálogos conocidos (whitelist) o
   se limita en longitud/formato: ningún valor del usuario
   llega "crudo" a la consulta ni a la pantalla.
   ===================================================== */

$tipo          = trim((string)($_GET['tipo'] ?? ''));
$marca         = trim((string)($_GET['marca'] ?? ''));
$ubicacion     = trim((string)($_GET['ubicacion'] ?? ''));
$estado        = trim((string)($_GET['estado'] ?? ''));
$responsable   = trim((string)($_GET['responsable'] ?? ''));
$identificador = trim((string)($_GET['identificador'] ?? ''));
$fechaDesde    = fechaValida(trim((string)($_GET['fecha_desde'] ?? '')));
$fechaHasta    = fechaValida(trim((string)($_GET['fecha_hasta'] ?? '')));

if ($tipo !== '' && !array_key_exists($tipo, $tiposEquipo)) {
    $tipo = '';
}

if ($marca !== '' && !array_key_exists($marca, $marcasEquipo)) {
    $marca = '';
}

if ($ubicacion !== '' && !in_array($ubicacion, $ubicacionesEquipo, true)) {
    $ubicacion = '';
}

if ($estado !== '' && !in_array($estado, $estadosMantenimiento, true)) {
    $estado = '';
}

// Si el rango de fechas viene invertido, se intercambia en vez de romper la consulta
if ($fechaDesde !== '' && $fechaHasta !== '' && $fechaDesde > $fechaHasta) {
    [$fechaDesde, $fechaHasta] = [$fechaHasta, $fechaDesde];
}

$responsable   = mb_substr($responsable, 0, 100);
$identificador = mb_substr($identificador, 0, 100);

/* =====================================================
   CONDICIONES COMUNES
   Se listan solo mantenimientos de tipo "Preventivo",
   sin excepción: esta vista existe únicamente para eso.
   ===================================================== */

function condicionesComunes(array $filtros): array
{
    $clausulas = ["m.tipo_mantenimiento = 'Preventivo'"];
    $params = [];

    if ($filtros['tipo'] !== '') {
        $clausulas[] = "e.tipo = :tipo";
        $params[':tipo'] = $filtros['tipo'];
    }

    if ($filtros['marca'] !== '') {
        $clausulas[] = "e.marca LIKE :marca";
        $params[':marca'] = "%{$filtros['marca']}%";
    }

    if ($filtros['ubicacion'] !== '') {
        $clausulas[] = "e.ubicacion LIKE :ubicacion";
        $params[':ubicacion'] = "%{$filtros['ubicacion']}%";
    }

    if ($filtros['estado'] !== '') {
        $clausulas[] = "m.estado = :estado";
        $params[':estado'] = $filtros['estado'];
    }

    if ($filtros['responsable'] !== '') {
        $clausulas[] = "m.responsable = :responsable";
        $params[':responsable'] = $filtros['responsable'];
    }

    if ($filtros['fechaDesde'] !== '') {
        $clausulas[] = "m.fecha >= :fecha_desde";
        $params[':fecha_desde'] = $filtros['fechaDesde'];
    }

    if ($filtros['fechaHasta'] !== '') {
        $clausulas[] = "m.fecha <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtros['fechaHasta'];
    }

    return [$clausulas, $params];
}

$filtrosActuales = [
    'tipo'        => $tipo,
    'marca'       => $marca,
    'ubicacion'   => $ubicacion,
    'estado'      => $estado,
    'responsable' => $responsable,
    'fechaDesde'  => $fechaDesde,
    'fechaHasta'  => $fechaHasta,
];

[$clausulas, $params] = condicionesComunes($filtrosActuales);

if ($identificador !== '') {
    $clausulas[] = "e.identificador = :identificador";
    $params[':identificador'] = $identificador;
}

$sqlWhere = 'WHERE ' . implode(' AND ', $clausulas);

/* =====================================================
   LISTAS PARA LOS SELECT (se recalculan según los demás
   filtros ya elegidos, igual que en ver_equipos.php)
   ===================================================== */

[$clausulasSinIdent, $paramsSinIdent] = condicionesComunes($filtrosActuales);
$whereSinIdent = 'WHERE ' . implode(' AND ', $clausulasSinIdent);

$stmtIdent = $conn->prepare("
    SELECT DISTINCT e.identificador
    FROM mantenimientos m
    JOIN equipos e ON e.id = m.equipo_id
    $whereSinIdent
    AND e.identificador IS NOT NULL AND e.identificador <> ''
    ORDER BY e.identificador
");
$stmtIdent->execute($paramsSinIdent);
$identificadoresDisponibles = $stmtIdent->fetchAll(PDO::FETCH_COLUMN);

[$clausulasSinResp, $paramsSinResp] = condicionesComunes(
    ['tipo' => $tipo, 'marca' => $marca, 'ubicacion' => $ubicacion, 'estado' => $estado,
     'responsable' => '', 'fechaDesde' => $fechaDesde, 'fechaHasta' => $fechaHasta]
);
$whereSinResp = 'WHERE ' . implode(' AND ', $clausulasSinResp);

$stmtResp = $conn->prepare("
    SELECT DISTINCT m.responsable
    FROM mantenimientos m
    JOIN equipos e ON e.id = m.equipo_id
    $whereSinResp
    AND m.responsable IS NOT NULL AND m.responsable <> ''
    ORDER BY m.responsable
");
$stmtResp->execute($paramsSinResp);
$responsablesDisponibles = $stmtResp->fetchAll(PDO::FETCH_COLUMN);

/* =====================================================
   PAGINACIÓN
   ===================================================== */

$porPagina = 10;

$pagina = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT) ?: 1;

if ($pagina < 1) {
    $pagina = 1;
}

$inicio = ($pagina - 1) * $porPagina;

$sqlTotal = "
    SELECT COUNT(*)
    FROM mantenimientos m
    JOIN equipos e ON e.id = m.equipo_id
    $sqlWhere
";

$stmtTotal = $conn->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalRegistros = (int) $stmtTotal->fetchColumn();

$totalPaginas = (int) ceil($totalRegistros / $porPagina);

if ($totalPaginas > 0 && $pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $inicio = ($pagina - 1) * $porPagina;
}

/* =====================================================
   CONSULTA PRINCIPAL (siempre parametrizada)
   ===================================================== */

$sql = "
    SELECT
        m.id,
        m.fecha,
        m.responsable,
        m.tipo_mantenimiento,
        m.descripcion,
        m.estado,
        m.observaciones,
        e.id AS equipo_id,
        e.tipo AS equipo_tipo,
        e.marca AS equipo_marca,
        e.identificador,
        e.ubicacion,
        e.codigo_barras
    FROM mantenimientos m
    JOIN equipos e ON e.id = m.equipo_id
    $sqlWhere
    ORDER BY m.fecha DESC, m.id DESC
    LIMIT :inicio, :porPagina
";

$stmt = $conn->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

$stmt->bindValue(':inicio', (int) $inicio, PDO::PARAM_INT);
$stmt->bindValue(':porPagina', (int) $porPagina, PDO::PARAM_INT);
$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

$queryFiltros = http_build_query([
    'tipo'          => $tipo,
    'marca'         => $marca,
    'ubicacion'     => $ubicacion,
    'estado'        => $estado,
    'responsable'   => $responsable,
    'identificador' => $identificador,
    'fecha_desde'   => $fechaDesde,
    'fecha_hasta'   => $fechaHasta,
]);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial de Preventivos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/ver_preventivos.css">
</head>

<body>
    <!-- Botón hamburguesa -->
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
        <a href="historial_preventivos.php"><i class='bx bx-history'></i> <span> Historial Preventivos</span></a>
        <a href="indicadores_mantenimiento.php"><i class='bx bx-bar-chart'></i> <span> Indicadores de Mantenimiento</span></a>
        <a href="hoja_vida_equipos.php"><i class='bx bx-file'></i> <span> Hoja de Vida General</span></a>
    </div>

    <!-- Contenido principal -->
    <div class="container">
        <div class="card">
            <h2>Historial de Mantenimientos Preventivos</h2>
            <p class="subtitulo">
                Mantenimientos registrados como <strong>Preventivo</strong> desde la Hoja de Vida de cada equipo.
            </p>

            <!-- FILTROS -->
            <form method="GET" class="filtros">
                <select name="tipo">
                    <option value="" <?= $tipo === '' ? 'selected' : '' ?>>Seleccione Tipo</option>
                    <?php foreach ($tiposEquipo as $codigo_ => $nombre_): ?>
                        <option value="<?= e($codigo_) ?>" <?= $tipo === $codigo_ ? 'selected' : '' ?>>
                            <?= e($nombre_) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="marca">
                    <option value="" <?= $marca === '' ? 'selected' : '' ?>>Seleccione Marca</option>
                    <?php foreach ($marcasEquipo as $codigoMarca_ => $nombreMarca_): ?>
                        <option value="<?= e($codigoMarca_) ?>" <?= $marca === $codigoMarca_ ? 'selected' : '' ?>>
                            <?= e($nombreMarca_) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="ubicacion">
                    <option value="" <?= $ubicacion === '' ? 'selected' : '' ?>>Seleccione Ubicación</option>
                    <?php foreach ($ubicacionesEquipo as $ubicacion_): ?>
                        <option value="<?= e($ubicacion_) ?>" <?= $ubicacion === $ubicacion_ ? 'selected' : '' ?>>
                            <?= e($ubicacion_) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- <select name="estado">
                    <option value="" <?= $estado === '' ? 'selected' : '' ?>>Seleccione Estado</option>
                    <?php foreach ($estadosMantenimiento as $estado_): ?>
                        <option value="<?= e($estado_) ?>" <?= $estado === $estado_ ? 'selected' : '' ?>>
                            <?= e($estado_) ?>
                        </option>
                    <?php endforeach; ?>
                </select> -->

                <!-- RESPONSABLE: se autocompleta según los demás filtros -->
                <!-- <select name="responsable">
                    <option value="" <?= $responsable === '' ? 'selected' : '' ?>>Todos los responsables</option>
                    <?php foreach ($responsablesDisponibles as $responsable_): ?>
                        <option value="<?= e($responsable_) ?>" <?= $responsable === $responsable_ ? 'selected' : '' ?>>
                            <?= e($responsable_) ?>
                        </option>
                    <?php endforeach; ?>
                </select> -->

                <!-- IDENTIFICADOR: se autocompleta según los demás filtros -->
                <select name="identificador">
                    <option value="" <?= $identificador === '' ? 'selected' : '' ?>>Todos los identificadores</option>
                    <?php foreach ($identificadoresDisponibles as $identificador_): ?>
                        <option value="<?= e($identificador_) ?>" <?= $identificador === $identificador_ ? 'selected' : '' ?>>
                            <?= e($identificador_) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="campo-fecha">
                    <span>Desde</span>
                    <input type="date" name="fecha_desde" value="<?= e($fechaDesde) ?>">
                </label>

                <label class="campo-fecha">
                    <span>Hasta</span>
                    <input type="date" name="fecha_hasta" value="<?= e($fechaHasta) ?>">
                </label>

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
            <p class="tabla-scroll-hint">
                <i class='bx bx-move-horizontal'></i>
                Desliza horizontalmente para ver todas las columnas
            </p>
            <div class="tabla-scroll">
                <table class="tabla-preventivos">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Identificador</th>
                            <th>Tipo</th>
                            <th>Marca</th>
                            <th>Ubicación</th>
                            <th>Responsable</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                            <th>Hoja de Vida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($result)): ?>
                            <tr>
                                <td colspan="10" class="celda-vacia">
                                    No se encontraron mantenimientos preventivos con los filtros seleccionados.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($result as $fila): ?>
                            <?php
                                $claseEstado = 'activo';
                                if ($fila['estado'] === 'En reparación') {
                                    $claseEstado = 'reparacion';
                                } elseif ($fila['estado'] === 'Dado de baja') {
                                    $claseEstado = 'baja';
                                }
                            ?>
                            <tr>
                                <td data-label="Fecha"><?= e($fila['fecha']) ?></td>
                                <td data-label="Identificador"><?= e($fila['identificador']) ?></td>
                                <td data-label="Tipo"><?= e($fila['equipo_tipo']) ?></td>
                                <td data-label="Marca"><?= e($fila['equipo_marca']) ?></td>
                                <td data-label="Ubicación"><?= e($fila['ubicacion']) ?></td>
                                <td data-label="Responsable"><?= e($fila['responsable']) ?></td>
                                <td data-label="Descripción"><?= e($fila['descripcion']) ?></td>
                                <td data-label="Estado">
                                    <span class="badge <?= e($claseEstado) ?>">
                                        <?= e($fila['estado']) ?>
                                    </span>
                                </td>
                                <td data-label="Observaciones"><?= e($fila['observaciones']) ?: '—' ?></td>
                                <td data-label="Hoja de Vida">
                                    <a href="hoja_vida_equipos.php?codigo=<?= urlencode($fila['codigo_barras'] ?? '') ?>" class="btn-hoja-vida">
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
                $paginasPorBloque = 10;
                $bloqueActual = (int) ceil($pagina / $paginasPorBloque);
                $primeraPagina = (($bloqueActual - 1) * $paginasPorBloque) + 1;
                $ultimaPagina = min($primeraPagina + $paginasPorBloque - 1, $totalPaginas);
                ?>

                <?php if ($pagina > 1): ?>
                    <a class="pag-anterior" href="?pagina=<?= ($pagina - 1) ?>&<?= e($queryFiltros) ?>">
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
                    <a class="pag-siguiente" href="?pagina=<?= ($pagina + 1) ?>&<?= e($queryFiltros) ?>">
                        Siguiente
                    </a>
                <?php endif; ?>

                <?php if ($totalPaginas > 0): ?>
                    <span class="indicador-paginacion">
                        Páginas <?= $primeraPagina ?> - <?= $ultimaPagina ?> de <?= $totalPaginas ?>
                        (<?= $totalRegistros ?> mantenimientos preventivos)
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script nonce="<?= e($cspNonce) ?>">
        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                document.body.classList.toggle('sidebar-open');
            } else {
                document.body.classList.toggle('sidebar-collapsed');
            }
        }
        document.getElementById('btnToggleSidebar').addEventListener('click', toggleSidebar);

        function toggleGlosario() {
            const glosario = document.getElementById('glosario');
            const boton = document.getElementById('glosarioToggleBtn');
            const abierto = glosario.classList.toggle('abierto');
            boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
            try {
                localStorage.setItem('glosarioAbiertoPreventivos', abierto ? '1' : '0');
            } catch (err) {
                /* localStorage puede no estar disponible */
            }
        }
        document.getElementById('glosarioToggleBtn').addEventListener('click', toggleGlosario);

        document.addEventListener('DOMContentLoaded', () => {
            const glosario = document.getElementById('glosario');
            if (!glosario) return;
            let guardado = null;
            try {
                guardado = localStorage.getItem('glosarioAbiertoPreventivos');
            } catch (err) {
                /* ignorar */
            }
            if (guardado === '1') {
                glosario.classList.add('abierto');
                document.getElementById('glosarioToggleBtn').setAttribute('aria-expanded', 'true');
            }
        });
    </script>
</body>

</html>