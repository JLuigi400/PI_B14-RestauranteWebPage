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
    $id_proveedor = $_SESSION['id_usu'];

    // Validaciones
    if ($id_producto <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de producto inválido']);
        exit();
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Obtener información del producto (incluyendo imagen) antes de eliminar
        $sql_info = "SELECT imagen_producto FROM productos_proveedor WHERE id_producto = ? AND id_proveedor = ?";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("ii", $id_producto, $id_proveedor);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();

        if ($result_info->num_rows === 0) {
            throw new Exception('Producto no encontrado o no pertenece al proveedor');
        }

        $producto_info = $result_info->fetch_assoc();
        $nombre_imagen = $producto_info['imagen_producto'];

        // Eliminar el producto
        $sql_delete = "DELETE FROM productos_proveedor WHERE id_producto = ? AND id_proveedor = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $id_producto, $id_proveedor);

        if (!$stmt_delete->execute()) {
            throw new Exception('Error al eliminar el producto');
        }

        // Si el producto tenía imagen, eliminarla del servidor
        if ($nombre_imagen && file_exists('../IMG/UPLOADS/INSUMOS/' . $nombre_imagen)) {
            if (!unlink('../IMG/UPLOADS/INSUMOS/' . $nombre_imagen)) {
                error_log("No se pudo eliminar la imagen: " . $nombre_imagen);
                // No lanzar excepción, solo registrar el error
            }
        }

        // Confirmar transacción
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado correctamente'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error en eliminar_producto_proveedor.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
