<?php

require '../bd/conn.php';

if($_SERVER["REQUEST_METHOD"]=="POST"){

$sql="UPDATE equipos SET

tipo=?,
marca=?,
identificador=?,
asignado_a=?,
serial=?,
procesador=?,
ram=?,
disco=?,
estado=?,
ubicacion=?

WHERE id=?";

$stmt=$conn->prepare($sql);

$stmt->execute([

$_POST['tipo'],
$_POST['marca'],
$_POST['identificador'],
$_POST['asignado_a'],
$_POST['serial'],
$_POST['procesador'],
$_POST['ram'],
$_POST['disco'],
$_POST['estado'],
$_POST['ubicacion'],
$_POST['id']

]);

header("Location: ../views/ver_equipos.php");
exit();

}
?>