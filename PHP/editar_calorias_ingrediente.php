<?php
session_start();
include 'db_config.php';

header('Content-Type: application/json');

// Verificar que el usuario sea administrador o dueño de restaurante
if (!isset($_SESSION['id_usu']) || ($_SESSION['id_rol'] != 1 && $_SESSION['id_rol'] != 2)) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_inv']) && isset($_POST['calorias'])) {
    $id_inv = intval($_POST['id_inv']);
    $calorias = floatval($_POST['calorias']);
    $id_usuario = $_SESSION['id_usu'];
    $id_rol = $_SESSION['id_rol'];
    
    if ($calorias < 0) {
        echo json_encode(['success' => false, 'message' => 'Las calorías no pueden ser negativas']);
        exit();
    }
    
    if ($id_rol == 1) {
        // Administrador: puede editar cualquier ingrediente
        $stmt_verificar = $conn->prepare("
            SELECT id_inv FROM inventario WHERE id_inv = ? LIMIT 1
        ");
        $stmt_verificar->bind_param("i", $id_inv);
    } else {
        // Dueño: solo puede editar ingredientes de su restaurante
        $stmt_verificar = $conn->prepare("
            SELECT i.id_inv 
            FROM inventario i
            JOIN restaurante r ON i.id_res = r.id_res
            WHERE i.id_inv = ? AND r.id_usu = ?
        ");
        $stmt_verificar->bind_param("ii", $id_inv, $id_usuario);
    }
    
    $stmt_verificar->execute();
    $resultado = $stmt_verificar->get_result();
    
    if ($resultado->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No autorizado para editar este ingrediente']);
        exit();
    }
    
    // Verificar si la columna calorias_base existe, si no, agregarla
    try {
        $check_column = $conn->query("SHOW COLUMNS FROM inventario LIKE 'calorias_base'");
        if ($check_column->num_rows == 0) {
            $conn->query("ALTER TABLE inventario ADD COLUMN calorias_base DECIMAL(8,2) DEFAULT 50.00 COMMENT 'Calorías base por unidad del ingrediente'");
        }
    } catch (Exception $e) {
        // Si hay error, continuamos asumiendo que la columna existe
    }
    
    // Actualizar las calorías
    $stmt_actualizar = $conn->prepare("
        UPDATE inventario 
        SET calorias_base = ?, fecha_actualizacion = CURRENT_TIMESTAMP 
        WHERE id_inv = ?
    ");
    $stmt_actualizar->bind_param("di", $calorias, $id_inv);
    
    if ($stmt_actualizar->execute()) {
        echo json_encode(['success' => true, 'message' => 'Calorías actualizadas correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar las calorías']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
}
?>
