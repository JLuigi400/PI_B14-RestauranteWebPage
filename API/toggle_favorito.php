<?php
/**
 * API para agregar/quitar restaurantes de favoritos
 * Salud Juárez - Sistema de Restaurantes
 * Versión: 1.0.0
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Iniciar sesión y verificar autenticación
session_start();

if (!isset($_SESSION['id_usu'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Inicia sesión para continuar.'
    ]);
    exit();
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ]);
    exit();
}

require_once '../PHP/conexion.php';

$id_usu = $_SESSION['id_usu'];
$id_res = $_POST['id_res'] ?? null;

// Validar datos
if (!$id_res || !is_numeric($id_res)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID de restaurante inválido.'
    ]);
    exit();
}

$id_res = (int)$id_res;

try {
    // Verificar que el restaurante exista y esté activo
    $sql_check = "SELECT id_res, nombre_res FROM restaurante WHERE id_res = ? AND estatus_res = 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param('i', $id_res);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'El restaurante no existe o no está disponible.'
        ]);
        exit();
    }
    
    $restaurante = $result_check->fetch_assoc();
    
    // Verificar si ya está en favoritos
    $sql_fav = "SELECT id_favorito FROM favoritos WHERE id_usu = ? AND id_res = ?";
    $stmt_fav = $conn->prepare($sql_fav);
    $stmt_fav->bind_param('ii', $id_usu, $id_res);
    $stmt_fav->execute();
    $result_fav = $stmt_fav->get_result();
    
    $conn->begin_transaction();
    
    if ($result_fav->num_rows > 0) {
        // Ya está en favoritos, eliminarlo
        $sql_delete = "DELETE FROM favoritos WHERE id_usu = ? AND id_res = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param('ii', $id_usu, $id_res);
        $stmt_delete->execute();
        
        $accion = 'quitado';
        $message = "Restaurante '{$restaurante['nombre_res']}' quitado de favoritos";
        
    } else {
        // No está en favoritos, agregarlo
        $sql_insert = "INSERT INTO favoritos (id_usu, id_res, fecha_agregado) VALUES (?, ?, NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param('ii', $id_usu, $id_res);
        $stmt_insert->execute();
        
        $accion = 'agregado';
        $message = "Restaurante '{$restaurante['nombre_res']}' agregado a favoritos";
    }
    
    $conn->commit();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => $message,
        'accion' => $accion,
        'id_res' => $id_res,
        'nombre_restaurante' => $restaurante['nombre_res']
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar favoritos: ' . $e->getMessage()
    ]);
}

// Cerrar statements
if (isset($stmt_check)) $stmt_check->close();
if (isset($stmt_fav)) $stmt_fav->close();
if (isset($stmt_delete)) $stmt_delete->close();
if (isset($stmt_insert)) $stmt_insert->close();

$conn->close();
?>
