<?php
/**
 * Sistema de alertas para el dashboard de dueños
 * Muestra notificaciones de inventario bajo y otras alertas importantes
 */

// Este archivo se incluye en el dashboard.php
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    exit('Acceso no autorizado');
}

$id_usuario = $_SESSION['id_usu'];

// Obtener restaurante del dueño
$query_res = $conn->prepare("SELECT id_res, nombre_res FROM restaurante WHERE id_usu = ?");
$query_res->bind_param("i", $id_usuario);
$query_res->execute();
$result_res = $query_res->get_result();
$restaurante = $result_res->fetch_assoc();

if (!$restaurante) {
    exit('Restaurante no encontrado');
}

$id_res = $restaurante['id_res'];

// Configuración de umbrales
$stock_critico = 5;
$stock_bajo = 10;

// 1. Alertas de inventario bajo
$stmt_critico = $conn->prepare("
    SELECT COUNT(*) as total, GROUP_CONCAT(nombre_insumo SEPARATOR ', ') as ingredientes
    FROM inventario 
    WHERE id_res = ? AND stock_inv <= ?
");
$stmt_critico->bind_param("ii", $id_res, $stock_critico);
$stmt_critico->execute();
$result_critico = $stmt_critico->get_result();
$alerta_critico = $result_critico->fetch_assoc();

$stmt_bajo = $conn->prepare("
    SELECT COUNT(*) as total
    FROM inventario 
    WHERE id_res = ? AND stock_inv > ? AND stock_inv <= ?
");
$stmt_bajo->bind_param("iii", $id_res, $stock_critico, $stock_bajo);
$stmt_bajo->execute();
$result_bajo = $stmt_bajo->get_result();
$alerta_bajo = $result_bajo->fetch_assoc();

// 2. Pedidos de re-stock pendientes
$stmt_pendientes = $conn->prepare("
    SELECT COUNT(*) as total
    FROM pedidos_restock 
    WHERE id_res = ? AND estado = 'pendiente'
");
$stmt_pendientes->bind_param("i", $id_res);
$stmt_pendientes->execute();
$result_pendientes = $stmt_pendientes->get_result();
$pedidos_pendientes = $result_pendientes->fetch_assoc();

// 3. Platillos sin ingredientes disponibles
$stmt_platillos_sin_stock = $conn->prepare("
    SELECT COUNT(DISTINCT p.id_pla) as total
    FROM platillos p
    LEFT JOIN platillo_ingredientes pi ON p.id_pla = pi.id_pla
    LEFT JOIN inventario i ON pi.id_inv = i.id_inv
    WHERE p.id_res = ? AND p.visible = 1
      AND (i.stock_inv <= 0 OR i.stock_inv IS NULL)
");
$stmt_platillos_sin_stock->bind_param("i", $id_res);
$stmt_platillos_sin_stock->execute();
$result_platillos = $stmt_platillos_sin_stock->get_result();
$platillos_afectados = $result_platillos->fetch_assoc();

// 4. Últimas actualizaciones de inventario
$stmt_ultimas_actualizaciones = $conn->prepare("
    SELECT nombre_insumo, stock_inv, medida_inv, fecha_actualizacion
    FROM inventario 
    WHERE id_res = ? AND fecha_actualizacion IS NOT NULL
    ORDER BY fecha_actualizacion DESC
    LIMIT 3
");
$stmt_ultimas_actualizaciones->bind_param("i", $id_res);
$stmt_ultimas_actualizaciones->execute();
$ultimas_actualizaciones = $stmt_ultimas_actualizaciones->get_result();

// Generar HTML de alertas
$alertas_html = '';

// Alerta crítica de stock
if ($alerta_critico['total'] > 0) {
    $alertas_html .= '
    <div class="alert alert-critical" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 15px; border-radius: 10px; margin-bottom: 15px; animation: pulse 2s infinite;">
        <h4 style="margin: 0 0 8px 0;">🚨 ¡Alerta Crítica de Inventario!</h4>
        <p style="margin: 0;">Tienes <strong>' . $alerta_critico['total'] . '</strong> ingrediente(s) con stock crítico (≤ ' . $stock_critico . '):</p>
        <p style="margin: 5px 0; font-size: 14px;">' . htmlspecialchars($alerta_critico['ingredientes']) . '</p>
        <div style="margin-top: 10px;">
            <a href="restock_inventario.php" class="btn btn-warning" style="background: #ffc107; color: #212529; padding: 8px 16px; text-decoration: none; border-radius: 6px; display: inline-block;">🛒 Pedir Re-stock Urgente</a>
        </div>
    </div>';
}

// Alerta de stock bajo
if ($alerta_bajo['total'] > 0) {
    $alertas_html .= '
    <div class="alert alert-warning" style="background: linear-gradient(135deg, #ffc107, #e0a800); color: #212529; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
        <h4 style="margin: 0 0 8px 0;">⚠️ Stock Bajo</h4>
        <p style="margin: 0;">Tienes <strong>' . $alerta_bajo['total'] . '</strong> ingrediente(s) con stock bajo (≤ ' . $stock_bajo . ')</p>
        <div style="margin-top: 10px;">
            <a href="inventario_crud.php" class="btn btn-info" style="background: #17a2b8; color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px; display: inline-block;">📊 Ver Inventario</a>
        </div>
    </div>';
}

// Alerta de pedidos pendientes
if ($pedidos_pendientes['total'] > 0) {
    $alertas_html .= '
    <div class="alert alert-info" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
        <h4 style="margin: 0 0 8px 0;">📦 Pedidos Pendientes</h4>
        <p style="margin: 0;">Tienes <strong>' . $pedidos_pendientes['total'] . '</strong> pedido(s) de re-stock pendiente(s) de entrega</p>
        <div style="margin-top: 10px;">
            <a href="restock_inventario.php" class="btn btn-light" style="background: white; color: #17a2b8; padding: 8px 16px; text-decoration: none; border-radius: 6px; display: inline-block;">📋 Ver Pedidos</a>
        </div>
    </div>';
}

// Alerta de platillos afectados
if ($platillos_afectados['total'] > 0) {
    $alertas_html .= '
    <div class="alert alert-secondary" style="background: linear-gradient(135deg, #6c757d, #5a6268); color: white; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
        <h4 style="margin: 0 0 8px 0;">🍽️ Platillos Afectados</h4>
        <p style="margin: 0;">Hay <strong>' . $platillos_afectados['total'] . '</strong> platillo(s) que podrían verse afectados por falta de ingredientes</p>
        <div style="margin-top: 10px;">
            <a href="gestion_platillos.php" class="btn btn-light" style="background: white; color: #6c757d; padding: 8px 16px; text-decoration: none; border-radius: 6px; display: inline-block;">🍴 Revisar Menú</a>
        </div>
    </div>';
}

// Si no hay alertas críticas, mostrar resumen positivo
if (empty($alertas_html)) {
    $alertas_html = '
    <div class="alert alert-success" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
        <h4 style="margin: 0 0 8px 0;">✅ Todo en Orden</h4>
        <p style="margin: 0;">Tu inventario está en buen estado. No hay alertas críticas en este momento.</p>
    </div>';
}

// Agregar últimas actualizaciones si existen
if ($ultimas_actualizaciones->num_rows > 0) {
    $alertas_html .= '
    <div class="recent-updates" style="background: white; padding: 15px; border-radius: 10px; margin-bottom: 15px; border: 2px solid #e9ecef;">
        <h4 style="margin: 0 0 10px 0; color: #495057;">📈 Últimas Actualizaciones de Inventario</h4>
        <ul style="margin: 0; padding-left: 20px; list-style: none;">';
    
    while ($actualizacion = $ultimas_actualizaciones->fetch_assoc()) {
        $fecha = date('d/m H:i', strtotime($actualizacion['fecha_actualizacion']));
        $alertas_html .= '
            <li style="margin: 5px 0; color: #6c757d;">
                📅 ' . $fecha . ' - ' . htmlspecialchars($actualizacion['nombre_insumo']) . ' 
                (<strong>' . number_format($actualizacion['stock_inv'], 2) . ' ' . htmlspecialchars($actualizacion['medida_inv']) . '</strong>)
            </li>';
    }
    
    $alertas_html .= '
        </ul>
    </div>';
}

// Estilos CSS para las animaciones
$alertas_html .= '
<style>
@keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.9; transform: scale(1.02); }
    100% { opacity: 1; transform: scale(1); }
}

.alert-critical {
    animation: pulse 2s infinite;
}

.btn {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
</style>';

// Devolver el HTML de las alertas
echo $alertas_html;
?>
