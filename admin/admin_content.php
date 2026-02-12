<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success = "";
$error = "";

// Proceso la carga o edición de contenido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['upload_content'])) {
        $edit_id = isset($_POST['edit_id']) ? (int) $_POST['edit_id'] : 0;
        $titulo = mysqli_real_escape_string($conn, $_POST['titulo']);
        $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);
        $categoria = mysqli_real_escape_string($conn, $_POST['categoria']);
        $orden = (int) $_POST['orden'];

        $target_file = "";
        $uploadOk = 1;

        // Gestiono los archivos (solo si se sube uno nuevo)
        if (isset($_FILES["media"]) && !empty($_FILES["media"]["name"])) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir))
                mkdir($target_dir, 0777, true);

            $file_name = time() . "_" . basename($_FILES["media"]["name"]);
            $target_file = $target_dir . $file_name;
            $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            if ($categoria == 'imagen') {
                $check = getimagesize($_FILES["media"]["tmp_name"]);
                if ($check === false) {
                    $uploadOk = 0;
                    $error = "El archivo no es una imagen válida.";
                }
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["media"]["tmp_name"], $target_file)) {
                    $uploadOk = 0;
                    $error = "Error al mover el archivo.";
                }
            }
        }

        if ($uploadOk == 1) {
            if ($edit_id > 0) {
                // Entro en Modo Edición
                if (!empty($target_file)) {
                    // Borro la imagen anterior si se subió una nueva
                    $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT imagen FROM contenido WHERE id = $edit_id"));
                    if ($old && !empty($old['imagen']) && file_exists($old['imagen']))
                        unlink($old['imagen']);
                    $query = "UPDATE contenido SET titulo='$titulo', descripcion='$descripcion', imagen='$target_file', categoria='$categoria', orden=$orden WHERE id = $edit_id";
                } else {
                    $query = "UPDATE contenido SET titulo='$titulo', descripcion='$descripcion', categoria='$categoria', orden=$orden WHERE id = $edit_id";
                }
                $msg = "Contenido actualizado.";
            } else {
                // Entro en Modo Nuevo
                $query = "INSERT INTO contenido (titulo, descripcion, imagen, categoria, orden) VALUES ('$titulo', '$descripcion', '$target_file', '$categoria', $orden)";
                $msg = "Contenido publicado correctamente.";
            }

            if (mysqli_query($conn, $query)) {
                $success = $msg;
            } else {
                $error = "Error en BD: " . mysqli_error($conn);
            }
        }
    }
}

// Obtengo datos para editar
$edit_item = null;
if (isset($_GET['edit'])) {
    $eid = (int) $_GET['edit'];
    $res_edit = mysqli_query($conn, "SELECT * FROM contenido WHERE id = $eid");
    $edit_item = mysqli_fetch_assoc($res_edit);
}

// Elimino el contenido
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $res = mysqli_query($conn, "SELECT imagen FROM contenido WHERE id = $id");
    $content = mysqli_fetch_assoc($res);
    if ($content) {
        if (!empty($content['imagen']) && file_exists($content['imagen']))
            unlink($content['imagen']);
        mysqli_query($conn, "DELETE FROM contenido WHERE id = $id");
    }
    header("Location: admin_content.php");
    exit();
}

$contenidos = mysqli_query($conn, "SELECT * FROM contenido ORDER BY orden ASC, created_at DESC");

include '../includes/header.php';
?>

<link rel="stylesheet" href="../css/admin_content.css">

<style>
    @media (max-width: 768px) {
        main.users-table-container {
            padding-top: 80px !important;
        }
    }
</style>

