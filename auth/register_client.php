<?php
// register_client.php - Registro libre de clientes
session_start();
include '../includes/db.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: ../client/client_panel.php");
    exit();
}

$error = '';
$success = '';

if (isset($_POST['register'])) {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Verifico el nombre de usuario
        $check = mysqli_query($conn, "SELECT id FROM usuarios WHERE nombre = '$nombre'");
        if (mysqli_num_rows($check) > 0) {
            $error = "El nombre de usuario ya está registrado. Por favor elige otro.";
        } else {
            // Genero email ficticio único
            $unique_email = preg_replace('/[^a-z0-9]/', '', strtolower($nombre)) . '_' . time() . '@client.local';
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO usuarios (nombre, email, password, rol, estado, tipo_contenido) VALUES ('$nombre', '$unique_email', '$pass_hash', 'viewer', 'activo', 'standard')";

            if (mysqli_query($conn, $query)) {
                $new_id = mysqli_insert_id($conn);
                $_SESSION['usuario_id'] = $new_id;
                $_SESSION['nombre'] = $nombre;
                $_SESSION['rol'] = 'viewer';

                header("Location: ../client/client_panel.php");
                exit();
            } else {
                $error = "Error al registrar: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Cliente - SOCIAL-MILLIONS</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Montserrat:wght@300;400;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --gold: #d4af37;
            --dark-gold: #b8962e;
            --black: #0a0a0a;
            --dark-gray: #1a1a1a;
            --text-gray: #ccc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--black);
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('../img/login-bg.jpg');
            background-size: cover;
            background-position: center;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
        }

        .login-container {
            background: rgba(20, 20, 20, 0.95);
            padding: 40px;
            border-radius: 15px;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 10;
            border: 1px solid #333;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .brand-title {
            text-align: center;
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .brand-title span {
            color: var(--gold);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--gold);
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 5px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: var(--gold);
            outline: none;
            box-shadow: 0 0 10px rgba(212, 175, 55, 0.2);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--gold), var(--dark-gold));
            color: #000;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: var(--text-gray);
            font-size: 0.9rem;
        }

        .form-footer a {
            color: var(--gold);
            text-decoration: none;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 10px;
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid red;
            color: #ffcccc;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="overlay"></div>
    <div class="login-container">
        <h1 class="brand-title">SOCIAL<span>MILLIONS</span></h1>
        <h2 style="text-align:center; font-size:1.2rem; margin-bottom:20px; color:#ccc;">Crear Cuenta de Cliente</h2>

        <?php if ($error): ?>
            <div class="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nombre de Usuario</label>
                <input type="text" name="nombre" placeholder="Tu nombre público" required>
            </div>

            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="******" required>
            </div>
            <div class="form-group">
                <label>Confirmar Contraseña</label>
                <input type="password" name="confirm_password" placeholder="******" required>
            </div>
            <button type="submit" name="register" class="btn-submit">Registrarse Gratis</button>
        </form>

        <div class="form-footer">
            <p>¿Ya tienes cuenta? <a href="login_viewer.php">Iniciar Sesión</a></p>
        </div>
    </div>
</body>

</html>