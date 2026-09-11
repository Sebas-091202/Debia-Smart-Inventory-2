<?php
session_start();
require '../bd/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Validar token CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Acceso denegado: Token CSRF inválido.");
    }

    $usuario = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    $sql = "SELECT id, usuario, contrasena, rol FROM usuarios WHERE usuario = :usuario LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Validar existencia de usuario Y contraseña en el mismo bloque
    if ($user && password_verify($contrasena, $user['contrasena'])) {
        
        // 3. Prevenir fijación de sesión
        session_regenerate_id(true);
        
        $_SESSION['id'] = $user['id'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol'] = $user['rol'];

        if ($user['rol'] === 'ADMIN') {
            header("Location: ../views/index_Admin.php");
        } else {
            header("Location: ../views/index_Usuario.php");
        }
        exit;
    } else {
        echo "<script>alert('Credenciales incorrectas'); window.location.href='../views/index_Login.php';</script>";
        exit;
    }
}