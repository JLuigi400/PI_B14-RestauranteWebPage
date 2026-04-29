<?php
// Backend para crear restaurantes
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
$id_usuario = $_SESSION['id_usu'];
$nombre_res = trim($_POST['nombre_res'] ?? '');
$direccion_res = trim($_POST['direccion_res'] ?? '');
$sector_res = trim($_POST['sector_res'] ?? '');
$telefono_res = trim($_POST['telefono_res'] ?? '');
$url_web = trim($_POST['url_web'] ?? '');
$descripcion_res = trim($_POST['descripcion_res'] ?? '');
$latitud = !empty($_POST['latitud']) ? floatval($_POST['latitud']) : null;
$longitud = !empty($_POST['longitud']) ? floatval($_POST['longitud']) : null;
$id_colonia = $_POST['id_colonia'] ?? '';

error_log("Datos recibidos en crear_restaurante.php: " . json_encode([
    'id_usuario' => $id_usuario,
    'nombre_res' => $nombre_res,
    'direccion_res' => $direccion_res,
    'sector_res' => $sector_res,
    'telefono_res' => $telefono_res,
    'url_web' => $url_web,
    'descripcion_res' => $descripcion_res,
    'latitud' => $latitud,
    'longitud' => $longitud,
    'id_colonia' => $id_colonia
]));

// Validar campos requeridos
if (empty($nombre_res) || empty($direccion_res) || empty($id_colonia)) {
    echo json_encode(['success' => false, 'message' => 'Por favor completa todos los campos requeridos']);
    exit;
}

// Validar coordenadas
if (!$latitud || !$longitud || $latitud < -90 || $latitud > 90 || $longitud < -180 || $longitud > 180) {
    echo json_encode(['success' => false, 'message' => 'Por favor selecciona una ubicación válida en el mapa']);
    exit;
}

