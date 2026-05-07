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
