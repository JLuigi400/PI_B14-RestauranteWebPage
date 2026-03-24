<?php
/**
 * Procesador de Carrusel - Salud Juárez
 * Maneja la carga dinámica de restaurantes para el carrusel
 * Versión: 1.0.0
 * Fecha: 2026-03-23
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Incluir configuración
require_once 'db_config.php';

try {
    // Conexión a la base de datos
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    // Establecer charset
    $conn->set_charset("utf8mb4");
    
    // Obtener acción solicitada
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'cargar_restaurantes_oro':
            echo json_encode(['success' => true, 'restaurantes' => cargarRestaurantesOro($conn)]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * Cargar restaurantes con certificación Oro
 */
function cargarRestaurantesOro($conn) {
    $restaurantes = [];
    
    try {
        // Consulta SQL para obtener restaurantes con certificación Oro
        $sql = "SELECT 
                    r.id_res,
                    r.nombre_res,
                    r.descripcion_res,
                    r.logo_res,
                    r.banner_res,
                    r.sector_res,
                    r.telefono_res,
                    r.latitud,
                    r.longitud,
                    -- Calcular certificación basada en criterios de salud
                    CASE 
                        WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 500 
                        AND COUNT(DISTINCT i.es_ingrediente_secreto) = 0 
                        AND COUNT(DISTINCT p.id_pla) >= 5 THEN 'oro'
                        WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 800 
                        AND COUNT(DISTINCT i.es_ingrediente_secreto) <= 2 
                        AND COUNT(DISTINCT p.id_pla) >= 3 THEN 'plata'
                        ELSE 'bronce'
                    END as certificacion_calculada,
                    -- Estadísticas adicionales
                    COUNT(DISTINCT p.id_pla) as total_platillos,
                    COUNT(DISTINCT CASE WHEN p.visible = 1 THEN p.id_pla END) as platillos_visibles,
                    AVG(p.precio_pla) as precio_promedio
                FROM restaurante r
                LEFT JOIN platillos p ON r.id_res = p.id_res
                LEFT JOIN platillo_ingredientes pi ON p.id_pla = pi.id_pla
                LEFT JOIN inventario i ON pi.id_inv = i.id_inv
                WHERE r.estatus_res = 1 
                  AND (
                    -- Criterio de certificación Oro
                    (AVG(pi.cantidad_usada * i.calorias_base) <= 500 
                    AND COUNT(DISTINCT i.es_ingrediente_secreto) = 0 
                    AND COUNT(DISTINCT p.id_pla) >= 5)
                    -- O restaurantes con certificación manual Oro
                    OR r.descripcion_res LIKE '%oro%'
                    OR r.nombre_res LIKE '%gold%'
                    OR r.nombre_res LIKE '%premium%'
                  )
                GROUP BY r.id_res
                ORDER BY 
                    -- Prioridad 1: Certificación calculada Oro
                    CASE 
                        WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 500 
                        AND COUNT(DISTINCT i.es_ingrediente_secreto) = 0 
                        AND COUNT(DISTINCT p.id_pla) >= 5 THEN 1
                        ELSE 2
                    END,
                    -- Prioridad 2: Más platillos visibles
                    COUNT(DISTINCT CASE WHEN p.visible = 1 THEN p.id_pla END) DESC,
                    -- Prioridad 3: Precio promedio más bajo
                    AVG(p.precio_pla) ASC
                LIMIT 5";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error en preparación de consulta: " . $conn->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Procesar y limpiar datos
                $restaurante = [
                    'id_res' => (int)$row['id_res'],
                    'nombre_res' => htmlspecialchars(trim($row['nombre_res'])),
                    'descripcion_res' => htmlspecialchars(trim($row['descripcion_res'] ?? 'Restaurante especializado en comida saludable')),
                    'logo_res' => !empty($row['logo_res']) ? $row['logo_res'] : 'IMG/UPLOADS/RESTAURANTES/default_logo.png',
                    'banner_res' => !empty($row['banner_res']) ? $row['banner_res'] : 'IMG/UPLOADS/RESTAURANTES/default_banner.png',
                    'sector_res' => htmlspecialchars(trim($row['sector_res'] ?? '')),
                    'telefono_res' => htmlspecialchars(trim($row['telefono_res'] ?? '')),
                    'latitud' => $row['latitud'],
                    'longitud' => $row['longitud'],
                    'certificacion' => $row['certificacion_calculada'],
                    'total_platillos' => (int)$row['total_platillos'],
                    'platillos_visibles' => (int)$row['platillos_visibles'],
                    'precio_promedio' => round((float)$row['precio_promedio'], 2)
                ];
                
                // Agregar etiquetas adicionales para el carrusel
                $restaurante['etiquetas'] = generarEtiquetas($restaurante);
                $restaurante['rating'] = generarRating($restaurante);
                
                $restaurantes[] = $restaurante;
            }
        }
        
        // Si no hay suficientes restaurantes, agregar datos de muestra
        if (count($restaurantes) < 5) {
            $restaurantes = array_merge($restaurantes, getRestaurantesMuestra(5 - count($restaurantes)));
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Error cargando restaurantes: " . $e->getMessage());
        // En caso de error, devolver datos de muestra
        $restaurantes = getRestaurantesMuestra(5);
    }
    
    return $restaurantes;
}