try {
    // Iniciar transacción
    mysqli_begin_transaction($conn);

    // Manejar nueva colonia si es necesario
    if ($id_colonia === 'otro') {
        $nueva_colonia = trim($_POST['nueva_colonia'] ?? '');
        $nueva_colonia_ciudad = trim($_POST['nueva_colonia_ciudad'] ?? '');
        $nueva_colonia_estado = trim($_POST['nueva_colonia_estado'] ?? '');
        $nueva_colonia_pais = trim($_POST['nueva_colonia_pais'] ?? '');
        $nueva_colonia_cp = trim($_POST['nueva_colonia_cp'] ?? '');
        $nueva_colonia_lat = !empty($_POST['nueva_colonia_lat']) ? floatval($_POST['nueva_colonia_lat']) : null;
        $nueva_colonia_lng = !empty($_POST['nueva_colonia_lng']) ? floatval($_POST['nueva_colonia_lng']) : null;
        
        // Validar campos requeridos
        if (empty($nueva_colonia) || empty($nueva_colonia_ciudad) || empty($nueva_colonia_estado) || empty($nueva_colonia_pais)) {
            throw new Exception('El nombre, ciudad, estado y país de la nueva colonia son requeridos');
        }

        // Obtener el siguiente ID disponible para la nueva colonia
        $query_max_id = "SELECT MAX(id_colonia) as max_id FROM colonias";
        $stmt_max = mysqli_prepare($conn, $query_max_id);
        mysqli_stmt_execute($stmt_max);
        $result_max = mysqli_stmt_get_result($stmt_max);
        $row_max = mysqli_fetch_assoc($result_max);
        $nuevo_id_colonia = ($row_max['max_id'] ?? 0) + 1;

        // Insertar nueva colonia con todos los campos
        $query_colonia = "INSERT INTO colonias (id_colonia, nombre_colonia, ciudad, estado, pais, codigo_postal, latitud, longitud, estatus_colonia) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt_colonia = mysqli_prepare($conn, $query_colonia);
        mysqli_stmt_bind_param($stmt_colonia, 'issssidd', 
            $nuevo_id_colonia, 
            $nueva_colonia, 
            $nueva_colonia_ciudad, 
            $nueva_colonia_estado, 
            $nueva_colonia_pais, 
            $nueva_colonia_cp, 
            $nueva_colonia_lat, 
            $nueva_colonia_lng
        );
        
        if (!mysqli_stmt_execute($stmt_colonia)) {
            throw new Exception('Error al crear la nueva colonia: ' . mysqli_stmt_error($stmt_colonia));
        }
        
        error_log("Nueva colonia creada: ID=$nuevo_id_colonia, Nombre=$nueva_colonia, Ciudad=$nueva_colonia_ciudad, Estado=$nueva_colonia_estado, País=$nueva_colonia_pais");
        
        $id_colonia = $nuevo_id_colonia;
    }

    // Insertar restaurante
    $query_restaurante = "INSERT INTO restaurante (
        id_usu, nombre_res, direccion_res, sector_res, telefono_res, url_web, 
        descripcion_res, latitud, longitud, id_colonia, logo_res, banner_res,
        estatus_res, validado_admin, fecha_registro
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'default_logo.png', 'default_banner.png', 1, 0, NOW())";
    
    $stmt_restaurante = mysqli_prepare($conn, $query_restaurante);
    mysqli_stmt_bind_param($stmt_restaurante, 'issssssddi', 
        $id_usuario, $nombre_res, $direccion_res, $sector_res, $telefono_res, 
        $url_web, $descripcion_res, $latitud, $longitud, $id_colonia
    );
    
    if (!mysqli_stmt_execute($stmt_restaurante)) {
        throw new Exception('Error al crear el restaurante: ' . mysqli_stmt_error($stmt_restaurante));
    }
    
    $id_res = mysqli_insert_id($conn);

    // Manejo de archivos (logo y banner)
    $directorio_uploads = '../UPLOADS/RESTAURANTES/';
    
    // Crear directorio si no existe
    if (!is_dir($directorio_uploads)) {
        mkdir($directorio_uploads, 0755, true);
    }

    // Procesar logo si se subió
    if (isset($_FILES['logo_res']) && $_FILES['logo_res']['error'] === 0) {
        $logo_info = procesarImagen($_FILES['logo_res'], $directorio_uploads, 'logo', $id_res);
        if ($logo_info) {
            $query_logo = "UPDATE restaurante SET logo_res = ? WHERE id_res = ?";
            $stmt_logo = mysqli_prepare($conn, $query_logo);
            mysqli_stmt_bind_param($stmt_logo, 'si', $logo_info['ruta'], $id_res);
            mysqli_stmt_execute($stmt_logo);
        }
    }

    // Procesar banner si se subió
    if (isset($_FILES['banner_res']) && $_FILES['banner_res']['error'] === 0) {
        $banner_info = procesarImagen($_FILES['banner_res'], $directorio_uploads, 'banner', $id_res);
        if ($banner_info) {
            $query_banner = "UPDATE restaurante SET banner_res = ? WHERE id_res = ?";
            $stmt_banner = mysqli_prepare($conn, $query_banner);
            mysqli_stmt_bind_param($stmt_banner, 'si', $banner_info['ruta'], $id_res);
            mysqli_stmt_execute($stmt_banner);
        }
    }

    // Confirmar transacción
    mysqli_commit($conn);

    // Respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Restaurante creado correctamente',
        'id_res' => $id_res
    ]);

} catch (Exception $e) {
    // Revertir transacción
    mysqli_rollback($conn);
    
    error_log("Error en crear_restaurante.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error al crear el restaurante: ' . $e->getMessage()
    ]);
} finally {
    // Cerrar conexión
    if (isset($conn)) {
        mysqli_close($conn);
    }
}

// Función para procesar imágenes (reutilizada de actualizar_restaurante.php)
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
        $imagen_original = null;
        switch ($tipo) {
            case 'image/jpeg':
            case 'image/jpg':
                $imagen_original = imagecreatefromjpeg($ruta);
                break;
            case 'image/png':
                $imagen_original = imagecreatefrompng($ruta);
                break;
            case 'image/webp':
                $imagen_original = imagecreatefromwebp($ruta);
                break;
        }

        if ($imagen_original) {
            $imagen_nueva = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
            
            // Mantener transparencia para PNG
            if ($tipo === 'image/png') {
                imagealphablending($imagen_nueva, false);
                imagesavealpha($imagen_nueva, true);
                imagefill($imagen_nueva, 0, 0, imagecolorallocatealpha($imagen_nueva, 255, 255, 255, 127));
            }

            imagecopyresampled($imagen_nueva, $imagen_original, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);

            // Guardar imagen optimizada
            switch ($tipo) {
                case 'image/jpeg':
                case 'image/jpg':
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
}
?>
