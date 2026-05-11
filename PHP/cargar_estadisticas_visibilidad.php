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

    // Calcular estadísticas
    $estadisticas = [];

    // Vistas esta semana
    $sql_vistas_semana = "SELECT COUNT(*) as vistas 
                           FROM vistas_proveedor 
                           WHERE id_proveedor = ? 
                           AND fecha_vista >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $stmt_vistas_semana = $conn->prepare($sql_vistas_semana);
    $stmt_vistas_semana->bind_param("i", $id_proveedor);
    $stmt_vistas_semana->execute();
    $result_vistas_semana = $stmt_vistas_semana->get_result();
    $estadisticas['vistas_semana'] = $result_vistas_semana->fetch_assoc()['vistas'] ?? 0;

    // Contactos esta semana
    $sql_contactos_semana = "SELECT COUNT(*) as contactos 
                              FROM contactos_proveedor 
                              WHERE id_proveedor = ? 
                              AND fecha_contacto >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $stmt_contactos_semana = $conn->prepare($sql_contactos_semana);
    $stmt_contactos_semana->bind_param("i", $id_proveedor);
    $stmt_contactos_semana->execute();
    $result_contactos_semana = $stmt_contactos_semana->get_result();
    $estadisticas['contactos_semana'] = $result_contactos_semana->fetch_assoc()['contactos'] ?? 0;

    // Vistas totales
    $sql_vistas_totales = "SELECT COUNT(*) as vistas 
                            FROM vistas_proveedor 
                            WHERE id_proveedor = ?";
    $stmt_vistas_totales = $conn->prepare($sql_vistas_totales);
    $stmt_vistas_totales->bind_param("i", $id_proveedor);
    $stmt_vistas_totales->execute();
    $result_vistas_totales = $stmt_vistas_totales->get_result();
    $estadisticas['vistas_totales'] = $result_vistas_totales->fetch_assoc()['vistas'] ?? 0;

    // Productos activos
    $sql_productos_activos = "SELECT COUNT(*) as activos 
                               FROM productos_proveedor 
                               WHERE id_proveedor = ? 
                               AND disponibilidad = 1";
    $stmt_productos_activos = $conn->prepare($sql_productos_activos);
    $stmt_productos_activos->bind_param("i", $id_proveedor);
    $stmt_productos_activos->execute();
    $result_productos_activos = $stmt_productos_activos->get_result();
    $estadisticas['productos_activos'] = $result_productos_activos->fetch_assoc()['activos'] ?? 0;

    echo json_encode([
        'success' => true,
        'estadisticas' => $estadisticas
    ]);

} catch (Exception $e) {
    error_log("Error en cargar_estadisticas_visibilidad.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar estadísticas']);
}
?>
