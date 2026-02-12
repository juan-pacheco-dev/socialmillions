<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$success = "";
$error = "";

// Proceso la carga de documentos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_doc'])) {
    $titulo = mysqli_real_escape_string($conn, $_POST['titulo']);
    $descripcion = mysqli_real_escape_string($conn, $_POST['descripcion']);

    $file = $_FILES['documento'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt'];

    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            $newFileName = "doc_" . time() . "_" . uniqid() . "." . $fileExt;
            $fileDestination = 'uploads/documents/' . $newFileName;

            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                $query = "INSERT INTO documentos (titulo, descripcion, ruta, tipo_archivo) VALUES ('$titulo', '$descripcion', '$fileDestination', '$fileExt')";
                if (mysqli_query($conn, $query)) {
                    $success = "Documento subido correctamente.";
                } else {
                    $error = "Error al guardar en la base de datos.";
                }
            } else {
                $error = "Error al mover el archivo al servidor.";
            }
        } else {
            $error = "Error en la carga del archivo.";
        }
    } else {
        $error = "Formato de archivo no permitido.";
    }
}

// Elimino el documento
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $res = mysqli_query($conn, "SELECT ruta FROM documentos WHERE id = $id");
    if ($doc = mysqli_fetch_assoc($res)) {
        if (file_exists($doc['ruta'])) {
            unlink($doc['ruta']);
        }
        mysqli_query($conn, "DELETE FROM documentos WHERE id = $id");
        $success = "Documento eliminado.";
    }
}

$documentos = mysqli_query($conn, "SELECT * FROM documentos ORDER BY created_at DESC");

include '../includes/header.php';
?>

<link rel="stylesheet" href="../css/admin_content.css">
<link rel="stylesheet" href="../css/admin_users.css">

<main class="users-table-container" style="padding-top: 120px;">
    <div class="admin-content-container" style="max-width: 900px; margin: 0 auto; padding: 0 15px;">
        <div
            style="background: #0f172a; border: 1px solid #1e293b; border-radius: 16px; padding: 40px; margin-bottom: 48px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <h2 class="table-title"
                style="margin-bottom: 32px; font-size: 1.8rem; border-left: 4px solid #3b82f6; padding-left: 15px; color: white !important;">
                Subir Documento</h2>

            <?php if ($success): ?>
                <div class="alert alert-success"
                    style="background: rgba(22, 163, 74, 0.1); border: 1px solid #16a34a; color: #4ade80; padding: 15px; border-radius: 8px; margin-bottom: 24px;">
                    ✅ <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"
                    style="background: rgba(220, 38, 38, 0.1); border: 1px solid #dc2626; color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 24px;">
                    ❌ <?php echo $error; ?></div>
            <?php endif; ?>

            <form action="admin_documents.php" method="POST" enctype="multipart/form-data" class="form-grid">
                <div class="form-group full-width" style="margin-bottom: 20px;">
                    <label style="color: #94a3b8; font-weight: 600; font-size: 0.9rem;">Título del Documento</label>
                    <input type="text" name="titulo" class="form-control"
                        style="background: #020617; border-color: #334155; color: white;"
                        placeholder="Ej: Manual de Streamer" required>
                </div>
                <div class="form-group full-width" style="margin-bottom: 20px;">
                    <label style="color: #94a3b8; font-weight: 600; font-size: 0.9rem;">Descripción Corta</label>
                    <textarea name="descripcion" class="form-control" rows="2"
                        style="background: #020617; border-color: #334155; color: white;"
                        placeholder="De qué trata este archivo..."></textarea>
                </div>
                <div class="form-group full-width" style="margin-bottom: 30px;">
                    <label style="color: #94a3b8; font-weight: 600; font-size: 0.9rem;">Seleccionar Archivo (PDF, Word,
                        PPT...)</label>
                    <input type="file" name="documento" class="form-control"
                        style="background: #020617; border-color: #334155; color: white;" required>
                </div>
                <div class="form-group full-width" style="margin-top: 10px;">
                    <button type="submit" name="upload_doc" class="btn btn-primary"
                        style="width: 100%; font-weight: 800; font-size: 1.1rem; padding: 16px; background: #2563eb; border: none; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);">🚀
                        Subir Documento</button>
                </div>
            </form>
        </div>

        <h3 style="margin-bottom: 24px; color: #3b82f6; font-weight: 800;">Documentos Disponibles</h3>
        <div class="scroll-hint"><span>↔️ Desliza para ver más</span></div>
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th style="min-width: 300px;">Documento</th>
                        <th style="min-width: 100px;">Tipo</th>
                        <th style="min-width: 150px;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = mysqli_fetch_assoc($documentos)): ?>
                        <tr>
                            <td>
                                <strong style="color: #ffffff;"><?php echo htmlspecialchars($d['titulo']); ?></strong>
                                <p style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">
                                    <?php echo htmlspecialchars($d['descripcion']); ?>
                                </p>
                            </td>
                            <td>
                                <span
                                    style="background: #1e293b; color: #3b82f6; padding: 4px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">
                                    <?php echo $d['tipo_archivo']; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="<?php echo htmlspecialchars($d['ruta']); ?>" target="_blank" class="action-btn"
                                        style="background: #16a34a; padding: 8px 12px; color: white; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 700;">👁️
                                        Ver</a>
                                    <a href="admin_documents.php?delete=<?php echo $d['id']; ?>" class="action-btn"
                                        style="background: #dc2626; padding: 8px 12px; color: white; border-radius: 6px; text-decoration: none; font-size: 0.85rem;"
                                        onclick="return confirm('¿Eliminar este documento?')">🗑️</a>
                                </div>
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

<?php include '../includes/footer.php'; ?>