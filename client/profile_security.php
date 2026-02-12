<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'user' && $_SESSION['rol'] !== 'model')) {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_email = trim($_POST['new_email']);
    $new_password = trim($_POST['new_password']);
    $uid = $_SESSION['usuario_id'];

    if (empty($new_email) || empty($new_password)) {
        $message = "Todos los campos son obligatorios.";
        $message_type = "error";
    } else {
        // Hascheo la contraseña
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Actualizo la BD
        $sql = "UPDATE usuarios SET email = ?, password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $new_email, $hashed_password, $uid);

        if (mysqli_stmt_execute($stmt)) {
            $message = "✅ Credenciales actualizadas correctamente.";
            $message_type = "success";
            // Actualizo el Email de Sesión si es necesario
            $_SESSION['email'] = $new_email;
        } else {
            $message = "Error al actualizar: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

include '../includes/header.php';
?>
<link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/user_panel.css?v=<?php echo time(); ?>">

<style>
    .form-container {
        max-width: 500px;
        margin: 50px auto;
        padding: 30px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        color: #94a3b8;
        margin-bottom: 8px;
    }

    input {
        width: 100%;
        padding: 12px;
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 8px;
        color: #fff;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }

    .alert.success {
        background: rgba(34, 197, 94, 0.2);
        color: #86efac;
    }

    .alert.error {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
    }
</style>

<main class="panel-hero">
    <div class="container" style="padding-top: 100px;">
        <div class="panel-header-content">
            <h1>Seguridad de Cuenta</h1>
            <p>Actualiza tu correo y contraseña.</p>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Nuevo Correo Electrónico</label>
                    <input type="email" name="new_email" required
                        value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="new_password" required placeholder="********">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Guardar Cambios</button>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="user_panel.php" style="color: #94a3b8;">Volver</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>