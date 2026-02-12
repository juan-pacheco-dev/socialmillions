<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Lógica para Eliminar
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM impulsos WHERE id = $id");
    header("Location: admin_impulsos.php");
    exit();
}

// Lógica para Exportar (Formato CSV para Excel - separado por punto y coma para región español)
if (isset($_GET['export']) && $_GET['export'] == 'true') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Reporte_Impulsos_' . date('Ymd') . '.csv"');
    header('Cache-Control: max-age=0');
    
    // BOM UTF-8 para codificación correcta en Excel
    echo "\xEF\xBB\xBF";
    
    // Fila de encabezado (separada por punto y coma para Excel en español)
    echo "Usuario;Bigo ID;Fecha;Hora;Registro;Agendado\n";

    $export_result = mysqli_query($conn, "SELECT i.*, u.nombre, u.bigo_id FROM impulsos i JOIN usuarios u ON i.user_id = u.id ORDER BY i.fecha DESC");

    if ($export_result) {
        while ($row = mysqli_fetch_assoc($export_result)) {
            $formatted_time = date('h:i A', strtotime($row['hora']));
            $agendado_status = isset($row['agendado']) && $row['agendado'] ? 'Sí' : 'No';
            
            // Limpio datos para el CSV
            $nombre = str_replace(';', ',', $row['nombre']);
            $bigo_id = str_replace(';', ',', $row['bigo_id']);
            $registro = date('d/m/y H:i', strtotime($row['created_at']));
            
            echo $nombre . ';' . $bigo_id . ';' . $row['fecha'] . ';' . $formatted_time . ';' . $registro . ';' . $agendado_status . "\n";
        }
    }
    exit();
}



include '../includes/header.php';

// Obtengo datos para mostrar
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$where_clause = "1=1";
if ($filter_date != '') {
    $filter_date_escaped = mysqli_real_escape_string($conn, $filter_date);
    $where_clause .= " AND i.fecha = '$filter_date_escaped'";
}

$display_query = "SELECT i.*, u.nombre, u.bigo_id FROM impulsos i JOIN usuarios u ON i.user_id = u.id WHERE $where_clause ORDER BY i.fecha DESC, i.hora ASC";
$result = mysqli_query($conn, $display_query);
?>
<link rel="stylesheet" href="../css/index.css">
<style>
    .table-container {
        max-width: 1100px;
        margin: 100px auto;
        padding: 20px;
        background: #0f172a;
        border-radius: 12px;
        border: 1px solid #1e293b;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        color: #fff;
    }

    th,
    td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #334155;
    }

    th {
        background: #1e293b;
        color: #94a3b8;
    }

    tr:hover {
        background: #1e293b;
    }

    .filter-box {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .agendado-label {
        color: #4ade80;
        font-weight: bold;
        font-size: 0.8rem;
    }

    .agendado-checkbox {
        cursor: pointer;
        accent-color: #4ade80;
    }
</style>

<main class="container">
    <div class="table-container">
        <h2 style="color:white; margin-bottom: 20px;">🚀 Gestión de Impulsos</h2>

        <div class="filter-box">
            <form method="GET" style="display: flex; gap: 10px;">
                <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>"
                    style="padding: 8px; border-radius: 4px; border: 1px solid #334155; background: #020617; color: white;">
                <button type="submit" class="btn btn-outline">Filtrar</button>
                <?php if ($filter_date != ''): ?><a href="admin_impulsos.php" class="btn btn-outline">Limpiar</a>
                <?php endif; ?>
            </form>

            <a href="?export=true" class="btn btn-primary">📊 Exportar Excel</a>
        </div>

        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Bigo ID</th>
                        <th>Fecha Impulso</th>
                        <th>Hora</th>
                        <th>Agendado</th>
                        <th>Registrado el</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td data-label="Usuario">
                                    <?php echo htmlspecialchars($row['nombre']); ?>
                                </td>
                                <td data-label="Bigo ID">
                                    <?php echo htmlspecialchars($row['bigo_id']); ?>
                                </td>
                                <td data-label="Fecha Impulso">
                                    <?php echo date('d/m/Y', strtotime($row['fecha'])); ?>
                                </td>
                                <td data-label="Hora">
                                    <?php echo date('h:i A', strtotime($row['hora'])); ?>
                                </td>
                                <td data-label="Agendado">
                                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                        <input type="checkbox" class="agendado-checkbox"
                                            onchange="toggleAgendadoImpulso(this, <?php echo $row['id']; ?>)"
                                            <?php echo (isset($row['agendado']) && $row['agendado']) ? 'checked' : ''; ?>>
                                        <span class="agendado-label"
                                            style="<?php echo (isset($row['agendado']) && $row['agendado']) ? '' : 'display: none;'; ?>">📅
                                            Agendado</span>
                                    </label>
                                </td>
                                <td data-label="Registrado el" style="color: #64748b; font-size: 0.85rem;">
                                    <?php echo $row['created_at']; ?>
                                </td>
                                <td data-label="Acciones" class="text-right">
                                    <a href="admin_impulsos.php?delete=<?php echo $row['id']; ?>"
                                        onclick="return confirm('¿Eliminar este registro de impulso?')"
                                        class="btn btn-sm btn-outline"
                                        style="padding: 5px 10px; color: #ef4444; border-color: #ef4444; font-size: 0.8rem;">
                                        🗑️ Borrar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px;">No hay impulsos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <a href="admin_panel.php" style="color: #94a3b8;">Volver al Panel</a>
        </div>
    </div>
</main>

<script>
    function toggleAgendadoImpulso(checkbox, impulsoId) {
        const agendado = checkbox.checked ? 1 : 0;
        const label = checkbox.parentElement.querySelector('.agendado-label');

        if (label) {
            label.style.display = agendado ? 'inline' : 'none';
        }

        const formData = new FormData();
        formData.append('impulso_id', impulsoId);
        formData.append('agendado', agendado);

        fetch('../api/update_agendado_impulso.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error al actualizar el estado: ' + (data.message || 'Error desconocido'));
                    checkbox.checked = !checkbox.checked;
                    if (label) label.style.display = checkbox.checked ? 'inline' : 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al servidor.');
                checkbox.checked = !checkbox.checked;
                if (label) label.style.display = checkbox.checked ? 'inline' : 'none';
            });
    }
</script>

<?php include '../includes/footer.php'; ?>