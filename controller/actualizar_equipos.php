<?php
require '../bd/conn.php';
require_once '../lib/phpqrcode/qrlib.php';

session_start(); // ESTO ES OBLIGATORIO

// VALIDAR SESIÓN
if (!isset($_SESSION['id'])) {
    die("Error: sesión no válida");
}

$id = $_POST['id'];
$codigo = $_POST['codigo_barras'];

/* 1. OBTENER CÓDIGO ANTERIOR */
$old = $conn->prepare("SELECT codigo_barras FROM equipos WHERE id=?");
$old->execute([$id]);
$oldCodigo = $old->fetchColumn();


/* 2. ACTUALIZAR EQUIPO */
$sql = "
UPDATE equipos SET 
    tipo=?, marca=?, identificador=?, asignado_a=?, serial=?,
    procesador=?, ram=?, disco=?, disco2=?, estado=?, ubicacion=?, codigo_barras=?
WHERE id=?
";

$stmt = $conn->prepare($sql);
$stmt->execute([
    $_POST['tipo'],
    $_POST['marca'],
    $_POST['identificador'],
    $_POST['asignado_a'],
    $_POST['serial'],
    $_POST['procesador'],
    $_POST['ram'],
    $_POST['disco'],
    $_POST['disco2'],
    $_POST['estado'],
    $_POST['ubicacion'],
    $codigo,
    $id
]);

$log = $conn->prepare("
INSERT INTO logs_sistema (usuario_id, accion, detalle)
VALUES (?, ?, ?)
");

$log->execute([
    $_SESSION['id'],
    'ACTUALIZAR EQUIPO',
    'Se actualizó el equipo con serial: ' . $codigo
]);



/* 3. GENERAR QR */
$rutaQR = "../qrs/";

if (!file_exists($rutaQR)) {
    mkdir($rutaQR);
}

/* eliminar QR viejo */
if ($oldCodigo && $oldCodigo != $codigo) {
    $archivoViejo = $rutaQR . $oldCodigo . ".png";
    if (file_exists($archivoViejo)) {
        unlink($archivoViejo);
    }
}

/* crear nuevo QR */
$archivoQR = $rutaQR . $codigo . ".png";

$url = "http://localhost/debia-smart-inventory/views/hoja_vida_equipos.php?codigo=" . $codigo;

QRcode::png($url, $archivoQR, QR_ECLEVEL_L, 4);


/* 🔥 4. REDIRECCIÓN */
header("Location: ../views/ver_qr.php?codigo=" . $codigo);
exit;

?>