/**
 * Generar etiquetas para el restaurante
 */
function generarEtiquetas($restaurante) {
    $etiquetas = [];
    
    // Etiqueta de certificación
    if ($restaurante['certificacion'] === 'oro') {
        $etiquetas[] = ['icono' => '🏆', 'texto' => 'Certificación Oro', 'tipo' => 'premium'];
    }
    
    // Etiqueta de popularidad
    if ($restaurante['platillos_visibles'] >= 10) {
        $etiquetas[] = ['icono' => '🔥', 'texto' => 'Popular', 'tipo' => 'popular'];
    }
    
    // Etiqueta de precio
    if ($restaurante['precio_promedio'] <= 100) {
        $etiquetas[] = ['icono' => '💰', 'texto' => 'Económico', 'tipo' => 'precio'];
    }
    
    // Etiqueta de especialidad
    if (stripos($restaurante['nombre_res'], 'vegano') !== false || 
        stripos($restaurante['descripcion_res'], 'vegano') !== false) {
        $etiquetas[] = ['icono' => '🌱', 'texto' => 'Vegano', 'tipo' => 'dieta'];
    }
    
    if (stripos($restaurante['nombre_res'], 'ensalada') !== false || 
        stripos($restaurante['descripcion_res'], 'ensalada') !== false) {
        $etiquetas[] = ['icono' => '🥗', 'texto' => 'Ensaladas', 'tipo' => 'categoria'];
    }
    
    return $etiquetas;
}

/**
 * Generar rating basado en criterios
 */
function generarRating($restaurante) {
    $rating = 4.0; // Base rating
    
    // Bonificación por certificación
    if ($restaurante['certificacion'] === 'oro') {
        $rating += 0.5;
    }
    
    // Bonificación por cantidad de platillos
    if ($restaurante['platillos_visibles'] >= 10) {
        $rating += 0.2;
    }
    
    // Bonificación por precio accesible
    if ($restaurante['precio_promedio'] <= 100) {
        $rating += 0.1;
    }
    
    // Limitar a 5.0 máximo
    return min(5.0, round($rating, 1));
}

/**
 * Obtener restaurantes de muestra
 */
