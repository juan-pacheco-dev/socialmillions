<?php
// agency/agency_dashboard.php
// Incluido desde loader.php, tiene acceso a $AGENCY_DATA y conexión DB

// --- 1. LÓGICA DE PROCESAMIENTO (RECARGA SEGURO CON HEADERS) ---

// Procesar Subida de Logo
$msg_logo = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo_agency'])) {
    $target_dir = $root_path . "uploads/agencies/";
    if (!file_exists($target_dir))
        mkdir($target_dir, 0755, true);

    $file_ext = strtolower(pathinfo($_FILES["logo_agency"]["name"], PATHINFO_EXTENSION));
    $new_filename = "logo_" . $AGENCY_DATA['slug'] . "_" . time() . "." . $file_ext;
    $target_file = $target_dir . $new_filename;

    if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        if (move_uploaded_file($_FILES["logo_agency"]["tmp_name"], $target_file)) {
            $path_db = "uploads/agencies/" . $new_filename;
            $qid = $AGENCY_DATA['id'];
            mysqli_query($conn, "UPDATE agencias SET logo_path = '$path_db' WHERE id = $qid");
            header("Location: ./?success=logo");
            exit();
        }
    }
}

// Procesar Cambio de Contraseña
$msg_pass = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    $uid = $_SESSION['usuario_id'];

    if ($new === $confirm) {
        $q = mysqli_query($conn, "SELECT password FROM usuarios WHERE id = $uid");
        $user_data = mysqli_fetch_assoc($q);
        if (password_verify($current, $user_data['password'])) {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE usuarios SET password = '$new_hash' WHERE id = $uid");
            header("Location: ./?success=pass");
            exit();
        } else {
            $msg_pass = "Error: La contraseña actual es incorrecta.";
        }
    } else {
        $msg_pass = "Error: Las contraseñas no coinciden.";
    }
}

// APROBAR / RECHAZAR / DESBLOQUEAR USUARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve_user' && isset($_POST['user_id'])) {
        $uid = (int) $_POST['user_id'];
        mysqli_query($conn, "UPDATE usuarios SET estado = 'activo' WHERE id = $uid AND agencia_id = " . $AGENCY_DATA['id']);
        header("Location: ./?success=approved");
        exit();
    }
    if ($_POST['action'] === 'reject_user' && isset($_POST['user_id'])) {
        $uid = (int) $_POST['user_id'];
        mysqli_query($conn, "DELETE FROM usuarios WHERE id = $uid AND agencia_id = " . $AGENCY_DATA['id']);
        header("Location: ./?success=rejected");
        exit();
    }
    if ($_POST['action'] === 'toggle_event_access' && isset($_POST['user_id'])) {
        $uid = (int) $_POST['user_id'];
        $val = (int) $_POST['access_value'];
        mysqli_query($conn, "UPDATE usuarios SET event_access = $val WHERE id = $uid AND agencia_id = " . $AGENCY_DATA['id']);
        header("Location: ./?success=access_updated");
        exit();
    }
}

// GESTIÓN DE DOCUMENTOS
$msg_doc = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    $titulo = mysqli_real_escape_string($conn, $_POST['titulo']);
    $desc = mysqli_real_escape_string($conn, $_POST['descripcion']);
    if (isset($_FILES['documento']) && $_FILES['documento']['error'] === 0) {
        $fileExt = strtolower(pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt'];
        if (in_array($fileExt, $allowed)) {
            $newName = "doc_" . $AGENCY_DATA['id'] . "_" . time() . "." . $fileExt;
            $dest = $root_path . 'uploads/documents/' . $newName;
            if (!file_exists($root_path . 'uploads/documents/'))
                mkdir($root_path . 'uploads/documents/', 0755, true);
            if (move_uploaded_file($_FILES['documento']['tmp_name'], $dest)) {
                $db_path = 'uploads/documents/' . $newName;
                mysqli_query($conn, "INSERT INTO documentos (titulo, descripcion, ruta, tipo_archivo, agencia_id) VALUES ('$titulo', '$desc', '$db_path', '$fileExt', " . $AGENCY_DATA['id'] . ")");
                header("Location: ./?success=doc");
                exit();
            }
        }
    }
}
if (isset($_GET['delete_doc'])) {
    $did = (int) $_GET['delete_doc'];
    $res = mysqli_query($conn, "SELECT ruta FROM documentos WHERE id = $did AND agencia_id = " . $AGENCY_DATA['id']);
    if ($doc = mysqli_fetch_assoc($res)) {
        if (file_exists($root_path . $doc['ruta']))
            unlink($root_path . $doc['ruta']);
        mysqli_query($conn, "DELETE FROM documentos WHERE id = $did");
        header("Location: ./?success=deleted");
        exit();
    }
}

