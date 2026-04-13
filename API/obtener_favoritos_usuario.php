<?php
/**
 * API para obtener favoritos del usuario logueado
 * Salud Juárez - Sistema de Restaurantes
 * Versión: 1.0.0
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Iniciar sesión y verificar autenticación
session_start();

if (!isset($_SESSION['id_usu'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Inicia sesión para continuar.',
        'favoritos' => []
    ]);
    exit();
}

require_once '../PHP/conexion.php';

$id_usu = $_SESSION['id_usu'];

try {
    // Consulta para obtener favoritos del usuario con información del restaurante
    $sql = "SELECT 
                f.id_res,
                r.nombre_res,
                r.direccion_res,
                r.sector_res,
                r.telefono_res,
                r.latitud,
                r.longitud,
                r.logo_res,
                r.descripcion_res,
                r.validado_admin,
                f.fecha_agregado,
                COALESCE(AVG(pu.calificacion), 0) as rating_promedio,
                COUNT(DISTINCT p.id_pla) as total_platillos
            FROM favoritos f
            INNER JOIN restaurante r ON f.id_res = r.id_res
            LEFT JOIN platillos p ON r.id_res = p.id_res AND p.visible = 1
            LEFT JOIN platillos_usuarios pu ON p.id_pla = pu.id_pla
            WHERE f.id_usu = ? 
            AND r.estatus_res = 1
            GROUP BY f.id_res, r.id_res, f.fecha_agregado
            ORDER BY f.fecha_agregado DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_usu);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }
    
    $favoritos = [];
    
    while ($row = $result->fetch_assoc()) {
        // Limpiar y formatear datos
        $favorito = [
            'id_res' => (int)$row['id_res'],
            'nombre_res' => htmlspecialchars(trim($row['nombre_res'])),
            'direccion_res' => htmlspecialchars(trim($row['direccion_res'])),
            'sector_res' => htmlspecialchars(trim($row['sector_res'])),
            'telefono_res' => $row['telefono_res'] ? htmlspecialchars(trim($row['telefono_res'])) : null,
            'latitud' => $row['latitud'] ? (float)$row['latitud'] : null,
            'longitud' => $row['longitud'] ? (float)$row['longitud'] : null,
            'logo_res' => $row['logo_res'] ? htmlspecialchars(trim($row['logo_res'])) : null,
            'descripcion_res' => $row['descripcion_res'] ? htmlspecialchars(trim($row['descripcion_res'])) : null,
            'validado_admin' => (bool)$row['validado_admin'],
            'fecha_agregado' => $row['fecha_agregado'],
            'rating_promedio' => (float)$row['rating_promedio'],
            'total_platillos' => (int)$row['total_platillos']
        ];
        
        $favoritos[] = $favorito;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'favoritos' => $favoritos,
        'total' => count($favoritos),
        'message' => 'Favoritos obtenidos correctamente'
    ]);
    
} catch (Exception $e) {
    // Error en la ejecución
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los favoritos: ' . $e->getMessage(),
        'favoritos' => []
    ]);
}

$stmt->close();
$conn->close();
?>