<main class="users-table-container" style="padding-top: 120px;">
    <div class="content-form-container" style="max-width: 1000px; margin: 0 auto; padding: 0 15px;">
        <h2 class="table-title"
            style="color: white !important; font-weight: 800; border-left: 4px solid #3b82f6; padding-left: 15px;">
            Contenido Exclusivo</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"
                style="background: rgba(34, 197, 94, 0.1); border: 1px solid #16a34a; color: #4ade80; padding: 15px; border-radius: 8px; margin-bottom: 24px;">
                ✅ <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"
                style="background: rgba(220, 38, 38, 0.1); border: 1px solid #dc2626; color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 24px;">
                ❌ <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div
            style="background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 30px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <form action="admin_content.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" value="<?php echo $edit_item ? $edit_item['id'] : ''; ?>">

                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label for="titulo"
                            style="color: #94a3b8; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px; display: block;">Título</label>
                        <input type="text" name="titulo" id="titulo" class="form-control"
                            style="background: #020617; border-color: #334155; color: white;"
                            value="<?php echo $edit_item ? htmlspecialchars($edit_item['titulo']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="orden"
                            style="color: #94a3b8; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px; display: block;">Orden
                            (Menor = Primero)</label>
                        <input type="number" name="orden" id="orden" class="form-control"
                            style="background: #020617; border-color: #334155; color: white;"
                            value="<?php echo $edit_item ? (int) $edit_item['orden'] : '0'; ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="descripcion"
                        style="color: #94a3b8; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px; display: block;">Descripción
                        / Contenido</label>
                    <textarea name="descripcion" id="descripcion" class="form-control"
                        style="background: #020617; border-color: #334155; color: white;"
                        rows="3"><?php echo $edit_item ? htmlspecialchars($edit_item['descripcion']) : ''; ?></textarea>
                </div>

                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label for="categoria"
                            style="color: #94a3b8; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px; display: block;">Tipo
                            de Contenido</label>
                        <select name="categoria" id="categoria" class="form-control"
                            style="background: #020617; border-color: #334155; color: white;"
                            onchange="toggleFileUpload(this.value)">
                            <option value="imagen" <?php echo ($edit_item && $edit_item['categoria'] == 'imagen') ? 'selected' : ''; ?>>Imagen</option>
                            <option value="video" <?php echo ($edit_item && $edit_item['categoria'] == 'video') ? 'selected' : ''; ?>>Video</option>
                            <option value="texto" <?php echo ($edit_item && $edit_item['categoria'] == 'texto') ? 'selected' : ''; ?>>Solo Texto / Mensaje</option>
                        </select>
                    </div>
                    <div class="form-group" id="media_group"
                        style="<?php echo ($edit_item && $edit_item['categoria'] == 'texto') ? 'display:none;' : 'display:block;'; ?>">
                        <label for="media"
                            style="color: #94a3b8; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px; display: block;">Seleccionar
                            Archivo (<?php echo $edit_item ? 'Opcional' : 'Requerido'; ?>)</label>
                        <input type="file" name="media" id="media" class="form-control"
                            style="background: #020617; border-color: #334155; color: white;">
                        <?php if ($edit_item && !empty($edit_item['imagen'])): ?>
                            <small style="color: #60a5fa; font-size: 0.75rem;">Actual:
                                <?php echo basename($edit_item['imagen']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="upload_content" class="btn btn-primary"
                        style="flex: 2; font-weight: 800; padding: 14px; background: #2563eb; border: none; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);">
                        <?php echo $edit_item ? '💾 Guardar Cambios' : '🚀 Publicar Ahora'; ?>
                    </button>
                    <?php if ($edit_item): ?>
                        <a href="admin_content.php" class="btn btn-primary"
                            style="flex: 1; padding: 14px; background: #334155; border: none;">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <script>
            function toggleFileUpload(val) {
                const group = document.getElementById('media_group');
                const fileInput = document.getElementById('media');
                if (val === 'texto') {
                    group.style.display = 'none';
                    fileInput.required = false;
                } else {
                    group.style.display = 'block';
                    fileInput.required = <?php echo $edit_item ? 'false' : 'false'; ?>;
                }
            }
        </script>

        <h3 style="margin-bottom: 24px; color: #3b82f6; font-weight: 800;">Biblioteca de Contenido</h3>
        <div class="content-preview-grid"
            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php while ($c = mysqli_fetch_assoc($contenidos)): ?>
                <div class="content-card"
                    style="background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; transition: 0.3s;"
                    onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#1e293b'">
                    <?php if ($c['categoria'] == 'imagen'): ?>
                        <div style="height: 180px; overflow: hidden;">
                            <img src="<?php echo $c['imagen']; ?>" class="content-media"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php elseif ($c['categoria'] == 'video'): ?>
                        <div style="height: 180px; background: #020617; overflow: hidden; position: relative;">
                            <video muted preload="metadata" style="width: 100%; height: 100%; object-fit: cover;">
                                <source src="<?php echo $c['imagen']; ?>" type="video/mp4">
                            </video>
                            <div
                                style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem;">
                                ▶ VIDEO</div>
                        </div>
                    <?php else: ?>
                        <div
                            style="height: 180px; display:flex; align-items:center; justify-content:center; background:#1e293b; color:#94a3b8; font-weight:800; text-align: center; padding: 20px;">
                            <span>📝 TEXTO SOLO</span>
                        </div>
                    <?php endif; ?>

                    <div class="content-info"
                        style="padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div
                                style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                <h4
                                    style="margin: 0; font-size: 1.1rem; color: white; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 80%;">
                                    <?php echo htmlspecialchars($c['titulo']); ?>
                                </h4>
                                <span
                                    style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; padding: 2px 8px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; border: 1px solid rgba(59, 130, 246, 0.3);">#<?php echo $c['orden']; ?></span>
                            </div>
                            <p
                                style="color: #94a3b8; font-size: 0.85rem; line-height: 1.4; margin-bottom: 20px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($c['descripcion']); ?>
                            </p>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: auto;">
                            <a href="admin_content.php?edit=<?php echo $c['id']; ?>"
                                style="flex: 1; text-align: center; background: #1e293b; color: white; border: 1px solid #334155; padding: 8px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: 0.3s;"
                                onmouseover="this.style.background='#334155'"
                                onmouseout="this.style.background='#1e293b'">Editar</a>
                            <a href="admin_content.php?delete=<?php echo $c['id']; ?>"
                                style="flex: 1; text-align: center; background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); padding: 8px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: 0.3s;"
                                onmouseover="this.style.background='#ef4444'; this.style.color='#fff'"
                                onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#f87171'"
                                onclick="return confirm('¿Borrar permanentemente?')">Eliminar</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div style="margin-top: 48px; text-align: center;">
            <a href="admin_panel.php"
                style="color: #94a3b8; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s;"
                onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">
                &larr; Volver al Panel de Administración
            </a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>