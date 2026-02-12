<?php
// includes/db_servidor.php
// Archivo de conexión optimizado para PRODUCCIÓN

// Configuración de visualización de errores
// EN PRODUCCIÓN: Se recomienda poner 'display_errors' en 0 y revisar los logs.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Activar reporte estricto de MySQLi para poder usar Try-Catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 1. Inicializar objeto MySQLi
    $mysqli = mysqli_init();
    if (!$mysqli) {
        throw new Exception("Error inicializando MySQLi");
    }

    // 2. Configurar opciones de robustez antes de conectar
    // Timeout de 10 segundos para no colgar el proceso si la DB no responde
    $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

    // 3. Credenciales de Base de Datos (XAMPP)
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'socialmillions';

    // 4. Intentar Conexión
    $connected = @mysqli_real_connect(
        $mysqli,
        $db_host,
        $db_user,
        $db_pass,
        $db_name
    );

    if (!$connected) {
        throw new Exception(mysqli_connect_error());
    }

    // 5. Configurar charset explícitamente
    if (!$mysqli->set_charset("utf8mb4")) {
        throw new Exception("Error configurando charset: " . $mysqli->error);
    }

    // 6. Asignar variable estándar del proyecto
    $conn = $mysqli;

} catch (mysqli_sql_exception $e) {
    // Captura errores específicos de SQL
    error_log("Error SQL Crítico: " . $e->getMessage());
    die("<h1>Error del Sistema</h1><p>No se pudo conectar a la base de datos. El incidente ha sido registrado.</p>");
} catch (Exception $e) {
    // Captura otros errores generales
    error_log("Error General DB: " . $e->getMessage());
    die("<h1>Error del Sistema</h1><p>Ocurrió un error inesperado en la conexión.</p>");
}

// 7. Gestión de Sesión Robusta
if (session_status() === PHP_SESSION_NONE) {
    // Configuraciones recomendadas de sesión para seguridad
    // ini_set('session.cookie_httponly', 1); // Evita acceso JS a cookies
    // ini_set('session.use_strict_mode', 1); // Evita fijación de sesión
    session_start();
}
?>