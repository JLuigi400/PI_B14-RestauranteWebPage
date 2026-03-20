<?php
// ==========================================
// ARCHIVO: PHP/check_session.php
// Verificador de estado de sesión para JavaScript
// ==========================================

// Iniciar sesión para poder verificar
session_start();

// Cabeceras para respuesta JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Respuesta predeterminada
$response = [
    'logged_in' => false,
    'user_id' => null,
    'username' => null,
    'rol_id' => null,
    'rol_nombre' => null,
    'nombre_completo' => null,
    'session_id' => session_id(),
    'timestamp' => date('Y-m-d H:i:s')
];

// Verificar si hay sesión activa
if (isset($_SESSION['id_usu'])) {
    $response = array_merge($response, [
        'logged_in' => true,
        'user_id' => $_SESSION['id_usu'] ?? null,
        'username' => $_SESSION['nick'] ?? null,
        'rol_id' => $_SESSION['id_rol'] ?? null,
        'rol_nombre' => $_SESSION['rol_nombre'] ?? null,
        'nombre_completo' => $_SESSION['nombre_completo'] ?? null
    ]);
    
    // Opcional: verificar tiempo de sesión
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        $timeout = 30 * 60; // 30 minutos
        
        if ($inactive_time > $timeout) {
            // Sesión expirada, limpiar y marcar como no logged
            session_destroy();
            $response['logged_in'] = false;
            $response['session_expired'] = true;
        } else {
            // Actualizar última actividad
            $_SESSION['last_activity'] = time();
        }
    } else {
        // Establecer primera actividad
        $_SESSION['last_activity'] = time();
    }
}

// Enviar respuesta JSON
echo json_encode($response, JSON_PRETTY_PRINT);
?>
