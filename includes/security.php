<?php
// includes/security.php

// Aseguro que la sesión esté iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Genero un token CSRF y lo guardo si no existe. Retorno el token.
 */
function generar_token_csrf()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifico si el token recibido coincide con el de la sesión.
 * @param string $token El token enviado por el formulario
 * @return bool True si es válido, False si no
 */
function verificar_token_csrf($token)
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>