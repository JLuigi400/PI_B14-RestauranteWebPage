<?php
// Backend para obtener información del usuario actual
session_start();

// Verificar sesión
if (!isset($_SESSION['id_usu'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

// Conexión a la base de datos
require_once 'conexion.php';

try {
    $id_usuario = $_SESSION['id_usu'];
    $id_rol = $_SESSION['id_rol'];

    // Obtener información completa del usuario
    $query = "SELECT u.id_usu, u.username_usu, u.correo_usu, u.id_rol, u.estatus_usu,
                     p.nombre_per, p.apellidos_per, p.correo_per, p.cel_per
              FROM usuarios u
              LEFT JOIN perfiles p ON u.id_usu = p.id_usu
              WHERE u.id_usu = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $usuario = mysqli_fetch_assoc($resultado);

    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Obtener información adicional según el rol
    $info_adicional = [];

    if ($id_rol == 1) {
        // Admin: obtener estadísticas básicas
        $query_admin = "SELECT COUNT(*) as total_restaurantes 
                      FROM restaurante";
        $stmt_admin = mysqli_prepare($conexion, $query_admin);
        mysqli_stmt_execute($stmt_admin);
        $resultado_admin = mysqli_stmt_get_result($stmt_admin);
        $admin_stats = mysqli_fetch_assoc($resultado_admin);
        
        $info_adicional['estadisticas'] = [
            'total_restaurantes' => $admin_stats['total_restaurantes']
        ];
    } else if ($id_rol == 2) {
        // Dueño: obtener restaurantes del usuario
        $query_dueño = "SELECT r.id_res, r.nombre_res, r.estatus_res, r.validado_admin
                        FROM restaurante r 
                        WHERE r.id_usu = ?";
        $stmt_dueño = mysqli_prepare($conexion, $query_dueño);
        mysqli_stmt_bind_param($stmt_dueño, 'i', $id_usuario);
        mysqli_stmt_execute($stmt_dueño);
        $resultado_dueño = mysqli_stmt_get_result($stmt_dueño);
        
        $restaurantes = [];
        while ($rest = mysqli_fetch_assoc($resultado_dueño)) {
            $restaurantes[] = $rest;
        }
        
        $info_adicional['restaurantes'] = $restaurantes;
        $info_adicional['total_restaurantes'] = count($restaurantes);
    }

    // Combinar información
    $respuesta = array_merge($usuario, $info_adicional);

    echo json_encode([
        'success' => true,
        'data' => $respuesta,
        'message' => 'Información de usuario obtenida correctamente'
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_usuario_actual.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener información del usuario: ' . $e->getMessage()
    ]);
} finally {
    // Cerrar conexión
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}
?>
