<?php
// admin/admin_agencies.php
session_start();

// Verifico si el usuario está logueado y es admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/db.php';
$message = "";
$error = "";

// Función para generar slug
function createSlug($str, $delimiter = '-')
{
    $slug = strtolower(trim(preg_replace('/[\s-]+/', $delimiter, preg_replace('/[^A-Za-z0-9-]+/', $delimiter, preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $str))))), $delimiter));
    return $slug;
}

// 1. Proceso la Creación de Agencia
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_agency'])) {
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Genero el slug
    $slug = createSlug($nombre);

    // Valido duplicados
    $check = mysqli_query($conn, "SELECT id FROM agencias WHERE slug = '$slug'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Ya existe una agencia con un nombre similar (slug duplicado).";
    } else {
        // Inicio la Transacción
        mysqli_begin_transaction($conn);
        try {
            // 1. Inserto la Agencia
            $query_agency = "INSERT INTO agencias (nombre, slug) VALUES ('$nombre', '$slug')";
            if (!mysqli_query($conn, $query_agency)) {
                throw new Exception("Error creando agencia: " . mysqli_error($conn));
            }
            $agency_id = mysqli_insert_id($conn);

            // 2. Creo el Admin de Agencia
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $query_user = "INSERT INTO usuarios (nombre, email, password, rol, estado, agencia_id, plataforma, es_nuevo) 
                           VALUES ('Admin $nombre', '$email', '$password_hashed', 'agency_admin', 'activo', $agency_id, 'N/A', 0)";

            if (!mysqli_query($conn, $query_user)) {
                throw new Exception("Error creando usuario admin: " . mysqli_error($conn));
            }

            // 3. Creo la Estructura de Carpetas (Base)
            $base_dir = __DIR__ . "/../agencies/$slug";
            if (!file_exists($base_dir)) {
                if (!mkdir($base_dir, 0755, true)) {
                    throw new Exception("No se pudo crear el directorio de la agencia.");
                }
            }

            // Creo el archivo index.php base
            $stub_content = "<?php\n";
            $stub_content .= "// Punto de entrada para la agencia: $nombre\n";
            $stub_content .= "\$agency_slug = '$slug';\n";
            $stub_content .= "include '../../agency/loader.php';\n";
            $stub_content .= "?>";

            if (file_put_contents("$base_dir/index.php", $stub_content) === false) {
                throw new Exception("No se pudo escribir el archivo index.php.");
            }

            mysqli_commit($conn);
            $message = "¡Agencia '$nombre' creada exitosamente!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// Obtengo las Agencias
$agencias = mysqli_query($conn, "SELECT a.*, (SELECT COUNT(*) FROM usuarios WHERE agencia_id = a.id) as total_users FROM agencias a ORDER BY created_at DESC");

include '../includes/header.php';
?>

<div class="container" style="padding-top: 100px; padding-bottom: 60px;">
    <div style="max-width: 900px; margin: 0 auto;">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="color: white; margin: 0;">🏢 Gestión de Agencias</h1>
            <a href="admin_panel.php" class="btn btn-secondary"
                style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; text-decoration: none;">&larr;
                Volver</a>
        </div>

        <?php if ($message): ?>
            <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario Crear Agencia -->
        <div
            style="background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; padding: 25px; margin-bottom: 40px;">
            <h3
                style="color: white; margin-top: 0; border-bottom: 1px solid #334155; padding-bottom: 15px; margin-bottom: 20px;">
                ➕ Crear Nueva Agencia
            </h3>
            <form method="POST" action="">
                <input type="hidden" name="create_agency" value="1">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="color: #94a3b8; display: block; margin-bottom: 8px;">Nombre de la Agencia</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Talento Latino" required
                            style="width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; color: white; border-radius: 6px;">
                    </div>
                    <div>
                        <label style="color: #94a3b8; display: block; margin-bottom: 8px;">Logo (Opcional - Subir
                            después)</label>
                        <input type="text" disabled placeholder="Se sube desde el panel de agencia"
                            style="width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; color: #64748b; border-radius: 6px;">
                    </div>
                </div>

                <h4 style="color: #cbd5e1; margin: 20px 0 10px 0; font-size: 0.95rem;">👤 Datos del Administrador de la
                    Agencia</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="color: #94a3b8; display: block; margin-bottom: 8px;">Email del Admin</label>
                        <input type="email" name="email" class="form-control" placeholder="admin@agencia.com" required
                            style="width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; color: white; border-radius: 6px;">
                    </div>
                    <div>
                        <label style="color: #94a3b8; display: block; margin-bottom: 8px;">Contraseña</label>
                        <input type="password" name="password" class="form-control" placeholder="******" required
                            style="width: 100%; padding: 10px; background: #1e293b; border: 1px solid #334155; color: white; border-radius: 6px;">
                    </div>
                </div>

                <div style="margin-top: 25px; text-align: right;">
                    <button type="submit"
                        style="background: #3b82f6; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1rem;">
                        Crear Agencia 🚀
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Agencias -->
        <h3 style="color: white; margin-bottom: 20px;">📂 Agencias Activas</h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php while ($row = mysqli_fetch_assoc($agencias)): ?>
                <div
                    style="background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 20px; position: relative;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                        <span
                            style="background: #3b82f6; color: white; font-size: 0.8rem; padding: 2px 8px; border-radius: 4px;">ID:
                            <?php echo $row['id']; ?>
                        </span>
                        <span style="color: #94a3b8; font-size: 0.8rem;">
                            <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                        </span>
                    </div>

                    <h4 style="color: white; margin: 0 0 10px 0; font-size: 1.2rem;">
                        <?php echo htmlspecialchars($row['nombre']); ?>
                    </h4>
                    <p style="color: #64748b; margin: 0 0 15px 0; font-size: 0.9rem;">
                        URL: /agencies/
                        <?php echo $row['slug']; ?>/
                    </p>

                    <div
                        style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #334155; padding-top: 15px;">
                        <div style="color: #cbd5e1; font-weight: bold;">
                            👥
                            <?php echo $row['total_users']; ?> Usuarios
                        </div>
                        <a href="../agencies/<?php echo $row['slug']; ?>/" target="_blank"
                            style="color: #3b82f6; text-decoration: none; font-size: 0.9rem;">
                            Ir al Panel &rarr;
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>