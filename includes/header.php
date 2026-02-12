<?php
// includes/header.php
header('Content-Type: text/html; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lógica para manejar carpetas anidadas y rutas relativas
$current_script = $_SERVER['PHP_SELF'];
$rel = (preg_match('/\/(admin|model|client|auth|exports)\//', $current_script)) ? '../' : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOCIAL-MILLIONS - Potencia tu Carrera</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Favicons del sitio -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $rel; ?>img/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $rel; ?>img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $rel; ?>img/favicon-16x16.png">
    <link rel="shortcut icon" href="<?php echo $rel; ?>img/favicon.ico">

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo $rel; ?>css/global.css?v=1.7">
    <link rel="stylesheet" href="<?php echo $rel; ?>css/header.css?v=1.8">
    <link rel="stylesheet" href="<?php echo $rel; ?>css/footer.css?v=1.7">
    <link rel="manifest" href="<?php echo $rel; ?>manifest.json">
    <meta name="theme-color" content="#02040a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Social Millions">

    <?php if (strpos($current_script, '/admin/') !== false): ?>
        <link rel="stylesheet" href="<?php echo $rel; ?>css/admin_global.css?v=<?php echo time(); ?>">
    <?php endif; ?>
</head>

<body>

    <header class="main-header">
        <div class="container header-container">
            <!-- Logo -->
            <div class="logo">
                <a href="<?php echo $rel; ?>index.php"
                    style="display: flex; align-items: center; gap: 12px; text-decoration: none;">
                    <img src="<?php echo $rel; ?>img/logo.jpeg" alt="Logo SOCIAL-MILLIONS"
                        style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #3b82f6;">
                    <span class="logo-text">SOCIAL<span class="highlight">MILLIONS</span></span>
                </a>
            </div>

            <!-- Mobile Toggle -->
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Navigation -->
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li><a href="<?php echo $rel; ?>index.php">Inicio</a></li>
                    <?php if (isset($_SESSION['usuario_id'])):
                        $panel_url = $rel . 'client/user_panel.php';
                        if ($_SESSION['rol'] == 'admin') {
                            $panel_url = $rel . 'admin/admin_panel.php';
                        } elseif ($_SESSION['rol'] == 'model') {
                            $panel_url = $rel . 'client/user_panel.php';
                        } else {
                            $panel_url = $rel . 'client/user_panel.php';
                        }
                        ?>
                        <!-- Link removed -->
                        <li><a href="<?php echo $panel_url; ?>" class="nav-btn-outline">Mi Panel</a></li>
                        <li><a href="<?php echo $rel; ?>auth/logout.php" class="btn btn-primary"
                                style="background-color: #ef4444;">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <!-- Link removed -->
                        <li><a href="<?php echo $rel; ?>auth/login.php" class="nav-btn-outline">Iniciar Sesión</a></li>
                        <li><a href="<?php echo $rel; ?>auth/register.php" class="btn btn-primary">Registrarse</a></li>
                    <?php endif; ?>
                    <!-- Botón de instalación PWA (Oculto por defecto) -->
                    <li id="pwa-install-item" style="display: none; margin-top: 10px;">
                        <button id="installAppBtn" class="btn"
                            style="background: linear-gradient(135deg, #10b981, #059669); width: 100%; border: none;">
                            📲 Instalar App
                        </button>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.getElementById('menuToggle');
            const mainNav = document.getElementById('mainNav');

            if (menuToggle && mainNav) {
                menuToggle.addEventListener('click', function () {
                    menuToggle.classList.toggle('active');
                    mainNav.classList.toggle('active');
                });

                // Cierro el menú al hacer clic en un enlace
                const navLinks = mainNav.querySelectorAll('a');
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        menuToggle.classList.remove('active');
                        mainNav.classList.remove('active');
                    });
                });
            }

            // Lógica de instalación PWA
            let deferredPrompt;
            const installItem = document.getElementById('pwa-install-item');
            const installBtn = document.getElementById('installAppBtn');

            window.addEventListener('beforeinstallprompt', (e) => {
                // Prevengo que aparezca la mini-barra en móviles
                e.preventDefault();
                // Guardo el evento para dispararlo después
                deferredPrompt = e;
                // Actualizo la UI para notificar al usuario que puede instalar la PWA
                if (installItem) installItem.style.display = 'block';
                console.log('PWA install prompt captured');
            });

            if (installBtn) {
                installBtn.addEventListener('click', async () => {
                    if (deferredPrompt) {
                        // Muestro el prompt de instalación
                        deferredPrompt.prompt();
                        // Espero la respuesta del usuario
                        const { outcome } = await deferredPrompt.userChoice;
                        console.log(`User response to the install prompt: ${outcome}`);
                        // Limpio el prompt ya que fue usado
                        deferredPrompt = null;
                        // Oculto el botón después de la instalación
                        if (outcome === 'accepted') {
                            installItem.style.display = 'none';
                        }
                    }
                });
            }
        });
    </script>