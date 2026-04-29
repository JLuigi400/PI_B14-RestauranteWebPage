<?php
// Backend para actualizar restaurantes
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

// Obtener datos del formulario
$id_res = isset($_POST['id_res']) ? (int)$_POST['id_res'] : 0;
$id_usuario = $_SESSION['id_usu'];
$id_rol = $_SESSION['id_rol'];

if ($id_res === 0) {
    $response = ['success' => false, 'message' => 'ID de restaurante no válido'];
    error_log("Error ID: " . json_encode($response));
    echo json_encode($response);
    exit;
}

try {
    // Obtener datos actuales del restaurante para verificar permisos
    $query_actual = "SELECT id_usu, logo_res, banner_res FROM restaurante WHERE id_res = ?";
    $stmt_actual = mysqli_prepare($conn, $query_actual);
    mysqli_stmt_bind_param($stmt_actual, 'i', $id_res);
    mysqli_stmt_execute($stmt_actual);
    $resultado_actual = mysqli_stmt_get_result($stmt_actual);
    $restaurante_actual = mysqli_fetch_assoc($resultado_actual);

    if (!$restaurante_actual) {
        $response = ['success' => false, 'message' => 'Restaurante no encontrado'];
        error_log("Error restaurante no encontrado: " . json_encode($response));
        echo json_encode($response);
        exit;
    }

    // Verificar permisos
    if ($id_rol != 1 && $restaurante_actual['id_usu'] != $id_usuario) {
        $response = ['success' => false, 'message' => 'No tienes permisos para editar este restaurante'];
        error_log("Error permisos: " . json_encode($response));
        echo json_encode($response);
        exit;
    }

    // Iniciar transacción
    mysqli_begin_transaction($conn);

    // Construir consulta dinámica
    $campos_actualizar = [];
    $tipos = '';
    $valores = [];

    // Campos básicos
    if (isset($_POST['nombre_res']) && !empty(trim($_POST['nombre_res']))) {
        $campos_actualizar[] = "nombre_res = ?";
        $tipos .= 's';
        $valores[] = trim($_POST['nombre_res']);
    }

    if (isset($_POST['descripcion_res'])) {
        $campos_actualizar[] = "descripcion_res = ?";
        $tipos .= 's';
        $valores[] = trim($_POST['descripcion_res']);
    }

    if (isset($_POST['telefono_res'])) {
        $campos_actualizar[] = "telefono_res = ?";
        $tipos .= 's';
        $valores[] = trim($_POST['telefono_res']);
    }

    if (isset($_POST['url_web'])) {
        $campos_actualizar[] = "url_web = ?";
        $tipos .= 's';
        $valores[] = trim($_POST['url_web']);
    }

    if (isset($_POST['direccion_res']) && !empty(trim($_POST['direccion_res']))) {
        $campos_actualizar[] = "direccion_res = ?";
        $tipos .= 's';
        $valores[] = trim($_POST['direccion_res']);
    }

    if (isset($_POST['sector_res'])) {
        $campos_actualizar[] = "sector_res = ?";
        $tipos .= 's';
        $valores[] = trim($_POST['sector_res']);
    }

    if (isset($_POST['id_colonia']) && !empty($_POST['id_colonia'])) {
        $campos_actualizar[] = "id_colonia = ?";
        $tipos .= 'i';
        $valores[] = (int)$_POST['id_colonia'];
    }

    // Coordenadas
    if (isset($_POST['latitud']) && !empty($_POST['latitud'])) {
        $campos_actualizar[] = "latitud = ?";
        $tipos .= 'd';
        $valores[] = (float)$_POST['latitud'];
    }

    if (isset($_POST['longitud']) && !empty($_POST['longitud'])) {
        $campos_actualizar[] = "longitud = ?";
        $tipos .= 'd';
        $valores[] = (float)$_POST['longitud'];
    }

    // Campos de administración (solo admin)
    if ($id_rol == 1) {
        if (isset($_POST['estatus_res'])) {
            $campos_actualizar[] = "estatus_res = ?";
            $tipos .= 'i';
            $valores[] = (int)$_POST['estatus_res'];
        }

        if (isset($_POST['validado_admin'])) {
            $validado = (int)$_POST['validado_admin'];
            $campos_actualizar[] = "validado_admin = ?";
            $tipos .= 'i';
            $valores[] = $validado;

            // Si se aprueba, registrar fecha y admin
            if ($validado == 1) {
                $campos_actualizar[] = "fecha_validacion = NOW()";
                $campos_actualizar[] = "id_admin_validador = ?";
                $tipos .= 'i';
                $valores[] = $id_usuario;
                $campos_actualizar[] = "motivo_rechazo = NULL";
            } else if ($validado == 2) {
                // Si se rechaza, guardar motivo
                if (isset($_POST['motivo_rechazo'])) {
                    $campos_actualizar[] = "motivo_rechazo = ?";
                    $tipos .= 's';
                    $valores[] = trim($_POST['motivo_rechazo']);
                }
            }
        }
    }

    // Agregar fecha de actualización
    $campos_actualizar[] = "fecha_actualizacion = NOW()";

    // Si hay campos para actualizar
    if (!empty($campos_actualizar)) {
        $query = "UPDATE restaurante SET " . implode(', ', $campos_actualizar) . " WHERE id_res = ?";
        $tipos .= 'i';
        $valores[] = $id_res;

        $stmt = mysqli_prepare($conn, $query);
        
        // Bind parameters dinámicamente
        mysqli_stmt_bind_param($stmt, $tipos, ...$valores);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Error al actualizar el restaurante: ' . mysqli_stmt_error($stmt));
        }

        $filas_afectadas = mysqli_stmt_affected_rows($stmt);
    }

    // Manejo de archivos (logo y banner)
    $directorio_uploads = '../UPLOADS/RESTAURANTES/';
    
    // Crear directorio si no existe
    if (!is_dir($directorio_uploads)) {
        mkdir($directorio_uploads, 0755, true);
    }

    // Procesar logo
    if (isset($_FILES['logo_res']) && $_FILES['logo_res']['error'] === 0) {
        $logo_info = procesarImagen($_FILES['logo_res'], $directorio_uploads, 'logo', $id_res);
        if ($logo_info) {
            // Eliminar logo anterior si no es el default
            if ($restaurante_actual['logo_res'] && $restaurante_actual['logo_res'] !== 'default_logo.png') {
                $logo_anterior = $directorio_uploads . basename($restaurante_actual['logo_res']);
                if (file_exists($logo_anterior)) {
                    unlink($logo_anterior);
                }
            }

            // Actualizar en base de datos
            $query_logo = "UPDATE restaurante SET logo_res = ? WHERE id_res = ?";
            $stmt_logo = mysqli_prepare($conn, $query_logo);
            mysqli_stmt_bind_param($stmt_logo, 'si', $logo_info['ruta'], $id_res);
            mysqli_stmt_execute($stmt_logo);
        }
    }

    // Procesar banner
    if (isset($_FILES['banner_res']) && $_FILES['banner_res']['error'] === 0) {
        $banner_info = procesarImagen($_FILES['banner_res'], $directorio_uploads, 'banner', $id_res);
        if ($banner_info) {
            // Eliminar banner anterior si no es el default
            if ($restaurante_actual['banner_res'] && $restaurante_actual['banner_res'] !== 'default_banner.png') {
                $banner_anterior = $directorio_uploads . basename($restaurante_actual['banner_res']);
                if (file_exists($banner_anterior)) {
                    unlink($banner_anterior);
                }
            }

            // Actualizar en base de datos
            $query_banner = "UPDATE restaurante SET banner_res = ? WHERE id_res = ?";
            $stmt_banner = mysqli_prepare($conn, $query_banner);
            mysqli_stmt_bind_param($stmt_banner, 'si', $banner_info['ruta'], $id_res);
            mysqli_stmt_execute($stmt_banner);
        }
    }

    // Confirmar transacción
    mysqli_commit($conn);

    // Enviar notificación por EmailJS si se actualizó correctamente
    if ($filas_afectadas > 0) {
        enviarNotificacionEmailBackend($id_res, $id_usuario, $id_rol, $restaurante_actual);
    }

    // Respuesta exitosa
    $response = [
        'success' => true, 
        'message' => 'Restaurante actualizado correctamente',
        'id_res' => $id_res
    ];
    error_log("Respuesta exitosa: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    // Revertir transacción
    mysqli_rollback($conn);
    
    error_log("Error en actualizar_restaurante.php: " . $e->getMessage());
    
    $response = [
        'success' => false, 
        'message' => 'Error al actualizar el restaurante: ' . $e->getMessage()
    ];
    error_log("Respuesta de error: " . json_encode($response));
    echo json_encode($response);
} finally {
    // Cerrar conexión
    if (isset($conn)) {
        mysqli_close($conn);
    }
}

