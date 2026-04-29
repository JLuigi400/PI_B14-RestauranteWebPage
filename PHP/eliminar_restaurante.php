<?php
// Backend para eliminar restaurantes
session_start();

// Verificar sesión
if (!isset($_SESSION['id_usu'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Conexión a la base de datos
require_once 'conexion.php';

// Obtener datos
$id_res = isset($_POST['id_res']) ? (int)$_POST['id_res'] : 0;
$id_usuario = $_SESSION['id_usu'];
$id_rol = $_SESSION['id_rol'];

if ($id_res === 0) {
    echo json_encode(['success' => false, 'message' => 'ID de restaurante no válido']);
    exit;
}

try {
    // Iniciar transacción
    mysqli_begin_transaction($conn);

    // Obtener datos del restaurante para verificar permisos y archivos
    $query_restaurante = "SELECT r.*, 
                                 COUNT(DISTINCT p.id_pla) as total_platillos,
                                 COUNT(DISTINCT pi.id_inv) as total_ingredientes
                          FROM restaurante r
                          LEFT JOIN platillos p ON r.id_res = p.id_res
                          LEFT JOIN platillo_ingredientes pi ON p.id_pla = pi.id_pla
                          WHERE r.id_res = ?
                          GROUP BY r.id_res";
    
    $stmt_restaurante = mysqli_prepare($conn, $query_restaurante);
    mysqli_stmt_bind_param($stmt_restaurante, 'i', $id_res);
    mysqli_stmt_execute($stmt_restaurante);
    $resultado_restaurante = mysqli_stmt_get_result($stmt_restaurante);
    $restaurante = mysqli_fetch_assoc($resultado_restaurante);

    if (!$restaurante) {
        throw new Exception('Restaurante no encontrado');
    }

    // Verificar permisos (Admin puede eliminar todos, Dueño solo los suyos)
    if ($id_rol != 1 && $restaurante['id_usu'] != $id_usuario) {
        throw new Exception('No tienes permisos para eliminar este restaurante');
    }

    // Eliminar archivos de imágenes si no son los defaults
    $directorio_uploads = '../UPLOADS/RESTAURANTES/';
    
    if ($restaurante['logo_res'] && $restaurante['logo_res'] !== 'default_logo.png') {
        $logo_path = $directorio_uploads . basename($restaurante['logo_res']);
        if (file_exists($logo_path)) {
            unlink($logo_path);
        }
    }
    
    if ($restaurante['banner_res'] && $restaurante['banner_res'] !== 'default_banner.png') {
        $banner_path = $directorio_uploads . basename($restaurante['banner_res']);
        if (file_exists($banner_path)) {
            unlink($banner_path);
        }
    }

    // Eliminar en orden correcto por dependencias

    // 1. Eliminar relaciones de platillo-ingredientes
    $query_delete_pi = "DELETE pi FROM platillo_ingredientes pi 
                        JOIN platillos p ON pi.id_pla = p.id_pla 
                        WHERE p.id_res = ?";
    $stmt_delete_pi = mysqli_prepare($conn, $query_delete_pi);
    mysqli_stmt_bind_param($stmt_delete_pi, 'i', $id_res);
    mysqli_stmt_execute($stmt_delete_pi);

    // 2. Eliminar platillos
    $query_delete_platillos = "DELETE FROM platillos WHERE id_res = ?";
    $stmt_delete_platillos = mysqli_prepare($conn, $query_delete_platillos);
    mysqli_stmt_bind_param($stmt_delete_platillos, 'i', $id_res);
    mysqli_stmt_execute($stmt_delete_platillos);

    // 3. Eliminar restaurante
    $query_delete_restaurante = "DELETE FROM restaurante WHERE id_res = ?";
    $stmt_delete_restaurante = mysqli_prepare($conn, $query_delete_restaurante);
    mysqli_stmt_bind_param($stmt_delete_restaurante, 'i', $id_res);
    mysqli_stmt_execute($stmt_delete_restaurante);

    // Verificar que se eliminó
    if (mysqli_stmt_affected_rows($stmt_delete_restaurante) === 0) {
        throw new Exception('No se pudo eliminar el restaurante');
    }

    // Confirmar transacción
    mysqli_commit($conn);

    // Registrar acción
    error_log("Restaurante ID $id_res eliminado por usuario ID $id_usuario");

    // Respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Restaurante eliminado correctamente',
        'platillos_eliminados' => $restaurante['total_platillos'],
        'ingredientes_eliminados' => $restaurante['total_ingredientes']
    ]);

} catch (Exception $e) {
    // Revertir transacción
    mysqli_rollback($conn);
    
    error_log("Error en eliminar_restaurante.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error al eliminar el restaurante: ' . $e->getMessage()
    ]);
} finally {
    // Cerrar conexión
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>
