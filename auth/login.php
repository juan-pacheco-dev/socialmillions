<?php
// Iniciar sesión de PHP para manejar persistencia de usuario
// Iniciar sesión de PHP para manejar persistencia de usuario
// session_start(); // Ya lo gestiona security.php si no está iniciada.
// Mejor retiramos el session_start explícito y dejamos que lo maneje login o el include, pero para evitar doble session_start, el include lo verifica.
// Sin embargo, login.php linea 3 tiene session_start(). 
// Voy a reemplazar el bloque superior para incluir security.php

include '../includes/db.php';
include '../includes/security.php'; // Esto maneja la sesión y funciones CSRF

$error = "";

// Procesar el formulario cuando se envía mediante POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar CSRF
    if (!isset($_POST['csrf_token']) || !verificar_token_csrf($_POST['csrf_token'])) {
        $error = "Error de validación de seguridad (CSRF). Por favor recarga la página.";
    } else {
        // Escapar el email/usuario
        $identifier = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];

        if (empty($identifier) || empty($password)) {
            $error = "Por favor, completa todos los campos.";
        } else {
            // Buscar al usuario por correo electrónico O nombre de usuario
            $query = "SELECT * FROM usuarios WHERE email = '$identifier' OR nombre = '$identifier'";
            $result = mysqli_query($conn, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                // Verificar si la contraseña ingresada coincide con el hash en la base de datos
                if (password_verify($password, $user['password'])) {
                    // Verificar el estado del usuario
                    if ($user['estado'] !== 'activo' && $user['rol'] !== 'admin') {
                        $error = "Tu cuenta está " . ($user['estado'] == 'pendiente' ? "pendiente de aprobación" : "bloqueada") . ".";
                    } else {
                        // Autenticación exitosa: Guardar datos en la sesión
                        $_SESSION['usuario_id'] = $user['id'];
                        $_SESSION['nombre'] = $user['nombre'];
                        $_SESSION['rol'] = $user['rol'];
                        $_SESSION['agencia_id'] = $user['agencia_id'] ?? null; // Necesario para el acceso al panel de agencia
                        $_SESSION['bigo_id'] = $user['bigo_id'] ?? null;
                        $_SESSION['plataforma'] = $user['plataforma'] ?? null; // AGREGADO: para filtrar elementos de UI como Impulsos
                        $_SESSION['celular'] = $user['celular'] ?? null;
                        $_SESSION['tipo_contenido'] = $user['tipo_contenido'] ?? null; // Verificación de seguridad

                        // Verificar si el espectador tiene modelo vinculado (Corrección importante)


                        // Redirección basada en el rol y tipo de contenido
                        session_write_close();
                        if ($user['rol'] == 'admin') {
                            header("Location: ../admin/admin_panel.php");
                        } elseif ($user['rol'] == 'agency_admin' && !empty($user['agencia_id'])) {
                            // Redirigir al panel de su agencia específica
                            $qa = mysqli_query($conn, "SELECT slug FROM agencias WHERE id = " . (int) $user['agencia_id']);
                            if ($ra = mysqli_fetch_assoc($qa)) {
                                header("Location: ../agencies/" . $ra['slug'] . "/");
                            } else {
                                // Fallback si no encuentra la agencia
                                header("Location: ../client/user_panel.php");
                            }
                        } elseif ($user['rol'] == 'model') {
                            header("Location: ../client/user_panel.php");
                        } else {
                            // Usuarios normales o streamers normales (rol 'user')
                            header("Location: ../client/user_panel.php");
                        }
                        exit();
                    }
                } else {
                    $error = "La contraseña es incorrecta.";
                }
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
        }
    }
}

include '../includes/header.php';
?>

<!-- Link al CSS específico de login -->
<link rel="stylesheet" href="../css/login.css">

<main class="container">
    <div class="auth-container">
        <h2 class="auth-title">Iniciar Sesión</h2>

        <!-- Mostrar mensajes de error -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">

            <div class="form-group">
                <label for="email">Usuario o Correo</label>
                <input type="text" name="email" id="email" class="form-control" placeholder="Tu usuario o correo"
                    required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••"
                    required>
            </div>

            <button type="submit" class="btn btn-primary btn-auth">Entrar</button>
        </form>

        <div class="auth-footer" style="margin-top: 10px;">
            <a href="forgot_password.php" style="font-size: 0.9rem; color: #60a5fa;">¿Olvidaste tu contraseña?</a>
        </div>

        <div class="auth-footer">
            ¿No tienes una cuenta? <a href="register.php">Regístrate gratis</a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>