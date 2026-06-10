<?php
require '../bd/conn.php';
session_start();

/* VALIDAR SESIÓN PRIMERO */
if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/index_Login.php");
    exit;
}

/* VALIDAR ROL DESPUÉS */
if ($_SESSION['rol'] !== 'USUARIO') {
    header("Location: ../views/index_Login.php");
    exit;
}

// Evitar cache para que no se pueda volver atrás después de cerrar sesión
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/* ===========================
FILTROS
=========================== */

$tipo = $_GET['tipo'] ?? '';
$marca = $_GET['marca'] ?? '';
$identificador = $_GET['identificador'] ?? '';
$asignado = $_GET['asignado'] ?? '';
$ubicacion = $_GET['ubicacion'] ?? '';

$where = " WHERE 1=1 ";
$params = [];

if ($tipo != '') {
    $where .= " AND e.tipo = ?";
    $params[] = $tipo;
}
if ($marca != '') {
    $where .= " AND e.marca = ?";
    $params[] = $marca;
}
if ($identificador != '') {
    $where .= " AND e.identificador LIKE ?";
    $params[] = "%$identificador%";
}
if ($asignado != '') {
    $where .= " AND e.asignado_a LIKE ?";
    $params[] = "%$asignado%";
}
if ($ubicacion != '') {
    $where .= " AND e.ubicacion LIKE ?";
    $params[] = "%$ubicacion%";
}

/* ===========================
PAGINACIÓN
=========================== */

$pagina = $_GET['pagina'] ?? 1;
$limite = 6;
$offset = ($pagina - 1) * $limite;

// TOTAL REGISTROS
$totalSQL = "
SELECT COUNT(*) 
FROM mantenimiento_repuestos mr
JOIN mantenimientos m ON mr.mantenimiento_id = m.id
JOIN repuestos r ON mr.repuesto_id = r.id
JOIN equipos e ON m.equipo_id = e.id
$where
";

$totalStmt = $conn->prepare($totalSQL);
$totalStmt->execute($params);
$totalRegistros = $totalStmt->fetchColumn();

$totalPaginas = ceil($totalRegistros / $limite);

/* ===========================
CONSULTA
=========================== */

