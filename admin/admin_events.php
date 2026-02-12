<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success = "";
$error = "";

// Proceso la creación de un nuevo evento
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_event'])) {
    $titulo = mysqli_real_escape_string($conn, $_POST['titulo']);
    $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
    $fecha_auto = date('Y-m-d');
    $hora = $_POST['hora'];
    $cupo = (int) $_POST['cupo_maximo'];
    $fechas_input = $_POST['fechas']; // Texto con fechas separadas por comas

    if (empty($titulo) || empty($hora) || empty($fechas_input)) {
        $error = "Título, hora y al menos una fecha son obligatorios.";
    } else {
        $query = "INSERT INTO eventos (titulo, descripcion, fecha, hora, cupo_maximo, agencia_id) VALUES ('$titulo', '$descripcion', '$fecha_auto', '$hora', $cupo, 1)";
        if (mysqli_query($conn, $query)) {
            $new_event_id = mysqli_insert_id($conn);
            // Proceso las fechas individuales
            $fechas_array = explode(',', $fechas_input);
            foreach ($fechas_array as $f_str) {
                $f_clean = trim($f_str);
                if (!empty($f_clean)) {
                    $f_sql = date('Y-m-d', strtotime($f_clean));
                    mysqli_query($conn, "INSERT INTO eventos_fechas (evento_id, fecha) VALUES ($new_event_id, '$f_sql')");
                }
            }
            $success = "Evento y fechas creados correctamente.";
        } else {
            $error = "Error al crear evento: " . mysqli_error($conn);
        }
    }
}

// Agrego fecha a un evento existente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_single_date'])) {
    $eid = (int) $_POST['event_id'];
    $f_nueva = mysqli_real_escape_string($conn, $_POST['nueva_fecha']);
    if (!empty($f_nueva)) {
        mysqli_query($conn, "INSERT INTO eventos_fechas (evento_id, fecha) VALUES ($eid, '$f_nueva')");
        $success = "Fecha añadida.";
    }
}

// Elimino una fecha específica
if (isset($_GET['delete_date'])) {
    $did = (int) $_GET['delete_date'];
    mysqli_query($conn, "DELETE FROM eventos_fechas WHERE id = $did");
    header("Location: admin_events.php");
    exit();
}

// Elimino el evento completo
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM eventos WHERE id = $id");
    header("Location: admin_events.php");
    exit();
}

// Quito al participante del evento
if (isset($_GET['remove_user']) && isset($_GET['event_id'])) {
    $uid = (int) $_GET['remove_user'];
    $eid = (int) $_GET['event_id'];
    mysqli_query($conn, "DELETE FROM inscripciones WHERE usuario_id = $uid AND evento_id = $eid");
    header("Location: admin_events.php");
    exit();
}

$eventos = mysqli_query($conn, "SELECT * FROM eventos ORDER BY created_at DESC");

include '../includes/header.php';
?>

<link rel="stylesheet" href="../css/admin_events.css">
<link rel="stylesheet" href="../css/admin_users.css"> <!-- Reusamos tablas -->

