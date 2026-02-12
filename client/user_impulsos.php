<?php
session_start();
include '../includes/db.php';

// Verificación de Autenticación
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'user' && $_SESSION['rol'] !== 'model')) {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['usuario_id'];
    $action = $_POST['action'] ?? 'create';

    if ($action == 'create') {
        $fecha = $_POST['fecha'];
        $hora = $_POST['hora'];

        if (empty($fecha) || empty($hora)) {
            $message = "Por favor selecciona fecha y hora.";
            $message_type = "error";
        } else {
            // VALIDACIÓN: 2 Días de Anticipación
            $today = new DateTime();
            $today->setTime(0, 0, 0);

            $selected_date = new DateTime($fecha);
            $selected_date->setTime(0, 0, 0);

            $min_date = clone $today;
            $min_date->modify('+2 days');

            if ($selected_date < $min_date) {
                $message = "⚠️ Solo puedes registrar Impulsos con 2 días de anticipación. (Mínimo: " . $min_date->format('d/m/Y') . ")";
                $message_type = "error";
            } else {
                // Guardo en la BD
                $sql = "INSERT INTO impulsos (user_id, fecha, hora) VALUES ('$user_id', '$fecha', '$hora')";
                if (mysqli_query($conn, $sql)) {
                    $formatted_time = date('h:i A', strtotime($hora));
                    $message = "✅ Impulso registrado correctamente para el " . $selected_date->format('d/m/Y') . " a las $formatted_time.";
                    $message_type = "success";
                } else {
                    $message = "Error en base de datos: " . mysqli_error($conn);
                    $message_type = "error";
                }
            }
        }
    } elseif ($action == 'edit') {
        $impulso_id = (int) $_POST['impulso_id'];
        $fecha = $_POST['fecha'];
        $hora = $_POST['hora'];

        // Verifico propiedad y tiempos
        $check = mysqli_query($conn, "SELECT fecha FROM impulsos WHERE id = $impulso_id AND user_id = $user_id");
        if ($row = mysqli_fetch_assoc($check)) {
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $selected_date = new DateTime($fecha);
            $selected_date->setTime(0, 0, 0);
            $min_date = clone $today;
            $min_date->modify('+2 days');

            if ($selected_date < $min_date) {
                $message = "⚠️ Solo puedes reprogramar Impulsos con 2 días de anticipación. (Mínimo: " . $min_date->format('d/m/Y') . ")";
                $message_type = "error";
            } else {
                mysqli_query($conn, "UPDATE impulsos SET fecha = '$fecha', hora = '$hora' WHERE id = $impulso_id");
                $message = "✅ Impulso actualizado correctamente.";
                $message_type = "success";
            }
        }
    } elseif ($action == 'delete') {
        $impulso_id = (int) $_POST['impulso_id'];
        // Aseguro que el usuario solo puede eliminar los suyos
        mysqli_query($conn, "DELETE FROM impulsos WHERE id = $impulso_id AND user_id = $user_id");
        $message = "✅ Impulso eliminado correctamente.";
        $message_type = "success";
    }
}

// Obtengo impulsos existentes
$user_id = $_SESSION['usuario_id'];
$res_impulsos = mysqli_query($conn, "SELECT * FROM impulsos WHERE user_id = $user_id ORDER BY fecha ASC");

include '../includes/header.php';
?>
<!-- CSS -->
<link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/user_panel.css?v=<?php echo time(); ?>">

