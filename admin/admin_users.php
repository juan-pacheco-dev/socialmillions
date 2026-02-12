<?php
// Inicio sesión (lo gestiona security.php si no lo está)
// session_start();
include '../includes/db.php';
include '../includes/security.php';

// Verifico si el usuario tiene rol de administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Proceso acciones: Aprobar, Bloquear, Eliminar y Promover
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['id'])) {

    // Verifico el token de seguridad CSRF
    if (!isset($_POST['csrf_token']) || !verificar_token_csrf($_POST['csrf_token'])) {
        die("Error de seguridad: Token inválido. Por favor recarga la página.");
    }

    $id = (int) $_POST['id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        mysqli_query($conn, "UPDATE usuarios SET estado = 'activo' WHERE id = $id");
    } elseif ($action == 'block') {
        mysqli_query($conn, "UPDATE usuarios SET estado = 'bloqueado' WHERE id = $id");
    } elseif ($action == 'delete') {
        // 1. Obtengo información del usuario para verificar si tiene carpeta
        $u_res = mysqli_query($conn, "SELECT nombre FROM usuarios WHERE id = $id");
        if ($u_row = mysqli_fetch_assoc($u_res)) {
            // 2. Intento buscar y borrar la carpeta del modelo
            // Como el nombre pudo cambiar, busco por el sufijo _ID en uploads
            $files = glob("../uploads/*_" . $id);
            foreach ($files as $folder) {
                if (is_dir($folder)) {
                    // Borro recursivamente la carpeta y su contenido
                    $it = new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS);
                    $files_it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ($files_it as $file) {
                        if ($file->isDir()) {
                            rmdir($file->getRealPath());
                        } else {
                            unlink($file->getRealPath());
                        }
                    }
                    rmdir($folder);
                }
            }
            // También elimino la carpeta antigua si existe
            $old_folder = "uploads/model_content/$id";
            if (is_dir($old_folder)) {
                $it = new RecursiveDirectoryIterator($old_folder, RecursiveDirectoryIterator::SKIP_DOTS);
                $files_it = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files_it as $file) {
                    if ($file->isDir())
                        rmdir($file->getRealPath());
                    else
                        unlink($file->getRealPath());
                }
                rmdir($old_folder);
            }
        }
        mysqli_query($conn, "DELETE FROM usuarios WHERE id = $id");
    } elseif ($action == 'make_model' || $action == 'approve') {
        // Obtengo el tipo de contenido para definir el rol
        $u_q = mysqli_query($conn, "SELECT tipo_contenido FROM usuarios WHERE id = $id");
        if ($u_row = mysqli_fetch_assoc($u_q)) {
            $new_role = ($u_row['tipo_contenido'] == '+18') ? 'model' : 'user';

            // Promuevo manteniendo rol 'user' para normales y 'model' para +18. Reseteo es_nuevo.
            mysqli_query($conn, "UPDATE usuarios SET rol = '$new_role', estado = 'activo', es_nuevo = 0 WHERE id = $id");
        }
    } elseif ($action == 'toggle_event_access') {
        // Alterno el acceso a eventos (Activar/Desactivar)
        // Primero obtengo el estado actual
        $cur_q = mysqli_query($conn, "SELECT event_access FROM usuarios WHERE id = $id");
        $cur = mysqli_fetch_assoc($cur_q);
        $new_val = ($cur['event_access'] == 1) ? 0 : 1;
        mysqli_query($conn, "UPDATE usuarios SET event_access = $new_val WHERE id = $id");
    }
    header("Location: admin_users.php");
    exit();
}

