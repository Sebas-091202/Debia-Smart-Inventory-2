<?php
require '../bd/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $correo = trim($_POST['correo']);
    $numero_identificacion = trim($_POST['numero_identificacion']);
    $rol = trim($_POST['rol']);

    // ENCRIPTAR CONTRASEÑA
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);

    $tabla = '';
    $redireccion = '';

    switch ($rol) {
        case 'ADMIN':
            $tabla = 'usuarios';
            $redireccion = '../views/index_Admin.php';
            break;
        case 'USUARIO':
            $tabla = 'usuarios';
            $redireccion = '../views/index_Usuario.php';
            break;
        default:
            echo "<script>alert('Rol no válido.'); window.location.href='../views/index_Login.php';</script>";
            exit;
    }

    $rol = $_POST['rol'];

    /* VALIDAR QUE SOLO EXISTA UN ADMIN */
    if ($rol == 'ADMIN') {

        $stmt = $conn->query("
    SELECT COUNT(*) FROM usuarios WHERE rol='ADMIN'
    ");

        $yaExiste = $stmt->fetchColumn();

        if ($yaExiste > 0) {

            // FORZAR A USUARIO
            $rol = 'USUARIO';
        }
    }


    $sql = "INSERT INTO $tabla (nombre, usuario, correo, numero_identificacion, contrasena, rol) VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$nombre, $usuario, $correo, $numero_identificacion, $contrasena, $rol])) {
        echo "<script>alert('Registro exitoso.'); window.location.href='$redireccion';</script>";
    } else {
        echo "<script>alert('Error al registrar.'); window.location.href='../views/index_Login.php';</script>";
    }
?>