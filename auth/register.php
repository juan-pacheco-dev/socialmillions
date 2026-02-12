<?php
// Incluir la conexión a la base de datos
include '../includes/db.php';

$error = "";
$success = "";

// Procesar el formulario cuando se envía mediante POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpio los datos de entrada para evitar inyecciones SQL
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $bigo_id = mysqli_real_escape_string($conn, $_POST['bigo_id'] ?? '');
    $celular = mysqli_real_escape_string($conn, $_POST['celular']);
    $password = $_POST['password'];
    $tipo_contenido = mysqli_real_escape_string($conn, $_POST['tipo_contenido']);

    // NUEVOS CAMPOS: Agrego Plataforma y Agencia
    $plataforma = mysqli_real_escape_string($conn, $_POST['plataforma'] ?? 'Bigo');
    $agencia_id = isset($_POST['agencia_id']) && !empty($_POST['agencia_id']) ? (int) $_POST['agencia_id'] : 'NULL';

    // Verifico si el streamer ya existe o es nuevo
    $es_streamer_existente = isset($_POST['es_streamer']) && $_POST['es_streamer'] === 'si';

    // Defino campos adicionales exclusivos para nuevos streamers
    $experiencia = '';
    $disponibilidad = '';
    $compromiso = '';
    $es_nuevo = 0;

    if (!$es_streamer_existente) {
        $experiencia = mysqli_real_escape_string($conn, $_POST['experiencia'] ?? '');
        $disponibilidad = mysqli_real_escape_string($conn, $_POST['disponibilidad'] ?? '');
        $compromiso = mysqli_real_escape_string($conn, $_POST['compromiso'] ?? '');
        $es_nuevo = 1; // Marco como nuevo streamer para resaltarlo en el admin
    }

    // Realizo validaciones básicas de los datos
    if (empty($nombre) || empty($email) || empty($bigo_id) || empty($celular) || empty($password) || empty($tipo_contenido)) {
        $error = "Todos los campos son obligatorios, incluyendo el ID de Plataforma.";
    } else {
        // Verifico si el correo electrónico ya está registrado
        $query_check = "SELECT id, estado FROM usuarios WHERE email = '$email'";
        $result_check = mysqli_query($conn, $query_check);

        if (mysqli_num_rows($result_check) > 0) {
            $row_check = mysqli_fetch_assoc($result_check);
            if ($row_check['estado'] == 'pendiente') {
                $error = "Estas en espera de tu confirmación";
            } else {
                $error = "Este correo ya esta en uso";
            }
        } else {
            // Encripto la contraseña antes de guardarla
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            // Inserto el nuevo usuario con sus datos de postulación y agencia
            $query_insert = "INSERT INTO usuarios (nombre, email, bigo_id, plataforma, agencia_id, celular, password, rol, estado, experiencia, tipo_contenido, disponibilidad, motivo, es_nuevo) 
                             VALUES ('$nombre', '$email', '$bigo_id', '$plataforma', $agencia_id, '$celular', '$password_hashed', 'user', 'pendiente', '$experiencia', '$tipo_contenido', '$disponibilidad', '$compromiso', $es_nuevo)";

            if (mysqli_query($conn, $query_insert)) {
                $success = "¡Postulación enviada! Nuestro equipo de reclutamiento validará tu perfil y te contactará pronto.";
            } else {
                $error = "Ocurrió un error al registrar: " . mysqli_error($conn);
            }
        }
    }
}

include '../includes/header.php';
?>

<!-- Vinculo el CSS específico para el registro -->
<link rel="stylesheet" href="../css/register_client.css">

