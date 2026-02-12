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

$query = "SELECT * FROM contenido WHERE agencia_id = $my_agency_id ORDER BY orden ASC, created_at DESC";
$result = mysqli_query($conn, $query);

include '../includes/header.php';

// Obtener datos del usuario para la marca de agua
$wm_text = $_SESSION['nombre'] . " (" . $_SESSION['bigo_id'] . ")";
?>

<link rel="stylesheet" href="../css/admin_content.css">
<link rel="stylesheet" href="../css/user_panel.css">
<link rel="stylesheet" href="../css/protection.css">

<style>
    /* Custom page-specific overrides if needed */
    body {
        background: #000;
        color: #fff;
    }
</style>

<main class="container">
    <div class="panel-container">
        <h2 class="welcome-msg">Contenido Exclusivo</h2>
        <p>Solo para nuestros miembros premium. Protección de contenido activa 🛡️</p>

        <div class="content-preview-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($c = mysqli_fetch_assoc($result)): ?>
                    <div class="content-card">
                        <?php if ($c['categoria'] != 'texto'): ?>
                            <div class="media-container" id="container_<?php echo $c['id']; ?>">

                                <div class="media-wrapper">
                                    <?php if ($c['categoria'] == 'imagen'): ?>
                                        <img src="../admin/<?php echo htmlspecialchars($c['imagen']); ?>" class="content-media"
                                            oncontextmenu="return false;" ondragstart="return false;"
                                            onclick="openLightbox('../admin/<?php echo htmlspecialchars($c['imagen']); ?>', 'image')">
                                    <?php elseif ($c['categoria'] == 'video'): ?>
                                        <video id="video_<?php echo $c['id']; ?>" controls
                                            controlslist="nodownload nofullscreen nopictureinpicture" oncontextmenu="return false;"
                                            class="content-media" style="background: #020617; width: 100%; display: block;">
                                            <source src="../admin/<?php echo htmlspecialchars($c['imagen']); ?>"
                                                type="video/<?php echo pathinfo($c['imagen'], PATHINFO_EXTENSION); ?>">
                                            Tu navegador no soporta el formato de video.
                                        </video>
                                        <button class="fullscreen-btn"
                                            onclick="toggleFullscreen('container_<?php echo $c['id']; ?>')">Pantalla Completa ⛶</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="content-info" style="user-select: text;"> <!-- Dejar que el texto sí se lea -->
                            <h4><?php echo htmlspecialchars($c['titulo']); ?></h4>
                            <p style="color: #cbd5e1; font-size: 0.95rem; line-height: 1.6;">
                                <?php echo htmlspecialchars($c['descripcion']); ?>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No hay contenido disponible por ahora.</p>
            <?php endif; ?>
        </div>

        <div style="margin-top: 40px;">
            <a href="user_panel.php" class="btn btn-primary">Volver al Panel</a>
        </div>
    </div>
</main>

<script src="../js/protection.js"></script>
<script>
    // Inicio marcas de agua para cada contenedor
    document.addEventListener('DOMContentLoaded', function () {
        const wmText = '<?php echo addslashes($wm_text); ?>';
        document.querySelectorAll('.media-container').forEach(container => {
            ProtectionSystem.spawnWatermarks(container.id, wmText, 4);
        });
    });

    // 1. Bloqueo de teclas adicional (opcional, ya lo hace protection.js)

    // 3. Lógica del Lightbox
    function openLightbox(src, type) {
        const lightbox = document.getElementById('mediaLightbox');
        const img = document.getElementById('lightboxImg');
        const video = document.getElementById('lightboxVideo');
        const wmText = '<?php echo addslashes($wm_text); ?>';

        if (type === 'image') {
            img.src = src;
            img.style.display = 'block';
            video.style.display = 'none';
        } else {
            video.src = src;
            video.style.display = 'block';
            img.style.display = 'none';
        }

        lightbox.style.display = 'flex';
        ProtectionSystem.spawnWatermarks('lightboxContainer', wmText, 5);
    }

    function closeLightbox() {
        const lightbox = document.getElementById('mediaLightbox');
        const video = document.getElementById('lightboxVideo');
        video.pause();
        video.src = "";
        lightbox.style.display = 'none';
    }

    // 4. Pantalla Completa con Corrección de Marca de Agua
    function toggleFullscreen(containerId) {
        const container = document.getElementById(containerId);
        if (!document.fullscreenElement) {
            if (container.requestFullscreen) {
                container.requestFullscreen();
            } else if (container.webkitRequestFullscreen) {
                container.webkitRequestFullscreen();
            } else if (container.msRequestFullscreen) {
                container.msRequestFullscreen();
            }
        } else {
            document.exitFullscreen();
        }
    }
</script>

<div id="mediaLightbox">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <div class="lightbox-content-wrapper" id="lightboxContainer">

        <img id="lightboxImg" class="lightbox-media" src="" style="display:none;">
        <video id="lightboxVideo" class="lightbox-media" controls controlslist="nodownload"
            style="display:none;"></video>
    </div>
</div>

<?php include '../includes/footer.php'; ?>