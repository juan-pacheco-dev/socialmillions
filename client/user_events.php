<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'user' && $_SESSION['rol'] !== 'model')) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['usuario_id'];
$success_msg = "";
$error_msg = "";

// Proceso la inscripción al evento
if (isset($_POST['inscribir_btn'])) {
    $evento_id = (int) $_POST['evento_id'];
    $fecha_asistencia = mysqli_real_escape_string($conn, $_POST['fecha_asistencia']);

    if (empty($fecha_asistencia)) {
        $error_msg = "Debes seleccionar una fecha para inscribirte.";
    } else {
        // Verifico si ya está inscrito EN ESA FECHA
        $check = mysqli_query($conn, "SELECT id FROM inscripciones WHERE usuario_id = $user_id AND evento_id = $evento_id AND fecha_asistencia = '$fecha_asistencia'");

        if (mysqli_num_rows($check) > 0) {
            $error_msg = "Ya estás inscrito en este evento para la fecha seleccionada.";
        } else {
            // --- VALIDO EL CUPO ---
            $event_check = mysqli_query($conn, "SELECT cupo_maximo FROM eventos WHERE id = $evento_id");
            $ev_data = mysqli_fetch_assoc($event_check);
            $cupo_max = $ev_data['cupo_maximo'];

            $count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM inscripciones WHERE evento_id = $evento_id");
            $count_data = mysqli_fetch_assoc($count_res);
            $actual_count = $count_data['total'];

            if ($cupo_max > 0 && $actual_count >= $cupo_max) {
                $error_msg = "Lo sentimos, los cupos para este evento acaban de agotarse.";
            } else {
                $query = "INSERT INTO inscripciones (usuario_id, evento_id, fecha_asistencia) VALUES ($user_id, $evento_id, '$fecha_asistencia')";
                if (mysqli_query($conn, $query)) {
                    $success_msg = "¡Inscripción exitosa!";
                } else {
                    $error_msg = "Error al inscribirse.";
                }
            }
        }
    }
}

// Obtengo el ID de agencia del usuario
$u_res = mysqli_query($conn, "SELECT agencia_id FROM usuarios WHERE id = $user_id");
$u_data = mysqli_fetch_assoc($u_res);
$my_agency_id = (int) ($u_data['agencia_id'] ?? 0);

// Obtengo eventos futuros de MI agencia
$query = "SELECT e.*, 
          (SELECT COUNT(*) FROM inscripciones i2 WHERE i2.evento_id = e.id) as inscritos_count
          FROM eventos e 
          WHERE e.agencia_id = $my_agency_id
          ORDER BY e.fecha ASC";
$result = mysqli_query($conn, $query);

include '../includes/header.php';
?>

<link rel="stylesheet" href="../css/admin_users.css"> <!-- For table styles if needed -->
<link rel="stylesheet" href="../css/admin_content.css"> <!-- For card similarities -->
<link rel="stylesheet" href="../css/user_panel.css">

