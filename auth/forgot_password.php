<?php
// forgot_password.php
include '../includes/db.php';

$error = "";
$success = "";
$step = 1; // Paso 1: Identificación, Paso 2: Cambio de contraseña
$email_temp = "";
$bigo_id_temp = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['verify_identity'])) {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $bigo_id = mysqli_real_escape_string($conn, $_POST['bigo_id']);

        $query = "SELECT id FROM usuarios WHERE email = '$email' AND bigo_id = '$bigo_id'";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $step = 2;
            $email_temp = $email;
            $bigo_id_temp = $bigo_id;
        } else {
            $error = "Los datos no coinciden con nuestros registros.";
        }
    } elseif (isset($_POST['reset_password'])) {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $bigo_id = mysqli_real_escape_string($conn, $_POST['bigo_id']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error = "Las contraseñas no coinciden.";
            $step = 2;
            $email_temp = $email;
            $bigo_id_temp = $bigo_id;
        } else {
            $password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $query_update = "UPDATE usuarios SET password = '$password_hashed' WHERE email = '$email' AND bigo_id = '$bigo_id'";
            
            if (mysqli_query($conn, $query_update)) {
                $success = "Contraseña actualizada exitosamente. Ya puedes iniciar sesión.";
                $step = 3; // Paso 3: Muestro el mensaje final de éxito
            } else {
                $error = "Error al actualizar la contraseña: " . mysqli_error($conn);
                $step = 2;
                $email_temp = $email;
                $bigo_id_temp = $bigo_id;
            }
        }
    }
}

include '../includes/header.php';
?>

<!-- Reutilizo los estilos de login/register para mantener coherencia -->
<link rel="stylesheet" href="../css/login.css">

<main class="container">
    <div class="auth-container">
        <h2 class="auth-title">Recuperar Contraseña</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <p style="margin-bottom: 20px; color: #94a3b8; font-size: 0.95rem;">Ingresa tu correo y Bigo ID para verificar tu identidad.</p>
            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="ejemplo@correo.com" required>
                </div>
                <div class="form-group">
                    <label for="bigo_id">Tu Bigo ID</label>
                    <input type="text" name="bigo_id" id="bigo_id" class="form-control" placeholder="ID de Bigo" required>
                </div>
                <button type="submit" name="verify_identity" class="btn btn-primary btn-auth">Verificar Datos</button>
            </form>
        <?php elseif ($step == 2): ?>
            <p style="margin-bottom: 20px; color: #69f0ae; font-size: 0.95rem;">¡Datos verificados! Ingresa tu nueva contraseña.</p>
            <form action="forgot_password.php" method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email_temp); ?>">
                <input type="hidden" name="bigo_id" value="<?php echo htmlspecialchars($bigo_id_temp); ?>">
                
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" name="reset_password" class="btn btn-primary btn-auth">Actualizar Contraseña</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer" style="margin-top: 24px;">
            <a href="login.php" style="color: #60a5fa;">← Volver al inicio de sesión</a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