// Función para procesar imágenes
function procesarImagen($archivo, $directorio, $prefijo, $id_res) {
    // Validar archivo
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($archivo['type'], $allowed_types)) {
        throw new Exception('Tipo de archivo no permitido. Solo se aceptan JPEG, PNG y WebP.');
    }

    if ($archivo['size'] > $max_size) {
        throw new Exception('El archivo es demasiado grande. Máximo 2MB.');
    }

    // Generar nombre único
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = $prefijo . '_' . $id_res . '_' . time() . '.' . $extension;
    $ruta_completa = $directorio . $nombre_archivo;

    // Mover archivo
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        throw new Exception('Error al subir el archivo.');
    }

    // Optimizar imagen si es necesario
    optimizarImagen($ruta_completa, $archivo['type']);

    return [
        'ruta' => 'UPLOADS/RESTAURANTES/' . $nombre_archivo,
        'nombre' => $nombre_archivo
    ];
}

// Función para optimizar imágenes
function optimizarImagen($ruta, $tipo) {
    // Redimensionar si es muy grande
    list($ancho, $alto) = getimagesize($ruta);
    $max_dimension = 1200;

    if ($ancho > $max_dimension || $alto > $max_dimension) {
        // Calcular nuevas dimensiones
        $ratio = min($max_dimension / $ancho, $max_dimension / $alto);
        $nuevo_ancho = round($ancho * $ratio);
        $nuevo_alto = round($alto * $ratio);

        // Crear imagen nueva
        $imagen_nueva = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);

        // Cargar imagen original según tipo
        switch ($tipo) {
            case 'image/jpeg':
                $imagen_original = imagecreatefromjpeg($ruta);
                break;
            case 'image/png':
                $imagen_original = imagecreatefrompng($ruta);
                imagealphablending($imagen_nueva, false);
                imagesavealpha($imagen_nueva, true);
                break;
            case 'image/webp':
                $imagen_original = imagecreatefromwebp($ruta);
                break;
            default:
                return; // No optimizar si no es un tipo soportado
        }

        // Redimensionar y guardar
        imagecopyresampled($imagen_nueva, $imagen_original, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);

        // Guardar imagen optimizada
        switch ($tipo) {
            case 'image/jpeg':
                imagejpeg($imagen_nueva, $ruta, 85);
                break;
            case 'image/png':
                imagepng($imagen_nueva, $ruta, 8);
                break;
            case 'image/webp':
                imagewebp($imagen_nueva, $ruta, 85);
                break;
        }

        // Liberar memoria
        imagedestroy($imagen_original);
        imagedestroy($imagen_nueva);
    }
}

