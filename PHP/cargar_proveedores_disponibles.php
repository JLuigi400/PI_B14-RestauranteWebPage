<?php
session_start();
include 'db_config.php';

// DEBUG: Log de inicio
error_log("[DEBUG cargar_proveedores_disponibles.php] === INICIO ===");
error_log("[DEBUG] SESSION: " . print_r($_SESSION, true));

// Verificar que el usuario sea dueño (rol 2)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    error_log("[DEBUG] ERROR: Usuario no autorizado. id_usu=" . ($_SESSION['id_usu'] ?? 'NO SET') . ", id_rol=" . ($_SESSION['id_rol'] ?? 'NO SET'));
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

try {
    error_log("[DEBUG] Usuario autorizado: id_usu=" . $_SESSION['id_usu']);
    
    // DEBUG: Verificar conexión a BD
    if ($conn->connect_error) {
        error_log("[DEBUG] ERROR DE CONEXIÓN BD: " . $conn->connect_error);
        throw new Exception("Error de conexión a BD");
    }
    error_log("[DEBUG] Conexión BD OK");
    
    // DEBUG: Contar proveedores totales
    $sql_count_total = "SELECT COUNT(*) as total FROM proveedores";
    $result_total = $conn->query($sql_count_total);
    $row_total = $result_total->fetch_assoc();
    error_log("[DEBUG] Total proveedores en BD: " . $row_total['total']);
    
    // DEBUG: Contar proveedores activos
    $sql_count_activos = "SELECT COUNT(*) as total FROM proveedores WHERE estado_visibilidad = 'activo'";
    $result_activos = $conn->query($sql_count_activos);
    $row_activos = $result_activos->fetch_assoc();
    error_log("[DEBUG] Proveedores con estado_visibilidad='activo': " . $row_activos['total']);
    
    // DEBUG: Contar productos disponibles
    $sql_count_prod = "SELECT COUNT(*) as total FROM productos_proveedor WHERE disponibilidad = 1";
    $result_prod = $conn->query($sql_count_prod);
    $row_prod = $result_prod->fetch_assoc();
    error_log("[DEBUG] Productos con disponibilidad=1: " . $row_prod['total']);
    
    // DEBUG: Ver relación proveedor-productos
    $sql_check_join = "SELECT 
                        p.id_proveedor, 
                        p.nombre_empresa, 
                        p.estado_visibilidad,
                        p.id_tipo_proveedor,
                        COUNT(pp.id_producto) as num_productos
                       FROM proveedores p
                       LEFT JOIN productos_proveedor pp ON p.id_proveedor = pp.id_proveedor AND pp.disponibilidad = 1
                       GROUP BY p.id_proveedor";
    $result_check = $conn->query($sql_check_join);
    error_log("[DEBUG] Detalle de proveedores y sus productos disponibles:");
    while ($row = $result_check->fetch_assoc()) {
        error_log("[DEBUG]   - ID:" . $row['id_proveedor'] . " | " . $row['nombre_empresa'] . " | Tipo:" . $row['id_tipo_proveedor'] . " | Estado:" . $row['estado_visibilidad'] . " | Productos:" . $row['num_productos']);
    }
    
    // QUERY PRINCIPAL: Obtener proveedores con productos disponibles
    // CORREGIDO: Usar id_tipo_proveedor en lugar de tipo_proveedor
    $sql = "SELECT DISTINCT 
                p.id_proveedor, 
                p.nombre_empresa, 
                p.id_tipo_proveedor,
                p.estado_visibilidad
            FROM proveedores p
            INNER JOIN productos_proveedor pp ON p.id_proveedor = pp.id_proveedor
            WHERE pp.disponibilidad = 1 
            AND p.estado_visibilidad = 'activo'
            ORDER BY p.nombre_empresa ASC";

    error_log("[DEBUG] Ejecutando query principal...");
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("[DEBUG] ERROR preparando statement: " . $conn->error);
        throw new Exception("Error preparando query");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $proveedores = [];
    while ($row = $result->fetch_assoc()) {
        $proveedores[] = $row;
    }

    error_log("[DEBUG] Proveedores encontrados con INNER JOIN: " . count($proveedores));
    error_log("[DEBUG] Detalle: " . print_r($proveedores, true));
    
    // DEBUG: Si no hay resultados, verificar por qué
    if (count($proveedores) === 0) {
        error_log("[DEBUG] ALERTA: No se encontraron proveedores con productos disponibles");
        
        // Verificar si hay productos pero no coinciden con proveedores
        $sql_debug = "SELECT pp.id_proveedor, pp.id_producto, pp.disponibilidad 
                      FROM productos_proveedor pp 
                      LEFT JOIN proveedores p ON pp.id_proveedor = p.id_proveedor 
                      WHERE p.id_proveedor IS NULL";
        $result_debug = $conn->query($sql_debug);
        if ($result_debug->num_rows > 0) {
            error_log("[DEBUG] Productos huérfanos (sin proveedor): " . $result_debug->num_rows);
        }
    }
    
    error_log("[DEBUG] === FIN === Enviando respuesta con " . count($proveedores) . " proveedores");
    
    echo json_encode([
        'success' => true,
        'proveedores' => $proveedores,
        'debug_info' => [
            'total_proveedores_bd' => $row_total['total'],
            'proveedores_activos' => $row_activos['total'],
            'productos_disponibles' => $row_prod['total'],
            'encontrados_con_join' => count($proveedores)
        ]
    ]);

} catch (Exception $e) {
    error_log("[DEBUG] EXCEPCIÓN: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
