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
    $id_pedido = intval($_GET['id_pedido'] ?? 0);
    
    if ($id_pedido <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
        exit();
    }

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

    // Obtener detalles completos del pedido
    $sql = "SELECT 
                p.*,
                r.nombre_restaurante,
                r.telefono_restaurante,
                r.contacto_restaurante,
                u.nombre_completo as nombre_usuario_solicitante
            FROM pedidos_proveedor p
            LEFT JOIN restaurantes r ON p.id_restaurante = r.id_restaurante
            LEFT JOIN usuarios u ON p.id_usuario_solicitante = u.id_usu
            WHERE p.id_pedido = ? AND p.id_proveedor = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_pedido, $id_proveedor);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit();
    }

    $pedido = $result->fetch_assoc();

    // Obtener detalles de productos del pedido
    $sql_detalles = "SELECT 
                        dp.*,
                        pp.nombre_producto,
                        pp.unidad_medida
                     FROM detalles_pedido_proveedor dp
                     LEFT JOIN productos_proveedor pp ON dp.id_producto = pp.id_producto
                     WHERE dp.id_pedido = ?";

    $stmt_detalles = $conn->prepare($sql_detalles);
    $stmt_detalles->bind_param("i", $id_pedido);
    $stmt_detalles->execute();
    $result_detalles = $stmt_detalles->get_result();

    $detalles = [];
    while ($row = $result_detalles->fetch_assoc()) {
        $detalles[] = $row;
    }

    $pedido['detalles'] = $detalles;

    echo json_encode([
        'success' => true,
        'pedido' => $pedido
    ]);

} catch (Exception $e) {
    error_log("Error en cargar_detalles_pedido.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar los detalles del pedido']);
}
?>
