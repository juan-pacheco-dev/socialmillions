<?php
session_start();
include '../includes/db.php';

// Verifico el inicio de sesión del cliente
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['nombre'])) {
    header("Location: ../auth/login.php");
    exit();
}

$client_id = $_SESSION['usuario_id'];
$client_name = $_SESSION['nombre'];
$msg = "";

// 1. AGREGO NUEVA MODELO mediante CÓDIGO
if (isset($_POST['redeem_code'])) {
    $code = mysqli_real_escape_string($conn, trim($_POST['code']));
    // Busco coincidencias en la tabla de códigos de acceso
    $q = mysqli_query($conn, "SELECT * FROM codigos_acceso WHERE code = '$code'");

    if (mysqli_num_rows($q) > 0) {
        $data = mysqli_fetch_assoc($q);
        $model_id = $data['usuario_id'];

        // Verifico la expiración (si no es permanente)
        // Algunos códigos podrían ser permanentes (fecha lejana).
        if (strtotime($data['expires_at']) < time()) {
            $msg = "❌ Este código ha expirado.";
        } else {
            // Añado a la tabla de relación cliente-modelo
            $check = mysqli_query($conn, "SELECT id FROM client_model_codes WHERE client_id = $client_id AND model_id = $model_id");
            if (mysqli_num_rows($check) == 0) {
                mysqli_query($conn, "INSERT INTO client_model_codes (client_id, model_id, code_used) VALUES ($client_id, $model_id, '$code')");
                $msg = "✅ Acceso concedido exitosamente.";
                // Elimino códigos de un solo uso si fuera necesario, pero el usuario solicitó 'código único'.
                // Contexto: 'Genera un código para clientes VIP'.
                // Por ahora lo mantengo, a menos que sea estrictamente de un solo uso.
            } else {
                $msg = "⚠️ Ya tienes acceso a esta modelo.";
            }
        }
    } else {
        $msg = "❌ Código inválido.";
    }
}

