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
    // Obtener datos del POST JSON
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id_producto = intval($data['id_producto'] ?? 0);
    $disponibilidad = intval($data['disponibilidad'] ?? 0);
    $id_proveedor = $_SESSION['id_usu'];

    // Validaciones
    if ($id_producto <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
        exit();
    }

    // Verificar que el producto pertenezca al proveedor
    $sql_check = "SELECT id_producto FROM productos_proveedor WHERE id_producto = ? AND id_proveedor = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_producto, $id_proveedor);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado o no pertenece al proveedor']);
        exit();
    }

    // Actualizar disponibilidad
    $sql = "UPDATE productos_proveedor SET disponibilidad = ?, fecha_actualizacion_producto = NOW() WHERE id_producto = ? AND id_proveedor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $disponibilidad, $id_producto, $id_proveedor);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => $disponibilidad ? 'Producto marcado como disponible' : 'Producto marcado como agotado',
            'disponibilidad' => $disponibilidad
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar disponibilidad']);
    }

} catch (Exception $e) {
    error_log("Error en toggle_disponibilidad_producto.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
?>
