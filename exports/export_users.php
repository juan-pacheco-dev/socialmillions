<?php
// export_users.php
session_start();
include '../includes/db.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Incluir el escritor XLSX
include '../includes/xlsx_writer.php';

// Obtener filtro
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Construir Query
$where_clause = "";
if ($filter === 'normal') {
    $where_clause = "WHERE bigo_id IS NOT NULL AND bigo_id != ''";
    $filename = 'streamers_socialmillions_' . date('Y-m-d') . '.xlsx';
} elseif ($filter === 'models') {
    $where_clause = "WHERE (bigo_id IS NULL OR bigo_id = '')";
    $filename = 'modelos_socialmillions_' . date('Y-m-d') . '.xlsx';
} else {
    $filename = 'usuarios_socialmillions_' . date('Y-m-d') . '.xlsx';
}

$query = "SELECT nombre, email, bigo_id, celular, estado, rol, created_at FROM usuarios $where_clause ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$data_rows = [];
while ($row = mysqli_fetch_row($result)) {
    $data_rows[] = $row;
}

// Encabezados
$headers = ['Nombre', 'Email', 'Bigo ID', 'Celular', 'Estado', 'Rol', 'Fecha Registro'];

// Generar y descargar archivo
XlsxWriter::output($filename, $data_rows, $headers);
exit();
