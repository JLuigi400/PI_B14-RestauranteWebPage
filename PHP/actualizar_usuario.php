<?php
// Backend para actualizar usuarios
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

// Verificar que sea administrador
if ($_SESSION['id_rol'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
    exit;
}

// Conexión a la base de datos
require_once 'conexion.php';

// Obtener datos del formulario
$id_usu = isset($_POST['id_usu']) ? (int)$_POST['id_usu'] : 0;
$id_admin = $_SESSION['id_usu'];

if ($id_usu === 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario no válido']);
    exit;
}

try {
    // Obtener datos actuales del usuario
    $query_actual = "SELECT * FROM usuarios WHERE id_usu = ?";
    $stmt_actual = mysqli_prepare($conexion, $query_actual);
    mysqli_stmt_bind_param($stmt_actual, 'i', $id_usu);
    mysqli_stmt_execute($stmt_actual);
    $resultado_actual = mysqli_stmt_get_result($stmt_actual);
    $usuario_actual = mysqli_fetch_assoc($resultado_actual);

    if (!$usuario_actual) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }

    // Iniciar transacción
    mysqli_begin_transaction($conexion);

    // Construir consulta dinámica para usuarios
    $campos_actualizar = [];
    $tipos = '';
    $valores = [];

    // Campos básicos de usuario
    if (isset($_POST['correo_usu']) && !empty(trim($_POST['correo_usu']))) {
        $campos_actualizar[] = "correo_usu = ?";
        $tipos .= 's';
        $valores[] = trim($_POST['correo_usu']);
    }

    if (isset($_POST['id_rol'])) {
        $campos_actualizar[] = "id_rol = ?";
        $tipos .= 'i';
        $valores[] = (int)$_POST['id_rol'];
    }

    if (isset($_POST['estatus_usu'])) {
        $campos_actualizar[] = "estatus_usu = ?";
        $tipos .= 'i';
        $valores[] = (int)$_POST['estatus_usu'];
    }

    // Agregar fecha de actualización
    $campos_actualizar[] = "fecha_actualizacion = NOW()";

    // Si hay campos para actualizar en usuarios
    if (!empty($campos_actualizar)) {
        $query = "UPDATE usuarios SET " . implode(', ', $campos_actualizar) . " WHERE id_usu = ?";
        $tipos .= 'i';
        $valores[] = $id_usu;

        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, $tipos, ...$valores);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error al actualizar el usuario: ' . mysqli_stmt_error($stmt));
        }
    }

    // Construir consulta dinámica para perfiles
    $campos_perfil_actualizar = [];
    $tipos_perfil = '';
    $valores_perfil = [];

    // Verificar si existe perfil
    $query_perfil_existente = "SELECT id_per FROM perfiles WHERE id_usu = ?";
    $stmt_perfil_existente = mysqli_prepare($conexion, $query_perfil_existente);
    mysqli_stmt_bind_param($stmt_perfil_existente, 'i', $id_usu);
    mysqli_stmt_execute($stmt_perfil_existente);
    $resultado_perfil_existente = mysqli_stmt_get_result($stmt_perfil_existente);
    $perfil_existente = mysqli_fetch_assoc($resultado_perfil_existente);

    // Campos de perfil
    if (isset($_POST['nombre_per']) && !empty(trim($_POST['nombre_per']))) {
        $campos_perfil_actualizar[] = "nombre_per = ?";
        $tipos_perfil .= 's';
        $valores_perfil[] = trim($_POST['nombre_per']);
    }

    if (isset($_POST['apellidos_per']) && !empty(trim($_POST['apellidos_per']))) {
        $campos_perfil_actualizar[] = "apellidos_per = ?";
        $tipos_perfil .= 's';
        $valores_perfil[] = trim($_POST['apellidos_per']);
    }

    if (isset($_POST['correo_per'])) {
        $campos_perfil_actualizar[] = "correo_per = ?";
        $tipos_perfil .= 's';
        $valores_perfil[] = trim($_POST['correo_per']);
    }

    if (isset($_POST['cel_per'])) {
        $campos_perfil_actualizar[] = "cel_per = ?";
        $tipos_perfil .= 's';
        $valores_perfil[] = trim($_POST['cel_per']);
    }

    // Si hay campos para actualizar en perfiles
    if (!empty($campos_perfil_actualizar)) {
        if ($perfil_existente) {
            // Actualizar perfil existente
            $query_perfil = "UPDATE perfiles SET " . implode(', ', $campos_perfil_actualizar) . " WHERE id_usu = ?";
            $tipos_perfil .= 'i';
            $valores_perfil[] = $id_usu;
        } else {
            // Crear nuevo perfil
            $query_perfil = "INSERT INTO perfiles (id_usu, " . implode(', ', $campos_perfil_actualizar) . ") VALUES (?, " . str_repeat('?,', count($campos_perfil_actualizar)) . ")";
            $tipos_perfil = 'i' . $tipos_perfil;
            $valores_perfil = array_merge([$id_usu], $valores_perfil);
        }

        $stmt_perfil = mysqli_prepare($conexion, $query_perfil);
        mysqli_stmt_bind_param($stmt_perfil, $tipos_perfil, ...$valores_perfil);
        
        if (!mysqli_stmt_execute($stmt_perfil)) {
            throw new Exception('Error al actualizar el perfil: ' . mysqli_stmt_error($stmt_perfil));
        }
    }

    // Confirmar transacción
    mysqli_commit($conexion);

    // Enviar notificación por EmailJS si se actualizó correctamente
    $filas_afectadas = mysqli_stmt_affected_rows($stmt) + mysqli_stmt_affected_rows($stmt_perfil ?? $stmt_perfil_existente);
    
    if ($filas_afectadas > 0) {
        enviarNotificacionEmailUsuario($id_usu, $id_admin, $usuario_actual);
    }

    // Respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Usuario actualizado correctamente',
        'updated' => $filas_afectadas > 0
    ]);

} catch (Exception $e) {
    // Revertir transacción
    mysqli_rollback($conexion);
    
    error_log("Error en actualizar_usuario.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error al actualizar el usuario: ' . $e->getMessage()
    ]);
} finally {
    // Cerrar conexión
    if (isset($conexion)) {
        mysqli_close($conexion);
    }
}

