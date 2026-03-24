<?php
session_start();
include 'db_config.php';

// Verificar que el usuario sea un dueño de restaurante
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_pla']) && isset($_POST['ingredientes'])) {
    $id_pla = intval($_POST['id_pla']);
    $id_usuario = $_SESSION['id_usu'];
    
    // Verificar que el platillo pertenezca al restaurante del usuario
    $stmt_verificar = $conn->prepare("
        SELECT p.id_res 
        FROM platillos p 
        JOIN restaurante r ON p.id_res = r.id_res 
        WHERE p.id_pla = ? AND r.id_usu = ?
    ");
    $stmt_verificar->bind_param("ii", $id_pla, $id_usuario);
    $stmt_verificar->execute();
    $resultado = $stmt_verificar->get_result();
    
    if ($resultado->num_rows === 0) {
        header("Location: ../DIRECCIONES/gestion_platillos.php?error=not_owner");
        exit();
    }
    
    $id_res = $resultado->fetch_assoc()['id_res'];
    $ingredientes_agregados = 0;
    
    foreach ($_POST['ingredientes'] as $ingrediente_data) {
        $id_inv = intval($ingrediente_data['id_inv']);
        $cantidad = floatval($ingrediente_data['cantidad']);
        $unidad = $ingrediente_data['unidad'] ?? '';
        
        if ($id_inv > 0 && $cantidad > 0) {
            // Verificar que el ingrediente pertenezca al restaurante
            $stmt_verificar_inv = $conn->prepare("
                SELECT id_inv FROM inventario 
                WHERE id_inv = ? AND id_res = ?
            ");
            $stmt_verificar_inv->bind_param("ii", $id_inv, $id_res);
            $stmt_verificar_inv->execute();
            
            if ($stmt_verificar_inv->get_result()->num_rows > 0) {
                $stmt_ing = $conn->prepare("
                    INSERT INTO platillo_ingredientes (id_pla, id_inv, cantidad_usada, unidad_usada) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    cantidad_usada = VALUES(cantidad_usada), 
                    unidad_usada = VALUES(unidad_usada)
                ");
                $stmt_ing->bind_param("iids", $id_pla, $id_inv, $cantidad, $unidad);
                
                if ($stmt_ing->execute()) {
                    $ingredientes_agregados++;
                }
            }
        }
    }
    
    if ($ingredientes_agregados > 0) {
        header("Location: ../DIRECCIONES/revisar_ingredientes.php?id_pla={$id_pla}&status=success&count={$ingredientes_agregados}");
    } else {
        header("Location: ../DIRECCIONES/revisar_ingredientes.php?id_pla={$id_pla}&status=error");
    }
} else {
    header("Location: ../DIRECCIONES/gestion_platillos.php");
}
?>
