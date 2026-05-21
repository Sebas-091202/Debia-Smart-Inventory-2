<?php
require '../bd/conn.php';

$tipo = $_POST['tipo'];
$marca = strtoupper(substr($_POST['marca'], 0, 1));
$identificador = $_POST['identificador'];

$codigo = $tipo . "-" . $marca . "-" . $identificador;

$codigo = $_POST['codigo_barras'];

$stmt = $conn->prepare("
INSERT INTO equipos (
    tipo,
    marca,
    identificador,
    asignado_a,
    serial,
    procesador,
    ram,
    disco,

    disco2,
    estado,
    ubicacion,
    codigo_barras
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)

    estado,
    ubicacion,
    codigo_barras
) VALUES (?,?,?,?,?,?,?,?,?,?,?)

");

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
    $codigo //    AQUI ESTÁ LA CLAVE
]);

// Guarda normalmente todos los datos en BD, incluyendo el código de barras que se ha generado o ingresado

// GENERAR QR
$rutaQR = "../qrs/" . $codigo . ".png";

require_once("../lib/phpqrcode/qrlib.php");

$url = "http://localhost/ConsulSoft/views/hoja_vida_equipos.php?codigo=" . $codigo;

QRcode::png($url, $rutaQR, QR_ECLEVEL_L, 4);

header("Location: ../views/ver_qr.php?codigo=" . $codigo);
exit();
?>