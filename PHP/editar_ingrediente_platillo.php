<?php
session_start();
include 'db_config.php';

header('Content-Type: application/json');

// Verificar que el usuario sea un dueño de restaurante
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_detalle']) && isset($_POST['cantidad'])) {
    $id_detalle = intval($_POST['id_detalle']);
    $cantidad = floatval($_POST['cantidad']);
    $id_usuario = $_SESSION['id_usu'];
    
    if ($cantidad <= 0) {
        echo json_encode(['success' => false, 'message' => 'La cantidad debe ser mayor a 0']);
        exit();
    }
    
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
        echo json_encode(['success' => false, 'message' => 'No autorizado para editar este ingrediente']);
        exit();
    }
    
    // Actualizar la cantidad
    $stmt_actualizar = $conn->prepare("
        UPDATE platillo_ingredientes 
        SET cantidad_usada = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id_detalle = ?
    ");
    $stmt_actualizar->bind_param("di", $cantidad, $id_detalle);
    
    if ($stmt_actualizar->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cantidad actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la cantidad']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
}
?>
