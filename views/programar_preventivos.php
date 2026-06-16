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

/*=================================
EJECUTAR PREVENTIVO
=================================*/
if (isset($_GET['ejecutar'])) {

    $id = $_GET['ejecutar'];

    $conn->prepare("
UPDATE preventivos_programados
SET estado='Ejecutado',
fecha_ejecucion=CURDATE()
WHERE id=?
")->execute([$id]);

    header("Location: programar_preventivos.php");
    exit;
}



/*=================================
CATALOGO EQUIPOS
=================================*/

$equipos = $conn->query("
SELECT
id,
tipo,
marca,
identificador,
ubicacion
FROM equipos
ORDER BY tipo,marca
")->fetchAll(PDO::FETCH_ASSOC);



/* =====================
PAGINACIÓN PREVENTIVOS
===================== */

$porPagina = 6;
$pagina = $_GET['pagina'] ?? 1;

if ($pagina < 1) $pagina = 1;

$offset = ($pagina - 1) * $porPagina;


/* TOTAL REGISTROS */
$totalSQL = "
SELECT COUNT(*)
FROM preventivos_programados p
JOIN equipos e ON e.id = p.equipo_id
";

$totalStmt = $conn->query($totalSQL);
$totalRegistros = $totalStmt->fetchColumn();

$totalPaginas = ceil($totalRegistros / $porPagina);


/* CONSULTA PAGINADA */
$preventivos = $conn->query("
SELECT
p.*,
e.tipo,
e.marca,
e.identificador,
e.ubicacion
FROM preventivos_programados p
JOIN equipos e ON e.id = p.equipo_id
ORDER BY p.fecha_programada DESC
LIMIT $porPagina OFFSET $offset
")->fetchAll(PDO::FETCH_ASSOC);



/* KPIS */

$total = $conn->query("
SELECT COUNT(*)
FROM preventivos_programados
")->fetchColumn();

$reprogramados = $conn->query("
SELECT COUNT(*)
FROM preventivos_programados
WHERE estado='Reprogramado'
")->fetchColumn();


$ejecutados = $conn->query("
SELECT COUNT(*)
FROM preventivos_programados
WHERE estado='Ejecutado'
")->fetchColumn();


$vencidos = $conn->query("
SELECT COUNT(*)
FROM preventivos_programados
WHERE estado='Vencido'
")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Preventivos</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/programar_preventivos.css">

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

    <div class="main-content">


        <div class="card">
            <h2>Gestión de Mantenimiento Preventivo</h2>
        </div>

        <div class="card">
            <h3>Indicadores</h3>
            <div class="kpis">
                <div class="kpi">
                    <h1><?= $total ?></h1>Programados
                </div>

                <div class="kpi">
                    <h1><?= $reprogramados ?></h1>Reprogramados
                </div>

                <div class="kpi">
                    <h1><?= $ejecutados ?></h1>Ejecutados
                </div>

                <div class="kpi">
                    <h1><?= $vencidos ?></h1>Vencidos
                </div>
            </div>
        </div>



        <div class="card">

            <h3>Programar Preventivo</h3>

            <?php if (isset($_GET['error']) && $_GET['error'] == 'duplicado'): ?>

                <div class="alerta" style="background:#dc2626;color:white;">
                    Este equipo ya tiene un mantenimiento preventivo activo.
                    Debe ejecutarlo antes de crear otro.
                </div>

            <?php endif; ?>

            <?php if (isset($_GET['ok'])): ?>

                <div class="alerta" style="background:#16a34a;color:white;">
                    Preventivo programado correctamente.
                </div>

            <?php endif; ?>
            <form action="../controller/procesar_programar_preventivo.php" method="POST">

                <input type="hidden" name="equipo_id" id="equipo_id_final">

                <label>Tipo</label>
                <select id="tipoFiltro" required>
                    <option value="" disabled selected>
                        Seleccione Tipo
                    </option>
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



                <label>Marca</label>
                <select id="marcaFiltro" required>
                    <option value="" disabled selected>
                        Seleccione Marca
                    </option>
                    <option value="DELL">Dell</option>
                    <option value="INP">INPOWER</option>
                    <option value="A">Asus</option>
                    <option value="HP">Hewlett-Packard</option>
                    <option value="L">Lenovo</option>
                    <option value="LOG">Logitech</option>
                    <option value="EP">EPSON</option>
                    <option value="G">Genius</option>
                    <option value="S">Samsung</option>
                    <option value="H">Huawei</option>
                    <option value="MOT">Motorola</option>
                    <option value="X">Xiaomi</option>
                    <option value="CA">CANON</option>
                    <option value="GEN">Genérico</option>
                </select>


                <label>Identificador</label>
                <select id="identificadorFiltro" required>
                    <option value="" disabled selected>
                        Seleccione Identificador
                    </option>
                </select>

                <label>Frecuencia</label>
                <select name="tipo_frecuencia" required>
                    <option value="" disabled selected>
                        Seleccione
                    </option>
                    <option value="Semanal">Semanal</option>
                    <option value="Mensual">Mensual</option>
                    <option value="Trimestral">Trimestral</option>
                    <option value="Semestral">Semestral</option>
                    <option value="Anual">Anual</option>
                </select>

                <label>Fecha Programada</label>
                <input type="date" name="fecha_programada" required>

                <label>Responsable</label>
                <input name="responsable" required>

                <label>Descripción</label>
                <textarea name="descripcion" required></textarea>

                <button name="guardar" class="btn">Guardar Programación</button>
            </form>
        </div>

        <div class="card">
            <h3>Filtrar Preventivos Programados</h3>
            <select id="filtroEstado">
                <option value="">Todos</option>
                <option value="Programado">Programados</option>
                <option value="Reprogramado">Reprogramados</option>
                <option value="Ejecutado">Ejecutados</option>
                <option value="Vencido">Vencidos</option>
            </select>
        </div>

        <div class="card">

            <h3>Calendario de Preventivos</h3>


            <?php
            foreach ($preventivos as $p) {

                if (
                    $p['estado'] == 'Programado'
                    &&
                    strtotime($p['fecha_programada']) <= strtotime('+3 days')
                ) {
                    echo "
<div class='alerta'>
Preventivo próximo:
{$p['tipo']} - {$p['marca']} -
{$p['identificador']} -
{$p['fecha_programada']}
</div>";
                }
            }
            ?>


            <table id="tablaPreventivos">

                <thead>

                    <tr>
                        <th>Equipo</th>
                        <th>Frecuencia</th>
                        <th>Fecha a Ejecutar</th>
                        <th>Próximo Preventivo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($preventivos as $p): ?>

                        <tr data-estado="<?= $p['estado'] ?>">

                            <td>
                                <?= $p['tipo'] ?>
                                -
                                <?= $p['marca'] ?>
                                -
                                <?= $p['identificador'] ?>
                            </td>
                            <td><?= $p['tipo_frecuencia'] ?></td>
                            <td><?= $p['fecha_programada'] ?></td>
                            <td><?= $p['proxima_fecha_programada'] ?></td>

                            <td>

                                <?php
                                $clase = 'programado';

                                if ($p['estado'] == 'Ejecutado') $clase = 'ejecutado';
                                if ($p['estado'] == 'Reprogramado') $clase = 'reprogramado';
                                if ($p['estado'] == 'Vencido') $clase = 'vencido';
                                ?>

                                <span class="badge <?= $clase ?>">
                                    <?= $p['estado'] ?>
                                </span>

                            </td>


                            <td>

                                <div class="acciones">

                                    <a href="?ejecutar=<?= $p['id'] ?>">

                                        <button
                                            type="button"
                                            class="btn btn-green">
                                            Ejecutar
                                        </button>

                                    </a>


                                    <a href="reprogramar_preventivo.php?preventivo_id=<?= $p['id'] ?>">
                                        <button
                                            type="button"
                                            class="btn btn-orange">
                                            Reprogramar
                                        </button>
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>
            <div class="paginacion">

                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?= $pagina - 1 ?>">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a
                        class="<?= ($pagina == $i) ? 'activo-pagina' : '' ?>"
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



        /* EQUIPOS DINAMICOS */

        const equipos = <?= json_encode($equipos); ?>;

        let tipo = document.getElementById('tipoFiltro');
        let marca = document.getElementById('marcaFiltro');
        let activo = document.getElementById('identificadorFiltro');
        let hidden = document.getElementById('equipo_id_final');


        tipo.addEventListener('change', function() {

            marca.innerHTML = '<option>Seleccione</option>';

            let marcas = [
                ...new Set(
                    equipos
                    .filter(e => e.tipo === this.value)
                    .map(e => e.marca)
                )
            ];

            marcas.forEach(m => {

                let op = document.createElement('option');
                op.value = m;
                op.textContent = m;
                marca.appendChild(op);

            });

        });


        marca.addEventListener('change', function() {

            activo.innerHTML = '<option>Seleccione</option>';

            let filtrados = equipos.filter(e =>
                e.tipo === tipo.value &&
                e.marca === this.value
            );

            filtrados.forEach(eq => {

                let op = document.createElement('option');
                op.value = eq.id;
                op.textContent = eq.identificador;

                activo.appendChild(op);

            });

        });


        activo.addEventListener(
            'change',
            function() {
                hidden.value = this.value;
            }
        );



        /* FILTRO TABLA DINAMICA */

        document
            .getElementById('filtroEstado')
            .addEventListener(
                'change',
                function() {

                    let valor = this.value;

                    document
                        .querySelectorAll(
                            '#tablaPreventivos tbody tr'
                        )
                        .forEach(fila => {

                            if (
                                valor == '' ||
                                fila.dataset.estado === valor
                            ) {
                                fila.style.display = '';
                            } else {
                                fila.style.display = 'none';
                            }

                        });

                });
    </script>

</body>

</html>