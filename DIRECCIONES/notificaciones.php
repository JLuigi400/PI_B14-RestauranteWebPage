<?php
/**
 * Notificaciones de Usuario - Salud Juárez
 * Página para ver las notificaciones del usuario
 */

session_start();
include '../PHP/navbar.php';

if (!isset($_SESSION['id_usu'])) {
    header("Location: ../login.php");
    exit();
}

$id_usu = $_SESSION['id_usu'];
$nombre_usuario = $_SESSION['nombre_completo'] ?? $_SESSION['nick'];

// Conexión a BD
require_once '../PHP/db_config.php';

// Obtener notificaciones del usuario
$notificaciones = [];
try {
    $stmt = $conn->prepare("SELECT * FROM notificaciones WHERE id_usu = ? ORDER BY fecha_notificacion DESC LIMIT 50");
    $stmt->bind_param("i", $id_usu);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notificaciones[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    $notificaciones = [];
}

$total_no_leidas = count(array_filter($notificaciones, function($n) { return empty($n['leida']); }));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/modal_theme.css">
    <link rel="stylesheet" href="../CSS/navbar_zindex_fix.css">
    <style>
        .notif-container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .notif-header { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; }
        .notif-header h1 { margin: 0; font-size: 1.5em; }
        .notif-header p { margin: 5px 0 0; opacity: 0.9; }
        .notif-actions { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn-notif { padding: 8px 18px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9em; }
        .btn-marcar { background: #27ae60; color: white; }
        .btn-marcar:hover { background: #2ecc71; }
        .notif-item { background: white; padding: 18px 20px; margin-bottom: 10px; border-radius: 10px; border-left: 5px solid #3498db; box-shadow: 0 1px 4px rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: flex-start; transition: background 0.2s; }
        .notif-item:hover { background: #f8f9fa; }
        .notif-no-leida { border-left-color: #e74c3c; background: #fef9f9; }
        .notif-item .notif-content { flex: 1; }
        .notif-item .notif-content h4 { margin: 0 0 5px; color: #2c3e50; }
        .notif-item .notif-content p { margin: 0; color: #666; font-size: 0.9em; }
        .notif-item .notif-time { color: #999; font-size: 0.8em; min-width: 120px; text-align: right; }
        .notif-empty { text-align: center; padding: 60px 20px; color: #999; }
        .notif-empty h3 { font-size: 1.3em; margin-bottom: 10px; }
        .badge-count { background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
    </style>
</head>
<body>
    <main class="notif-container">
        <div class="notif-header">
            <h1>🔔 Notificaciones</h1>
            <p>
                <?php if ($total_no_leidas > 0): ?>
                    Tienes <span class="badge-count"><?php echo $total_no_leidas; ?></span> notificaciones sin leer
                <?php else: ?>
                    No tienes notificaciones sin leer
                <?php endif; ?>
            </p>
        </div>

        <?php if (!empty($notificaciones)): ?>
            <div class="notif-actions">
                <button class="btn-notif btn-marcar" onclick="marcarTodasLeidas()">✅ Marcar todas como leídas</button>
            </div>

            <?php foreach ($notificaciones as $notif): ?>
                <div class="notif-item <?php echo empty($notif['leida']) ? 'notif-no-leida' : ''; ?>">
                    <div class="notif-content">
                        <h4><?php echo htmlspecialchars($notif['titulo'] ?? $notif['tipo_notificacion'] ?? 'Notificación'); ?></h4>
                        <p><?php echo htmlspecialchars($notif['mensaje'] ?? $notif['contenido'] ?? ''); ?></p>
                    </div>
                    <div class="notif-time">
                        <?php echo date('d/m/Y H:i', strtotime($notif['fecha_notificacion'] ?? $notif['fecha_creacion'] ?? 'now')); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="notif-empty">
                <h3>📭 Sin notificaciones</h3>
                <p>No tienes notificaciones por el momento.</p>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../PHP/footer.php'; ?>

    <script>
        function marcarTodasLeidas() {
            fetch('../PHP/marcar_notificaciones.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al marcar notificaciones');
                }
            })
            .catch(() => alert('Error de conexión'));
        }
    </script>
</body>
</html>