<main class="container">
    <div class="auth-container">
        <h2 class="auth-title">Postúlate como Streamer</h2>
        <p style="text-align: center; color: #94a3b8; margin-bottom: 30px;">Completa tu perfil profesional para aplicar
            a la agencia.</p>

        <!-- Muestro los mensajes de error o éxito según corresponda -->
        <?php if ($error): ?>
            <div class="alert alert-danger"
                style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"
                style="background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:20px;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO DE REGISTRO -->
        <form action="" method="POST">

            <!-- PREGUNTA PRINCIPAL: ¿Ya eres streamer? -->
            <div class="streamer-question">
                <h3>¿Ya eres streamer en alguna plataforma?</h3>
                <div class="streamer-buttons">
                    <button type="button" class="streamer-btn" id="btnSi" onclick="setStreamerStatus('si')">
                        ✅ Sí
                    </button>
                    <button type="button" class="streamer-btn" id="btnNo" onclick="setStreamerStatus('no')">
                        ❌ No
                    </button>
                </div>
                <input type="hidden" name="es_streamer" id="esStreamerInput" value="">
            </div>

            <!-- FORMULARIO PRINCIPAL (Inicialmente oculto) -->
            <div id="mainForm" class="form-hidden">
                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Tu nombre" required>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="ejemplo@correo.com"
                        required>
                </div>

                <!-- NUEVO: Selector de Plataforma -->
                <div class="form-group">
                    <label for="plataforma">Plataforma</label>
                    <select name="plataforma" id="plataforma" class="form-control" required>
                        <option value="Bigo">Bigo Live</option>
                        <option value="TikTok">TikTok</option>
                        <option value="Mico">Mico</option>
                        <option value="Poppo">Poppo</option>
                        <option value="Otra">Otra</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="bigo_id">ID de Plataforma (Usuario/ID)</label>
                    <input type="text" name="bigo_id" id="bigo_id" class="form-control"
                        placeholder="Ingresa tu ID de usuario en la app" required>
                </div>

                <!-- NUEVO: Selector de Agencia -->
                <div class="form-group">
                    <label for="agencia_id">¿A cuál agencia perteneces?</label>
                    <select name="agencia_id" id="agencia_id" class="form-control" required>
                        <option value="">-- Selecciona una Agencia --</option>
                        <?php
                        // Consulto las agencias activas para el select
                        $query_agencias = "SELECT id, nombre FROM agencias ORDER BY nombre ASC";
                        $result_agencias = mysqli_query($conn, $query_agencias);
                        if ($result_agencias) {
                            while ($row_ag = mysqli_fetch_assoc($result_agencias)) {
                                echo '<option value="' . $row_ag['id'] . '">' . htmlspecialchars($row_ag['nombre']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="celular">Número de Celular (WhatsApp)</label>
                    <input type="text" name="celular" id="celular" class="form-control" placeholder="+57 300..."
                        required>
                </div>

                <div class="form-group">
                    <!-- Defino el tipo de contenido automáticamente -->
                    <input type="hidden" name="tipo_contenido" value="Stream Normal">
                </div>

                <!-- PREGUNTAS ADICIONALES (Solo para nuevos streamers) -->
                <div id="preguntasNuevos" class="preguntas-nuevos">
                    <div class="section-divider">
                        <div class="section-title">
                            📝 Cuéntanos más sobre ti
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="disponibilidad">Disponibilidad Diaria</label>
                        <select name="disponibilidad" id="disponibilidad" class="form-control">
                            <option value="">Selecciona horas...</option>
                            <option value="1-2 horas">1-2 horas diarias</option>
                            <option value="3-5 horas">3-5 horas diarias</option>
                            <option value="Tiempo Completo">Tiempo Completo (+6 horas)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="experiencia">¿Tienes experiencia previa?</label>
                        <textarea name="experiencia" id="experiencia" class="form-control" rows="3"
                            placeholder="TikTok, Instagram, etc..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="compromiso">¿Por qué quieres ser streamer?</label>
                        <textarea name="compromiso" id="compromiso" class="form-control" rows="3"
                            placeholder="Tu motivación..."></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••"
                        required>
                </div>

                <button type="submit" class="btn-auth" style="margin-top: 20px;">Enviar Postulación 🚀</button>
            </div>
        </form>

        <div class="auth-footer">
            ¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión</a>
        </div>
    </div>
</main>

<script>
    function setStreamerStatus(status) {
        const esStreamerInput = document.getElementById('esStreamerInput');
        const mainForm = document.getElementById('mainForm');
        const preguntasNuevos = document.getElementById('preguntasNuevos');
        const btnSi = document.getElementById('btnSi');
        const btnNo = document.getElementById('btnNo');

        esStreamerInput.value = status;
        mainForm.classList.remove('form-hidden');
        btnSi.classList.remove('active', 'active-no');
        btnNo.classList.remove('active', 'active-no');

        if (status === 'si') {
            btnSi.classList.add('active');
            preguntasNuevos.classList.remove('visible');
            document.getElementById('disponibilidad').required = false;
        } else {
            btnNo.classList.add('active-no');
            preguntasNuevos.classList.add('visible');
            document.getElementById('disponibilidad').required = true;
        }
        mainForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
</script>

<?php include '../includes/footer.php'; ?>