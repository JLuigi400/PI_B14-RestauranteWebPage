<?php
session_start();
include 'db_config.php';

// Verificar que el usuario sea dueño (rol 2)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

try {
    $id_usuario = $_SESSION['id_usu'];
    
    // Obtener solicitudes de pedidos del usuario
    $sql = "SELECT 
                pp.id_pedido,
                pp.fecha_solicitud,
                pp.estado_pedido,
                pp.total_pedido,
                p.nombre_empresa as nombre_proveedor,
                GROUP_CONCAT(ppd.nombre_producto SEPARATOR ', ') as nombre_producto
            FROM pedidos_proveedor pp
            LEFT JOIN proveedores p ON pp.id_proveedor = p.id_proveedor
            LEFT JOIN detalles_pedido_proveedor ppd ON pp.id_pedido = ppd.id_pedido
            LEFT JOIN productos_proveedor ppd2 ON ppd.id_producto = ppd2.id_producto
            WHERE pp.id_usuario_solicitante = ?
            GROUP BY pp.id_pedido
            ORDER BY pp.fecha_solicitud DESC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    $solicitudes = [];
    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }

    echo json_encode([
        'success' => true,
        'solicitudes' => $solicitudes
    ]);

} catch (Exception $e) {
    error_log("Error en cargar_solicitudes_restock.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar solicitudes']);
}
?>