// Función para enviar notificación por EmailJS (Backend)
function enviarNotificacionEmailBackend($id_res, $id_usuario, $id_rol, $restaurante_actual) {
    try {
        // Obtener información actualizada del restaurante
        global $conn;
        
        $query_actualizado = "SELECT r.*, u.username_usu, u.correo_usu,
                                   p.nombre_per, p.apellidos_per, p.correo_per
                            FROM restaurante r
                            JOIN usuarios u ON r.id_usu = u.id_usu
                            LEFT JOIN perfiles p ON u.id_usu = p.id_usu
                            WHERE r.id_res = ?";
        
        $stmt_actualizado = mysqli_prepare($conn, $query_actualizado);
        mysqli_stmt_bind_param($stmt_actualizado, 'i', $id_res);
        mysqli_stmt_execute($stmt_actualizado);
        $resultado_actualizado = mysqli_stmt_get_result($stmt_actualizado);
        $restaurante_actualizado = mysqli_fetch_assoc($resultado_actualizado);

        if (!$restaurante_actualizado) {
            error_log("No se pudo obtener información actualizada del restaurante ID: $id_res");
            return false;
        }

        // Determinar destinatario
        $esAdmin = ($id_rol == 1);
        
        if ($esAdmin) {
            // Notificar al dueño del restaurante
            $email_destinatario = $restaurante_actualizado['correo_per'] ?: $restaurante_actualizado['correo_usu'];
            $nombre_destinatario = $restaurante_actualizado['nombre_per'] ?: $restaurante_actualizado['username_usu'];
        } else {
            // Notificar al admin (si el dueño actualizó)
            $email_destinatario = 'admin@saludjuarez.com'; // Email del administrador
            $nombre_destinatario = 'Administrador del Sistema';
        }

        // Preparar datos para EmailJS
        $email_data = [
            'service_id' => 'service_kchdp9f',
            'template_id' => 'template_tnrferf',
            'public_key' => 'VkhEAneBLv5m5rOgO',
            'template_params' => [
                'to_name' => $nombre_destinatario,
                'to_email' => $email_destinatario,
                'restaurant_name' => $restaurante_actualizado['nombre_res'],
                'restaurant_id' => $id_res,
                'updated_by' => $restaurante_actualizado['username_usu'],
                'update_date' => date('d/m/Y H:i'),
                'action_url' => (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . "/restaurantes.php?id=$id_res"
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
            'accessToken' => '' // EmailJS Browser Key se usa en frontend, aquí iría server key si tuvieras
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
            error_log("Notificación por EmailJS enviada correctamente para restaurante ID: $id_res");
            return true;
        } else {
            error_log("Error al enviar notificación EmailJS. HTTP Code: $http_code, Response: $response");
            return false;
        }

    } catch (Exception $e) {
        error_log("Excepción en enviarNotificacionEmailBackend: " . $e->getMessage());
        return false;
    }
}
?>
