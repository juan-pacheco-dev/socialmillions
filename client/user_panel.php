<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está logueado y si tiene un rol permitido
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'user' && $_SESSION['rol'] !== 'model')) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/db.php'; // Requerido para la conexión $conn
include '../includes/header.php';

// Aseguro que los roles de sesión sean cadenas y sin espacios
$_SESSION['rol'] = trim($_SESSION['rol'] ?? '');

// Actualizo datos del usuario (Acceso a Eventos)
if (isset($_SESSION['usuario_id'])) {
    $uid = $_SESSION['usuario_id'];
    $u_q = mysqli_query($conn, "SELECT event_access, agencia_id FROM usuarios WHERE id = $uid");
    if ($u_q) {
        $u_data = mysqli_fetch_assoc($u_q);
        $event_access = $u_data['event_access'] ?? 0; // Por defecto 0 (Bloqueado)
        $_SESSION['event_access'] = $event_access; // Actualizo la sesión

        // Obtengo el logo de la agencia
        $agency_logo = null;
        if (!empty($u_data['agencia_id'])) {
            $aid = (int) $u_data['agencia_id'];
            $q_ag = mysqli_query($conn, "SELECT logo_path FROM agencias WHERE id = $aid");
            if ($r_ag = mysqli_fetch_assoc($q_ag)) {
                $agency_logo = $r_ag['logo_path'];
            }
        }
    }
}
?>

<!-- Vinculo el CSS moderno con limpieza de caché -->
<link rel="stylesheet" href="../css/index.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../css/user_panel.css?v=<?php echo time(); ?>">

<style>
    .card.locked {
        opacity: 0.6;
        cursor: not-allowed;
        position: relative;
        border-color: #333;
    }

    .card.locked:hover {
        transform: none;
        box-shadow: none;
    }

    .lock-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        z-index: 10;
        border-radius: 12px;
    }
</style>

<main class="panel-hero">
    <div class="container reveal active">
        <div class="panel-header-content">
            <?php if (isset($agency_logo) && $agency_logo): ?>
                <img src="../<?php echo htmlspecialchars($agency_logo); ?>" alt="Agency Logo"
                    style="max-height: 100px; margin-bottom: 10px; display: block;">
            <?php endif; ?>
            <span class="hero-badge">Portal de Streamers</span>
            <h1 class="welcome-msg">Hola, <span
                    class="gradient-text"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span></h1>
            <p class="panel-subtitle">Gestiona tu carrera, accede a recursos exclusivos y mantente al día con Social
                Millions.</p>
        </div>

        <div class="cards-grid">

            <?php if ($event_access == 1): ?>
                <a href="user_events.php" class="card">
                    <span class="card-icon">⚡</span>
                    <h3>Ver Eventos</h3>
                    <p>Explora y apúntate a las próximas actividades de la agencia.</p>
                    <span class="card-action-text">Entrar →</span>
                </a>
            <?php else: ?>
                <div class="card locked"
                    onclick="alert('🚫 Acceso Restringido. Espera a que un administrador active tu acceso a Eventos.')">
                    <div class="lock-overlay">
                        <span style="font-size: 3rem;">🔒</span>
                        <span style="color: #fff; font-weight: bold; margin-top: 10px;">Bloqueado</span>
                    </div>
                    <span class="card-icon">⚡</span>
                    <h3>Ver Eventos</h3>
                    <p>Acceso restringido a nuevos usuarios.</p>
                </div>
            <?php endif; ?>

            <?php if (($_SESSION['plataforma'] ?? '') === 'Bigo'): ?>
                <a href="user_impulsos.php" class="card">
                    <span class="card-icon">🚀</span>
                    <h3>Impulsos</h3>
                    <p>Regístrate para potenciar tu alcance. (Reserva con 2 días de anticipación).</p>
                    <span class="card-action-text">Registrar →</span>
                </a>
            <?php endif; ?>

            <a href="user_content.php" class="card">
                <span class="card-icon">💎</span>
                <h3>Contenido VIP</h3>
                <p>Accede a material exclusivo, guías de alta conversión y recursos de crecimiento premium.</p>
                <span class="card-action-text">Ver Material →</span>
            </a>
            <a href="user_documents.php" class="card">
                <span class="card-icon">🌎</span>
                <h3>Documentos</h3>
                <p>Descarga guías, contratos y material de apoyo oficial compartido por la gerencia.</p>
                <span class="card-action-text">Abrir Archivos →</span>
            </a>
        </div>



        <div class="system-status-container reveal active" style="margin-top: 60px;">
            <div class="status-card-neon">
                <div class="status-header">
                    <span class="status-dot"></span>
                    <h3>Estado del Sistema / Cuenta</h3>
                </div>
                <div class="status-body">
                    <div class="status-item">
                        <span class="label">BIGO ID:</span>
                        <span class="value"><?php echo htmlspecialchars($_SESSION['bigo_id'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="status-item">
                        <span class="label">CELULAR:</span>
                        <span class="value"><?php echo htmlspecialchars($_SESSION['celular'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="status-item">
                        <span class="label">ACCESO:</span>
                        <span class="value glow-green">VERIFICADO / TOTAL</span>
                    </div>
                </div>
                <div class="status-footer">
                    <p>Potenciado por la tecnología de Social Millions Management.</p>
                </div>
            </div>
        </div>

        <div class="panel-actions" style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            <a href="profile_security.php" class="btn btn-outline" style="border-color: #3b82f6; color: #3b82f6;">🔐
                Seguridad / Contraseña</a>
            <a href="../auth/logout.php" class="btn btn-outline"
                style="border-color: var(--accent-red); color: var(--accent-red);">Cerrar Sesión</a>
        </div>
    </div>
</main>

<!-- Interacción para el efecto Reveal -->
<script>
    const observerOptions = { threshold: 0.15 };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.reveal').forEach(el => {
        observer.observe(el);
    });
</script>

<?php include '../includes/footer.php'; ?>