// Proceso la carga de imágenes del usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id_assets'])) {
    $uid = (int) $_POST['user_id_assets'];
    $target_dir = "../uploads/users/";
    if (!file_exists($target_dir))
        mkdir($target_dir, 0777, true);

    // Obtengo datos actuales para borrar archivos antiguos
    $res_old = mysqli_query($conn, "SELECT foto_perfil, img_doc1, img_doc2 FROM usuarios WHERE id = $uid");
    $old_data = mysqli_fetch_assoc($res_old);

    $fields = ['foto_perfil', 'img_doc1', 'img_doc2'];
    foreach ($fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
            $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
            $filename = "user_" . $uid . "_" . $field . "_" . time() . "." . $ext;
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_file)) {
                // Elimino el archivo viejo si existe
                if (!empty($old_data[$field]) && file_exists($old_data[$field])) {
                    unlink($old_data[$field]);
                }
                // Actualizo la base de datos
                mysqli_query($conn, "UPDATE usuarios SET $field = '$target_file' WHERE id = $uid");
            }
        }
    }
    header("Location: admin_users.php");
    exit();
}

$current_admin_id = $_SESSION['usuario_id'];

// Manejo la búsqueda
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$search_query = "";
if (!empty($search)) {
    $search_query = " AND (nombre LIKE '%$search%' OR email LIKE '%$search%' OR bigo_id LIKE '%$search%') ";
}

// Manejo el filtro de acceso a eventos
$filter_event = isset($_GET['filter_event']) ? $_GET['filter_event'] : 'all';
$event_query = "";
if ($filter_event === 'locked') {
    $event_query = " AND event_access = 0 ";
} elseif ($filter_event === 'unlocked') {
    $event_query = " AND event_access = 1 ";
}

// ========== CONSULTAS SEPARADAS ==========

// 1. Streamers Nuevos (Pendientes de revisión)
$query_nuevos = "SELECT * FROM usuarios
WHERE id != $current_admin_id
AND es_nuevo = 1
AND rol NOT IN ('admin', 'viewer')
$search_query
$event_query
AND email NOT LIKE '%@viewer%'
ORDER BY created_at DESC";
$result_nuevos = mysqli_query($conn, $query_nuevos);

// 2. Streamers Normales (Excluyendo +18, viewers, nuevos y admin)
$query_streamers = "SELECT * FROM usuarios
WHERE id != $current_admin_id
AND rol NOT IN ('admin', 'viewer')
AND (tipo_contenido != '+18' OR tipo_contenido IS NULL OR tipo_contenido = '')
AND (es_nuevo = 0 OR es_nuevo IS NULL)
$search_query
$event_query
AND email NOT LIKE '%@viewer%'
ORDER BY created_at DESC";
$result_streamers = mysqli_query($conn, $query_streamers);

// 3. Streamers +18 (Contenido adulto, no nuevos)
$query_plus18 = "SELECT * FROM usuarios
WHERE id != $current_admin_id
AND rol NOT IN ('admin', 'viewer')
AND tipo_contenido = '+18'
AND (es_nuevo = 0 OR es_nuevo IS NULL)
$search_query
$event_query
AND email NOT LIKE '%@viewer%'
ORDER BY created_at DESC";
$result_plus18 = mysqli_query($conn, $query_plus18);

// 4. Viewers (rol = 'viewer') - SECCIÓN ELIMINADA
// Mantengo ocultos para no saturar el panel.

include '../includes/header.php';
?>

<link rel="stylesheet" href="../css/admin_users.css">