// GESTIÓN DE EVENTOS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $titulo = mysqli_real_escape_string($conn, $_POST['titulo']);
    $desc = mysqli_real_escape_string($conn, $_POST['descripcion']);
    $hora = $_POST['hora'];
    $cupo = (int) $_POST['cupo_maximo'];
    $fechas_input = $_POST['fechas'];
    if (!empty($titulo) && !empty($fechas_input)) {
        mysqli_query($conn, "INSERT INTO eventos (titulo, descripcion, fecha, hora, cupo_maximo, agencia_id) VALUES ('$titulo', '$desc', '" . date('Y-m-d') . "', '$hora', $cupo, " . $AGENCY_DATA['id'] . ")");
        $eid = mysqli_insert_id($conn);
        $fechas = explode(',', $fechas_input);
        foreach ($fechas as $f) {
            $f_sql = date('Y-m-d', strtotime(trim($f)));
            mysqli_query($conn, "INSERT INTO eventos_fechas (evento_id, fecha) VALUES ($eid, '$f_sql')");
        }
        header("Location: ./?success=event");
        exit();
    }
}
if (isset($_GET['delete_event'])) {
    $eid = (int) $_GET['delete_event'];
    mysqli_query($conn, "DELETE FROM eventos WHERE id = $eid AND agencia_id = " . $AGENCY_DATA['id']);
    header("Location: ./?success=deleted_event");
    exit();
}

// BORRAR INSCRIPCIÓN (QUITAR PERSONA DEL EVENTO)
if (isset($_GET['remove_attendee']) && isset($_GET['event_id'])) {
    $uid = (int) $_GET['remove_attendee'];
    $eid = (int) $_GET['event_id'];
    $f_asistencia = mysqli_real_escape_string($conn, $_GET['date'] ?? '');

    // Validar que el evento pertenezca a la agencia
    $check_ev = mysqli_query($conn, "SELECT id FROM eventos WHERE id = $eid AND agencia_id = " . $AGENCY_DATA['id']);
    if (mysqli_num_rows($check_ev) > 0) {
        mysqli_query($conn, "DELETE FROM inscripciones WHERE usuario_id = $uid AND evento_id = $eid AND fecha_asistencia = '$f_asistencia'");
        header("Location: ./?success=attendee_removed");
        exit();
    }
}

// GESTIÓN DE CONTENIDO VIP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_content'])) {
    $titulo = mysqli_real_escape_string($conn, $_POST['titulo']);
    $desc = mysqli_real_escape_string($conn, $_POST['descripcion']);
    $cat = $_POST['categoria'];
    $orden = (int) $_POST['orden'];
    $db_file = "";
    if (isset($_FILES["media"]) && $_FILES["media"]["error"] === 0) {
        $f_name = time() . "_" . basename($_FILES["media"]["name"]);
        if (move_uploaded_file($_FILES["media"]["tmp_name"], $root_path . "uploads/" . $f_name)) {
            $db_file = "uploads/" . $f_name;
        }
    }
    mysqli_query($conn, "INSERT INTO contenido (titulo, descripcion, imagen, categoria, orden, agencia_id) VALUES ('$titulo', '$desc', '$db_file', '$cat', $orden, " . $AGENCY_DATA['id'] . ")");
    header("Location: ./?success=vip");
    exit();
}
if (isset($_GET['delete_content'])) {
    $cid = (int) $_GET['delete_content'];
    $res = mysqli_query($conn, "SELECT imagen FROM contenido WHERE id = $cid AND agencia_id = " . $AGENCY_DATA['id']);
    if ($c = mysqli_fetch_assoc($res)) {
        if (!empty($c['imagen']) && file_exists($root_path . $c['imagen']))
            unlink($root_path . $c['imagen']);
        mysqli_query($conn, "DELETE FROM contenido WHERE id = $cid");
        header("Location: ./?success=deleted_vip");
        exit();
    }
}

// --- 2. OBTENER DATOS PARA MOSTRAR ---
$my_agency_id = $AGENCY_DATA['id'];
$result_users = mysqli_query($conn, "SELECT * FROM usuarios WHERE agencia_id = $my_agency_id AND rol != 'agency_admin' ORDER BY created_at DESC");
$result_docs = mysqli_query($conn, "SELECT * FROM documentos WHERE agencia_id = $my_agency_id ORDER BY created_at DESC");
$result_events = mysqli_query($conn, "SELECT * FROM eventos WHERE agencia_id = $my_agency_id ORDER BY created_at DESC");
$result_vip = mysqli_query($conn, "SELECT * FROM contenido WHERE agencia_id = $my_agency_id ORDER BY orden ASC");

