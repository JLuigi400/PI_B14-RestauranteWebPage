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
    $id_proveedor = $_SESSION['id_usu'];

    // Obtener productos del proveedor
    $sql = "SELECT 
                id_producto, 
                nombre_producto, 
                descripcion_producto, 
                categoria_producto, 
                unidad_medida, 
                precio_unitario, 
                precio_mayoreo, 
                cantidad_minima_mayoreo, 
                disponibilidad, 
                imagen_producto,
                fecha_registro_producto,
                fecha_actualizacion_producto
            FROM productos_proveedor 
            WHERE id_proveedor = ? 
            ORDER BY fecha_registro_producto DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_proveedor);
    $stmt->execute();
    $result = $stmt->get_result();

    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }

    echo json_encode([
        'success' => true,
        'productos' => $productos,
        'total' => count($productos)
    ]);

} catch (Exception $e) {
    error_log("Error en cargar_productos_proveedor.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar productos']);
}
?>
