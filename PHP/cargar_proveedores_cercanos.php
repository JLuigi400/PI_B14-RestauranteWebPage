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
    $sql_proveedor = "SELECT id_proveedor, latitud_proveedor, longitud_proveedor FROM proveedores WHERE id_usu = ?";
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
    $latitud = $proveedor_data['latitud_proveedor'];
    $longitud = $proveedor_data['longitud_proveedor'];

    // Obtener proveedores cercanos (excluyendo al actual)
    // CORREGIDO: Usar id_tipo_proveedor en lugar de tipo_proveedor
    $sql = "SELECT 
                p.id_proveedor,
                p.nombre_empresa,
                p.id_tipo_proveedor,
                p.latitud_proveedor,
                p.longitud_proveedor,
                (6371 * acos(cos(radians(?)) * cos(radians(p.latitud_proveedor)) * 
                 cos(radians(p.longitud_proveedor) - radians(?)) + 
                 sin(radians(?)) * sin(radians(p.latitud_proveedor)))) AS distancia_km
            FROM proveedores p
            WHERE p.id_proveedor != ? 
            AND p.latitud_proveedor IS NOT NULL 
            AND p.longitud_proveedor IS NOT NULL
            HAVING distancia_km <= 50
            ORDER BY distancia_km ASC
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dddi", $latitud, $longitud, $latitud, $id_proveedor);
    $stmt->execute();
    $result = $stmt->get_result();

    $proveedores = [];
    while ($row = $result->fetch_assoc()) {
        $proveedores[] = $row;
    }

    echo json_encode([
        'success' => true,
        'proveedores' => $proveedores
    ]);

} catch (Exception $e) {
    error_log("Error en cargar_proveedores_cercanos.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar proveedores cercanos']);
}
?>
