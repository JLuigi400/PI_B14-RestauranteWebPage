<?php
session_start();
include 'db_config.php';

// Verificar que el usuario sea dueño (rol 2)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

try {
    $id_usuario = $_SESSION['id_usu'];
    
    // Obtener restaurantes del usuario
    $sql = "SELECT id_res, nombre_res, latitud, longitud 
            FROM restaurante 
            WHERE id_usu = ? AND estatus_res = 1
            ORDER BY nombre_res ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    $restaurantes = [];
    while ($row = $result->fetch_assoc()) {
        $restaurantes[] = $row;
    }

    echo json_encode([
        'success' => true,
        'restaurantes' => $restaurantes
    ]);

} catch (Exception $e) {
    error_log("Error en cargar_restaurantes_usuario.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar restaurantes']);
}
?>