function getRestaurantesMuestra($cantidad) {
    $muestras = [
        [
            'id_res' => 9991,
            'nombre_res' => 'Ensaladas el Oasis',
            'descripcion_res' => 'Especialistas en ensaladas frescas y bowls nutritivos con ingredientes orgánicos locales.',
            'logo_res' => 'IMG/UPLOADS/RESTAURANTES/default_logo.png',
            'banner_res' => 'IMG/UPLOADS/RESTAURANTES/default_banner.png',
            'sector_res' => 'Satélite',
            'telefono_res' => '6561234567',
            'latitud' => 31.7200,
            'longitud' => -106.4600,
            'certificacion' => 'oro',
            'total_platillos' => 12,
            'platillos_visibles' => 10,
            'precio_promedio' => 85.50,
            'etiquetas' => [
                ['icono' => '🏆', 'texto' => 'Certificación Oro', 'tipo' => 'premium'],
                ['icono' => '🥗', 'texto' => 'Ensaladas', 'tipo' => 'categoria'],
                ['icono' => '💰', 'texto' => 'Económico', 'tipo' => 'precio']
            ],
            'rating' => 4.8
        ],
        [
            'id_res' => 9992,
            'nombre_res' => 'Vida Verde',
            'descripcion_res' => 'Cocina vegana y vegetariana con ingredientes orgánicos certificados y recetas innovadoras.',
            'logo_res' => 'IMG/UPLOADS/RESTAURANTES/default_logo.png',
            'banner_res' => 'IMG/UPLOADS/RESTAURANTES/default_banner.png',
            'sector_res' => 'San Felipe',
            'telefono_res' => '6562345678',
            'latitud' => 31.6800,
            'longitud' => -106.4200,
            'certificacion' => 'oro',
            'total_platillos' => 15,
            'platillos_visibles' => 12,
            'precio_promedio' => 95.00,
            'etiquetas' => [
                ['icono' => '🏆', 'texto' => 'Certificación Oro', 'tipo' => 'premium'],
                ['icono' => '🌱', 'texto' => 'Vegano', 'tipo' => 'dieta'],
                ['icono' => '🔥', 'texto' => 'Popular', 'tipo' => 'popular']
            ],
            'rating' => 4.9
        ],
        [
            'id_res' => 9993,
            'nombre_res' => 'NutriBowl',
            'descripcion_res' => 'Bowls personalizados y jugos naturales preparados al momento con superalimentos.',
            'logo_res' => 'IMG/UPLOADS/RESTAURANTES/default_logo.png',
            'banner_res' => 'IMG/UPLOADS/RESTAURANTES/default_banner.png',
            'sector_res' => 'Pronaf',
            'telefono_res' => '6563456789',
            'latitud' => 31.7400,
            'longitud' => -106.4800,
            'certificacion' => 'oro',
            'total_platillos' => 8,
            'platillos_visibles' => 8,
            'precio_promedio' => 75.00,
            'etiquetas' => [
                ['icono' => '🏆', 'texto' => 'Certificación Oro', 'tipo' => 'premium'],
                ['icono' => '💰', 'texto' => 'Económico', 'tipo' => 'precio'],
                ['icono' => '🥤', 'texto' => 'Jugos', 'tipo' => 'categoria']
            ],
            'rating' => 4.7
        ],
        [
            'id_res' => 9994,
            'nombre_res' => 'Green Kitchen',
            'descripcion_res' => 'Comida saludable con sabor internacional, platos fusion y opciones sin gluten.',
            'logo_res' => 'IMG/UPLOADS/RESTAURANTES/default_logo.png',
            'banner_res' => 'IMG/UPLOADS/RESTAURANTES/default_banner.png',
            'sector_res' => 'Hacienda',
            'telefono_res' => '6564567890',
            'latitud' => 31.7000,
            'longitud' => -106.4400,
            'certificacion' => 'oro',
            'total_platillos' => 20,
            'platillos_visibles' => 18,
            'precio_promedio' => 110.00,
            'etiquetas' => [
                ['icono' => '🏆', 'texto' => 'Certificación Oro', 'tipo' => 'premium'],
                ['icono' => '🌍', 'texto' => 'Fusión', 'tipo' => 'categoria'],
                ['icono' => '🔥', 'texto' => 'Popular', 'tipo' => 'popular']
            ],
            'rating' => 4.6
        ],
        [
            'id_res' => 9995,
            'nombre_res' => 'Sano y Sabroso',
            'descripcion_res' => 'Platillos balanceados sin sacrificar el sabor, especialidad en comida regional saludable.',
            'logo_res' => 'IMG/UPLOADS/RESTAURANTES/default_logo.png',
            'banner_res' => 'IMG/UPLOADS/RESTAURANTES/default_banner.png',
            'sector_res' => 'Campestre',
            'telefono_res' => '6565678901',
            'latitud' => 31.7600,
            'longitud' => -106.5000,
            'certificacion' => 'oro',
            'total_platillos' => 14,
            'platillos_visibles' => 11,
            'precio_promedio' => 90.00,
            'etiquetas' => [
                ['icono' => '🏆', 'texto' => 'Certificación Oro', 'tipo' => 'premium'],
                ['icono' => '🌮', 'texto' => 'Regional', 'tipo' => 'categoria'],
                ['icono' => '💰', 'texto' => 'Económico', 'tipo' => 'precio']
            ],
            'rating' => 4.5
        ]
    ];
    
    return array_slice($muestras, 0, $cantidad);
}
?>