// Mensajes de éxito basados en URL
$status_msg = "";
if (isset($_GET['success'])) {
    $s = $_GET['success'];
    if ($s == 'doc')
        $status_msg = "Documento subido con éxito.";
    if ($s == 'event')
        $status_msg = "Evento creado con éxito.";
    if ($s == 'vip')
        $status_msg = "Contenido VIP publicado.";
    if ($s == 'approved')
        $status_msg = "Usuario aprobado.";
    if ($s == 'rejected')
        $status_msg = "Postulación rechazada.";
    if ($s == 'access_updated')
        $status_msg = "Acceso a eventos actualizado.";
    if ($s == 'attendee_removed')
        $status_msg = "Inscripción eliminada correctamente.";
    if ($s == 'deleted')
        $status_msg = "Ítem eliminado.";
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Agencia -
        <?php echo htmlspecialchars($AGENCY_DATA['nombre']); ?>
    </title>
    <link rel="stylesheet" href="<?php echo $root_path; ?>css/estilos.css">
    <style>
        body {
            background: #0f172a;
            color: white;
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0;
            border-bottom: 1px solid #334155;
        }

        .agency-logo {
            max-height: 80px;
            /* Aumentado */
            border-radius: 8px;
        }

        .card {
            background: #1e293b;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #334155;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-success {
            background: #22c55e;
            color: white;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #334155;
        }

        th {
            color: #94a3b8;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            background: #0f172a;
            border: 1px solid #475569;
            color: white;
            border-radius: 8px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: #334155;
            color: #94a3b8;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .tab-btn.active {
            background: #3b82f6;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid #22c55e;
            color: #4ade80;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php if ($AGENCY_DATA['logo_path']): ?>
                    <img src="<?php echo $root_path . $AGENCY_DATA['logo_path']; ?>" class="agency-logo">
                <?php endif; ?>
                <h1>
                    <?php echo htmlspecialchars($AGENCY_DATA['nombre']); ?>
                </h1>
            </div>
            <a href="<?php echo $root_path; ?>auth/logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>

        <?php if ($status_msg): ?>
            <div class="alert alert-success">✅
                <?php echo $status_msg; ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('users')">👥 Streamers</button>
            <button class="tab-btn" onclick="showTab('docs')">📂 Documentos</button>
            <button class="tab-btn" onclick="showTab('events')">📅 Eventos</button>
            <button class="tab-btn" onclick="showTab('vip')">🎥 VIP</button>
            <button class="tab-btn" onclick="showTab('settings')">⚙️ Ajustes</button>
        </div>

        <!-- TAB: USUARIOS -->
        <div id="tab-users" class="tab-content active">
            <div class="card">
                <h2>Gestión de Streamers</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Plataforma / ID</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = mysqli_fetch_assoc($result_users)): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($u['nombre']); ?>
                                    </strong><br>
                                    <small>
                                        <?php echo htmlspecialchars($u['email']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($u['plataforma']); ?>:
                                    <code><?php echo htmlspecialchars($u['bigo_id']); ?></code>
                                </td>
                                <td>
                                    <span style="color: <?php echo $u['estado'] == 'activo' ? '#4ade80' : '#fbbf24'; ?>">
                                        <?php echo ucfirst($u['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">

                                        <!-- Botón Eventos (Desbloquear/Bloquear) -->
                                        <?php if ($u['estado'] == 'activo'): ?>
                                            <?php if ($u['event_access'] == 1): ?>
                                                <button name="action" value="toggle_event_access" class="btn btn-sm"
                                                    style="background: #475569; color: white;">Bloquear Eventos</button>
                                                <input type="hidden" name="access_value" value="0">
                                            <?php else: ?>
                                                <button name="action" value="toggle_event_access"
                                                    class="btn btn-primary btn-sm">Desbloquear Eventos</button>
                                                <input type="hidden" name="access_value" value="1">
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if ($u['estado'] != 'activo'): ?>
                                            <button name="action" value="approve_user"
                                                class="btn btn-success btn-sm">Aprobar</button>
                                        <?php endif; ?>
                                        <button name="action" value="reject_user" class="btn btn-danger btn-sm"
                                            onclick="return confirm('¿Eliminar?')">X</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB: DOCUMENTOS -->
        <div id="tab-docs" class="tab-content">
            <div class="card">
                <h3>Subir Recurso / Manual</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="text" name="titulo" class="form-control" placeholder="Título" required>
                    <textarea name="descripcion" class="form-control" placeholder="Descripción corta"></textarea>
                    <input type="file" name="documento" class="form-control" required title="PDF, Word, etc.">
                    <button type="submit" name="upload_doc" class="btn btn-primary">Subir Archivo</button>
                </form>
            </div>
            <div class="card">
                <h3>Tus Archivos</h3>
                <table>
                    <?php while ($d = mysqli_fetch_assoc($result_docs)): ?>
                        <tr>
                            <td><strong>
                                    <?php echo htmlspecialchars($d['titulo']); ?>
                                </strong></td>
                            <td><a href="<?php echo $root_path . $d['ruta']; ?>" target="_blank"
                                    class="btn btn-primary btn-sm">Ver</a></td>
                            <td><a href="?delete_doc=<?php echo $d['id']; ?>" class="btn btn-danger btn-sm">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>

        <!-- TAB: EVENTOS -->
        <div id="tab-events" class="tab-content">
            <div class="card">
                <h3>Crear Actividad / Casting</h3>
                <form method="POST">
                    <input type="text" name="titulo" class="form-control" placeholder="Nombre del Evento" required>
                    <textarea name="descripcion" class="form-control" placeholder="Detalles"></textarea>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="font-size: 0.8rem; color: #94a3b8;">Hora de Inicio</label>
                            <input type="time" name="hora" class="form-control" required>
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; color: #94a3b8;">Cupos (0 = ilimitado)</label>
                            <input type="number" name="cupo_maximo" class="form-control" placeholder="0" value="0">
                        </div>
                    </div>

                    <div id="event-dates-container">
                        <label style="font-size: 0.8rem; color: #94a3b8;">Fechas Disponibles</label>
                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <input type="date" id="new-date-picker" class="form-control" style="margin-bottom:0;">
                            <button type="button" class="btn btn-primary" onclick="addDate()">+</button>
                        </div>
                        <div id="dates-list" style="display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 15px;">
                        </div>
                        <input type="hidden" name="fechas" id="fechas-hidden-input">
                    </div>

                    <button type="submit" name="add_event" class="btn btn-success" style="width: 100%;">Publicar
                        Evento</button>
                </form>

                <script>
                    let selectedDates = [];
                    function addDate() {
                        const picker = document.getElementById('new-date-picker');
                        const date = picker.value;
                        if (date && !selectedDates.includes(date)) {
                            selectedDates.push(date);
                            renderDates();
                            picker.value = '';
                        }
                    }
                    function removeDate(date) {
                        selectedDates = selectedDates.filter(d => d !== date);
                        renderDates();
                    }
                    function renderDates() {
                        const list = document.getElementById('dates-list');
                        const hidden = document.getElementById('fechas-hidden-input');
                        list.innerHTML = '';
                        selectedDates.forEach(d => {
                            list.innerHTML += `<span style="background:#3b82f6; color:white; padding:4px 8px; border-radius:4px; font-size:0.8rem; display:flex; align-items:center; gap:5px;">${d} <span style="cursor:pointer; font-weight:bold;" onclick="removeDate('${d}')">×</span></span>`;
                        });
                        hidden.value = selectedDates.join(',');
                    }
                </script>
            </div>
            <div class="card">
                <h3>Eventos Publicados</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Inscritos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($e = mysqli_fetch_assoc($result_events)): 
                            $eid = $e['id'];
                            $q_ins_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM inscripciones WHERE evento_id = $eid");
                            $ins_count = mysqli_fetch_assoc($q_ins_count)['total'];
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($e['titulo']); ?></strong><br>
                                    <small><?php echo date('d/m/Y', strtotime($e['fecha'])); ?> - <?php echo $e['hora']; ?></small>
                                </td>
                                <td>
                                    <details>
                                        <summary style="cursor:pointer; color:#3b82f6;">Ver <?php echo $ins_count; ?> inscritos</summary>
                                        <div style="margin-top:10px; background:#0f172a; padding:10px; border-radius:8px;">
                                            <?php
                                            // Agrupar por fecha de asistencia
                                            $q_fechas_ins = mysqli_query($conn, "SELECT DISTINCT fecha_asistencia FROM inscripciones WHERE evento_id = $eid ORDER BY fecha_asistencia ASC");
                                            if(mysqli_num_rows($q_fechas_ins) > 0):
                                                while($f_row = mysqli_fetch_assoc($q_fechas_ins)):
                                                    $f_asistencia = $f_row['fecha_asistencia'];
                                                    $q_list = mysqli_query($conn, "SELECT u.id as uid, u.nombre, u.bigo_id, i.agendado FROM inscripciones i JOIN usuarios u ON i.usuario_id = u.id WHERE i.evento_id = $eid AND i.fecha_asistencia = '$f_asistencia'");
                                            ?>
                                                    <div style="margin-bottom:15px; border-bottom:1px solid #334155; padding-bottom:10px;">
                                                        <p style="color:#60a5fa; font-weight:bold; margin:0 0 5px 0;">📅 <?php echo date('d/m/Y', strtotime($f_asistencia)); ?></p>
                                                        <?php while($ins = mysqli_fetch_assoc($q_list)): ?>
                                                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; font-size:0.85rem;">
                                                                <span><?php echo htmlspecialchars($ins['nombre']); ?> (<?php echo htmlspecialchars($ins['bigo_id']); ?>)</span>
                                                                <div style="display:flex; align-items:center; gap:10px;">
                                                                    <label style="color:#4ade80; font-size:0.75rem;">
                                                                        <input type="checkbox" onchange="toggleAgendado(this, <?php echo $ins['uid']; ?>, <?php echo $eid; ?>, '<?php echo $f_asistencia; ?>')" <?php echo $ins['agendado']?'checked':''; ?>> Agendado
                                                                    </label>
                                                                    <a href="?remove_attendee=<?php echo $ins['uid']; ?>&event_id=<?php echo $eid; ?>&date=<?php echo $f_asistencia; ?>" 
                                                                       style="color:#ef4444; text-decoration:none; font-weight:bold;" 
                                                                       onclick="return confirm('¿Quitar a este usuario del evento?')">×</a>
                                                                </div>
                                                            </div>
                                                        <?php endwhile; ?>
                                                    </div>
                                            <?php 
                                                endwhile;
                                            else:
                                                echo "<p style='font-size:0.8rem; color:#64748b;'>Sin inscritos aún.</p>";
                                            endif;
                                            ?>
                                        </div>
                                    </details>
                                </td>
                                <td>
                                    <a href="?delete_event=<?php echo $e['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Borrar evento?')">Eliminar</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB: VIP -->
        <div id="tab-vip" class="tab-content">
            <div class="card">
                <h3>Publicar Contenido Premium</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="text" name="titulo" class="form-control" placeholder="Título" required>
                    <select name="categoria" class="form-control">
                        <option value="imagen">Imagen</option>
                        <option value="video">Video</option>
                    </select>
                    <input type="file" name="media" class="form-control">
                    <input type="number" name="orden" class="form-control" value="0">
                    <button type="submit" name="upload_content" class="btn btn-primary">Subir al Panel VIP</button>
                </form>
            </div>
            <div class="card">
                <h3>Galería VIP</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <?php while ($v = mysqli_fetch_assoc($result_vip)): ?>
                        <div style="background:rgba(0,0,0,0.2); padding:10px; border-radius:8px;">
                            <p><strong>
                                    <?php echo htmlspecialchars($v['titulo']); ?>
                                </strong></p>
                            <a href="?delete_content=<?php echo $v['id']; ?>" class="btn btn-danger btn-sm">Eliminar</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- TAB: AJUSTES -->
        <div id="tab-settings" class="tab-content">
            <div class="card">
                <h3>Imagen de Marca (Logo)</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="logo_agency" class="form-control" required>
                    <button type="submit" class="btn btn-primary">Actualizar Logo</button>
                </form>
            </div>
            <div class="card">
                <h3>Cambiar mi Contraseña</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <input type="password" name="current_password" class="form-control" placeholder="Contraseña Actual"
                        required>
                    <input type="password" name="new_password" class="form-control" placeholder="Nueva Contraseña"
                        required>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirmar Nueva"
                        required>
                    <button type="submit" class="btn btn-success">Actualizar Clave</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-' + tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        function toggleAgendado(checkbox, userId, eventId, fecha) {
            const agendado = checkbox.checked ? 1 : 0;
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('event_id', eventId);
            formData.append('fecha', fecha);
            formData.append('agendado', agendado);

            fetch('<?php echo $root_path; ?>api/update_agendado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error: ' + (data.message || 'Desconocido'));
                    checkbox.checked = !checkbox.checked;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión.');
                checkbox.checked = !checkbox.checked;
            });
        }
    </script>

</body>

</html>