<?php
session_start();
require '../bd/conn.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $conn->prepare("SELECT COUNT(id) FROM usuarios WHERE rol = :rol");
$stmt->execute(['rol' => 'ADMIN']);
$existeAdmin = $stmt->fetchColumn() > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Debia Smart Inventory - Acceso</title>
    <style>
        :root {
            --primary-color: #c40c0c;
            --secondary-color: #21344A;
            --bg-light: #f8f8f8;
            --text-dark: #333333;
            --text-light: #ffffff;
            --icon-color: #9191bd;
            --border-radius: 20px;
            --shadow-sm: 0 2px 5px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 5px 25px rgba(0, 0, 0, 0.2);
        }

        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            width: 100%;
            min-height: 100vh;
            background-image: url('../img/Background.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            background-color: #eaeaea;
            transition: background-image 0.5s ease;
        }

        .container-form {
            display: flex;
            flex-direction: row;
            align-items: stretch;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            background-color: var(--text-light);
            overflow: hidden;
            width: 850px; 
            max-width: 95%;
            min-height: 500px;
            margin-right: 8%;
            transition: all 0.6s ease-in-out;
            opacity: 1;
            transform: translateY(0);
        }

        .information {
            width: 40%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background-color: var(--primary-color);
            padding: 2.5rem;
            color: var(--text-light);
        }

        .info-childs { width: 100%; }
        .info-childs h2 { font-size: 2.2rem; margin-bottom: 1rem; }
        .info-childs p { margin-bottom: 2.5rem; line-height: 1.5; font-weight: 400; }

        .info-childs input[type="button"] {
            background-color: transparent;
            border: 2px solid var(--text-light);
            border-radius: var(--border-radius);
            padding: 10px 30px;
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .info-childs input[type="button"]:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            box-shadow: var(--shadow-sm);
        }

        .form-information {
            width: 60%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background-color: var(--bg-light);
            padding: 2.5rem;
        }

        .form-information-childs {
            width: 100%;
            max-width: 350px;
        }

        /* LOGO OCULTO EN ESCRITORIO POR DEFECTO */
        .form-logo {
            display: none; 
            margin: 0 auto 1.5rem auto;
        }

        .form-information-childs h2 {
            color: var(--text-dark);
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 100%;
        }

        .input-box {
            display: flex;
            align-items: center;
            border-radius: var(--border-radius);
            padding: 0 15px;
            background-color: var(--text-light);
            box-shadow: var(--shadow-sm);
            height: 48px;
            transition: box-shadow 0.3s ease;
        }

        .input-box:focus-within {
            box-shadow: 0 0 0 2px var(--icon-color);
        }

        .input-box i {
            color: var(--icon-color);
            font-size: 1.3rem;
            margin-right: 12px;
        }

        .input-box input, .input-box select {
            width: 100%;
            height: 100%;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .form input[type="submit"] {
            background-color: var(--primary-color);
            color: var(--text-light);
            border-radius: var(--border-radius);
            border: none;
            padding: 14px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            transition: background-color 0.3s ease;
            margin-top: 15px;
        }

        .form input[type="submit"]:hover { background-color: var(--secondary-color); }
        
        .hide { 
            opacity: 0;
            visibility: hidden;
            position: absolute;
            transform: translateY(-50px);
            z-index: -1;
        }

        /* RESPONSIVE DESIGN - MEDIA QUERIES */
        @media screen and (max-width: 850px) {
            body {
                justify-content: center;
                margin-right: 0;
                padding: 20px;
                background-image: none;
                background-color: var(--secondary-color);
            }
            .container-form {
                flex-direction: column;
                width: 100%;
                max-width: 450px;
                height: auto;
            }
            /* CORRECCIÓN: ANCHOS AL 100% PARA EVITAR QUE SE CORTEN */
            .information {
                width: 100%;
                padding: 2.5rem 2rem;
                order: 1;
            }
            .form-information {
                width: 100%;
                padding: 2.5rem 2rem;
                order: 2;
            }
            /* MUESTRA EL LOGO SOLO EN MÓVILES */
            .form-logo {
                display: block; 
                max-width: 150px;
                height: auto;
            }
        }
    </style>
</head>
<body>
    <!-- FORMULARIO DE REGISTRO -->
    <div class="container-form register">
        <div class="information">
            <div class="info-childs">
                <h2>Bienvenido</h2>
                <p>Si ya cuentas con usuario y contraseña, ¡inicia sesión!</p>
                <input type="button" value="Iniciar Sesión" id="sign-in">
            </div>
        </div>
        <div class="form-information">
            <div class="form-information-childs">
                <img src="../img/logo.png" alt="Logo Debia" class="form-logo">
                <h2>Crear una Cuenta</h2>
                <form class="form form-register" action="../controller/register.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="input-box">
                        <i class='bx bx-user'></i>
                        <input type="text" placeholder="Nombre completo" name="nombre" required>
                    </div>
                    <div class="input-box">
                        <i class='bx bx-user-circle'></i>
                        <input type="text" placeholder="Usuario" name="usuario" required>
                    </div>
                    <div class="input-box">
                        <i class='bx bx-envelope'></i>
                        <input type="email" placeholder="Correo electrónico" name="correo" required>
                    </div>
                    <div class="input-box">
                        <i class='bx bx-id-card'></i>
                        <input type="number" placeholder="Identificación" name="numero_identificacion" required>
                    </div>
                    <div class="input-box">
                        <select name="rol" required>
                            <option value="" disabled selected>Seleccione su Rol</option>
                            <?php if (!$existeAdmin): ?>
                                <option value="ADMIN">Administrador</option>
                            <?php endif; ?>
                            <option value="USUARIO">Usuario</option>
                        </select>
                    </div>
                    <div class="input-box">
                        <i class='bx bx-lock-alt'></i>
                        <input type="password" placeholder="Contraseña" name="contrasena" required>
                    </div>
                    <input type="submit" value="Registrarse">
                </form>
            </div>
        </div>
    </div>

    <!-- FORMULARIO DE INICIO DE SESIÓN -->
    <div class="container-form login hide">
        <div class="information">
            <div class="info-childs">
                <h2>¡Bienvenido!</h2>
                <p>Para unirte a nuestra comunidad por favor registra tus datos.</p>
                <input type="button" value="Registrarse" id="sign-up">
            </div>
        </div>
        <div class="form-information">
            <div class="form-information-childs">
                <img src="../img/logo.png" alt="Logo Debia" class="form-logo">
                <h2>Iniciar Sesión</h2>
                <form class="form form-login" action="../controller/login.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="input-box">
                        <i class='bx bx-user'></i>
                        <input type="text" placeholder="Usuario" name="usuario" required>
                    </div>
                    <div class="input-box">
                        <i class='bx bx-lock-alt'></i>
                        <input type="password" placeholder="Contraseña" name="contrasena" required>
                    </div>
                    <input type="submit" value="Iniciar Sesión">
                </form>
            </div>
        </div>
    </div>
    
    <script src="../js/script.js"></script>
</body>
</html>