<main class="users-table-container" style="padding-top: 120px;">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
        <h2 class="table-title" style="margin-bottom: 0; color: white;">Panel de Administración de Usuarios</h2>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="../exports/export_users.php?filter=normal" class="btn btn-primary"
                style="background: #16a34a; border-color: #15803d;">
                📊 Exportar Streamers
            </a>
            <a href="../exports/export_users.php?filter=models" class="btn btn-primary"
                style="background: #ec4899; border-color: #db2777;">
                📸 Exportar Modelos
            </a>
            <a href="../exports/export_nuevos.php" class="btn btn-primary"
                style="background: #f59e0b; border-color: #d97706;">
                📋 Exportar Nuevos
            </a>
        </div>
    </div>

    <!-- BUSCADOR Y FILTROS -->
    <form method="GET" class="search-container reveal active" style="flex-wrap: wrap; gap: 15px;">
        <input type="text" name="search" class="search-input" placeholder="Buscar por Nombre, Email o Bigo ID..."
            value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 280px; margin-bottom: 0;">

        <div style="display: flex; gap: 10px; flex: 1; min-width: 280px;">
            <select name="filter_event" class="search-input" style="flex: 1; cursor: pointer; margin-bottom: 0;">
                <option value="all" <?php echo $filter_event === 'all' ? 'selected' : ''; ?>>📅 Todos los Eventos</option>
                <option value="unlocked" <?php echo $filter_event === 'unlocked' ? 'selected' : ''; ?>>🔓 Acceso:
                    Permitido</option>
                <option value="locked" <?php echo $filter_event === 'locked' ? 'selected' : ''; ?>>🔒 Acceso: Bloqueado
                </option>
            </select>
            <button type="submit" class="btn btn-primary"
                style="padding: 0 25px; margin-bottom: 0; min-width: 100px;">🔍 Buscar</button>
        </div>

        <?php if (!empty($search) || $filter_event !== 'all'): ?>
            <a href="admin_users.php" class="btn btn-outline"
                style="display: flex; align-items: center; justify-content: center; width: 100%;">Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- ========== SECCIÓN 1: STREAMERS NUEVOS ========== -->
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title-custom nuevos">
                🆕 Streamers Nuevos (Postulaciones)
                <span class="section-badge badge-nuevos"><?php echo mysqli_num_rows($result_nuevos); ?></span>
            </h3>
        </div>

        <?php if (mysqli_num_rows($result_nuevos) > 0): ?>
            <div class="scroll-hint"><span>↔️ Desliza para ver más</span></div>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Celular</th>
                            <th class="hidden-mobile">Tipo</th>
                            <th class="hidden-mobile">Disponibilidad</th>
                            <th>Registro</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $count_nuevos = 0;
                        while ($user = mysqli_fetch_assoc($result_nuevos)):
                            $count_nuevos++;
                            $hidden_class = ($count_nuevos > 5) ? 'hidden' : '';
                            ?>
                            <tr class="user-row <?php echo $hidden_class; ?>" data-section="nuevos">
                                <td data-label="Nombre"><?php echo htmlspecialchars($user['nombre'] ?? ''); ?></td>
                                <td data-label="Email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td data-label="Celular"><?php echo htmlspecialchars($user['celular'] ?? ''); ?></td>
                                <td data-label="Tipo">
                                    <?php if ($user['tipo_contenido'] == '+18'): ?>
                                        <span style="color: #ec4899; font-weight:700;">+18 🔞</span>
                                    <?php else: ?>
                                        <span style="color: #3b82f6; font-weight:700;">Normal 🎮</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Disponibilidad">
                                    <?php echo htmlspecialchars($user['disponibilidad'] ?? 'No especificado'); ?>
                                </td>
                                <td data-label="Registro"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td data-label="Acciones">
                                    <div class="mobile-stack">
                                        <button class="action-btn" style="background: #6366f1; width: 100%;"
                                            onclick='openNuevoModal(<?php echo json_encode($user); ?>)'>
                                            👁️ Info
                                        </button>
                                        <form action="admin_users.php" method="POST" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="make_model">
                                            <button type="submit" class="action-btn btn-promote" style="width: 100%;"
                                                onclick="return confirm('¿Aprobar y promover a Streamer?')">✅ Aprobar</button>
                                        </form>

                                        <form action="admin_users.php" method="POST" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="action-btn btn-delete" style="width: 100%;"
                                                onclick="return confirm('¿Rechazar y eliminar?')">❌ Rechazar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if (mysqli_num_rows($result_nuevos) > 5): ?>
                <button class="load-more-btn" onclick="loadMore('nuevos')">Cargar más Streamers Nuevos</button>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>🎉 No hay postulaciones nuevas pendientes</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========== SECCIÓN 2: GESTIÓN DE STREAMERS (Normales) ========== -->
    <div class="section-card">
        <div class="section-header">
            <h3 class="section-title-custom streamers">
                🎮 Gestión de Streamers
                <span class="section-badge badge-streamers"><?php echo mysqli_num_rows($result_streamers); ?></span>
            </h3>
        </div>

        <?php if (mysqli_num_rows($result_streamers) > 0): ?>
            <div class="scroll-hint"><span>↔️ Desliza para ver más</span></div>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th class="hidden-mobile">Email</th>
                            <th>Bigo ID</th>
                            <th class="hidden-mobile">Celular</th>
                            <th>Estado</th>
                            <th class="hidden-mobile">Eventos</th>
                            <th class="hidden-mobile">Registro</th>
                            <th class="hidden-mobile">Perfil</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $count = 0;
                        while ($user = mysqli_fetch_assoc($result_streamers)):
                            $count++;
                            $hidden_class = ($count > 5) ? 'hidden' : '';
                            ?>
                            <tr class="user-row <?php echo $hidden_class; ?>" data-section="streamers">
                                <td data-label="Nombre"><?php echo htmlspecialchars($user['nombre'] ?? ''); ?></td>
                                <td data-label="Email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td data-label="Bigo ID">
                                    <strong><?php echo htmlspecialchars($user['bigo_id'] ?? ''); ?></strong>
                                </td>
                                <td data-label="Celular"><?php echo htmlspecialchars($user['celular'] ?? ''); ?></td>
                                <td data-label="Estado">
                                    </span>
                                </td>
                                <td data-label="Eventos">
                                    <form action="admin_users.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="toggle_event_access">
                                        <?php if ($user['event_access'] == 1): ?>
                                            <button type="submit" style="background:none; border:none; cursor:pointer;"
                                                title="Bloquear Eventos">🔓</button>
                                        <?php else: ?>
                                            <button type="submit" style="background:none; border:none; cursor:pointer;"
                                                title="Desbloquear Eventos">🔒 <span
                                                    style="font-size:0.7rem; color:red">Bloq</span></button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td data-label="Registro"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td data-label="Perfil">
                                    <button class="action-btn" style="background: #3b82f6;"
                                        onclick='openUserModal(<?php echo json_encode($user); ?>)'>
                                        🖼️ Assets
                                    </button>
                                </td>
                                <td data-label="Acciones">
                                    <div class="mobile-stack">
                                        <?php if ($user['estado'] == 'pendiente'): ?>
                                            <form action="admin_users.php" method="POST" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="action-btn btn-approve"
                                                    style="width: 100%;">Aceptar</button>
                                            </form>

                                            <form action="admin_users.php" method="POST" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="action-btn btn-delete" style="width: 100%;"
                                                    onclick="return confirm('¿Rechazar y eliminar?')">Rechazar</button>
                                            </form>
                                        <?php else: ?>
                                            <?php if ($user['estado'] == 'activo'): ?>
                                                <form action="admin_users.php" method="POST" style="margin:0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="block">
                                                    <button type="submit" class="action-btn btn-block"
                                                        style="width: 100%;">Bloquear</button>
                                                </form>
                                            <?php else: ?>
                                                <form action="admin_users.php" method="POST" style="margin:0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="action-btn btn-approve"
                                                        style="width: 100%;">Desbloquear</button>
                                                </form>
                                            <?php endif; ?>

                                            <form action="admin_users.php" method="POST" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="action-btn btn-delete" style="width: 100%;"
                                                    onclick="return confirm('¿Eliminar permanentemente?')">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if (mysqli_num_rows($result_streamers) > 5): ?>
                <button class="load-more-btn" onclick="loadMore('streamers')">Cargar más Streamers</button>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>No hay streamers normales registrados</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========== SECCIÓN 3: GESTIÓN +18 ========== -->
    <div class="section-card" style="border-color: rgba(236, 72, 153, 0.3);">
        <div class="section-header">
            <h3 class="section-title-custom plus18">
                🔞 Gestión +18
                <span class="section-badge badge-plus18"><?php echo mysqli_num_rows($result_plus18); ?></span>
            </h3>
        </div>

        <?php if (mysqli_num_rows($result_plus18) > 0): ?>
            <div class="scroll-hint"><span>↔️ Desliza para ver más</span></div>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th class="hidden-mobile">Email</th>
                            <th class="hidden-mobile">Celular</th>
                            <th>Estado</th>
                            <th class="hidden-mobile">Eventos</th>
                            <th class="hidden-mobile">Registro</th>
                            <th class="hidden-mobile">Perfil</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Reinicio el puntero de resultados MySQL
                        mysqli_data_seek($result_plus18, 0);
                        $count_plus18 = 0;
                        while ($user = mysqli_fetch_assoc($result_plus18)):
                            $count_plus18++;
                            $hidden_class = ($count_plus18 > 5) ? 'hidden' : '';
                            ?>
                            <tr class="user-row <?php echo $hidden_class; ?>" data-section="plus18">
                                <td data-label="Nombre"><?php echo htmlspecialchars($user['nombre'] ?? ''); ?></td>
                                <td data-label="Email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                <td data-label="Celular"><?php echo htmlspecialchars($user['celular'] ?? ''); ?></td>
                                <td data-label="Estado">
                                    </span>
                                </td>
                                <td data-label="Eventos">
                                    <form action="admin_users.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="action" value="toggle_event_access">
                                        <?php if ($user['event_access'] == 1): ?>
                                            <button type="submit" style="background:none; border:none; cursor:pointer;"
                                                title="Bloquear Eventos">🔓</button>
                                        <?php else: ?>
                                            <button type="submit" style="background:none; border:none; cursor:pointer;"
                                                title="Desbloquear Eventos">🔒 <span
                                                    style="font-size:0.7rem; color:red">Bloq</span></button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td data-label="Registro"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td data-label="Perfil">
                                    <button class="action-btn" style="background: #ec4899;"
                                        onclick='openUserModal(<?php echo json_encode($user); ?>)'>
                                        🖼️ Ver Assets
                                    </button>
                                </td>
                                <td data-label="Acciones">
                                    <?php if ($user['estado'] == 'pendiente'): ?>
                                        <form action="admin_users.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="action-btn btn-approve">Aceptar</button>
                                        </form>
                                        <form action="admin_users.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="action-btn btn-delete"
                                                onclick="return confirm('¿Rechazar?')">Rechazar</button>
                                        </form>
                                    <?php else: ?>
                                        <?php if ($user['estado'] == 'activo'): ?>
                                            <form action="admin_users.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="block">
                                                <button type="submit" class="action-btn btn-block">Bloquear</button>
                                            </form>
                                        <?php else: ?>
                                            <form action="admin_users.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="action-btn btn-approve">Desbloquear</button>
                                            </form>
                                        <?php endif; ?>
                                        <form action="admin_users.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="action-btn btn-delete"
                                                onclick="return confirm('¿Eliminar?')">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php if (mysqli_num_rows($result_plus18) > 5): ?>
                <button class="load-more-btn" onclick="loadMore('plus18')">Cargar más (+18)</button>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>No hay streamers +18 registrados</p>
            </div>
        <?php endif; ?>
    </div>


    <div style="margin-top: 48px;">
        <a href="admin_panel.php" class="btn btn-primary" style="padding: 14px 32px; font-weight: 800;">Volver al
            Panel</a>
    </div>
    </div>
