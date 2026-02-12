<?php
session_start();
include '../includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cliente_nombre = mysqli_real_escape_string($conn, $_POST['cliente_nombre']);
    $cliente_email = mysqli_real_escape_string($conn, $_POST['cliente_email']);
    $codigo = mysqli_real_escape_string($conn, $_POST['codigo']);
    
    // Verifico código y vigencia (5 minutos)
    $now = date('Y-m-d H:i:s');
    $query = "SELECT * FROM codigos_acceso WHERE codigo = '$codigo' AND expires_at > '$now' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $usuario_id = $data['usuario_id']; // ID de la modelo dueña del código
        
        // Registro el acceso
        $fecha = date('Y-m-d H:i:s');
        mysqli_query($conn, "INSERT INTO accesos_log (modelo_id, cliente_nombre, cliente_email, fecha_acceso) VALUES ($usuario_id, '$cliente_nombre', '$cliente_email', '$fecha')");
        
        // Creo Sesión de Visor Seguro
        $_SESSION['viewer_access'] = true;
        $_SESSION['viewer_model_id'] = $usuario_id;
        $_SESSION['viewer_name'] = $cliente_nombre;
        $_SESSION['viewer_code'] = $codigo;
        
        header("Location: secret_gallery.php");
        exit();
    } else {
        echo "<script>alert('Código inválido o expirado.'); window.location.href='adult_index.php';</script>";
        exit();
    }
} else {
    header("Location: ../adult_index.php");
    exit();
}
?>
