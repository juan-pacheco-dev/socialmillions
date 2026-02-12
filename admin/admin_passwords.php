<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$msg = "";
$error = "";

// Actualizo la Contraseña
if (isset($_POST['update_password'])) {
    $uid = (int) $_POST['user_id'];
    $new_pass = $_POST['new_password'];

    if (strlen($new_pass) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $query = "UPDATE usuarios SET password = '$hashed_pass' WHERE id = $uid";
        if (mysqli_query($conn, $query)) {
            $msg = "Contraseña de " . htmlspecialchars($_POST['user_name']) . " actualizada correctamente.";
        } else {
            $error = "Error al actualizar: " . mysqli_error($conn);
        }
    }
}

// Obtengo solo streamers/modelos (rol='user')
$query_users = "SELECT id, nombre, email FROM usuarios WHERE rol = 'user' ORDER BY nombre ASC";
$res_users = mysqli_query($conn, $query_users);

include '../includes/header.php';
?>

<style>
    .pass-wrapper {
        padding: 120px 20px 60px;
        max-width: 1100px;
        margin: 0 auto;
    }

    .admin-card-luxe {
        background: #0f172a;
        border: 1px solid #1e293b;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        background: #1e293b;
        border-radius: 12px;
        border: 1px solid #334155;
        margin-top: 20px;
    }

    .users-table {
        width: 100%;
        min-width: 900px;
        border-collapse: separate;
        border-spacing: 0 4px;
    }

    .users-table th {
        text-align: left;
        padding: 16px 20px;
        color: #94a3b8;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        background: #0f172a;
        border-bottom: 2px solid #334155;
        white-space: nowrap;
    }

    .users-table td {
        padding: 16px 20px;
        background: #1e293b;
        color: white;
        vertical-align: middle;
        font-size: 0.85rem;
    }

    .pass-action-form {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .pass-input-field {
        background: #020617;
        border: 1px solid #334155;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        width: 160px;
        font-size: 0.85rem;
        transition: 0.3s;
    }

    .pass-input-field:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.2);
    }

    .btn-save-luxe {
        background: #3b82f6;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        font-size: 0.85rem;
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-save-luxe:hover {
        background: #2563eb;
        transform: translateY(-2px);
    }

    .scroll-hint {
        display: none;
        text-align: center;
        padding: 12px;
        color: #60a5fa;
        font-size: 0.75rem;
        font-weight: 700;
        background: rgba(59, 130, 246, 0.1);
        border-radius: 8px;
        border: 1px dashed rgba(59, 130, 246, 0.3);
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    @media (max-width: 800px) {
        .pass-wrapper {
            padding-top: 100px;
            padding-left: 15px;
            padding-right: 15px;
        }

        .admin-card-luxe {
            padding: 20px;
        }

        .scroll-hint {
            display: flex;
            justify-content: center;
        }
    }
</style>

<div class="pass-wrapper">
    <div style="margin-bottom: 40px; text-align: center;">
        <h1 style="color: white; margin-bottom: 12px; font-size: 2.2rem; font-weight: 800;">🔑 Gestión de Contraseñas
        </h1>
        <p style="color: #94a3b8; font-size: 1rem; max-width: 600px; margin: 0 auto;">Cambia las contraseñas de
            streamers y modelos en caso de pérdida o bloqueo.</p>
    </div>

    <?php if ($msg): ?>
        <div
            style="background: rgba(34, 197, 94, 0.1); color: #4ade80; padding: 20px; border-radius: 12px; border: 1px solid rgba(34, 197, 94, 0.2); margin-bottom: 30px; text-align: center; font-weight: 700;">
            ✅
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div
            style="background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 20px; border-radius: 12px; border: 1px solid rgba(239, 68, 68, 0.2); margin-bottom: 30px; text-align: center; font-weight: 700;">
            ❌
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="scroll-hint"><span>↔️ Desliza hacia los lados para ver toda la tabla</span></div>

    <div class="admin-card-luxe">
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th style="min-width: 200px;">Nombre Completo</th>
                        <th style="min-width: 250px;">Correo Electrónico</th>
                        <th style="min-width: 300px;">Nueva Contraseña</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res_users) > 0): ?>
                        <?php while ($u = mysqli_fetch_assoc($res_users)): ?>
                            <tr>
                                <td style="font-weight: 700; color: #fff;">
                                    <?php echo htmlspecialchars($u['nombre']); ?>
                                </td>
                                <td style="color: #94a3b8;">
                                    <?php echo htmlspecialchars($u['email']); ?>
                                </td>
                                <td>
                                    <form method="POST" class="pass-action-form"
                                        onsubmit="return confirm('¿Estás seguro de cambiar la contraseña a este usuario?')">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="user_name"
                                            value="<?php echo htmlspecialchars($u['nombre']); ?>">
                                        <input type="text" name="new_password" placeholder="Mín. 6 caracteres" required
                                            class="pass-input-field">
                                        <button type="submit" name="update_password" class="btn-save-luxe">
                                            💾 Actualizar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #475569; padding: 50px; font-style: italic;">
                                No se encontraron usuarios registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 40px; text-align: center;">
        <a href="admin_panel.php"
            style="color: #94a3b8; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;"
            onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">
            &larr; Volver al Panel de Administración
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>