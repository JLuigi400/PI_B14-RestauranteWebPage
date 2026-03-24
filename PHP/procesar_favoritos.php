<?php
/**
 * Procesamiento de Favoritos - Salud Juárez
 * Manejo de agregar/eliminar restaurantes favoritos
 */

session_start();
include 'db_config.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['id_usu'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id_usuario = $_SESSION['id_usu'];
$id_rol = $_SESSION['id_rol'];

// Solo clientes pueden gestionar favoritos
if ($id_rol != 3) {
    echo json_encode(['success' => false, 'message' => 'Solo los clientes pueden gestionar favoritos']);
    exit();
}

// Función principal de enrutamiento
$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'agregar':
        agregarFavorito();
        break;
        
    case 'eliminar':
        eliminarFavorito();
        break;
        
    case 'listar':
        listarFavoritos();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

/**
 * Agregar restaurante a favoritos
 */
function agregarFavorito() {
    global $conn, $id_usuario;
    
    $id_res = intval($_POST['id_res'] ?? 0);
    
    if ($id_res <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de restaurante inválido']);
        return;
    }
    
    // Verificar que el restaurante exista y esté activo
    $stmt_verificar = $conn->prepare("
        SELECT id_res FROM restaurante 
        WHERE id_res = ? AND estatus_res = 1
    ");
    $stmt_verificar->bind_param("i", $id_res);
    $stmt_verificar->execute();
    
    if ($stmt_verificar->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'El restaurante no existe o no está activo']);
        return;
    }
    
    // Verificar que ya no esté en favoritos
    $stmt_ya_existe = $conn->prepare("
        SELECT id_favorito FROM favoritos 
        WHERE id_usu = ? AND id_res = ?
    ");
    $stmt_ya_existe->bind_param("ii", $id_usuario, $id_res);
    $stmt_ya_existe->execute();
    
    if ($stmt_ya_existe->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El restaurante ya está en tus favoritos']);
        return;
    }
    
    // Agregar a favoritos
    $stmt_agregar = $conn->prepare("
        INSERT INTO favoritos (id_usu, id_res, fecha_agregado)
        VALUES (?, ?, NOW())
    ");
    $stmt_agregar->bind_param("ii", $id_usuario, $id_res);
    
    if ($stmt_agregar->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Restaurante agregado a favoritos correctamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar a favoritos']);
    }
}

/**
 * Eliminar restaurante de favoritos
 */
function eliminarFavorito() {
    global $conn, $id_usuario;
    
    $id_res = intval($_POST['id_res'] ?? 0);
    
    if ($id_res <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de restaurante inválido']);
        return;
    }
    
    // Eliminar de favoritos
    $stmt_eliminar = $conn->prepare("
        DELETE FROM favoritos 
        WHERE id_usu = ? AND id_res = ?
    ");
    $stmt_eliminar->bind_param("ii", $id_usuario, $id_res);
    
    if ($stmt_eliminar->execute()) {
        if ($stmt_eliminar->affected_rows > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Restaurante eliminado de favoritos correctamente'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'El restaurante no estaba en tus favoritos']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar de favoritos']);
    }
}

/**
 * Listar favoritos del usuario
 */
function listarFavoritos() {
    global $conn, $id_usuario;
    
    $stmt_favoritos = $conn->prepare("
        SELECT r.*, f.fecha_agregado
        FROM favoritos f
        JOIN restaurante r ON f.id_res = r.id_res
        WHERE f.id_usu = ? AND r.estatus_res = 1
        ORDER BY f.fecha_agregado DESC
    ");
    $stmt_favoritos->bind_param("i", $id_usuario);
    $stmt_favoritos->execute();
    
    $favoritos = $stmt_favoritos->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'favoritos' => $favoritos,
        'total' => count($favoritos)
    ]);
}
?>