/* CATALOGO PARA FILTROS DINAMICOS */
$catalogo = $conn->query("
SELECT id, tipo, marca, identificador, ubicacion, asignado_a
FROM equipos
")->fetchAll(PDO::FETCH_ASSOC);

$sql = "
SELECT 
    e.tipo,
    e.marca,
    e.identificador,
    e.asignado_a,
    e.ubicacion,
    m.tipo_mantenimiento,
    m.fecha,
    r.nombre,
    mr.valor_unitario,
    r.serial
FROM mantenimiento_repuestos mr
JOIN mantenimientos m ON mr.mantenimiento_id = m.id
JOIN repuestos r ON mr.repuesto_id = r.id
JOIN equipos e ON m.equipo_id = e.id
$where
ORDER BY m.fecha DESC
LIMIT $limite OFFSET $offset
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Repuestos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/repuestos.css">
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
        <a href="index_Usuario.php"><i class='bx bx-home'></i> <span> Inicio</span></a>
        <a href="ver_equipos_usuario.php"><i class='bx bx-list-ul'></i> <span> Ver Equipos</span></a>
        <a href="ver_qr_usuario.php"><i class='bx bx-barcode'></i> <span> Ver QR</span></a>
        <a href="ver_correctivos_usuario.php"><i class='bx bx-check-square'></i> <span> Ver Correctivos</span></a>
        <a href="indicadores_mantenimiento_usuario.php"><i class='bx bx-bar-chart'></i> <span> Indicadores de Mantenimiento</span></a>
        <a href="repuestos_usuario.php"><i class='bx bx-cog'></i> <span> Gestión de Repuestos</span></a>
        <a href="hoja_vida_equipos_usuario.php"><i class='bx bx-file'></i> <span> Hoja de Vida General</span></a>
    </div>
    <div class="main-content">

        <div class="card">
            <h2>Gestión de Repuestos</h2>

            <form method="GET" class="filtros">

                <!-- TIPO -->
                <select id="tipoFiltro" name="tipo">
                    <option value="">Tipo</option>
                    <option value="P">Portátil</option>
                    <option value="TU">Todo en Uno</option>
                    <option value="E">Escritorio</option>
                </select>

                <!-- MARCA -->
                <select id="marcaFiltro" name="marca">
                    <option value="">Marca</option>
                </select>

                <!-- IDENTIFICADOR -->
                <select id="idFiltro" name="identificador">
                    <option value="">Identificador</option>
                </select>

                <!-- UBICACION -->
                <select id="ubicacionFiltro" name="ubicacion">
                    <option value="">Ubicación</option>
                </select>

                <button class="btn buscar">Buscar</button>
            </form>
        </div>

        <div class="card table-wrap">

            <table id="tablaRepuestos">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Marca</th>
                        <th>Identificador</th>
                        <th>Asignado</th>
                        <th>Ubicación</th>
                        <th>Mantenimiento</th>
                        <th>Fecha</th>
                        <th>Repuesto</th>
                        <th>Valor</th>
                        <th>Serial</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($datos as $d): ?>

                        <tr>
                            <td><?= $d['tipo'] ?></td>
                            <td><?= $d['marca'] ?></td>
                            <td><?= $d['identificador'] ?></td>
                            <td><?= $d['asignado_a'] ?></td>
                            <td><?= $d['ubicacion'] ?></td>

                            <td>
                                <span class="badge <?= $d['tipo_mantenimiento'] == 'Correctivo' ? 'correctivo' : 'preventivo' ?>">
                                    <?= $d['tipo_mantenimiento'] ?>
                                </span>
                            </td>

                            <td><?= $d['fecha'] ?></td>
                            <td><?= $d['nombre'] ?></td>
                            <td>$<?= number_format($d['valor_unitario'], 0, ',', '.') ?></td>
                            <td><?= $d['serial'] ?></td>

                        </tr>
        </div>
    </div>
<?php endforeach; ?>
</tbody>
</table>

<!-- CONTENEDOR DE ACCIONES -->
<div class="acciones-tabla">

    <!-- BOTÓN PDF -->
    <button onclick="descargarPDF()" class="btn pdf">
        Descargar PDF
    </button>
    <div class="paginacion">

        <?php if ($pagina > 1): ?>
            <a href="?pagina=<?= $pagina - 1 ?>">Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a class="<?= ($pagina == $i) ? 'activo-pagina' : '' ?>"
                href="?pagina=<?= $i ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="?pagina=<?= $pagina + 1 ?>">Siguiente</a>
        <?php endif; ?>

    </div>

</div>

</div>
</div>

<!-- 1. PRIMERO: librerías -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

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

    function descargarPDF() {

        console.log("PDF ejecutado"); // DEBUG

        const {
            jsPDF
        } = window.jspdf;

        if (!jsPDF) {
            alert("Error: jsPDF no está cargado");
            return;
        }

        let doc = new jsPDF();

        let tabla = document.getElementById("tablaRepuestos");

        if (!tabla) {
            alert("No se encontró la tabla");
            return;
        }

        let filas = [];

        tabla.querySelectorAll("tbody tr").forEach(tr => {

            let fila = [];

            tr.querySelectorAll("td").forEach(td => {
                fila.push(td.innerText.trim());
            });

            filas.push(fila);
        });

        if (filas.length === 0) {
            alert("No hay datos para exportar");
            return;
        }

        doc.text("REPORTE DE REPUESTOS", 60, 15);

        doc.autoTable({
            startY: 25,

            head: [
                [
                    'Tipo', 'Marca', 'Identificador', 'Asignado',
                    'Ubicación', 'Mantenimiento', 'Fecha',
                    'Repuesto', 'Valor', 'Serial'
                ]
            ],

            body: filas,

            styles: {
                fontSize: 8,
                cellPadding: 3,
                overflow: 'linebreak',
                halign: 'center'
            },

            headStyles: {
                fillColor: [0, 0, 150], // azul bonito
                textColor: [255, 255, 255],
                fontStyle: 'bold'
            },

            alternateRowStyles: {
                fillColor: [240, 240, 240]
            },

            columnStyles: {
                0: {
                    cellWidth: 12
                }, // Tipo
                1: {
                    cellWidth: 14
                }, // Marca
                2: {
                    cellWidth: 25
                }, // Identificador
                3: {
                    cellWidth: 26
                }, // Asignado
                4: {
                    cellWidth: 20
                }, // Ubicación
                5: {
                    cellWidth: 26
                }, // Mantenimiento
                6: {
                    cellWidth: 20
                }, // Fecha
                7: {
                    cellWidth: 19
                }, // Repuesto
                8: {
                    cellWidth: 15
                }, // Valor
                9: {
                    cellWidth: 25
                } // Serial
            },

            margin: {
                left: 5,
                right: 5
            }
        });

        doc.save("reporte_repuestos.pdf");
    }

    const equipos = <?= json_encode($catalogo); ?>;

    const tipo = document.getElementById('tipoFiltro');
    const marca = document.getElementById('marcaFiltro');
    const identificador = document.getElementById('idFiltro');
    const ubicacion = document.getElementById('ubicacionFiltro');

    /* =======================
    TIPO → MARCA
    ======================= */
    tipo.addEventListener('change', () => {

        marca.innerHTML = '<option value="">Marca</option>';
        identificador.innerHTML = '<option value="">Identificador</option>';
        ubicacion.innerHTML = '<option value="">Ubicación</option>';

        let marcas = [...new Set(
            equipos
            .filter(e => e.tipo === tipo.value)
            .map(e => e.marca)
        )];

        marcas.forEach(m => {
            let op = document.createElement('option');
            op.value = m;
            op.textContent = m;
            marca.appendChild(op);
        });

    });


    /* =======================
    MARCA → IDENTIFICADOR
    ======================= */
    marca.addEventListener('change', () => {

        identificador.innerHTML = '<option value="">Identificador</option>';
        ubicacion.innerHTML = '<option value="">Ubicación</option>';

        let ids = equipos.filter(e =>
            e.tipo === tipo.value &&
            e.marca === marca.value
        );

        ids.forEach(e => {
            let op = document.createElement('option');
            op.value = e.identificador;
            op.textContent = e.identificador;
            identificador.appendChild(op);
        });

    });


    /* =======================
    ID → UBICACION
    ======================= */
    identificador.addEventListener('change', () => {

        ubicacion.innerHTML = '<option value="">Ubicación</option>';

        let filtrados = equipos.filter(e =>
            e.tipo === tipo.value &&
            e.marca === marca.value &&
            e.identificador === identificador.value
        );

        filtrados.forEach(e => {
            let op = document.createElement('option');
            op.value = e.ubicacion;
            op.textContent = e.ubicacion;
            ubicacion.appendChild(op);
        });

    });
</script>

</body>

</html>