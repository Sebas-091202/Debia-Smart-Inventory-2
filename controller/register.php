<?php
session_start();
require '../bd/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Validar token CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Acceso denegado: Token CSRF inválido.");
    }

    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $correo = trim($_POST['correo']);
    $numero_identificacion = trim($_POST['numero_identificacion']);
    $rol = trim($_POST['rol']);
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);

    // Validación de lista blanca
    if (!in_array($rol, ['ADMIN', 'USUARIO'])) {
        echo "<script>alert('Rol no válido.'); window.location.href='../views/index_Login.php';</script>";
        exit;
    }

    try {
        // Iniciar transacción para evitar condiciones de carrera
        $conn->beginTransaction();

        if ($rol === 'ADMIN') {
            // FOR UPDATE bloquea la fila durante la lectura para evitar concurrencia
            $stmt = $conn->query("SELECT COUNT(id) FROM usuarios WHERE rol='ADMIN' FOR UPDATE");
            if ($stmt->fetchColumn() > 0) {
                $rol = 'USUARIO'; // Forzar si ya existe
            }
        }

        // Definir redirección basada en el rol final evaluado
        $redireccion = ($rol === 'ADMIN') ? '../views/index_Admin.php' : '../views/index_Usuario.php';

        $sql = "INSERT INTO usuarios (nombre, usuario, correo, numero_identificacion, contrasena, rol) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nombre, $usuario, $correo, $numero_identificacion, $contrasena, $rol]);
        
        $nuevoId = $conn->lastInsertId();

        // Autologuear al usuario tras el registro para que la redirección funcione
        session_regenerate_id(true);
        $_SESSION['id'] = $nuevoId;
        $_SESSION['usuario'] = $usuario;
        $_SESSION['rol'] = $rol;

        $conn->commit();
        echo "<script>alert('Registro exitoso.'); window.location.href='$redireccion';</script>";
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('El usuario o correo ya existe.'); window.location.href='../views/index_Login.php';</script>";
        exit;
    }
}