<style>
    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
        margin-top: 40px;
    }

    .event-card {
        background: #0f172a;
        padding: 40px;
        border-radius: 16px;
        border: 1px solid #1e293b;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
    }

    .event-card:hover {
        transform: translateY(-8px);
        border-color: #3b82f6;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
    }

    .event-card h3 {
        color: #ffffff !important;
        margin-bottom: 16px;
        font-weight: 800;
        font-size: 1.5rem;
        letter-spacing: -0.02em;
    }

    .event-date {
        font-size: 0.9rem;
        color: #60a5fa;
        margin-bottom: 20px;
        display: inline-block;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: rgba(96, 165, 250, 0.1);
        padding: 6px 12px;
        border-radius: 6px;
    }

    .event-card p {
        color: #cbd5e1;
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 24px;
    }

    .btn-inscrita {
        background: rgba(34, 197, 94, 0.15);
        color: #69f0ae;
        border: 1px solid rgba(34, 197, 94, 0.3);
        width: 100%;
        padding: 14px;
        border-radius: 10px;
        display: block;
        text-align: center;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
</style>

<main class="container">
    <div class="panel-container">
        <h2 class="welcome-msg">Próximos Eventos</h2>
        <p>Participa en nuestras actividades exclusivas.</p>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="events-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($e = mysqli_fetch_assoc($result)):
                    // Obtengo mis fechas para este evento
                    $e_id = $e['id'];
                    $mis_fechas_res = mysqli_query($conn, "SELECT fecha_asistencia FROM inscripciones WHERE usuario_id = $user_id AND evento_id = $e_id ORDER BY fecha_asistencia ASC");
                    $mis_fechas = [];
                    while ($mf = mysqli_fetch_assoc($mis_fechas_res)) {
                        $mis_fechas[] = $mf['fecha_asistencia'];
                    }
                    $ya_inscrito = count($mis_fechas) > 0;
                    ?>
                    <div class="event-card">
                        <h3><?php echo htmlspecialchars($e['titulo']); ?></h3>
                        <?php if ($ya_inscrito): ?>
                            <div style="display: flex; flex-direction: column; gap: 5px; margin-bottom: 20px;">
                                <?php foreach ($mis_fechas as $fecha): ?>
                                    <span class="event-date"
                                        style="background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); margin-bottom: 0;">
                                        📅 Asistirás el: <?php echo date('d/m/Y', strtotime($fecha)); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="event-date">🕒 Hora: <?php echo $e['hora']; ?></span>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($e['descripcion']); ?></p>

                        <div style="margin-top: auto;">
                            <?php if ($ya_inscrito): ?>
                                <div
                                    style="margin-bottom: 15px; background: rgba(34, 197, 94, 0.1); padding: 10px; border-radius: 8px; border: 1px solid rgba(34, 197, 94, 0.2);">
                                    <span style="color: #4ade80; font-weight: 800; font-size: 0.9rem;">✓ Estás inscrito</span>
                                    <p style="margin: 5px 0 0 0; font-size: 0.8rem; color: #86efac;">Puedes inscribirte a otra fecha
                                        si lo deseas.</p>
                                </div>
                            <?php endif; ?>

                            <?php
                            // Obtengo fechas del evento (disponibles)
                            $fechas_res = mysqli_query($conn, "SELECT fecha FROM eventos_fechas WHERE evento_id = $e_id ORDER BY fecha ASC");

                            // Verifico si quedan fechas disponibles (no inscritas)
                            $fechas_disponibles = false;
                            ?>

                            <?php if ($e['cupo_maximo'] > 0 && $e['inscritos_count'] >= $e['cupo_maximo']): ?>
                                <span class="btn-inscrita"
                                    style="background: rgba(220, 38, 38, 0.15); color: #f87171; border-color: rgba(220, 38, 38, 0.3);">⚠️
                                    Cupos Agotados</span>
                            <?php else: ?>
                                <form action="user_events.php" method="POST">
                                    <input type="hidden" name="evento_id" value="<?php echo $e['id']; ?>">
                                    <div class="form-group" style="margin-bottom: 16px;">
                                        <label style="font-size: 0.8rem; color: #94a3b8;">Elige tu fecha de asistencia:</label>
                                        <select name="fecha_asistencia" class="form-control" required
                                            style="background: #020617; border-color: #1e293b; color: white;">
                                            <option value="">Selecciona una opción...</option>
                                            <?php
                                            if (mysqli_num_rows($fechas_res) > 0):
                                                while ($f_row = mysqli_fetch_assoc($fechas_res)):
                                                    $f_val = $f_row['fecha'];
                                                    $f_label = date('d \d\e F, Y', strtotime($f_val));

                                                    // Si ya está inscrito en esta fecha, la marcamos o deshabilitamos
                                                    if (in_array($f_val, $mis_fechas)) {
                                                        echo '<option value="" disabled style="color: #86efac;">✓ ' . $f_label . ' (Inscrito)</option>';
                                                    } else {
                                                        $fechas_disponibles = true;
                                                        echo '<option value="' . $f_val . '">' . $f_label . '</option>';
                                                    }
                                                endwhile;
                                            else:
                                                echo '<option value="">No hay fechas disponibles</option>';
                                            endif;
                                            ?>
                                        </select>
                                    </div>
                                    <?php if ($fechas_disponibles): ?>
                                        <button type="submit" name="inscribir_btn" class="btn btn-primary"
                                            style="width: 100%; text-align: center; font-weight: 800; padding: 14px; border-radius: 10px;">Inscribirme</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary" disabled
                                            style="width: 100%; text-align: center; font-weight: 800; padding: 14px; border-radius: 10px; opacity: 0.5;">Ya
                                            te has inscrito a todo</button>
                                    <?php endif; ?>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No hay eventos programados por ahora.</p>
            <?php endif; ?>
        </div>

        <div style="margin-top: 48px;">
            <a href="user_panel.php" class="btn btn-primary" style="padding: 14px 32px;">Volver al Panel</a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>