</main>

<!-- Modal para ver Assets del Usuario -->
<div id="userAssetsModal" class="modal-overlay"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 1000; justify-content: center; align-items: center; padding: 20px;">
    <div class="modal-content"
        style="background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 32px; width: 100%; max-width: 600px; color: white; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 1.5rem; color: #3b82f6;">Perfil de <span id="modalUserName"></span></h3>
            <button onclick="closeUserModal()"
                style="background: transparent; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <form action="admin_users.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id_assets" id="modalUserId">

            <div class="asset-grid"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 20px; margin-bottom: 24px;">
                <!-- Foto de Perfil -->
                <div class="asset-item">
                    <label style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 8px;">Foto
                        Perfil</label>
                    <div id="preview_foto_perfil" class="img-preview"
                        style="width: 100%; height: 120px; background: #020617; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px dashed #334155; position: relative;">
                        <span style="color: #475569; font-size: 0.8rem;">Sin foto</span>
                    </div>
                    <div id="download_foto_perfil" style="margin-bottom: 10px;"></div>
                    <input type="file" name="foto_perfil" class="form-control" style="font-size: 0.8rem; padding: 5px;">
                </div>

                <!-- Doc 1 -->
                <div class="asset-item">
                    <label style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 8px;">Documento
                        1</label>
                    <div id="preview_img_doc1" class="img-preview"
                        style="width: 100%; height: 120px; background: #020617; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px dashed #334155; position: relative;">
                        <span style="color: #475569; font-size: 0.8rem;">Sin imagen</span>
                    </div>
                    <div id="download_img_doc1" style="margin-bottom: 10px;"></div>
                    <input type="file" name="img_doc1" class="form-control" style="font-size: 0.8rem; padding: 5px;">
                </div>

                <!-- Doc 2 -->
                <div class="asset-item">
                    <label style="display: block; font-size: 0.85rem; color: #cbd5e1; margin-bottom: 8px;">Documento
                        2</label>
                    <div id="preview_img_doc2" class="img-preview"
                        style="width: 100%; height: 120px; background: #020617; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px dashed #334155; position: relative;">
                        <span style="color: #475569; font-size: 0.8rem;">Sin imagen</span>
                    </div>
                    <div id="download_img_doc2" style="margin-bottom: 10px;"></div>
                    <input type="file" name="img_doc2" class="form-control" style="font-size: 0.8rem; padding: 5px;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary"
                style="width: 100%; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                💾 Guardar Cambios
            </button>
        </form>
    </div>
