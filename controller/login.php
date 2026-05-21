<?php
require '../bd/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $usuario = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    // SOLO buscar por usuario
    $sql = "SELECT * FROM usuarios WHERE usuario = :usuario";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['usuario' => $usuario]);

    $user = $stmt->fetch();

    // Validar contraseña correctamente
    if ($user && password_verify($contrasena, $user['contrasena'])) {

        session_start();
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol'] = $user['rol'];

        if ($user['rol'] == 'ADMIN') {
            header("Location: ../views/index_Admin.php");
        } else {
            header("Location: ../views/index_Usuario.php");
        }
        exit;

    } else {
        echo "<script>alert('Credenciales incorrectas'); window.location.href='../views/index_Login.php';</script>";
    }
}
?>