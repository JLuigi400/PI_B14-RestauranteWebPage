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
    
    $estado_visibilidad = $data['estado_visibilidad'] ?? 'activo';
    $radio_busqueda = intval($data['radio_busqueda'] ?? 10);
    $descripcion_destacada = $data['descripcion_destacada'] ?? '';

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

    // Actualizar opciones de visibilidad
    $sql = "UPDATE proveedores SET 
                estado_visibilidad = ?, 
                radio_busqueda = ?, 
                descripcion_destacada = ? 
             WHERE id_proveedor = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi", $estado_visibilidad, $radio_busqueda, $descripcion_destacada, $id_proveedor);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Opciones de visibilidad guardadas correctamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar las opciones']);
    }

} catch (Exception $e) {
    error_log("Error en guardar_opciones_visibilidad.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar las opciones']);
}
?>
