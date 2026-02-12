<?php
// export_nuevos.php - Exportar Streamers Nuevos (Postulaciones)
session_start();
include '../includes/db.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Incluir el escritor XLSX
include '../includes/xlsx_writer.php';

// Obtener los datos de streamers nuevos (es_nuevo = 1)
$query = "SELECT nombre, email, bigo_id, celular, tipo_contenido, disponibilidad, experiencia, motivo, created_at 
          FROM usuarios 
          WHERE es_nuevo = 1 AND rol = 'user'
          ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$data_rows = [];
while ($row = mysqli_fetch_row($result)) {
    $data_rows[] = $row;
}

// Encabezados
$headers = ['Nombre', 'Email', 'Bigo ID', 'Celular', 'Tipo Contenido', 'Disponibilidad', 'Experiencia', 'Motivación', 'Fecha Registro'];

// Generar y descargar archivo
XlsxWriter::output('streamers_nuevos_' . date('Y-m-d') . '.xlsx', $data_rows, $headers);
exit();
