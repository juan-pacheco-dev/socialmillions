<?php
// Inicio sesión para asegurarme de poder destruirla correctamente
session_start();

// Limpio todas las variables de sesión
$_SESSION = array();

// Si deseo destruir la sesión completamente, borro también la cookie de sesión.
// Si quiero borrar la sesión completamente, también elimino la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruyo la sesión.
session_destroy();

// Redirijo al usuario al inicio
header("Location: ../index.php");
exit();
?>
