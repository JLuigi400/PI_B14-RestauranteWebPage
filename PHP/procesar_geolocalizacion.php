<?php
/**
 * Procesamiento de Geolocalización - Salud Juárez
 * Manejo de coordenadas, direcciones y proveedores cercanos
 */

session_start();
include 'db_config.php';

header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['id_usu'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id_usuario = $_SESSION['id_usu'];
$id_rol = $_SESSION['id_rol'];

// Función principal de enrutamiento
$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'actualizar_coordenadas_restaurante':
        actualizarCoordenadasRestaurante();
        break;
        
    case 'obtener_restaurantes_mapa':
        obtenerRestaurantesMapa();
        break;
        
    case 'obtener_proveedores_cercanos':
        obtenerProveedoresCercanos();
        break;
        
    case 'buscar_direccion':
        buscarDireccion();
        break;
        
    case 'obtener_proveedores_categoria':
        obtenerProveedoresCategoria();
        break;
        
    case 'crear_solicitud_restock':
        crearSolicitudRestock();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

/**
 * Actualizar coordenadas del restaurante (solo dueños)
 */
function actualizarCoordenadasRestaurante() {
    global $conn, $id_usuario, $id_rol;
    
    if ($id_rol != 2) {
        echo json_encode(['success' => false, 'message' => 'Solo los dueños pueden actualizar coordenadas']);
        return;
    }
    
    $id_res = intval($_POST['id_res'] ?? 0);
    $latitud = floatval($_POST['latitud'] ?? 0);
    $longitud = floatval($_POST['longitud'] ?? 0);
    $direccion = $_POST['direccion'] ?? '';
    
    // Validar que el restaurante pertenezca al usuario
    $stmt_verificar = $conn->prepare("
        SELECT id_res FROM restaurante 
        WHERE id_res = ? AND id_usu = ?
    ");
    $stmt_verificar->bind_param("ii", $id_res, $id_usuario);
    $stmt_verificar->execute();
    
    if ($stmt_verificar->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No autorizado para este restaurante']);
        return;
    }
    
    // Validar coordenadas (rango aproximado de Ciudad Juárez)
    if ($latitud < 31.0 || $latitud > 32.0 || $longitud < -107.0 || $longitud > -106.0) {
        echo json_encode(['success' => false, 'message' => 'Coordenadas fuera del rango de Ciudad Juárez']);
        return;
    }
    
    // Actualizar coordenadas
    $stmt_actualizar = $conn->prepare("
        UPDATE restaurante 
        SET latitud = ?, longitud = ?, direccion_res = COALESCE(?, direccion_res)
        WHERE id_res = ?
    ");
    $stmt_actualizar->bind_param("ddsi", $latitud, $longitud, $direccion, $id_res);
    
    if ($stmt_actualizar->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Coordenadas actualizadas correctamente',
            'coordenadas' => ['latitud' => $latitud, 'longitud' => $longitud]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar coordenadas']);
    }
}

/**
 * Obtener restaurantes para mostrar en el mapa
 */
function obtenerRestaurantesMapa() {
    global $conn, $id_rol;
    
    $sql = "
        SELECT 
            r.*,
            u.username_usu as propietario,
            COUNT(DISTINCT p.id_pla) as total_platillos,
            COUNT(DISTINCT CASE WHEN p.visible = 1 THEN p.id_pla END) as platillos_visibles,
            CASE 
                WHEN r.latitud IS NULL OR r.longitud IS NULL THEN 0
                ELSE 1
            END as tiene_ubicacion,
            -- Calcular certificación basada en promedio de calorías y transparencia
            CASE 
                WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 500 AND COUNT(DISTINCT i.es_ingrediente_secreto) = 0 THEN 'oro'
                WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 800 AND COUNT(DISTINCT i.es_ingrediente_secreto) <= 2 THEN 'plata'
                ELSE 'bronce'
            END as certificacion
        FROM restaurante r
        LEFT JOIN usuarios u ON r.id_usu = u.id_usu
        LEFT JOIN platillos p ON r.id_res = p.id_res
        LEFT JOIN platillo_ingredientes pi ON p.id_pla = pi.id_pla
        LEFT JOIN inventario i ON pi.id_inv = i.id_inv
        WHERE r.estatus_res = 1
        GROUP BY r.id_res
        HAVING tiene_ubicacion = 1
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $restaurantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'restaurantes' => $restaurantes]);
}

/**
 * Obtener proveedores cercanos a un restaurante
 */
function obtenerProveedoresCercanos() {
    global $conn, $id_usuario, $id_rol;
    
    if ($id_rol != 2) {
        echo json_encode(['success' => false, 'message' => 'Solo los dueños pueden ver proveedores cercanos']);
        return;
    }
    
    $id_res = intval($_GET['id_res'] ?? 0);
    $categoria = $_GET['categoria'] ?? '';
    $radio_km = floatval($_GET['radio_km'] ?? 20);
    
    // Obtener coordenadas del restaurante
    $stmt_restaurante = $conn->prepare("
        SELECT latitud, longitud FROM restaurante 
        WHERE id_res = ? AND id_usu = ? AND latitud IS NOT NULL AND longitud IS NOT NULL
    ");
    $stmt_restaurante->bind_param("ii", $id_res, $id_usuario);
    $stmt_restaurante->execute();
    $restaurante = $stmt_restaurante->get_result()->fetch_assoc();
    
    if (!$restaurante) {
        echo json_encode(['success' => false, 'message' => 'Restaurante no encontrado o sin coordenadas']);
        return;
    }
    
    // Obtener proveedores cercanos usando fórmula de Haversine
    $sql = "
        SELECT 
            p.*,
            (6371 * acos(
                cos(radians(?)) * cos(radians(p.latitud)) * 
                cos(radians(p.longitud) - radians(?)) + 
                sin(radians(?)) * sin(radians(p.latitud))
            )) AS distancia_km
        FROM proveedores_insumos p
        WHERE p.estatus_proveedor = 1
        " . ($categoria ? "AND p.categoria_insumo = ?" : "") . "
        HAVING distancia_km <= ?
        ORDER BY distancia_km ASC
    ";
    
    $stmt = $conn->prepare($sql);
    
    if ($categoria) {
        $stmt->bind_param("dddsd", $restaurante['latitud'], $restaurante['longitud'], $restaurante['latitud'], $categoria, $radio_km);
    } else {
        $stmt->bind_param("ddsd", $restaurante['latitud'], $restaurante['longitud'], $restaurante['latitud'], $radio_km);
    }
    
    $stmt->execute();
    $proveedores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'proveedores' => $proveedores,
        'restaurante' => [
            'latitud' => $restaurante['latitud'],
            'longitud' => $restaurante['longitud']
        ]
    ]);
}

