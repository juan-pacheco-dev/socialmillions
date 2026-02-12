<?php
// agency/loader.php
// Este archivo es incluido por el index.php dentro de agencies/{slug}/

// 1. Configuración Básica
session_start();
// Ajustar ruta relativa a la raíz desde agencies/{slug}/
$root_path = '../../';

include $root_path . 'includes/db.php';

// 2. Identificar Agencia
if (!isset($agency_slug)) {
    die("Configuración de agencia inválida.");
}

$slug_safe = mysqli_real_escape_string($conn, $agency_slug);
$query = "SELECT * FROM agencias WHERE slug = '$slug_safe' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    echo "<h1>Agencia no encontrada</h1>";
    exit();
}

$AGENCY_DATA = mysqli_fetch_assoc($result);
$AGENCY_ID = $AGENCY_DATA['id'];
$AGENCY_NAME = htmlspecialchars($AGENCY_DATA['nombre']);

// 3. Verificar Autenticación
// Si no está logueado, redirigir al login principal (o podríamos tener uno específico)
if (!isset($_SESSION['usuario_id'])) {
    // Guardar intención de redirección (opcional)
    header("Location: " . $root_path . "auth/login.php?redirect_agency=" . $slug_safe);
    exit();
}

// 4. Verificar Permisos
// Un usuario puede entrar SI:
// a) Es Super Admin ('admin')
// b) Es Admin de Agencia ('agency_admin') Y su agencia_id coincide
// c) Es Usuario ('user') Y su agencia_id coincide (Opcional: Si queremos que los usuarios normales entren aquí)

$user_role = $_SESSION['rol'];
$user_agency = $_SESSION['agencia_id'] ?? null;

$authorized = false;

if ($user_role === 'admin') {
    // Super Admin tiene acceso total
    $authorized = true;
} elseif ($user_agency == $AGENCY_ID) {
    // Usuario pertenece a esta agencia
    if ($user_role === 'agency_admin') {
        $authorized = true;
    } else {
        // Es un usuario normal, ¿debería ver el panel de la agencia o solo su panel de modelo?
        // Por ahora, redirigimos a su panel de modelo normal, pero le damos acceso si quieres un dashboard de usuario de agencia.
        // Asumo que "User Panel" es genérico, así que redirigimos a users/ o model/
        header("Location: " . $root_path . "client/user_panel.php"); // Updated from model/model_panel.php
        exit();
    }
}

if (!$authorized) {
    echo "<h1>Acceso Denegado</h1><p>No tienes permiso para acceder a la agencia '$AGENCY_NAME'.</p>";
    echo "<a href='{$root_path}auth/logout.php'>Cerrar Sesión</a>";
    exit();
}

// 5. Cargar Dashboard de Agencia
// Definimos variables globales para que el dashboard las use
$CURRENT_AGENCY = $AGENCY_DATA;
include __DIR__ . '/agency_dashboard.php';
?>