</div>

<!-- Modal para Información de Streamers Nuevos -->
<div id="nuevoModal" class="modal-overlay"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 1000; justify-content: center; align-items: center; padding: 20px;">
    <div class="modal-content"
        style="background: #0f172a; border: 2px solid #f59e0b; border-radius: 16px; padding: 32px; width: 100%; max-width: 600px; color: white; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 1.5rem; color: #f59e0b;">📋 Postulación de <span
                    id="nuevoModalName"></span></h3>
            <button onclick="closeNuevoModal()"
                style="background: transparent; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <div id="nuevoInfoContainer">
            <!-- Se llena dinámicamente -->
        </div>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <form id="formAprobarModal" action="admin_users.php" method="POST" style="flex: 1; margin: 0;">
                <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                <input type="hidden" name="id" id="nuevoModalId_Aprobar">
                <input type="hidden" name="action" value="make_model">
                <button type="submit" class="btn btn-primary" style="width: 100%; background: #16a34a;"
                    onclick="return confirm('¿Aprobar y promover a Streamer?')">
                    ✅ Aprobar Postulación
                </button>
            </form>
            <form id="formRechazarModal" action="admin_users.php" method="POST" style="flex: 1; margin: 0;">
                <input type="hidden" name="csrf_token" value="<?php echo generar_token_csrf(); ?>">
                <input type="hidden" name="id" id="nuevoModalId_Rechazar">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-primary" style="width: 100%; background: #dc2626;"
                    onclick="return confirm('¿Rechazar y eliminar?')">
                    ❌ Rechazar
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Lightbox para visualizar imágenes en grande -->
<div id="lightboxOverlay" onclick="closeLightbox()"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 2000; justify-content: center; align-items: center; cursor: pointer;">
    <img id="lightboxImg" src=""
        style="max-width: 90%; max-height: 90%; border-radius: 8px; box-shadow: 0 0 50px rgba(59, 130, 246, 0.3);">
    <div style="position: absolute; top: 20px; right: 20px; color: white; font-size: 2rem;">&times;</div>
