<?php
require '../bd/conn.php';


/* VERIFICAR SI YA EXISTE UN ADMIN */
$stmt = $conn->query("
SELECT COUNT(*) 
FROM usuarios 
WHERE rol = 'ADMIN'
");

$existeAdmin = $stmt->fetchColumn() > 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/main.css">
    <title>Inicio de Sesion y Registro</title>
    <style>
        body {
            display: flex;
            justify-content: flex-end;
            /* mueve el contenido hacia la derecha */
            align-items: center;
            width: 100%;
            height: 100vh;
            background-image: url(../img/Background.png);
            background-size: cover;
            /* ahora abarca toda la pantalla */
            background-repeat: no-repeat;
            background-position: center;
            margin: 0;
        }

        .container-form {
            display: flex;
            border-radius: 20px;
            box-shadow: 0 5px 7px rgba(0, 0, 13, 11);
            height: 450px;
            max-width: 720px;
            transition: all 2s ease;
            margin-right: 100px;
            /* opcional: separa del borde derecho */
            background-color: white;
            /* opcional: para asegurar contraste */
        }

        @media screen and (max-width: 580px) {
            body {
                justify-content: center;
            }

            .container-form {
                margin: 0;
            }
        }

        .info-childs h2 {
            font-size: 2.2rem;
            justify-content: center;
            color: #ffffff;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 50px;
            color: white;
        }

        header img {
            height: 75px;
        }

        .input-box {
            font-family: 'Montserrat', sans-serif;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            border-radius: 20px;
            padding: 0 10px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
            width: 100%;
        }

        .input-box select {
            width: 100%;
            padding: 10px;
            background-color: #fff;
            border: none;
            outline: none;
            border-radius: 20px;
            color: #333;
            font-size: 1rem;
            cursor: pointer;
        }

        .input-box select:focus {
            outline: solid 2px #9191bd;
        }
    </style>
</head>

<body>
    <div class="container-form register">
        <div class="information">
            <div class="info-childs">
                <h2>Bienvenido</h2>
                <p>Si ya cuentas con usuario y contraseña, inicia sesion!</p>
                <input type="button" value="Iniciar Sesión" id="sign-in">
            </div>
        </div>
        <!-- FORMULARIO DE REGISTRO -->
        <div class="form-information">
            <div class="form-information-childs">
                <h2>Crear una Cuenta</h2>
                <form class="form form-register" action="../controller/register.php" method="post">
                    <div>
                        <label>
                            <i class='bx bx-user'></i>
                            <input type="text" placeholder="Nombre" name="nombre" id="nombre" required>
                        </label>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-user'></i>
                            <input type="text" placeholder="Usuario" name="usuario" id="usuario" required>
                        </label>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-user'></i>
                            <input type="text" placeholder="Correo" name="correo" id="correo" required>
                        </label>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-user'></i>
                            <input type="number" placeholder="Numero de identificación" name="numero_identificacion"
                                id="numero_identificacion" required>
                        </label>
                    </div>
                    <div class="input-box">
                            <select name="rol" id="rol" required>
                                <option value="">Seleccione su Rol</option>

                                <?php if (!$existeAdmin): ?>
                                    <option value="ADMIN">Administrador</option>
                                <?php endif; ?>

                                <option value="USUARIO">Usuario</option>
                            </select>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" placeholder="Contraseña" name="contrasena" id="contrasena" required>
                        </label>
                    </div>
                    <input type="submit" value="Registrarse">
                    <div class="alerta-error">Todos los campos son obligatorios</div>
                    <div class="alerta-exito">Te registraste correctamente</div>
                </form>
            </div>
        </div>
    </div>
    <!-- FORMULARIO DE INICIO DE SESION -->
    <div class="container-form login hide">
        <div class="information">
            <div class="info-childs">
                <h2>¡¡Bienvenido nuevamente!!</h2>
                <p>Para unirte a nuestra comunidad por favor registra tus datos!</p>
                <input type="button" value="Registrarse" id="sign-up">
            </div>
        </div>
        <div class="form-information">
            <div class="form-information-childs">
                <h2>Iniciar Sesión</h2>
                <p>o Iniciar Sesión con una cuenta</p>
                <form class="form form-login" action="../controller/login.php" method="post">
                    <div>
                        <label>
                            <i class='bx bx-user'></i>
                            <input type="text" placeholder="Usuario" name="usuario" id="usuario" required>
                        </label>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" placeholder="Contraseña" name="contrasena" id="contrasena" required>
                        </label>
                    </div>
                    <input type="submit" value="Iniciar Sesión">
                    <div class="alerta-error">Todos los campos son obligatorios</div>
                    <div class="alerta-exito">Te registraste correctamente</div>
                </form>
            </div>
        </div>

    </div>
    <script src="../js/script.js"></script>
</body>

</html>