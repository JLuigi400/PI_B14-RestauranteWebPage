<?php
session_start();
include 'db_config.php';

header('Content-Type: application/json');

// Verificar que el usuario sea un dueño de restaurante
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_detalle'])) {
    $id_detalle = intval($_POST['id_detalle']);
    $id_usuario = $_SESSION['id_usu'];
    
    // Verificar que el detalle pertenezca a un platillo del restaurante del usuario
    $stmt_verificar = $conn->prepare("
        SELECT pi.id_pla 
        FROM platillo_ingredientes pi
        JOIN platillos p ON pi.id_pla = p.id_pla
        JOIN restaurante r ON p.id_res = r.id_res
        WHERE pi.id_detalle = ? AND r.id_usu = ?
    ");
    $stmt_verificar->bind_param("ii", $id_detalle, $id_usuario);
    $stmt_verificar->execute();
    $resultado = $stmt_verificar->get_result();
    
    if ($resultado->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No autorizado para eliminar este ingrediente']);
        exit();
    }
    
    // Eliminar el ingrediente del platillo
    $stmt_eliminar = $conn->prepare("
        DELETE FROM platillo_ingredientes 
        WHERE id_detalle = ?
    ");
    $stmt_eliminar->bind_param("i", $id_detalle);
    
    if ($stmt_eliminar->execute()) {
        echo json_encode(['success' => true, 'message' => 'Ingrediente eliminado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el ingrediente']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
}
?>