// Función para enviar notificación por EmailJS
function enviarNotificacionEmailUsuario($id_usuario, $id_admin, $usuario_actual) {
    try {
        // Obtener información actualizada del usuario
        global $conexion;
        
        $query_actualizado = "SELECT u.*, r.nombre_rol, p.nombre_per, p.apellidos_per
                            FROM usuarios u
                            JOIN roles r ON u.id_rol = r.id_rol
                            LEFT JOIN perfiles p ON u.id_usu = p.id_usu
                            WHERE u.id_usu = ?";
        
        $stmt_actualizado = mysqli_prepare($conexion, $query_actualizado);
        mysqli_stmt_bind_param($stmt_actualizado, 'i', $id_usuario);
        mysqli_stmt_execute($stmt_actualizado);
        $resultado_actualizado = mysqli_stmt_get_result($stmt_actualizado);
        $usuario_actualizado = mysqli_fetch_assoc($resultado_actualizado);

        if (!$usuario_actualizado) {
            error_log("No se pudo obtener información actualizada del usuario ID: $id_usuario");
            return false;
        }

        // Obtener información del administrador
        $query_admin = "SELECT username_usu, correo_usu FROM usuarios WHERE id_usu = ?";
        $stmt_admin = mysqli_prepare($conexion, $query_admin);
        mysqli_stmt_bind_param($stmt_admin, 'i', $id_admin);
        mysqli_stmt_execute($stmt_admin);
        $resultado_admin = mysqli_stmt_get_result($stmt_admin);
        $admin_info = mysqli_fetch_assoc($resultado_admin);

        if (!$admin_info) {
            error_log("No se pudo obtener información del administrador ID: $id_admin");
            return false;
        }

        // Preparar datos para EmailJS
        $email_data = [
            'service_id' => 'service_kchdp9f',
            'template_id' => 'template_tnrferf',
            'public_key' => 'VkhEAneBLv5m5rOgO',
            'template_params' => [
                'to_name' => ($usuario_actualizado['nombre_per'] ?: $usuario_actualizado['username_usu']),
                'to_email' => $usuario_actualizado['correo_usu'],
                'restaurant_name' => 'N/A',
                'restaurant_id' => 'N/A',
                'updated_by' => $admin_info['username_usu'],
                'update_date' => date('d/m/Y H:i'),
                'action_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . "/admin_usuarios.php"
            ]
        ];

        // Enviar correo usando cURL
        $ch = curl_init();
        
        $url = 'https://api.emailjs.com/api/v1.0/email/send';
        
        $payload = json_encode([
            'service_id' => $email_data['service_id'],
            'template_id' => $email_data['template_id'],
            'user_id' => $email_data['public_key'],
            'template_params' => $email_data['template_params'],
            'accessToken' => ''
        ]);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            error_log("Notificación por EmailJS enviada correctamente para usuario ID: $id_usuario");
            return true;
        } else {
            error_log("Error al enviar notificación EmailJS. HTTP Code: $http_code, Response: $response");
            return false;
        }

    } catch (Exception $e) {
        error_log("Excepción en enviarNotificacionEmailUsuario: " . $e->getMessage());
        return false;
    }
}
?>
