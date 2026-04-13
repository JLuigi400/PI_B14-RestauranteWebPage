<?php
/**
 * API para obtener restaurantes con coordenadas para el mapa
 * Salud Juárez - Sistema de Restaurantes
 * Versión: 1.0.0
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../PHP/conexion.php';

try {
    // Consulta para obtener restaurantes activos con coordenadas
    $sql = "SELECT 
                r.id_res, 
                r.nombre_res, 
                r.direccion_res, 
                r.sector_res, 
                r.telefono_res, 
                r.latitud, 
                r.longitud,
                r.descripcion_res,
                r.logo_res,
                r.validado_admin,
                COALESCE(AVG(pu.calificacion), 0) as rating_promedio,
                COUNT(DISTINCT p.id_pla) as total_platillos
            FROM restaurante r
            LEFT JOIN platillo_ingredientes pi ON r.id_res = pi.id_res
            LEFT JOIN platillos p ON r.id_res = p.id_res AND p.visible = 1
            LEFT JOIN platillos_usuarios pu ON p.id_pla = pu.id_pla
            WHERE r.estatus_res = 1 
            AND r.latitud IS NOT NULL 
            AND r.longitud IS NOT NULL
            GROUP BY r.id_res
            ORDER BY r.nombre_res";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
    $restaurantes = [];
    
    while ($row = $result->fetch_assoc()) {
        // Limpiar y formatear datos
        $restaurante = [
            'id_res' => (int)$row['id_res'],
            'nombre_res' => htmlspecialchars(trim($row['nombre_res'])),
            'direccion_res' => htmlspecialchars(trim($row['direccion_res'])),
            'sector_res' => htmlspecialchars(trim($row['sector_res'])),
            'telefono_res' => $row['telefono_res'] ? htmlspecialchars(trim($row['telefono_res'])) : null,
            'latitud' => (float)$row['latitud'],
            'longitud' => (float)$row['longitud'],
            'descripcion_res' => $row['descripcion_res'] ? htmlspecialchars(trim($row['descripcion_res'])) : null,
            'logo_res' => $row['logo_res'] ? htmlspecialchars(trim($row['logo_res'])) : null,
            'validado_admin' => (bool)$row['validado_admin'],
            'rating_promedio' => (float)$row['rating_promedio'],
            'total_platillos' => (int)$row['total_platillos']
        ];
        
        $restaurantes[] = $restaurante;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'restaurantes' => $restaurantes,
        'total' => count($restaurantes),
        'message' => 'Restaurantes obtenidos correctamente'
    ]);
    
} catch (Exception $e) {
    // Error en la ejecución
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los restaurantes: ' . $e->getMessage(),
        'restaurantes' => []
    ]);
}

$conn->close();
?>
