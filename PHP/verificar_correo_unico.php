<?php
// Backend para verificar si un correo electrónico ya está en uso
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

try {
    $correo = isset($_POST['correo_usu']) ? trim($_POST['correo_usu']) : '';
    $id_usuario_actual = isset($_POST['id_usu']) ? (int)$_POST['id_usu'] : 0;

    if (empty($correo)) {
        echo json_encode(['success' => false, 'message' => 'Correo electrónico es requerido']);
        exit;
    }

    // Verificar si el correo ya existe (excluyendo el usuario actual)
    $query = "SELECT id_usu, username_usu FROM usuarios 
              WHERE correo_usu = ? AND id_usu != ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'si', $correo, $id_usuario_actual);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $usuario_existente = mysqli_fetch_assoc($resultado);

    if ($usuario_existente) {
        echo json_encode([
            'success' => true,
            'existe' => true,
            'message' => 'El correo electrónico ya está en uso',
            'usuario_id' => $usuario_existente['id_usu'],
            'usuario_username' => $usuario_existente['username_usu']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'existe' => false,
            'message' => 'El correo electrónico está disponible'
        ]);
    }

} catch (Exception $e) {
    error_log("Error en verificar_correo_unico.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error al verificar el correo: ' . $e->getMessage()
    ]);
} finally {
    // Cerrar conexión
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