// 2. OBTENGO LAS MODELOS VINCULADAS
$models_q = mysqli_query($conn, "
    SELECT DISTINCT u.id, u.nombre, u.foto_perfil, u.estado 
    FROM usuarios u
    JOIN client_model_codes cmc ON u.id = cmc.model_id
    WHERE cmc.client_id = $client_id
    UNION
    SELECT DISTINCT u.id, u.nombre, u.foto_perfil, u.estado
    FROM usuarios u
    JOIN viewer_access va ON u.id = va.model_id
    WHERE va.viewer_id = $client_id
");
$my_models = [];
while ($m = mysqli_fetch_assoc($models_q))
    $my_models[] = $m;

// 3. OBTENGO MENSAJES SIN LEER / BANDEJA DE ENTRADA
// Agrupo por remitente (Modelo)
$inbox_q = mysqli_query($conn, "
    SELECT m.*, u.nombre, u.foto_perfil 
    FROM messages m 
    JOIN usuarios u ON m.sender_id = u.id 
    WHERE m.receiver_id = $client_id 
    AND m.id IN (SELECT MAX(id) FROM messages WHERE receiver_id = $client_id GROUP BY sender_id)
    ORDER BY m.created_at DESC
");
$inbox_messages = [];
while ($im = mysqli_fetch_assoc($inbox_q))
    $inbox_messages[] = $im;

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel VIP | Cliente</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Montserrat:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css/adult.css"> <!-- Estilos base -->

    <style>
        :root {
            --luxury-gold: #d4af37;
            --luxury-card: #0a0a0a;
            --bg-dark: #050505;
        }

        body {
            background-color: var(--bg-dark);
            color: #e0e0e0;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
        }

        /* DISEÑO DE LA PÁGINA */
        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        /* BARRA LATERAL */
        .sidebar {
            width: 280px;
            background: #080808;
            border-right: 1px solid #222;
            display: flex;
            flex-direction: column;
            padding: 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .brand {
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            color: #fff;
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 1px solid #333;
            padding-bottom: 20px;
        }

        .brand span {
            color: var(--luxury-gold);
        }

        .user-mini-profile {
            text-align: center;
            margin-bottom: 30px;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid var(--luxury-gold);
            margin: 0 auto 10px;
            background: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--luxury-gold);
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .nav-item {
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            color: #888;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(212, 175, 55, 0.1);
            color: var(--luxury-gold);
        }

        .nav-icon {
            font-size: 1.2rem;
        }

        .logout-btn {
            margin-top: auto;
            padding: 12px;
            text-align: center;
            color: #ef4444;
            border: 1px solid #ef4444;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s;
        }

        .logout-btn:hover {
            background: #ef4444;
            color: #fff;
        }

        /* CONTENIDO PRINCIPAL */
        .main-content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        /* SECCIONES */
        .section-header {
            margin-bottom: 30px;
        }

        .section-header h2 {
            font-family: 'Cinzel', serif;
            color: #fff;
            margin: 0;
            font-size: 2rem;
        }

        .section-header p {
            color: #666;
            margin: 5px 0 0;
        }

        .grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 25px;
        }

        .model-card {
            background: #111;
            border: 1px solid #222;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
            position: relative;
            text-decoration: none;
        }

        .model-card:hover {
            transform: translateY(-5px);
            border-color: var(--luxury-gold);
        }

        .model-card-img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            filter: brightness(0.8);
            transition: 0.3s;
        }

        .model-card:hover .model-card-img {
            filter: brightness(1);
        }

        .model-info {
            padding: 15px;
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, #000, transparent);
            box-sizing: border-box;
        }

        .model-name {
            color: #fff;
            font-family: 'Cinzel', serif;
            font-size: 1.2rem;
            margin: 0;
            text-shadow: 0 2px 5px #000;
        }

        /* ESTILOS DE LA BANDEJA DE ENTRADA */
        .msg-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .msg-item {
            background: #111;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #222;
            display: flex;
            gap: 20px;
            align-items: start;
            transition: 0.3s;
            cursor: pointer;
        }

        .msg-item:hover {
            background: #151515;
            border-color: #444;
        }

        .msg-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .msg-content {
            flex: 1;
        }

        .msg-sender {
            color: var(--luxury-gold);
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .msg-preview {
            color: #ccc;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .msg-time {
            font-size: 0.8rem;
            color: #666;
            display: block;
            margin-top: 10px;
        }

        /* FORMULARIO PARA AGREGAR CÓDIGO */
        .add-code-box {
            background: linear-gradient(135deg, #111, #0a0a0a);
            border: 1px solid #333;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            max-width: 500px;
            margin: 0 auto;
        }

        .add-code-input {
            width: 100%;
            padding: 15px;
            background: #000;
            border: 1px solid #444;
            color: #d4af37;
            font-size: 1.2rem;
            text-align: center;
            letter-spacing: 2px;
            margin: 20px 0;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .add-code-btn {
            background: var(--luxury-gold);
            color: #000;
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .app-layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                border-right: none;
                border-bottom: 1px solid #222;
            }

            .main-content {
                padding: 20px;
            }
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="app-layout">
        <!-- BARRA LATERAL -->
        <aside class="sidebar">
            <div class="brand">PRO<span>VIP</span></div>

            <div class="user-mini-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($client_name, 0, 1)); ?>
                </div>
                <div style="color: #fff; font-weight: bold;"><?php echo htmlspecialchars($client_name); ?></div>
                <div style="color: #666; font-size: 0.8rem;">Membresía Activa</div>
            </div>

            <ul class="nav-menu">
                <li class="nav-item active" onclick="switchTab('models', this)">
                    <span class="nav-icon">💎</span> Mis Modelos
                </li>
                <li class="nav-item" onclick="switchTab('inbox', this)">
                    <span class="nav-icon">📬</span> Mensajes
                    <?php if (count($inbox_messages) > 0)
                        echo '<span style="background:var(--luxury-gold); color:#000; border-radius:50%; padding:2px 6px; font-size:0.7rem;">' . count($inbox_messages) . '</span>'; ?>
                </li>
                <li class="nav-item" onclick="switchTab('add-code', this)">
                    <span class="nav-icon">🔑</span> Canjear Código
                </li>
            </ul>

            <a href="../auth/logout.php" class="logout-btn">Cerrar Sesión</a>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            <?php if ($msg): ?>
                <div
                    style="padding:15px; background:rgba(212,175,55,0.1); border:1px solid var(--luxury-gold); color:var(--luxury-gold); border-radius:8px; margin-bottom:30px; text-align:center;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <!-- PESTAÑA: MIS MODELOS -->
            <div id="tab-models" class="tab-content active">
                <div class="section-header">
                    <h2>Mis Modelos Exclusivas</h2>
                    <p>Accede a tu contenido privado y galerías VIP.</p>
                </div>

                <div class="grid-cards">
                    <?php if (count($my_models) > 0): ?>
                        <?php foreach ($my_models as $model):
                            $img = !empty($model['foto_perfil']) ? '../' . $model['foto_perfil'] : 'https://via.placeholder.com/400x500/111/444?text=VIP';
                            ?>
                            <a href="secret_gallery.php?model=<?php echo $model['id']; ?>" class="model-card">
                                <img src="<?php echo $img; ?>" class="model-card-img">
                                <div class="model-info">
                                    <h3 class="model-name"><?php echo htmlspecialchars($model['nombre']); ?></h3>
                                    <span style="color:#4ade80; font-size:0.8rem;">● En línea</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1/-1; text-align:center; padding:50px; color:#666;">
                            <p style="font-size:3rem; margin-bottom:20px;">💎</p>
                            <p>No tienes modelos vinculadas aún.</p>
                            <button onclick="switchTab('add-code', document.querySelectorAll('.nav-item')[2])"
                                class="add-code-btn" style="margin-top:20px;">Vincular Modelo</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PESTAÑA: BANDEJA DE ENTRADA -->
            <div id="tab-inbox" class="tab-content">
                <div class="section-header">
                    <h2>Buzón de Mensajes</h2>
                    <p>Comunicaciones privadas directas de tus modelos.</p>
                </div>

                <div class="msg-list">
                    <?php if (count($inbox_messages) > 0): ?>
                        <?php foreach ($inbox_messages as $msg):
                            $sender_img = !empty($msg['foto_perfil']) ? '../' . $msg['foto_perfil'] : 'https://via.placeholder.com/50';
                            ?>
                            <div class="msg-item"
                                onclick="openMessageDialog(<?php echo $msg['sender_id']; ?>, '<?php echo htmlspecialchars($msg['nombre']); ?>', '<?php echo addslashes($msg['message']); ?>')">
                                <img src="<?php echo $sender_img; ?>" class="msg-avatar">
                                <div class="msg-content">
                                    <span class="msg-sender"><?php echo htmlspecialchars($msg['nombre']); ?></span>
                                    <div class="msg-preview"><?php echo htmlspecialchars($msg['message']); ?></div>
                                    <span class="msg-time"><?php echo date('d M, H:i', strtotime($msg['created_at'])); ?></span>
                                </div>
                                <button
                                    style="align-self:center; background:none; border:1px solid #444; color:#888; padding:5px 10px; border-radius:5px; cursor:pointer;">Responder</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:50px; color:#555;">
                            No tienes mensajes nuevos.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PESTAÑA: CANJEAR CÓDIGO -->
            <div id="tab-add-code" class="tab-content">
                <div class="section-header">
                    <h2>Canjear Código Secreto</h2>
                    <p>Introduce tu llave maestra para desbloquear contenido exclusivo.</p>
                </div>

                <div class="add-code-box">
                    <form method="POST">
                        <div style="font-size:3rem; margin-bottom:10px;">🔑</div>
                        <input type="text" name="code" class="add-code-input" placeholder="INGRESA TU CÓDIGO" required
                            autocomplete="off">
                        <button type="submit" name="redeem_code" class="add-code-btn">DESBLOQUEAR ACCESO</button>
                    </form>
                </div>
            </div>

        </main>
    </div>


    <script>
        function switchTab(tabId, el) {
            // Encabezados
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            el.classList.add('active');

            // Contenidos
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            const tab = document.getElementById('tab-' + tabId);
            if (tab) tab.classList.add('active');
        }

        function openMessageDialog(id, name, text) {
            // ¿Reutilizar lógica de galería secreta? No, mejor redirigir.
            // El usuario quiere leer. Redirijo a la galería de la modelo para chatear.
            window.location.href = 'secret_gallery.php?model=' + id + '&open_chat=1';
        }

    </script>

</body>

</html>