<?php
session_start();
include 'db_config.php';

// Verificar autenticación (cualquier rol puede ver productos públicos)
if (!isset($_SESSION['id_usu'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

try {
    $id_proveedor = intval($_GET['id_proveedor'] ?? 0);
    
    if ($id_proveedor <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de proveedor inválido']);
        exit();
    }

    // Obtener productos públicos del proveedor
    $sql = "SELECT 
                id_producto, 
                nombre_producto, 
                descripcion_producto, 
                categoria_producto, 
                unidad_medida, 
                precio_unitario, 
                precio_mayoreo, 
                cantidad_minima_mayoreo, 
                imagen_producto
            FROM productos_proveedor 
            WHERE id_proveedor = ? 
            AND disponibilidad = 1
            ORDER BY nombre_producto ASC";

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
        'productos' => $productos
    ]);

} catch (Exception $e) {
    error_log("Error en cargar_productos_proveedor_public.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar productos']);
}
?>
