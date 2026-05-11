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
    $sql_proveedor = "SELECT id_proveedor, nombre_empresa, latitud_proveedor, longitud_proveedor, estado_visibilidad, radio_busqueda, descripcion_destacada FROM proveedores WHERE id_usu = ?";
    $stmt_proveedor = $conn->prepare($sql_proveedor);
    $stmt_proveedor->bind_param("i", $id_usu);
    $stmt_proveedor->execute();
    $result_proveedor = $stmt_proveedor->get_result();
    
    if ($result_proveedor->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Proveedor no encontrado']);
        exit();
    }
    
    $proveedor_data = $result_proveedor->fetch_assoc();
    
    // Si las coordenadas son NULL, asignar coordenadas por defecto (Centro de Cd. Juárez)
    if ($proveedor_data['latitud_proveedor'] === null || $proveedor_data['longitud_proveedor'] === null) {
        $proveedor_data['latitud_proveedor'] = 31.690363; // Centro de Cd. Juárez
        $proveedor_data['longitud_proveedor'] = -106.424548;
        $proveedor_data['coordenadas_defecto'] = true;
        
        // Marcar que necesita completar perfil
        $proveedor_data['mensaje_perfil'] = 'Por favor, completa tu perfil con tu ubicación exacta para mejorar tu visibilidad en el mapa.';
    } else {
        $proveedor_data['coordenadas_defecto'] = false;
        $proveedor_data['mensaje_perfil'] = '';
    }

    echo json_encode([
        'success' => true,
        'proveedor' => $proveedor_data
    ]);

} catch (Exception $e) {
    error_log("Error en cargar_datos_visibilidad.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar datos del proveedor']);
}
?>
