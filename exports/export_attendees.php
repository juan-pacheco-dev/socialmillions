<?php
session_start();
include '../includes/db.php';

// Verificar si el usuario es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['event_id']) || !isset($_GET['date'])) {
    die("Faltan parámetros.");
}

$evento_id = (int)$_GET['event_id'];
$fecha = $_GET['date']; // string format Y-m-d

// Obtener info del evento
$ev_query = mysqli_query($conn, "SELECT titulo FROM eventos WHERE id = $evento_id");
$evento = mysqli_fetch_assoc($ev_query);
$titulo_evento = $evento ? $evento['titulo'] : 'Evento';

// Configurar nombre de archivo
$filename = "Asistencia_" . preg_replace('/[^a-zA-Z0-9]/', '_', $titulo_evento) . "_" . $fecha . ".xls";

// Configurar cabeceras Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Obtener inscritos
$query = "
    SELECT u.nombre, u.email, u.bigo_id, u.celular, i.fecha_inscripcion 
    FROM inscripciones i 
    JOIN usuarios u ON i.usuario_id = u.id 
    WHERE i.evento_id = $evento_id AND i.fecha_asistencia = '$fecha'
    ORDER BY u.nombre ASC
";
$result = mysqli_query($conn, $query);

?>
<meta charset="utf-8">
<table border="1">
    <thead>
        <tr>
            <th colspan="5" style="background-color: #0f172a; color: white; font-size: 14px; text-align: center;">
                REPORTE DE ASISTENCIA - <?php echo htmlspecialchars($titulo_evento); ?> (<?php echo $fecha; ?>)
            </th>
        </tr>
        <tr style="background-color: #2563eb; color: white;">
            <th>Nombre Streamer</th>
            <th>Bigo ID</th>
            <th>Celular</th>
            <th>Email</th>
            <th>Fecha Inscripción</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                    <td style="font-weight: bold;"><?php echo htmlspecialchars($row['bigo_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['celular']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['fecha_inscripcion']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align: center;">No hay inscritos para esta fecha.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
