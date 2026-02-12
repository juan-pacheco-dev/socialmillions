<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $bigo_id = mysqli_real_escape_string($conn, $_POST['bigo_id']);
    $celular = mysqli_real_escape_string($conn, $_POST['celular']);

    // Verifico si el ID de Bigo existe
    $check = mysqli_query($conn, "SELECT id FROM usuarios WHERE bigo_id = '$bigo_id' OR email = '$bigo_id@socialmillions.com'");
    if (mysqli_num_rows($check) > 0) {
        $message = "Error: Ese Bigo ID o Email ya existe.";
        $message_type = "error";
    } else {
        // Genero credenciales automáticamente
        $email = $bigo_id . "@socialmillions.com";
        $password_plain = $bigo_id;
        $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

        // Por defecto: Eventos Bloqueados (0)
        $sql = "INSERT INTO usuarios (nombre, email, password, bigo_id, celular, rol, event_access) VALUES ('$nombre', '$email', '$password_hash', '$bigo_id', '$celular', 'user', 0)";

        if (mysqli_query($conn, $sql)) {
            $message = "✅ Usuario creado exitosamente.<br>Email: <b>$email</b><br>Pass: <b>$password_plain</b>";
            $message_type = "success";
        } else {
            $message = "Error DB: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

include '../includes/header.php';
?>
<link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
<style>
    .form-box {
        max-width: 500px;
        margin: 50px auto;
        padding: 40px;
        background: #0f172a;
        border: 1px solid #1e293b;
        border-radius: 12px;
    }

    input {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        background: #1e293b;
        border: 1px solid #334155;
        color: white;
        border-radius: 6px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        color: #94a3b8;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
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

    @media (max-width: 768px) {
        main {
            padding-top: 80px;
        }

        .form-box {
            padding: 25px 20px;
        }
    }
</style>

<main class="container" style="padding-top: 100px;">
    <div class="form-box">
        <h2 style="text-align: center; color: white; margin-bottom: 20px;">Alta Manual de Usuario</h2>
        <?php if ($message): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Nombre Completo</label>
            <input type="text" name="nombre" required>

            <label>Bigo ID (Será su contraseña inicial)</label>
            <input type="text" name="bigo_id" required>

            <label>Celular</label>
            <input type="text" name="celular" required>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Usuario</button>
        </form>
        <div style="text-align: center; margin-top: 20px;">
            <a href="admin_panel.php" style="color: #94a3b8;">Volver al Panel</a>
        </div>
    </div>
</main>
<?php include '../includes/footer.php'; ?>