<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está logueado y si es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/header.php';
?>
<style>
    .admin-wrapper {
        padding: 120px 20px 60px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .admin-header-box {
        margin-bottom: 40px;
        text-align: center;
    }

    .admin-badge-luxe {
        display: inline-block;
        padding: 6px 16px;
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 15px;
    }

    .panel-grid-luxe {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
    }

    .panel-card-luxe {
        background: #0f172a;
        border: 1px solid #1e293b;
        padding: 40px 25px;
        border-radius: 16px;
        text-align: center;
        text-decoration: none;
        transition: 0.3s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 220px;
        border-bottom: 4px solid transparent;
    }

    .panel-card-luxe:hover {
        transform: translateY(-8px);
        border-color: #3b82f6;
        background: #1e293b;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
    }

    .panel-icon-luxe {
        font-size: 3.5rem;
        margin-bottom: 24px;
    }

    .panel-card-luxe h3 {
        color: white;
        font-size: 1.4rem;
        margin-bottom: 12px;
        font-weight: 800;
    }

    .panel-card-luxe p {
        color: #94a3b8;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .logout-btn-luxe {
        display: inline-block;
        margin-top: 50px;
        color: #f87171;
        text-decoration: none;
        font-weight: 700;
        padding: 12px 30px;
        border: 1px solid rgba(248, 113, 113, 0.3);
        border-radius: 8px;
        transition: 0.3s;
        background: rgba(248, 113, 113, 0.05);
    }

    .logout-btn-luxe:hover {
        background: #f87171;
        color: white;
    }

    @media (max-width: 768px) {
        .admin-wrapper {
            padding-top: 100px;
        }

        .panel-grid-luxe {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .panel-card-luxe {
            padding: 35px 20px;
            min-height: auto;
        }

        .panel-icon-luxe {
            font-size: 3rem;
            margin-bottom: 15px;
        }
    }
</style>

<main class="admin-wrapper">
    <div class="admin-header-box">
        <span class="admin-badge-luxe">ADMINISTRADOR</span>
        <h2 style="font-size: 2.2rem; color: white; margin-bottom: 10px;">Panel de Control</h2>
        <p style="color: #94a3b8;">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>. Gestiona tu agencia
            con herramientas integrales.</p>
    </div>

    <div class="panel-grid-luxe">
        <a href="admin_users.php" class="panel-card-luxe">
            <div class="panel-icon-luxe">👥</div>
            <h3>Usuarios</h3>
            <p>Gestiona streamers, postulaciones y moderación del equipo.</p>
        </a>
        <a href="admin_events.php" class="panel-card-luxe">
            <div class="panel-icon-luxe">📅</div>
            <h3>Eventos</h3>
            <p>Crea actividades y supervisa las listas de participación.</p>
        </a>
        <a href="admin_content.php" class="panel-card-luxe">
            <div class="panel-icon-luxe">🎥</div>
            <h3>Contenido</h3>
            <p>Sube y organiza material premium para tus streamers.</p>
        </a>

        <a href="admin_documents.php" class="panel-card-luxe">
            <div class="panel-icon-luxe">📂</div>
            <h3>Documentos</h3>
            <p>Recursos compartidos, contratos y manuales oficiales.</p>
        </a>
        <a href="admin_impulsos.php" class="panel-card-luxe" style="border-color: rgba(236, 72, 153, 0.3);">
            <div class="panel-icon-luxe">🚀</div>
            <h3>Impulsos</h3>
            <p>Ver registros de impulsos y exportar reporte.</p>
        </a>
        <a href="admin_create_user.php" class="panel-card-luxe" style="border-color: rgba(245, 158, 11, 0.3);">
            <div class="panel-icon-luxe">➕</div>
            <h3>Crear Usuario</h3>
            <p>Registro manual rápido con credenciales auto-generadas.</p>
        </a>
        <a href="admin_bigos.php" class="panel-card-luxe" style="border-color: rgba(59, 130, 246, 0.3);">
            <div class="panel-icon-luxe">🆔</div>
            <h3>Bigo IDs</h3>
            <p>Sincronización y auditoría de identificadores de pago.</p>
        </a>
        <a href="admin_agencies.php" class="panel-card-luxe" style="border-color: rgba(236, 72, 153, 0.3);">
            <div class="panel-icon-luxe">🏢</div>
            <h3>Agencias</h3>
            <p>Crear y gestionar sub-agencias (Paneles independientes).</p>
        </a>
        <a href="admin_passwords.php" class="panel-card-luxe" style="border-color: rgba(16, 185, 129, 0.3);">
            <div class="panel-icon-luxe">🔑</div>
            <h3>Contraseñas</h3>
            <p>Recuperación y cambio de claves para todo el equipo.</p>
        </a>
    </div>

    <div style="text-align: center;">
        <a href="../auth/logout.php" class="logout-btn-luxe">Cerrar Sesión Segura</a>
    </div>
</main>

<?php include '../includes/footer.php'; ?>