<main class="users-table-container" style="padding-top: 120px;">
    <div class="event-form-container" style="max-width: 900px; margin: 0 auto; padding: 0 15px;">
        <div
            style="background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 40px; margin-bottom: 48px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <h2 class="table-title"
                style="margin-bottom: 32px; font-size: 1.8rem; border-left: 4px solid #3b82f6; padding-left: 15px; color: white !important;">
                Crear Nuevo Evento</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"
                    style="background: rgba(22, 163, 74, 0.1); border: 1px solid #16a34a; color: #4ade80; padding: 15px; border-radius: 8px; margin-bottom: 24px;">
                    ✅ <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"
                    style="background: rgba(220, 38, 38, 0.1); border: 1px solid #dc2626; color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 24px;">
                    ❌ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="admin_events.php" method="POST" class="form-grid">
                <div class="form-group full-width">
                    <label style="color: #94a3b8; font-weight: 600; font-size: 0.9rem;">Título del Evento</label>
                    <input type="text" name="titulo" class="form-control"
                        style="background: #020617; border-color: #334155; color: white;"
                        placeholder="Ej: Casting Global Diciembre" required>
                </div>

                <div class="form-group full-width">
                    <label style="color: #94a3b8; font-weight: 600; font-size: 0.9rem;">Descripción Detallada</label>
                    <textarea name="descripcion" class="form-control" rows="3"
                        style="background: #020617; border-color: #334155; color: white;"
                        placeholder="Describe de qué trata el evento..."></textarea>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div class="form-group">
                        <label style="color: #94a3b8; font-weight: 600; font-size: 0.9rem;">🕒 Hora de Inicio</label>
                        <input type="time" name="hora" class="form-control"
                            style="background: #020617; border-color: #334155; color: white;" required>
                    </div>

                    <div class="form-group">
                        <label style="color: #94a3b8; font-weight: 600; font-size: 0.9rem;">👥 Cupos (0 = ∞)</label>
                        <input type="number" name="cupo_maximo" class="form-control"
                            style="background: #020617; border-color: #334155; color: white;" value="0">
                        <small style="color: #64748b; font-size: 0.75rem;">Capacidad máxima de asistentes</small>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label style="color: #94a3b8; font-weight: 600; font-size: 0.9rem;">📅 Fechas Disponibles</label>
                    <div style="display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;">
                        <input type="date" id="picker_date" class="form-control"
                            style="background: #020617; border-color: #334155; color: white; width: auto; flex: 1; min-width: 150px;">
                        <button type="button" onclick="addDateTag()" class="btn btn-primary"
                            style="padding: 0 20px; font-size: 0.9rem; flex: 1; min-height: 40px;">+ Agregar</button>
                    </div>

                    <div id="date_tags_container"
                        style="display: flex; flex-wrap: wrap; gap: 8px; min-height: 45px; background: #020617; border: 1px solid #334155; padding: 10px; border-radius: 8px;">
                        <!-- Contenido llenado por JS -->
                    </div>
                    <input type="hidden" name="fechas" id="hidden_fechas" required>
                    <small style="color: #64748b; font-size: 0.75rem;">Elige las fechas en el calendario y haz clic en
                        "Agregar".</small>
                </div>

                <script>
                    const selectedDates = new Set();
                    const container = document.getElementById('date_tags_container');
                    const hiddenInput = document.getElementById('hidden_fechas');
                    const picker = document.getElementById('picker_date');

                    function addDateTag() {
                        const val = picker.value;
                        if (!val) return;
                        if (selectedDates.has(val)) return;

                        selectedDates.add(val);
                        renderTags();
                        picker.value = '';
                    }

                    function removeDateTag(date) {
                        selectedDates.delete(date);
                        renderTags();
                    }

                    function renderTags() {
                        container.innerHTML = '';
                        const sortedDates = Array.from(selectedDates).sort();

                        sortedDates.forEach(date => {
                            const tag = document.createElement('span');
                            tag.style.cssText = 'background: #1e293b; color: #cbd5e1; font-size: 0.8rem; padding: 5px 12px; border-radius: 20px; border: 1px solid #334155; display: flex; align-items: center; gap: 8px;';

                            const d = new Date(date + 'T00:00:00');
                            const label = d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });

                            tag.innerHTML = `${label} <span style="color: #f87171; cursor: pointer; font-weight: bold;" onclick="removeDateTag('${date}')">&times;</span>`;
                            container.appendChild(tag);
                        });

                        hiddenInput.value = Array.from(selectedDates).join(',');
                    }
                </script>

                <div class="form-group full-width" style="margin-top: 10px;">
                    <button type="submit" name="add_event" class="btn btn-primary"
                        style="width: 100%; font-weight: 800; font-size: 1.1rem; padding: 16px; background: #2563eb; border: none; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);">
                        ⚡ Publicar Evento
                    </button>
                </div>
            </form>
        </div>

        <h3 style="margin-bottom: 24px; color: #3b82f6; font-weight: 800;">Eventos Activos</h3>
        <div class="scroll-hint"><span>↔️ Desliza para ver más</span></div>
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th style="min-width: 400px;">Información del Evento</th>
                        <th style="min-width: 100px;">Hora</th>
                        <th style="min-width: 100px;">Cupo</th>
                        <th style="min-width: 150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($e = mysqli_fetch_assoc($eventos)):
                        $evento_id = $e['id'];
                        $inscritos = mysqli_query($conn, "SELECT u.id as user_id, u.nombre, u.bigo_id, u.celular FROM inscripciones i JOIN usuarios u ON i.usuario_id = u.id WHERE i.evento_id = $evento_id");
                        ?>
                        <tr class="event-row">
                            <td data-label="Evento">
                                <strong
                                    style="font-size: 1.1rem; color: #3b82f6;"><?php echo htmlspecialchars($e['titulo']); ?></strong>
                                <p style="font-size: 0.85rem; color: #94a3b8; margin: 5px 0 10px 0;">
                                    <?php echo htmlspecialchars($e['descripcion']); ?>
                                </p>

                                <div
                                    style="margin-bottom: 15px; background: #020617; border-radius: 8px; padding: 12px; border: 1px solid #1e293b;">
                                    <h5
                                        style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.05em;">
                                        Fechas Disponibles</h5>
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                        <?php
                                        $fechas_ev = mysqli_query($conn, "SELECT * FROM eventos_fechas WHERE evento_id = $evento_id ORDER BY fecha ASC");
                                        while ($fe = mysqli_fetch_assoc($fechas_ev)): ?>
                                            <span
                                                style="background: #1e293b; color: #cbd5e1; font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; border: 1px solid #334155; display: flex; align-items: center; gap: 5px;">
                                                <?php echo date('d/m/Y', strtotime($fe['fecha'])); ?>
                                                <a href="admin_events.php?delete_date=<?php echo $fe['id']; ?>"
                                                    style="color: #f87171; text-decoration: none;"
                                                    onclick="return confirm('¿Eliminar esta fecha?')"
                                                    title="Eliminar fecha">&times;</a>
                                            </span>
                                        <?php endwhile; ?>
                                    </div>
                                    <form action="admin_events.php" method="POST"
                                        style="margin-top: 10px; display: flex; gap: 5px; flex-wrap: wrap;">
                                        <input type="hidden" name="event_id" value="<?php echo $evento_id; ?>">
                                        <input type="date" name="nueva_fecha" class="form-control"
                                            style="font-size: 0.75rem; padding: 5px; height: auto; width: 130px; background: #020617; color: white; border-color: #334155;">
                                        <button type="submit" name="add_single_date" class="btn btn-primary"
                                            style="font-size: 0.75rem; padding: 4px 12px; height: auto;">+ Fecha</button>
                                    </form>
                                </div>

                                <details
                                    style="background: #020617; border-radius: 8px; padding: 10px; border: 1px solid #1e293b;">
                                    <summary style="cursor: pointer; color: #60a5fa; font-weight: 700; outline: none;">
                                        👥 Ver Inscritos (<?php
                                        $total_ins = mysqli_query($conn, "SELECT COUNT(*) as total FROM inscripciones WHERE evento_id = $evento_id");
                                        $total_row = mysqli_fetch_assoc($total_ins);
                                        echo $total_row['total'];
                                        ?>)
                                    </summary>
                                    <div style="margin-top: 15px;">
                                        <?php
                                        // Obtengo fechas únicas con inscritos
                                        $fechas_ins = mysqli_query($conn, "SELECT DISTINCT fecha_asistencia FROM inscripciones WHERE evento_id = $evento_id AND fecha_asistencia IS NOT NULL ORDER BY fecha_asistencia ASC");

                                        if (mysqli_num_rows($fechas_ins) > 0): ?>
                                            <?php while ($f_row = mysqli_fetch_assoc($fechas_ins)):
                                                $f_asistencia = $f_row['fecha_asistencia'];
                                                // ORDEN CRONOLÓGICO: los más antiguos primero
                                                // Uso 'i.id' para ordenar porque 'created_at' no existe
                                                $inscritos = mysqli_query($conn, "SELECT u.id as user_id, u.nombre, u.bigo_id, u.celular, i.agendado FROM inscripciones i JOIN usuarios u ON i.usuario_id = u.id WHERE i.evento_id = $evento_id AND i.fecha_asistencia = '$f_asistencia' ORDER BY i.id ASC");
                                                ?>
                                                <div style="margin-bottom: 20px;">
                                                    <h5
                                                        style="color: #3b82f6; border-bottom: 1px solid #1e293b; padding-bottom: 5px; margin-bottom: 10px; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                                        <span>📅 <?php echo date('d \d\e F', strtotime($f_asistencia)); ?></span>
                                                        <a href="../exports/export_attendees.php?event_id=<?php echo $evento_id; ?>&date=<?php echo $f_asistencia; ?>"
                                                            class="btn btn-sm btn-primary"
                                                            style="font-size: 0.75rem; padding: 2px 8px; text-decoration: none;">📊
                                                            Exportar</a>
                                                    </h5>
                                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                                        <?php while ($ins = mysqli_fetch_assoc($inscritos)): ?>
                                                            <div
                                                                style="display: flex; justify-content: space-between; align-items: center; background: #0f172a; padding: 8px 12px; border-radius: 6px; border: 1px solid #334155; gap: 10px;">
                                                                <span style="font-size: 0.85rem; color: #cbd5e1;">
                                                                    <?php echo htmlspecialchars($ins['nombre']); ?>
                                                                    <div
                                                                        style="margin-top: 4px; display: flex; align-items: center; gap: 10px;">
                                                                        <span
                                                                            style="background: rgba(59, 130, 246, 0.1); color: #60a5fa; font-size: 0.8rem; padding: 2px 8px; border-radius: 4px; font-weight: 800; border: 1px solid rgba(59, 130, 246, 0.3);">
                                                                            ID: <?php echo htmlspecialchars($ins['bigo_id']); ?>
                                                                        </span>

                                                                        <label
                                                                            style="display: flex; align-items: center; gap: 5px; cursor: pointer; color: #4ade80; font-size: 0.8rem; font-weight: bold;">
                                                                            <input type="checkbox"
                                                                                onchange="toggleAgendado(this, <?php echo $ins['user_id']; ?>, <?php echo $evento_id; ?>, '<?php echo $f_asistencia; ?>')"
                                                                                <?php echo $ins['agendado'] ? 'checked' : ''; ?>
                                                                                style="cursor: pointer; accent-color: #4ade80;">
                                                                            <span class="agendado-label"
                                                                                style="<?php echo $ins['agendado'] ? '' : 'display: none;'; ?>">📅
                                                                                Agendado</span>
                                                                        </label>
                                                                    </div>
                                                                </span>
                                                                <a href="admin_events.php?remove_user=<?php echo $ins['user_id']; ?>&event_id=<?php echo $evento_id; ?>"
                                                                    style="color: #f87171; text-decoration: none; font-size: 1.1rem; padding: 0 5px;"
                                                                    onclick="return confirm('¿Quitar a este usuario del evento?')"
                                                                    title="Quitar">
                                                                    &times;
                                                                </a>
                                                            </div>
                                                        <?php endwhile; ?>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <p style="color: #64748b; font-size: 0.85rem; padding-left: 10px;">Sin inscritos
                                                aún.</p>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            </td>
                            <td data-label="Hora" class="text-center">
                                <span
                                    style="background: #1e1b4b; color: #a5b4fc; padding: 4px 10px; border-radius: 4px; font-weight: 700; white-space: nowrap;"><?php echo $e['hora']; ?></span>
                            </td>
                            <td data-label="Cupo" class="text-center" style="font-weight: bold; color: white;">
                                <?php echo $e['cupo_maximo'] == 0 ? '∞' : $e['cupo_maximo']; ?>
                            </td>
                            <td data-label="Acciones">
                                <a href="admin_events.php?delete=<?php echo $e['id']; ?>" class="action-btn btn-delete"
                                    style="width: 100%; justify-content: center;"
                                    onclick="return confirm('¿Borrar este evento permanentemente?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 48px; text-align: center;">
            <a href="admin_panel.php" class="btn btn-primary"
                style="padding: 14px 32px; font-weight: 800; border-radius: 8px;">&larr; Volver al Dashboard</a>
        </div>
    </div>
</main>

<script>
    function toggleAgendado(checkbox, userId, eventId, fecha) {
        const agendado = checkbox.checked ? 1 : 0;
        const label = checkbox.parentElement.querySelector('.agendado-label');

        // Cambio la visibilidad de la etiqueta inmediatamente para mejor UX
        if (label) {
            label.style.display = agendado ? 'inline' : 'none';
        }

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('event_id', eventId);
        formData.append('fecha', fecha);
        formData.append('agendado', agendado);

        fetch('../api/update_agendado.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error al actualizar el estado: ' + (data.message || 'Error desconocido'));
                    checkbox.checked = !checkbox.checked; // Revierto el check
                    if (label) label.style.display = checkbox.checked ? 'inline' : 'none'; // Revierto la etiqueta
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al servidor.');
                checkbox.checked = !checkbox.checked; // Revierto el check
                if (label) label.style.display = checkbox.checked ? 'inline' : 'none'; // Revierto la etiqueta
            });
    }
</script>

<?php include '../includes/footer.php'; ?>