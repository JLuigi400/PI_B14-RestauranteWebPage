<?php
/**
 * Procesamiento de Validación de Restaurantes - Salud Juárez
 * Manejo de validación, rechazo y suspensión de restaurantes
 */

session_start();
include 'db_config.php';

header('Content-Type: application/json');

// Verificar sesión y rol de administrador
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id_admin = $_SESSION['id_usu'];

// Función principal de enrutamiento
$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'validar':
        validarRestaurante();
        break;
        
    case 'rechazar':
        rechazarRestaurante();
        break;
        
    case 'detener':
        detenerRestaurante();
        break;
        
    case 'reactivar':
        reactivarRestaurante();
        break;
        
    case 'listar_pendientes':
        listarPendientes();
        break;
        
    case 'listar_validados':
        listarValidados();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

/**
 * Validar restaurante (aprobar)
 */
function validarRestaurante() {
    global $conn, $id_admin;
    
    $id_res = intval($_POST['id_res'] ?? 0);
    
    if ($id_res <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de restaurante inválido']);
        return;
    }
    
    // Verificar que el restaurante exista y esté pendiente
    $stmt_verificar = $conn->prepare("
        SELECT r.nombre_res, u.email_usu, u.nombre_usu 
        FROM restaurante r 
        JOIN usuarios u ON r.id_usu = u.id_usu
        WHERE r.id_res = ? AND (r.estatus_res = 0 OR r.estatus_res IS NULL)
    ");
    $stmt_verificar->bind_param("i", $id_res);
    $stmt_verificar->execute();
    $restaurante = $stmt_verificar->get_result()->fetch_assoc();
    
    if (!$restaurante) {
        echo json_encode(['success' => false, 'message' => 'Restaurante no encontrado o ya validado']);
        return;
    }
    
    // Validar restaurante
    $stmt_validar = $conn->prepare("
        UPDATE restaurante 
        SET estatus_res = 1, 
            fecha_validacion = NOW(),
            id_admin_validador = ?
        WHERE id_res = ?
    ");
    $stmt_validar->bind_param("ii", $id_admin, $id_res);
    
    if ($stmt_validar->execute()) {
        // Aquí se podría enviar notificación al dueño del restaurante
        // Por ahora, solo registramos en el log
        
        echo json_encode([
            'success' => true, 
            'message' => "Restaurante '{$restaurante['nombre_res']}' validado correctamente"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al validar restaurante']);
    }
}

/**
 * Rechazar y eliminar restaurante
 */
function rechazarRestaurante() {
    global $conn, $id_admin;
    
    $id_res = intval($_POST['id_res'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    
    if ($id_res <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de restaurante inválido']);
        return;
    }
    
    if (empty($motivo)) {
        echo json_encode(['success' => false, 'message' => 'El motivo de rechazo es obligatorio']);
        return;
    }
    
    // Verificar que el restaurante exista
    $stmt_verificar = $conn->prepare("
        SELECT r.nombre_res, u.email_usu, u.nombre_usu, u.id_usu
        FROM restaurante r 
        JOIN usuarios u ON r.id_usu = u.id_usu
        WHERE r.id_res = ?
    ");
    $stmt_verificar->bind_param("i", $id_res);
    $stmt_verificar->execute();
    $restaurante = $stmt_verificar->get_result()->fetch_assoc();
    
    if (!$restaurante) {
        echo json_encode(['success' => false, 'message' => 'Restaurante no encontrado']);
        return;
    }
    
    // Iniciar transacción para eliminación segura
    $conn->begin_transaction();
    
    try {
        // Registrar el rechazo en una tabla de log (opcional)
        $stmt_log = $conn->prepare("
            INSERT INTO validacion_log (id_res, id_admin, accion, motivo, fecha_accion)
            VALUES (?, ?, 'rechazo', ?, NOW())
        ");
        $stmt_log->bind_param("iis", $id_res, $id_admin, $motivo);
        $stmt_log->execute();
        
        // Eliminar todos los datos relacionados con el restaurante
        // 1. Eliminar platillos y sus relaciones
        $stmt_eliminar_platillo_ingredientes = $conn->prepare("
            DELETE pi FROM platillo_ingredientes pi
            JOIN platillos p ON pi.id_pla = p.id_pla
            WHERE p.id_res = ?
        ");
        $stmt_eliminar_platillo_ingredientes->bind_param("i", $id_res);
        $stmt_eliminar_platillo_ingredientes->execute();
        
        $stmt_eliminar_platillos = $conn->prepare("DELETE FROM platillos WHERE id_res = ?");
        $stmt_eliminar_platillos->bind_param("i", $id_res);
        $stmt_eliminar_platillos->execute();
        
        // 2. Eliminar inventario
        $stmt_eliminar_inventario = $conn->prepare("DELETE FROM inventario WHERE id_res = ?");
        $stmt_eliminar_inventario->bind_param("i", $id_res);
        $stmt_eliminar_inventario->execute();
        
        // 3. Eliminar solicitudes de re-stock
        $stmt_eliminar_solicitudes = $conn->prepare("DELETE FROM solicitudes_restock WHERE id_res = ?");
        $stmt_eliminar_solicitudes->bind_param("i", $id_res);
        $stmt_eliminar_solicitudes->execute();
        
        // 4. Eliminar favoritos
        $stmt_eliminar_favoritos = $conn->prepare("DELETE FROM favoritos WHERE id_res = ?");
        $stmt_eliminar_favoritos->bind_param("i", $id_res);
        $stmt_eliminar_favoritos->execute();
        
        // 5. Finalmente eliminar el restaurante
        $stmt_eliminar_restaurante = $conn->prepare("DELETE FROM restaurante WHERE id_res = ?");
        $stmt_eliminar_restaurante->bind_param("i", $id_res);
        $stmt_eliminar_restaurante->execute();
        
        // Confirmar transacción
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "Restaurante '{$restaurante['nombre_res']}' rechazado y eliminado correctamente"
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al rechazar restaurante: ' . $e->getMessage()]);
    }
}

/**
 * Detener restaurante (cambiar estatus a inactivo)
 */
function detenerRestaurante() {
    global $conn, $id_admin;
    
    $id_res = intval($_POST['id_res'] ?? 0);
    
    if ($id_res <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de restaurante inválido']);
        return;
    }
    
    // Verificar que el restaurante exista y esté activo
    $stmt_verificar = $conn->prepare("
        SELECT nombre_res FROM restaurante 
        WHERE id_res = ? AND estatus_res = 1
    ");
    $stmt_verificar->bind_param("i", $id_res);
    $stmt_verificar->execute();
    $restaurante = $stmt_verificar->get_result()->fetch_assoc();
    
    if (!$restaurante) {
        echo json_encode(['success' => false, 'message' => 'Restaurante no encontrado o ya está inactivo']);
        return;
    }
    
    // Detener restaurante
    $stmt_detener = $conn->prepare("
        UPDATE restaurante 
        SET estatus_res = 0, 
            fecha_suspension = NOW(),
            id_admin_suspensor = ?
        WHERE id_res = ?
    ");
    $stmt_detener->bind_param("ii", $id_admin, $id_res);
    
    if ($stmt_detener->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => "Restaurante '{$restaurante['nombre_res']}' detenido correctamente"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al detener restaurante']);
    }
}

/**
 * Reactivar restaurante
 */
function reactivarRestaurante() {
    global $conn, $id_admin;
    
    $id_res = intval($_POST['id_res'] ?? 0);
    
    if ($id_res <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de restaurante inválido']);
        return;
    }
    
    // Verificar que el restaurante exista y esté inactivo
    $stmt_verificar = $conn->prepare("
        SELECT nombre_res FROM restaurante 
        WHERE id_res = ? AND estatus_res = 0
    ");
    $stmt_verificar->bind_param("i", $id_res);
    $stmt_verificar->execute();
    $restaurante = $stmt_verificar->get_result()->fetch_assoc();
    
    if (!$restaurante) {
        echo json_encode(['success' => false, 'message' => 'Restaurante no encontrado o ya está activo']);
        return;
    }
    
    // Reactivar restaurante
    $stmt_reactivar = $conn->prepare("
        UPDATE restaurante 
        SET estatus_res = 1, 
            fecha_reactivacion = NOW(),
            id_admin_reactivador = ?
        WHERE id_res = ?
    ");
    $stmt_reactivar->bind_param("ii", $id_admin, $id_res);
    
    if ($stmt_reactivar->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => "Restaurante '{$restaurante['nombre_res']}' reactivado correctamente"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al reactivar restaurante']);
    }
}

/**
 * Listar restaurantes pendientes de validación
 */
function listarPendientes() {
    global $conn;
    
    $stmt_pendientes = $conn->prepare("
        SELECT r.*, u.nombre_usu, u.apellido_usu, u.email_usu, u.telefono_usu
        FROM restaurante r
        JOIN usuarios u ON r.id_usu = u.id_usu
        WHERE r.estatus_res = 0 OR r.estatus_res IS NULL
        ORDER BY r.fecha_registro DESC
    ");
    $stmt_pendientes->execute();
    $pendientes = $stmt_pendientes->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'pendientes' => $pendientes,
        'total' => count($pendientes)
    ]);
}

/**
 * Listar restaurantes validados
 */
function listarValidados() {
    global $conn;
    
    $stmt_validados = $conn->prepare("
        SELECT r.*, u.nombre_usu, u.apellido_usu,
               COUNT(DISTINCT p.id_pla) as total_platillos,
               COUNT(DISTINCT CASE WHEN p.visible = 1 THEN p.id_pla END) as platillos_visibles
        FROM restaurante r
        JOIN usuarios u ON r.id_usu = u.id_usu
        LEFT JOIN platillos p ON r.id_res = p.id_res
        WHERE r.estatus_res = 1
        GROUP BY r.id_res
        ORDER BY r.fecha_validacion DESC
    ");
    $stmt_validados->execute();
    $validados = $stmt_validados->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'validados' => $validados,
        'total' => count($validados)
    ]);
}
?>
