<?php
/**
 * Perfil de Usuario - Salud Juárez
 * Página para que el usuario vea y edite su perfil
 */

session_start();
include '../PHP/navbar.php';

if (!isset($_SESSION['id_usu'])) {
    header("Location: ../login.php");
    exit();
}

$id_usu = $_SESSION['id_usu'];
$nombre_usuario = $_SESSION['nombre_completo'] ?? $_SESSION['nick'];
$id_rol = $_SESSION['id_rol'];

// Conexión a BD
require_once '../PHP/db_config.php';

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT u.*, p.nombre_per, p.apellidos_per, p.telefono_per, p.direccion_per, r.nombre_rol 
                        FROM usuarios u 
                        LEFT JOIN perfiles p ON u.id_usu = p.id_usu 
                        LEFT JOIN roles r ON u.id_rol = r.id_rol 
                        WHERE u.id_usu = ?");
$stmt->bind_param("i", $id_usu);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

// Determinar rol
$rol_nombre = $usuario['nombre_rol'] ?? 'Sin rol';
$rol_color = '';
switch ($id_rol) {
    case 1: $rol_color = '#e74c3c'; break;
    case 2: $rol_color = '#f39c12'; break;
    case 3: $rol_color = '#27ae60'; break;
    default: $rol_color = '#3498db'; break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/modal_theme.css">
    <link rel="stylesheet" href="../CSS/navbar_zindex_fix.css">
    <style>
        .perfil-container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .perfil-header { background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; padding: 30px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 20px; }
        .perfil-avatar { width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-size: 2.5em; }
        .perfil-info h1 { margin: 0; font-size: 1.5em; }
        .perfil-info p { margin: 5px 0 0; opacity: 0.9; }
        .perfil-card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .perfil-card h2 { color: #2c3e50; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #ecf0f1; }
        .perfil-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .perfil-field { padding: 12px; background: #f8f9fa; border-radius: 8px; }
        .perfil-field label { display: block; color: #7f8c8d; font-size: 0.85em; margin-bottom: 4px; }
        .perfil-field .value { font-weight: 600; color: #2c3e50; }
        .rol-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; color: white; font-size: 0.85em; font-weight: 600; }
        .btn-editar { background: #3498db; color: white; padding: 10px 25px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 15px; }
        .btn-editar:hover { background: #2980b9; }
    </style>
</head>
<body>
    <main class="perfil-container">
        <div class="perfil-header">
            <div class="perfil-avatar">👤</div>
            <div class="perfil-info">
                <h1><?php echo htmlspecialchars($nombre_usuario); ?></h1>
                <p><span class="rol-badge" style="background:<?php echo $rol_color; ?>;"><?php echo htmlspecialchars($rol_nombre); ?></span></p>
            </div>
        </div>

        <div class="perfil-card">
            <h2>Información Personal</h2>
            <div class="perfil-grid">
                <div class="perfil-field">
                    <label>Nombre de Usuario</label>
                    <div class="value"><?php echo htmlspecialchars($usuario['username_usu'] ?? ''); ?></div>
                </div>
                <div class="perfil-field">
                    <label>Correo Electrónico</label>
                    <div class="value"><?php echo htmlspecialchars($usuario['correo_usu'] ?? ''); ?></div>
                </div>
                <div class="perfil-field">
                    <label>Nombre</label>
                    <div class="value"><?php echo htmlspecialchars(($usuario['nombre_per'] ?? '') . ' ' . ($usuario['apellidos_per'] ?? '')); ?></div>
                </div>
                <div class="perfil-field">
                    <label>Teléfono</label>
                    <div class="value"><?php echo htmlspecialchars($usuario['telefono_per'] ?? 'No especificado'); ?></div>
                </div>
                <div class="perfil-field">
                    <label>Dirección</label>
                    <div class="value"><?php echo htmlspecialchars($usuario['direccion_per'] ?? 'No especificada'); ?></div>
                </div>
                <div class="perfil-field">
                    <label>Estado</label>
                    <div class="value"><?php echo ($usuario['estatus_usu'] ?? 1) ? 'Activo' : 'Inactivo'; ?></div>
                </div>
            </div>
        </div>

        <div class="perfil-card" style="text-align:center;">
            <a href="javascript:void(0)" onclick="alert('Función de edición de perfil en desarrollo')" class="btn-editar">✏️ Editar Perfil</a>
        </div>
    </main>

    <?php include '../PHP/footer.php'; ?>
</body>
</html>
