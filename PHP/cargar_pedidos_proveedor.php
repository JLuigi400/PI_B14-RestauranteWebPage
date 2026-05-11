<?php
session_start();
include 'db_config.php';

// Verificar que el usuario sea proveedor (rol 4)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 4) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

try {
    // Obtener id_proveedor desde la tabla proveedores usando id_usu de sesión
    $id_usu = $_SESSION['id_usu'];
    $sql_proveedor = "SELECT id_proveedor FROM proveedores WHERE id_usu = ?";
    $stmt_proveedor = $conn->prepare($sql_proveedor);
    $stmt_proveedor->bind_param("i", $id_usu);
    $stmt_proveedor->execute();
    $result_proveedor = $stmt_proveedor->get_result();
    
    if ($result_proveedor->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Proveedor no encontrado']);
        exit();
    }
    
    $proveedor_data = $result_proveedor->fetch_assoc();
    $id_proveedor = $proveedor_data['id_proveedor'];

    // Obtener pedidos del proveedor con JOIN a restaurantes
    $sql = "SELECT 
                p.id_pedido,
                p.id_restaurante,
                p.estado_pedido,
                p.fecha_solicitud,
                p.fecha_confirmacion,
                p.fecha_envio,
                p.fecha_entrega,
                p.fecha_cancelacion,
                p.subtotal_productos,
                p.costo_envio,
                p.total_pedido,
                p.metodo_pago,
                p.direccion_entrega,
                p.notas_pedido,
                p.notas_internas,
                r.nombre_restaurante,
                r.telefono_restaurante,
                r.contacto_restaurante,
                u.nombre_completo as nombre_usuario_solicitante,
                u.nick as nick_solicitante
            FROM pedidos_proveedor p
            LEFT JOIN restaurantes r ON p.id_restaurante = r.id_restaurante
            LEFT JOIN usuarios u ON p.id_usuario_solicitante = u.id_usu
            WHERE p.id_proveedor = ? 
            ORDER BY p.fecha_solicitud DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_proveedor);
    $stmt->execute();
    $result = $stmt->get_result();

    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }

    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos,
        'total' => count($pedidos)
    ]);

} catch (Exception $e) {
    error_log("Error en cargar_pedidos_proveedor.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar los pedidos']);
}
?>