<style>
    .form-container {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        padding: 30px;
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        max-width: 500px;
        margin: 0 auto;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        color: #94a3b8;
        margin-bottom: 8px;
    }

    input[type="date"],
    input[type="time"] {
        width: 100%;
        padding: 12px;
        background: #1e293b;
        border: 1px solid #334155;
        border-radius: 8px;
        color: white;
        font-family: 'Inter', sans-serif;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }

    .alert.error {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
        border: 1px solid #ef4444;
    }

    .alert.success {
        background: rgba(34, 197, 94, 0.2);
        color: #86efac;
        border: 1px solid #22c55e;
    }

    /* Impulsos Table */
    .impulsos-table-container {
        margin-top: 40px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 20px;
        padding: 20px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .impulsos-table {
        width: 100%;
        border-collapse: collapse;
        color: white;
    }

    .impulsos-table th,
    .impulsos-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .impulsos-table th {
        color: #94a3b8;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .btn-icon {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        border-radius: 5px;
        transition: 0.2s;
    }

    .btn-icon:hover {
        background: rgba(255, 255, 255, 0.1);
    }
</style>

<main class="panel-hero">
    <div class="container" style="padding-top: 100px;">
        <div class="panel-header-content">
            <h1 class="welcome-msg">Registrar <span class="gradient-text">Impulso</span></h1>
            <p class="panel-subtitle">Agenda tu impulso con anticipación.</p>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Fecha del Impulso (Mínimo 2 días antes)</label>
                    <input type="date" name="fecha" required min="<?php echo date('Y-m-d', strtotime('+2 days')); ?>">
                </div>

                <div class="form-group">
                    <label>Hora</label>
                    <input type="time" name="hora" required>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Registrar Impulso</button>
                </div>
            </form>
        </div>

        <!-- LISTA DE IMPULSOS -->
        <div class="impulsos-table-container">
            <h2 style="font-size: 1.2rem; margin-bottom: 15px;">Mis Impulsos Agendados</h2>
            <?php if (mysqli_num_rows($res_impulsos) > 0): ?>
                <table class="impulsos-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($imp = mysqli_fetch_assoc($res_impulsos)):
                            $date_obj = new DateTime($imp['fecha']);
                            $today = new DateTime();
                            $today->setTime(0, 0, 0);
                            $min_editable = clone $today;
                            $min_editable->modify('+2 days');
                            $is_editable = ($date_obj >= $min_editable);
                            ?>
                            <tr>
                                <td><?php echo $date_obj->format('d/m/Y'); ?></td>
                                <td><?php echo date('h:i A', strtotime($imp['hora'])); ?></td>
                                <td>
                                    <?php if ($date_obj < $today): ?>
                                        <span style="color: #64748b;">Finalizado</span>
                                    <?php elseif (!$is_editable): ?>
                                        <span style="color: #fbbf24;">En curso / Muy pronto</span>
                                    <?php else: ?>
                                        <span style="color: #22c55e;">Agendado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_editable): ?>
                                        <button class="btn-icon"
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($imp)); ?>)"
                                            title="Editar">✏️</button>
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('¿Eliminar este impulso?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="impulso_id" value="<?php echo $imp['id']; ?>">
                                            <button type="submit" class="btn-icon" title="Eliminar">🗑️</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size: 0.8rem; color: #475569;">No modificable</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #475569; padding: 20px;">No tienes impulsos registrados.</p>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="user_panel.php" style="color: #94a3b8; text-decoration: none;">← Volver al Panel</a>
        </div>
    </div>
</main>

<!-- Modal Edición -->
<div id="editModal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;">
    <div
        style="background: #0f172a; border: 1px solid #334155; padding: 30px; border-radius: 20px; width: 90%; max-width: 400px;">
        <h3 style="margin-bottom: 20px;">Editar Impulso</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="impulso_id" id="edit_id">
            <div class="form-group">
                <label>Nueva Fecha</label>
                <input type="date" name="fecha" id="edit_fecha" required
                    min="<?php echo date('Y-m-d', strtotime('+2 days')); ?>">
            </div>
            <div class="form-group">
                <label>Nueva Hora</label>
                <input type="time" name="hora" id="edit_hora" required>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeEditModal()" class="btn btn-outline"
                    style="flex: 1;">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(imp) {
        document.getElementById('edit_id').value = imp.id;
        document.getElementById('edit_fecha').value = imp.fecha;
        document.getElementById('edit_hora').value = imp.hora;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
</script>

<?php include '../includes/footer.php'; ?>