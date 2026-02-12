<?php
// admin_security.php - Gestión de Contraseñas de Emergencia
session_start();
include '../includes/db.php';

// Verifico permisos de admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success = "";
$error = "";

// Proceso cambio de contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id']) && isset($_POST['new_password'])) {
    $uid = (int) $_POST['user_id'];
    $new_pass = $_POST['new_password'];

    if (!empty($new_pass)) {
        // Encripto nueva contraseña (NO se puede descifrar la anterior, solo sobrescribir)
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);

        $upd = mysqli_query($conn, "UPDATE usuarios SET password = '$hashed' WHERE id = $uid");

        if ($upd) {
            // Obtengo nombre para mostrar en mensaje
            $user_res = mysqli_query($conn, "SELECT nombre FROM usuarios WHERE id = $uid");
            $u_data = mysqli_fetch_assoc($user_res);
            $nombre = $u_data['nombre'] ?? 'Usuario';

            $success = "✅ Contraseña de <b>$nombre</b> actualizada correctamente.";
        } else {
            $error = "Error al actualizar en base de datos.";
        }
    } else {
        $error = "La contraseña no puede estar vacía.";
    }
}

// Obtengo usuarios
$users = mysqli_query($conn, "SELECT id, nombre, email, rol, celular FROM usuarios ORDER BY nombre ASC");

include '../includes/header.php';
?>

<style>
    body {
        background: #0f172a;
        color: #e2e8f0;
        font-family: 'Inter', sans-serif;
    }

    .security-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 20px;
    }

    .security-header {
        background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
        padding: 30px;
        border-radius: 16px;
        margin-bottom: 30px;
        text-align: center;
        box-shadow: 0 10px 25px rgba(239, 68, 68, 0.2);
    }

    .security-header h2 {
        margin: 0;
        color: white;
        font-size: 2rem;
        font-weight: 800;
    }

    .security-header p {
        margin: 10px 0 0;
        color: rgba(255, 255, 255, 0.9);
    }

    .search-box {
        width: 100%;
        padding: 15px;
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 10px;
        color: white;
        font-size: 1rem;
        margin-bottom: 20px;
    }

    .user-list {
        background: #1e293b;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #334155;
    }

    .user-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid #334155;
        transition: background 0.2s;
    }

    .user-item:last-child {
        border-bottom: none;
    }

    .user-item:hover {
        background: #334155;
    }

    .user-info h4 {
        margin: 0 0 5px;
        color: #f8fafc;
        font-size: 1.1rem;
    }

    .user-info span {
        font-size: 0.85rem;
        color: #94a3b8;
        display: block;
    }

    .user-role {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: bold;
        margin-top: 5px;
    }

    .role-admin {
        background: #ef4444;
        color: white;
    }

    .role-user {
        background: #3b82f6;
        color: white;
    }

    .role-model {
        background: #ec4899;
        color: white;
    }

    .btn-reset {
        background: #ef4444;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .btn-reset:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background: #0f172a;
        padding: 30px;
        border-radius: 16px;
        width: 100%;
        max-width: 400px;
        border: 1px solid #334155;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
    }

    .modal-title {
        margin-top: 0;
        color: #f8fafc;
        text-align: center;
    }

    .form-input {
        width: 100%;
        padding: 12px;
        background: #1e293b;
        border: 1px solid #475569;
        border-radius: 8px;
        color: white;
        margin: 15px 0;
        font-size: 1rem;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .btn-save {
        flex: 1;
        background: #16a34a;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
    }

    .btn-cancel {
        flex: 1;
        background: #334155;
        color: white;
        border: none;
        padding: 12px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
    }
</style>

<main class="users-table-container"
    style="padding-top: 120px; background: transparent; border: none; box-shadow: none;">
    <div class="security-container" style="margin-top: 0;">

        <div style="margin-bottom: 20px;">
            <a href="admin_panel.php"
                style="color: #94a3b8; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                &larr; Volver al Panel
            </a>
        </div>

        <div class="security-header">
            <h2>🔐 Gestión de Contraseñas</h2>
            <p>Herramienta de emergencia para restablecer accesos.</p>
            <small
                style="display: block; margin-top: 10px; color: #fecaca; background: rgba(0,0,0,0.2); padding: 5px; border-radius: 5px;">
                ⚠️ <b>Nota de Seguridad:</b> Las contraseñas en la base de datos están <b>encriptadas (hased)</b>.
                Es matemáticamente imposible "descifrarlas" o verlas.
                Solo puedes reasignar una <b>NUEVA</b> contraseña si el usuario la olvidó.
            </small>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <input type="text" id="searchInput" class="search-box"
            placeholder="🔍 Buscar usuario por nombre, email o celular..." onkeyup="filterUsers()">

        <div class="user-list" id="userList">
            <?php while ($u = mysqli_fetch_assoc($users)): ?>
                <div class="user-item">
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($u['nombre']); ?></h4>
                        <span>📧 <?php echo htmlspecialchars($u['email']); ?></span>
                        <span style="font-size: 0.8rem;">📱
                            <?php echo htmlspecialchars($u['celular'] ?? 'No registrado'); ?></span>
                        <span
                            class="user-role role-<?php echo $u['rol']; ?>"><?php echo strtoupper($u['rol'] == 'user' ? 'Streamer' : $u['rol']); ?></span>
                    </div>
                    <button class="btn-reset"
                        onclick="openResetModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nombre']); ?>')">
                        🔄 Cambiar Clave
                    </button>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</main>

<!-- Modal Reset -->
<div id="resetModal" class="modal">
    <div class="modal-content">
        <h3 class="modal-title">Nueva Contraseña</h3>
        <p style="color: #94a3b8; text-align: center; font-size: 0.9rem;">Asignar nueva clave para <b id="modalUserName"
                style="color: white;"></b></p>

        <form action="admin_security.php" method="POST">
            <input type="hidden" name="user_id" id="modalUserId">

            <label style="color: #cbd5e1; font-size: 0.9rem;">Escribe la nueva contraseña:</label>
            <input type="text" name="new_password" class="form-input" placeholder="Ej: Streamer2025!" required
                autocomplete="off">

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn-save">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    function filterUsers() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const items = document.getElementsByClassName('user-item');

        for (let i = 0; i < items.length; i++) {
            const txt = items[i].innerText.toLowerCase();
            if (txt.includes(filter)) {
                items[i].style.display = "flex";
            } else {
                items[i].style.display = "none";
            }
        }
    }

    function openResetModal(id, name) {
        document.getElementById('modalUserId').value = id;
        document.getElementById('modalUserName').innerText = name;
        document.getElementById('resetModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('resetModal').style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera
    window.onclick = function (event) {
        const modal = document.getElementById('resetModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

<?php include '../includes/footer.php'; ?>