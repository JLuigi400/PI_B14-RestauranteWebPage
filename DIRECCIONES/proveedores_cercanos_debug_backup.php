<?php
// Versión de diagnóstico para identificar el problema exacto
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "DEBUG: Iniciando archivo...<br>";

try {
    session_start();
    echo "DEBUG: Sesión iniciada correctamente<br>";
} catch (Exception $e) {
    echo "ERROR: No se pudo iniciar sesión: " . $e->getMessage() . "<br>";
    die();
}

try {
    include '../PHP/db_config.php';
    echo "DEBUG: Configuración de BD cargada<br>";
} catch (Exception $e) {
    echo "ERROR: No se pudo cargar configuración: " . $e->getMessage() . "<br>";
    die();
}

// Verificar que sea un dueño de restaurante
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    echo "ERROR: Usuario no autorizado. Redirigiendo...<br>";
    header("Location: ../login.php");
    exit();
}

echo "DEBUG: Usuario autorizado. ID: " . $_SESSION['id_usu'] . "<br>";

$id_usuario = $_SESSION['id_usu'];

try {
    // Test de conexión a BD
    if (!$conn) {
        echo "ERROR: No hay conexión a la base de datos<br>";
        die();
    }
    echo "DEBUG: Conexión a BD exitosa<br>";
    
    // Test de consulta simple
    $stmt_test = $conn->prepare("SELECT 1");
    $stmt_test->execute();
    echo "DEBUG: Consulta de prueba exitosa<br>";
    
} catch (Exception $e) {
    echo "ERROR: Problema con la base de datos: " . $e->getMessage() . "<br>";
    die();
}

// Obtener restaurantes del usuario
try {
    $stmt_restaurantes = $conn->prepare("
        SELECT id_res, nombre_res, latitud, longitud 
        FROM restaurante 
        WHERE id_usu = ? AND estatus_res = 1
        ORDER BY nombre_res ASC
    ");
    $stmt_restaurantes->bind_param("i", $id_usuario);
    $stmt_restaurantes->execute();
    $restaurantes = $stmt_restaurantes->get_result();
    echo "DEBUG: Restaurantes encontrados: " . $restaurantes->num_rows . "<br>";
} catch (Exception $e) {
    echo "ERROR: Error al obtener restaurantes: " . $e->getMessage() . "<br>";
}

// Obtener categorías de proveedores
try {
    $stmt_categorias = $conn->prepare("
        SELECT DISTINCT tipo_proveedor 
        FROM proveedores 
        WHERE estado_visibilidad = 'activo' 
        ORDER BY tipo_proveedor ASC
    ");
    $stmt_categorias->execute();
    $categorias = $stmt_categorias->get_result();
    echo "DEBUG: Categorías encontradas: " . $categorias->num_rows . "<br>";
} catch (Exception $e) {
    echo "ERROR: Error al obtener categorías: " . $e->getMessage() . "<br>";
}

echo "DEBUG: Cargando HTML...<br>";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores Cercanos | Salud Juárez</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        #mapa {
            width: 100%;
            height: 400px;
            background: #e9ecef;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Proveedores Cercanos - DEBUG</h1>
            <p>Este es un archivo de diagnóstico para identificar problemas</p>
        </div>
        
        <div class="section">
            <h3>Selector de Restaurante</h3>
            <select id="restauranteSelect">
                <option value="">Selecciona un restaurante...</option>
                <?php 
                if (isset($restaurantes) && $restaurantes->num_rows > 0) {
                    $restaurantes->data_seek(0);
                    while ($restaurante = $restaurantes->fetch_assoc()): 
                ?>
                    <option value="<?php echo $restaurante['id_res']; ?>">
                        <?php echo htmlspecialchars($restaurante['nombre_res']); ?>
                    </option>
                    <?php 
                    endwhile; 
                } else {
                    echo "<option>No se encontraron restaurantes</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="section">
            <h3>Mapa de Proveedores</h3>
            <div id="mapa">El mapa se cargará aquí...</div>
        </div>
        
        <div class="section">
            <h3>Información de Depuración</h3>
            <p><strong>Restaurantes:</strong> <?php echo isset($restaurantes) ? $restaurantes->num_rows : 0; ?></p>
            <p><strong>Categorías:</strong> <?php echo isset($categorias) ? $categorias->num_rows : 0; ?></p>
            <p><strong>Usuario ID:</strong> <?php echo $id_usuario; ?></p>
        </div>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        console.log('DEBUG: JavaScript cargado');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DEBUG: DOM listo');
            
            // Inicializar mapa simple
            try {
                var mapa = L.map('mapa').setView([31.690363, -106.424548], 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(mapa);
                console.log('DEBUG: Mapa inicializado');
            } catch (error) {
                console.error('ERROR: No se pudo inicializar el mapa:', error);
                document.getElementById('mapa').innerHTML = 'Error al cargar el mapa: ' + error.message;
            }
        });
    </script>
</body>
</html>
