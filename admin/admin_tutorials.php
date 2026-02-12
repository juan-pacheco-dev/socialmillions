<?php
session_start();
include '../includes/db.php';

// Verifico si es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$msg = "";
$edit_mode = false;
$edit_data = null;

// PROCESO LA ELIMINACIÓN
if (isset($_GET['delete_id'])) {
    $del_id = (int) $_GET['delete_id'];
    $check = mysqli_query($conn, "SELECT filename FROM admin_tutorials WHERE id = $del_id");
    if ($row = mysqli_fetch_assoc($check)) {
        if (file_exists($row['filename'])) {
            unlink($row['filename']);
        }
        mysqli_query($conn, "DELETE FROM admin_tutorials WHERE id = $del_id");
        $msg = "✅ Tutorial eliminado exitosamente.";
    }
}

// PROCESO LA EDICIÓN (Cargar datos)
if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    $edit_mode = true;
    $res = mysqli_query($conn, "SELECT * FROM admin_tutorials WHERE id = $edit_id");
    $edit_data = mysqli_fetch_assoc($res);
}

// PROCESO LA CARGA / ACTUALIZACIÓN
if (isset($_POST['save_tutorial'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $target_id = isset($_POST['edit_id']) ? (int) $_POST['edit_id'] : null;

    $file_uploaded = false;
    $target_file = "";
    $file_type = "";

    if (!empty($_FILES["archivo"]["name"])) {
        $target_dir = "uploads/tutorials/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES["archivo"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strpos($_FILES["archivo"]["type"], 'video') !== false ? 'video' : 'image';

        if (move_uploaded_file($_FILES["archivo"]["tmp_name"], $target_file)) {
            $file_uploaded = true;
            // Si estoy editando y subo nuevo archivo, borro el anterior
            if ($target_id) {
                $old = mysqli_query($conn, "SELECT filename FROM admin_tutorials WHERE id = $target_id");
                if ($o = mysqli_fetch_assoc($old)) {
                    if (file_exists($o['filename']))
                        unlink($o['filename']);
                }
            }
        }
    }

    if ($target_id) {
        // ACTUALIZO
        if ($file_uploaded) {
            $sql = "UPDATE admin_tutorials SET title='$title', description='$description', filename='$target_file', file_type='$file_type' WHERE id=$target_id";
        } else {
            $sql = "UPDATE admin_tutorials SET title='$title', description='$description' WHERE id=$target_id";
        }
        mysqli_query($conn, $sql);
        $msg = "✅ Tutorial actualizado correctamente.";
        $edit_mode = false;
    } else {
        // INSERTO NUEVO
        if ($file_uploaded) {
            $sql = "INSERT INTO admin_tutorials (title, description, filename, file_type) VALUES ('$title', '$description', '$target_file', '$file_type')";
            mysqli_query($conn, $sql);
            $msg = "✅ Tutorial cargado correctamente.";
        } else {
            $msg = "❌ Debes subir un archivo para el nuevo tutorial.";
        }
    }
}

include '../includes/header.php';
?>

<link
    href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Montserrat:wght@300;400;600&display=swap"
    rel="stylesheet">
<link rel="stylesheet" href="../css/tutorials.css">

<style>
    /* Admin specific colors/tweak */
    input,
    textarea {
        width: 100%;
        border-radius: 8px;
        padding: 12px;
        background: #000;
        border: 1px solid #333;
        color: white;
        margin-bottom: 15px;
        outline: none;
        transition: 0.3s;
    }

    input:focus,
    textarea:focus {
        border-color: var(--gold);
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--gold);
        font-size: 0.8rem;
        text-transform: uppercase;
    }

    .btn-submit {
        width: 100%;
        padding: 14px;
        background: var(--gold);
        border: none;
        border-radius: 8px;
        color: #000;
        font-weight: 700;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-submit:hover {
        background: #fff;
    }
</style>

<div class="tutorial-container" style="padding-top: 120px;">
    <div class="tutorial-header">
        <h1>Gestión de Academia</h1>
        <p style="color: #666;">Sube guías visuales y videos para tus modelos</p>
    </div>

    <?php if ($msg): ?>
        <div
            style="background: rgba(212, 175, 55, 0.1); border: 1px solid var(--gold); color: #fff; padding: 15px; border-radius: 10px; margin-bottom: 30px; text-align: center;">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <div class="admin-tutorial-layout">
        <!-- FORMULARIO -->
        <div class="admin-form-card">
            <h3 style="color: #fff; margin-bottom: 25px; border-bottom: 1px solid #222; padding-bottom: 10px;">
                <?php echo $edit_mode ? "Editar Tutorial" : "Nuevo Tutorial"; ?>
            </h3>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>

                <label>Título del Tutorial</label>
                <input type="text" name="title" required
                    value="<?php echo $edit_mode ? htmlspecialchars($edit_data['title']) : ''; ?>">

                <label>Descripción Corta</label>
                <textarea name="description" rows="3"
                    required><?php echo $edit_mode ? htmlspecialchars($edit_data['description']) : ''; ?></textarea>

                <label>Archivo (Imagen o Video)</label>
                <?php if ($edit_mode): ?>
                    <p style="font-size: 0.75rem; color: #888; margin-bottom: 10px;">Subir nuevo para reemplazar el actual.
                    </p>
                <?php endif; ?>
                <input type="file" name="archivo" <?php echo $edit_mode ? '' : 'required'; ?>
                    style="padding: 10px; border: 1px dashed #333; margin-bottom: 30px;">

                <button type="submit" name="save_tutorial" class="btn-submit">
                    <?php echo $edit_mode ? "Guardar Cambios" : "Subir Tutorial"; ?>
                </button>

                <?php if ($edit_mode): ?>
                    <a href="admin_tutorials.php"
                        style="display:block; text-align:center; margin-top:15px; color:#ef4444; text-decoration:none; font-size:0.8rem;">Cancelar
                        Edición</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- LISTADO -->
        <div class="tutorial-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
            <?php
            $res = mysqli_query($conn, "SELECT * FROM admin_tutorials ORDER BY created_at DESC");
            while ($row = mysqli_fetch_assoc($res)):
                ?>
                <div class="tutorial-card">
                    <div class="tutorial-media">
                        <?php if ($row['file_type'] == 'video'): ?>
                            <video src="<?php echo $row['filename']; ?>"></video>
                        <?php else: ?>
                            <img src="<?php echo $row['filename']; ?>">
                        <?php endif; ?>

                        <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 5px;">
                            <a href="admin_tutorials.php?edit_id=<?php echo $row['id']; ?>"
                                style="background: rgba(0,0,0,0.7); padding: 5px 8px; border-radius: 5px; color: var(--gold); text-decoration: none;">✏️</a>
                            <a href="admin_tutorials.php?delete_id=<?php echo $row['id']; ?>"
                                onclick="return confirm('¿Eliminar tutorial?');"
                                style="background: rgba(0,0,0,0.7); padding: 5px 8px; border-radius: 5px; color: #ef4444; text-decoration: none;">🗑️</a>
                        </div>
                    </div>
                    <div class="tutorial-info" style="padding: 15px;">
                        <h4 style="color: #fff; margin: 0; font-family: 'Cinzel', serif; font-size: 1rem;">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </h4>
                        <p style="font-size: 0.8rem; color: #777; margin-top: 5px; line-height: 1.4;">
                            <?php echo htmlspecialchars($row['description']); ?>
                        </p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>