</div>

<script>
    function openUserModal(user) {
        document.getElementById('modalUserId').value = user.id;
        document.getElementById('modalUserName').innerText = user.nombre;

        // Preparo las previsualizaciones y botones de descarga
        updatePreview('preview_foto_perfil', 'download_foto_perfil', user.foto_perfil, 'Foto_Perfil_' + user.nombre);
        updatePreview('preview_img_doc1', 'download_img_doc1', user.img_doc1, 'Documento1_' + user.nombre);
        updatePreview('preview_img_doc2', 'download_img_doc2', user.img_doc2, 'Documento2_' + user.nombre);

        document.getElementById('userAssetsModal').style.display = 'flex';
    }

    function updatePreview(id, downloadId, path, filename) {
        const container = document.getElementById(id);
        const downloadContainer = document.getElementById(downloadId);

        if (path && path !== '') {
            container.innerHTML = `<img src="${path}" style="width: 100%; height: 100%; object-fit: cover; cursor: zoom-in;" onclick="openLightbox('${path}')">`;
            downloadContainer.innerHTML = `<a href="${path}" download="${filename}" style="display: inline-block; background: #1e293b; color: #60a5fa; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; text-decoration: none; border: 1px solid #334155;">📥 Descargar Original</a>`;
        } else {
            container.innerHTML = `<span style="color: #475569; font-size: 0.8rem;">Sin imagen</span>`;
            downloadContainer.innerHTML = '';
        }
    }

    function openNuevoModal(user) {
        document.getElementById('nuevoModalName').innerText = user.nombre;

        // Construyo el HTML con la información del usuario
        let html = `
        <div class="nuevo-info">
            <div class="nuevo-info-item">
                <strong>📧 Email:</strong>
                <span>${user.email || 'No especificado'}</span>
            </div>
            <div class="nuevo-info-item">
                <strong>📱 Celular:</strong>
                <span>${user.celular || 'No especificado'}</span>
            </div>
            <div class="nuevo-info-item">
                <strong>🎮 Bigo ID:</strong>
                <span>${user.bigo_id || 'No tiene'}</span>
            </div>
            <div class="nuevo-info-item">
                <strong>📺 Tipo:</strong>
                <span>${user.tipo_contenido == '+18' ? '🔞 Contenido +18' : '🎮 Stream Normal'}</span>
            </div>
            <div class="nuevo-info-item">
                <strong>⏰ Disponibilidad:</strong>
                <span>${user.disponibilidad || 'No especificado'}</span>
            </div>
        </div>
        
        <div class="nuevo-info" style="margin-top: 15px;">
            <div style="color: #f59e0b; font-weight: 700; margin-bottom: 10px;">💬 Experiencia:</div>
            <p style="color: #e2e8f0; margin: 0; white-space: pre-wrap;">${user.experiencia || 'No proporcionó información'}</p>
        </div>
        
        <div class="nuevo-info" style="margin-top: 15px;">
            <div style="color: #f59e0b; font-weight: 700; margin-bottom: 10px;">🎯 Motivación:</div>
            <p style="color: #e2e8f0; margin: 0; white-space: pre-wrap;">${user.motivo || 'No proporcionó información'}</p>
        </div>
        
        <div class="nuevo-info" style="margin-top: 15px; background: #1e3a5f;">
            <div class="nuevo-info-item">
                <strong>📅 Fecha Registro:</strong>
                <span>${new Date(user.created_at).toLocaleDateString('es-ES')}</span>
            </div>
        </div>
    `;

        document.getElementById('nuevoInfoContainer').innerHTML = html;
        document.getElementById('nuevoModalId_Aprobar').value = user.id;
        document.getElementById('nuevoModalId_Rechazar').value = user.id;

        document.getElementById('nuevoModal').style.display = 'flex';
    }

    function closeNuevoModal() {
        document.getElementById('nuevoModal').style.display = 'none';
    }

    function openLightbox(src) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('lightboxOverlay').style.display = 'flex';
    }

    function closeLightbox() {
        document.getElementById('lightboxOverlay').style.display = 'none';
    }

    function closeUserModal() {
        document.getElementById('userAssetsModal').style.display = 'none';
    }

    function loadMore(section) {
        const hiddenRows = document.querySelectorAll(`.user-row.hidden[data-section="${section}"]`);
        for (let i = 0; i < 5 && i < hiddenRows.length; i++) {
            hiddenRows[i].classList.remove('hidden');
        }
        // Hide button if no more hidden rows in this section
        if (document.querySelectorAll(`.user-row.hidden[data-section="${section}"]`).length === 0) {
            event.target.style.display = 'none';
        }
    }