/**
 * Buscar dirección usando Nominatim API
 */
function buscarDireccion() {
    $direccion = $_GET['direccion'] ?? '';
    
    if (empty($direccion)) {
        echo json_encode(['success' => false, 'message' => 'Dirección vacía']);
        return;
    }
    
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($direccion) . "&limit=5&countrycodes=mx";
    
    $opciones = [
        'http' => [
            'header' => "User-Agent: SaludJuarez/1.0\r\n"
        ]
    ];
    
    $contexto = stream_context_create($opciones);
    $respuesta = file_get_contents($url, false, $contexto);
    
    if ($respuesta === false) {
        echo json_encode(['success' => false, 'message' => 'Error al buscar dirección']);
        return;
    }
    
    $resultados = json_decode($respuesta, true);
    
    if (empty($resultados)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron resultados']);
        return;
    }
    
    $sugerencias = array_map(function($resultado) {
        return [
            'display_name' => $resultado['display_name'],
            'latitud' => floatval($resultado['lat']),
            'longitud' => floatval($resultado['lon']),
            'importancia' => $resultado['importance'] ?? 0
        ];
    }, $resultados);
    
    // Ordenar por importancia
    usort($sugerencias, function($a, $b) {
        return $b['importancia'] <=> $a['importancia'];
    });
    
    echo json_encode(['success' => true, 'sugerencias' => $sugerencias]);
}

/**
 * Obtener proveedores por categoría
 */
function obtenerProveedoresCategoria() {
    global $conn;
    
    $categoria = $_GET['categoria'] ?? '';
    
    $sql = "
        SELECT * FROM proveedores_insumos 
        WHERE estatus_proveedor = 1
    ";
    
    if (!empty($categoria)) {
        $sql .= " AND categoria_insumo = ?";
    }
    
    $sql .= " ORDER BY nombre_tienda ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($categoria)) {
        $stmt->bind_param("s", $categoria);
    }
    
    $stmt->execute();
    $proveedores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'proveedores' => $proveedores]);
}

/**
 * Crear solicitud de re-stock
 */
function crearSolicitudRestock() {
    global $conn, $id_usuario, $id_rol;
    
    if ($id_rol != 2) {
        echo json_encode(['success' => false, 'message' => 'Solo los dueños pueden crear solicitudes']);
        return;
    }
    
    $id_proveedor = intval($_POST['id_proveedor'] ?? 0);
    $id_inv = intval($_POST['id_inv'] ?? 0);
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    $unidad = $_POST['unidad'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    
    // Validar datos
    if ($id_proveedor <= 0 || $id_inv <= 0 || $cantidad <= 0 || empty($unidad)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos']);
        return;
    }
    
    // Obtener restaurante del usuario
    $stmt_restaurante = $conn->prepare("
        SELECT id_res FROM restaurante WHERE id_usu = ?
    ");
    $stmt_restaurante->bind_param("i", $id_usuario);
    $stmt_restaurante->execute();
    $restaurante = $stmt_restaurante->get_result()->fetch_assoc();
    
    if (!$restaurante) {
        echo json_encode(['success' => false, 'message' => 'No se encontró el restaurante del usuario']);
        return;
    }
    
    // Verificar que el ingrediente pertenezca al restaurante
    $stmt_ingrediente = $conn->prepare("
        SELECT nombre_insumo FROM inventario 
        WHERE id_inv = ? AND id_res = ?
    ");
    $stmt_ingrediente->bind_param("ii", $id_inv, $restaurante['id_res']);
    $stmt_ingrediente->execute();
    $ingrediente = $stmt_ingrediente->get_result()->fetch_assoc();
    
    if (!$ingrediente) {
        echo json_encode(['success' => false, 'message' => 'El ingrediente no pertenece a tu restaurante']);
        return;
    }
    
    // Crear solicitud
    $stmt_solicitud = $conn->prepare("
        INSERT INTO solicitudes_restock 
        (id_res, id_proveedor, id_inv, cantidad_solicitada, unidad_medida, urgencia, observaciones)
        VALUES (?, ?, ?, ?, ?, 'media', ?)
    ");
    
    $stmt_solicitud->bind_param("iiidss", 
        $restaurante['id_res'], 
        $id_proveedor, 
        $id_inv, 
        $cantidad, 
        $unidad, 
        $observaciones
    );
    
    if ($stmt_solicitud->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Solicitud de re-stock creada correctamente',
            'id_solicitud' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear solicitud']);
    }
}
?>
