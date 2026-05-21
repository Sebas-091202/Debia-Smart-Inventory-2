<?php

require '../bd/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/programar_preventivos.php');
    exit;
}


/*=========================================
CAPTURA DATOS
=========================================*/

$equipo_id         = $_POST['equipo_id'];
$frecuencia        = $_POST['tipo_frecuencia'];
$fecha_programada  = $_POST['fecha_programada'];
$descripcion       = $_POST['descripcion'];
$responsable       = $_POST['responsable'];


/*=========================================
VALIDAR SI YA EXISTE PREVENTIVO ACTIVO
=========================================*/

$validar = $conn->prepare("
SELECT COUNT(*)
FROM preventivos_programados
WHERE equipo_id = ?
AND estado IN (
    'Programado',
    'Vencido',
    'Reprogramado'
)
");

$validar->execute([$equipo_id]);

$existe = $validar->fetchColumn();


if ($existe > 0) {

    header(
      "Location: ../views/programar_preventivos.php?error=duplicado"
    );
    exit;
}



/*=========================================
CALCULAR PRÓXIMA FECHA
=========================================*/

$proximo = $fecha_programada;

switch ($frecuencia) {

    case 'Semanal':
        $proximo = date(
            'Y-m-d',
            strtotime('+1 week', strtotime($fecha_programada))
        );
        break;

    case 'Mensual':
        $proximo = date(
            'Y-m-d',
            strtotime('+1 month', strtotime($fecha_programada))
        );
        break;

    case 'Trimestral':
        $proximo = date(
            'Y-m-d',
            strtotime('+3 months', strtotime($fecha_programada))
        );
        break;

    case 'Semestral':
        $proximo = date(
            'Y-m-d',
            strtotime('+6 months', strtotime($fecha_programada))
        );
        break;

    case 'Anual':
        $proximo = date(
            'Y-m-d',
            strtotime('+1 year', strtotime($fecha_programada))
        );
        break;
}



/*=========================================
INSERTAR NUEVO PREVENTIVO
=========================================*/

$sql = "
INSERT INTO preventivos_programados(
    equipo_id,
    tipo_frecuencia,
    fecha_programada,
    fecha_vencimiento,
    proxima_fecha_programada,
    estado,
    descripcion,
    responsable_programacion
)
VALUES(
    ?,?,?,?,?,?,?,?
)
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    $equipo_id,
    $frecuencia,
    $fecha_programada,
    $fecha_programada,
    $proximo,
    'Programado',
    $descripcion,
    $responsable
]);


header(
'Location: ../views/programar_preventivos.php?ok=1'
);

exit;