</script>

<!-- SCRIPTS PARA MODALES Y PAGINACIÓN -->
<script>
    // --- FUNCIONALIDAD DE CARGAR MÁS ---
    function loadMore(section) {
        // Selecciono todas las filas ocultas de la sección
        const hiddenRows = document.querySelectorAll(`.user-row.hidden[data-section="${section}"]`);
        let count = 0;
        
        // Muestro las siguientes 5 filas
        hiddenRows.forEach(row => {
            if (count < 5) {
                row.classList.remove('hidden');
                count++;
            }
        });

        // Oculto el botón si ya no quedan filas por mostrar
        const remainingHidden = document.querySelectorAll(`.user-row.hidden[data-section="${section}"]`);
        if (remainingHidden.length === 0) {
            // Busco y oculto el botón correspondiente
            // El botón está después de la tabla, lo busco en el DOM
            // Ocultamos todos los botones que llamen a esta sección si no quedan filas
             const buttons = document.querySelectorAll(`button[onclick="loadMore('${section}')"]`);
             buttons.forEach(btn => btn.style.display = 'none');
        }
    }

    // --- USER ASSETS MODAL ---
    function openUserModal(user) {
        document.getElementById('modalUserName').innerText = user.nombre;
        document.getElementById('modalUserId').value = user.id;

        const fields = ['foto_perfil', 'img_doc1', 'img_doc2'];
        
        fields.forEach(field => {
            const previewDiv = document.getElementById('preview_' + field);
            const downloadDiv = document.getElementById('download_' + field);
            
            if (user[field]) {
                // Determine file type
                const fileExt = user[field].split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                    previewDiv.innerHTML = `<img src="${user[field]}" style="width:100%; height:100%; object-fit:cover;">`;
                } else {
                    previewDiv.innerHTML = `<span style="font-size:2rem;">📄</span><span style="font-size:0.7rem;">${fileExt.toUpperCase()}</span>`;
                }
                
                downloadDiv.innerHTML = `<a href="${user[field]}" target="_blank" class="btn btn-outline" style="width:100%; padding: 5px; font-size: 0.8rem;">⬇️ Descargar</a>`;
            } else {
                previewDiv.innerHTML = `<span style="color: #475569; font-size: 0.8rem;">Sin archivo</span>`;
                downloadDiv.innerHTML = '';
            }
        });

        document.getElementById('userAssetsModal').style.display = 'flex';
    }

    function closeUserModal() {
        document.getElementById('userAssetsModal').style.display = 'none';
    }

    // --- NUEVO STREAMER MODAL ---
    function openNuevoModal(user) {
        document.getElementById('nuevoModalName').innerText = user.nombre;
        document.getElementById('nuevoModalId_Aprobar').value = user.id;
        document.getElementById('nuevoModalId_Rechazar').value = user.id;

        const infoContainer = document.getElementById('nuevoInfoContainer');
        infoContainer.innerHTML = `
            <div class="nuevo-info">
                <div class="nuevo-info-item"><strong>Email:</strong> <span>${user.email}</span></div>
                <div class="nuevo-info-item"><strong>Celular:</strong> <span>${user.celular}</span></div>
                <div class="nuevo-info-item"><strong>Plataforma:</strong> <span>${user.plataforma || 'N/A'}</span></div>
                <div class="nuevo-info-item"><strong>ID Plataforma:</strong> <span>${user.bigo_id}</span></div>
                <div class="nuevo-info-item"><strong>Experiencia:</strong> <p style="margin:5px 0 0 0; color:#cbd5e1; font-style:italic;">${user.experiencia || 'Sin especificar'}</p></div>
                <div class="nuevo-info-item"><strong>Disponibilidad:</strong> <span>${user.disponibilidad || 'N/A'}</span></div>
                <div class="nuevo-info-item"><strong>Motivación:</strong> <p style="margin:5px 0 0 0; color:#cbd5e1; font-style:italic;">${user.motivo || 'Sin especificar'}</p></div>
            </div>
        `;

        document.getElementById('nuevoModal').style.display = 'flex';
    }

    function closeNuevoModal() {
        document.getElementById('nuevoModal').style.display = 'none';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('userAssetsModal')) {
            closeUserModal();
        }
        if (event.target == document.getElementById('nuevoModal')) {
            closeNuevoModal();
        }
    }
</script>

<?php include '../includes/footer.php'; ?>