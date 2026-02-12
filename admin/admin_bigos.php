<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$msg = "";
$error = "";

// Actualizo el ID de Bigo
if (isset($_POST['update_bigo'])) {
    $uid = (int) $_POST['user_id'];
    $new_bigo = mysqli_real_escape_string($conn, $_POST['new_bigo']);
    $query = "UPDATE usuarios SET bigo_id = '$new_bigo' WHERE id = $uid";
    if (mysqli_query($conn, $query)) {
        $msg = "Bigo ID actualizado correctamente.";
    } else {
        $error = "Error al actualizar: " . mysqli_error($conn);
    }
}

// Obtengo solo streamers (rol='user')
$query_users = "SELECT id, nombre, email, bigo_id FROM usuarios WHERE rol = 'user' ORDER BY nombre ASC";
$res_users = mysqli_query($conn, $query_users);

include '../includes/header.php';
?>

<style>
    .bigo-wrapper {
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

    .users-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 4px;
    }

    .users-table th {
        padding: 12px 15px;
        text-align: left;
        color: #94a3b8;
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #1e293b;
        white-space: nowrap;
    }

    .users-table td {
        padding: 12px 15px;
        background: #1e293b;
        color: #ffffff;
        font-size: 0.85rem;
        vertical-align: middle;
    }

    .status-id-badge {
        background: rgba(59, 130, 246, 0.1);
        color: #60a5fa;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.8rem;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }

    .bigo-action-form {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .bigo-input-field {
        background: #020617;
        border: 1px solid #334155;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        width: 140px;
        font-size: 0.85rem;
        transition: 0.3s;
    }

    .bigo-input-field:focus {
        border-color: #3b82f6;
        outline: none;
    }

    .btn-save-luxe {
        background: #3b82f6;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        font-size: 0.8rem;
        transition: 0.3s;
    }

    .btn-save-luxe:hover {
        background: #2563eb;
        transform: translateY(-2px);
    }

    @media (max-width: 800px) {
        .bigo-wrapper {
            padding-top: 100px;
            padding-left: 15px;
            padding-right: 15px;
        }

        .admin-card-luxe {
            padding: 20px;
        }

        .scroll-hint {
            display: flex !important;
            justify-content: center;
            padding: 8px;
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            font-size: 0.7rem;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 1px dashed rgba(59, 130, 246, 0.3);
        }
    }
</style>

<div class="bigo-wrapper">
    <div style="margin-bottom: 40px; text-align: center;">
        <h1 style="color: white; margin-bottom: 12px; font-size: 2.2rem; font-weight: 800;">🆔 Gestión de Bigo IDs</h1>
        <p style="color: #94a3b8; font-size: 1rem; max-width: 600px; margin: 0 auto;">Control centralizado de
            identificadores para streamers. Asegura un rastreo preciso de la actividad.</p>
    </div>

    <?php if ($msg): ?>
        <div
            style="background: rgba(34, 197, 94, 0.1); color: #4ade80; padding: 20px; border-radius: 12px; border: 1px solid rgba(34, 197, 94, 0.2); margin-bottom: 30px; text-align: center; font-weight: 700;">
            ✅ <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="scroll-hint"><span>↔️ Desliza hacia los lados para ver toda la tabla</span></div>

    <div class="admin-card-luxe">
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th style="min-width: 150px;">Streamer</th>
                        <th style="min-width: 200px;">Email de Contacto</th>
                        <th style="min-width: 150px;">ID Actual</th>
                        <th style="min-width: 250px;">Acción de Actualización</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($res_users) > 0): ?>
                        <?php while ($u = mysqli_fetch_assoc($res_users)): ?>
                            <tr>
                                <td style="font-weight: 700; color: #fff;">
                                    <?php echo htmlspecialchars($u['nombre']); ?>
                                </td>
                                <td style="color: #94a3b8; font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($u['email']); ?>
                                </td>
                                <td>
                                    <span class="status-id-badge">
                                        <?php echo htmlspecialchars($u['bigo_id'] ? $u['bigo_id'] : 'SIN ASIGNAR'); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="bigo-action-form">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="text" name="new_bigo"
                                            value="<?php echo htmlspecialchars($u['bigo_id']); ?>" placeholder="Nuevo ID..."
                                            required class="bigo-input-field">
                                        <button type="submit" name="update_bigo" class="btn-save-luxe">
                                            💾 Guardar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #475569; padding: 50px; font-style: italic;">
                                No se encontraron streamers registrados en la base de datos.</td>
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