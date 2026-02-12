<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'user' && $_SESSION['rol'] !== 'model')) {
    header("Location: ../auth/login.php");
    exit();
}

// Obtengo el ID de agencia del usuario
$uid = $_SESSION['usuario_id'];
$u_res = mysqli_query($conn, "SELECT agencia_id FROM usuarios WHERE id = $uid");
$u_data = mysqli_fetch_assoc($u_res);
$my_agency_id = (int) ($u_data['agencia_id'] ?? 0);

$documentos = mysqli_query($conn, "SELECT * FROM documentos WHERE agencia_id = $my_agency_id ORDER BY created_at DESC");

include '../includes/header.php';
?>

<link rel="stylesheet" href="../css/user_panel.css">

<style>
    .docs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
        margin-top: 32px;
    }

    .doc-card {
        background: #0f172a;
        border: 1px solid #1e293b;
        border-radius: 16px;
        padding: 24px;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .doc-card:hover {
        transform: translateY(-5px);
        border-color: #3b82f6;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }

    .doc-icon {
        width: 48px;
        height: 48px;
        background: #1e293b;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .doc-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.1rem;
        word-break: break-word;
    }

    .doc-desc {
        color: #94a3b8;
        font-size: 0.9rem;
        line-height: 1.5;
        flex-grow: 1;
    }

    .btn-download {
        background: #2563eb;
        color: white;
        text-decoration: none;
        padding: 12px;
        border-radius: 10px;
        text-align: center;
        font-weight: 800;
        font-size: 0.9rem;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-download:hover {
        background: #1d4ed8;
    }

    .file-type-badge {
        font-size: 0.7rem;
        text-transform: uppercase;
        background: #020617;
        padding: 2px 8px;
        border-radius: 4px;
        color: #60a5fa;
        font-weight: 900;
    }
</style>

<main class="container">
    <div class="panel-container">
        <h2 class="welcome-msg">Documentos de la Agencia</h2>
        <p>Descarga recursos, guías y material de apoyo aquí.</p>

        <div class="docs-grid">
            <?php if (mysqli_num_rows($documentos) > 0): ?>
                <?php while ($d = mysqli_fetch_assoc($documentos)):
                    $icon = "📄";
                    $ext = $d['tipo_archivo'];
                    if ($ext == 'pdf')
                        $icon = "📕";
                    if ($ext == 'doc' || $ext == 'docx')
                        $icon = "📘";
                    if ($ext == 'xls' || $ext == 'xlsx')
                        $icon = "📗";
                    if ($ext == 'ppt' || $ext == 'pptx')
                        $icon = "📙";
                    if ($ext == 'txt')
                        $icon = "🗒️";
                    ?>
                    <div class="doc-card">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div class="doc-icon"><?php echo $icon; ?></div>
                            <span class="file-type-badge"><?php echo $ext; ?></span>
                        </div>
                        <div>
                            <div class="doc-title"><?php echo htmlspecialchars($d['titulo']); ?></div>
                            <div class="doc-desc"><?php echo htmlspecialchars($d['descripcion']); ?></div>
                        </div>
                        <a href="<?php echo htmlspecialchars($d['ruta']); ?>" download class="btn-download">
                            <span>📥 Descargar</span>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #64748b; margin-top: 40px;">No hay documentos compartidos por ahora.</p>
            <?php endif; ?>
        </div>

        <div style="margin-top: 56px;">
            <a href="user_panel.php" class="btn btn-primary" style="padding: 14px 32px;">Volver al Panel</a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>