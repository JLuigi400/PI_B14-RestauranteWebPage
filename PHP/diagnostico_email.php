<?php
/**
 * DIAGNÓSTICO COMPLETO - EmailJS Data Flow
 * Ejecutar para verificar qué datos se están enviando
 */

include 'db_config.php';
header('Content-Type: application/json');

// Simular los datos que vendrían del POST
$id_proveedor = 4; // ID del proveedor de prueba
$id_usuario_solicitante = 1; // ID del usuario de prueba

// Verificar si el proveedor tiene email
$sql = "SELECT id_proveedor, nombre_empresa, email_proveedor FROM proveedores WHERE id_proveedor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_proveedor);
$stmt->execute();
$result = $stmt->get_result();
$proveedor = $result->fetch_assoc();

// Verificar si el restaurante existe para ese usuario
$sql2 = "SELECT nombre_res FROM restaurante WHERE id_usu = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $id_usuario_solicitante);
$stmt2->execute();
$result2 = $stmt2->get_result();
$restaurante = $result2->fetch_assoc();

// Verificar usuarios - nombre
$sql3 = "SELECT id_usu, username_usu, correo_usu FROM usuarios WHERE id_usu = ?";
$stmt3 = $conn->prepare($sql3);
$stmt3->bind_param("i", $id_usuario_solicitante);
$stmt3->execute();
$result3 = $stmt3->get_result();
$usuario = $result3->fetch_assoc();

// Verificar perfiles - nombre real
$sql4 = "SELECT nombre_per, apellidos_per FROM perfiles WHERE id_usu = ?";
$stmt4 = $conn->prepare($sql4);
$stmt4->bind_param("i", $id_usuario_solicitante);
$stmt4->execute();
$result4 = $stmt4->get_result();
$perfil = $result4->fetch_assoc();

echo json_encode([
    'proveedor' => $proveedor,
    'restaurante' => $restaurante,
    'usuario' => $usuario,
    'perfil' => $perfil,
    'datos_para_emailjs' => [
        'correo_proveedor' => $proveedor['email_proveedor'] ?? 'VACÍO - ERROR',
        'nombre_empresa' => $proveedor['nombre_empresa'] ?? 'Sin nombre',
        'nombre_restaurante' => $restaurante['nombre_res'] ?? 'Sin restaurante',
        'nombre_usuario' => ($perfil['nombre_per'] ?? '') . ' ' . ($perfil['apellidos_per'] ?? '') ?: ($usuario['username_usu'] ?? 'Sin usuario')
    ]
], JSON_PRETTY_PRINT);
?>
