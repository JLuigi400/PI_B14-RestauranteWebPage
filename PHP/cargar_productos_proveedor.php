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
    $id_usu = $_SESSION['id_usu'];

    // Primero obtener el id_proveedor correspondiente al id_usu
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
