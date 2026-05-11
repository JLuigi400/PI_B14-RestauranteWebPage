<?php
// Console log PHP para diagnóstico
error_log("DEBUG: Iniciando proveedores_cercanos.php");
echo "<!-- DEBUG PHP: Iniciando archivo -->\n";

session_start();
echo "<!-- DEBUG PHP: Sesión iniciada -->\n";

include '../PHP/db_config.php';
echo "<!-- DEBUG PHP: db_config cargado -->\n";

if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    echo "<!-- DEBUG PHP: Usuario no autorizado, redirigiendo -->\n";
    header("Location: ../login.php");
    exit();
}

echo "<!-- DEBUG PHP: Usuario autorizado -->\n";
$id_usuario = $_SESSION['id_usu'];
echo "<!-- DEBUG PHP: ID Usuario: $id_usuario -->\n";

// Obtener restaurantes
try {
    $stmt_restaurantes = $conn->prepare("SELECT id_res, nombre_res, latitud, longitud FROM restaurante WHERE id_usu = ? AND estatus_res = 1");
    $stmt_restaurantes->bind_param("i", $id_usuario);
    $stmt_restaurantes->execute();
    $restaurantes = $stmt_restaurantes->get_result();
    echo "<!-- DEBUG PHP: Restaurantes cargados: " . $restaurantes->num_rows . " -->\n";
} catch (Exception $e) {
    echo "<!-- ERROR PHP: " . $e->getMessage() . " -->\n";
}

// Obtener categorías
try {
    $stmt_categorias = $conn->prepare("SELECT DISTINCT tipo_proveedor FROM proveedores WHERE estado_visibilidad = 'activo'");
    $stmt_categorias->execute();
    $categorias = $stmt_categorias->get_result();
    echo "<!-- DEBUG PHP: Categorías cargadas: " . $categorias->num_rows . " -->\n";
} catch (Exception $e) {
    echo "<!-- ERROR PHP: " . $e->getMessage() . " -->\n";
}

// Obtener proveedores
try {
    $stmt_proveedores = $conn->prepare("SELECT id_proveedor, nombre_empresa, tipo_proveedor, latitud_proveedor, longitud_proveedor, telefono, direccion_empresa FROM proveedores WHERE estado_visibilidad = 'activo'");
    $stmt_proveedores->execute();
    $proveedores_reales = $stmt_proveedores->get_result();
    echo "<!-- DEBUG PHP: Proveedores cargados: " . $proveedores_reales->num_rows . " -->\n";
} catch (Exception $e) {
    echo "<!-- ERROR PHP: " . $e->getMessage() . " -->\n";
}

// Obtener alertas
try {
    $stmt_stock_bajo = $conn->prepare("SELECT i.*, r.nombre_res FROM inventario i JOIN restaurante r ON i.id_res = r.id_res WHERE r.id_usu = ? AND i.stock_inv <= 10 AND i.stock_inv > 0");
    $stmt_stock_bajo->bind_param("i", $id_usuario);
    $stmt_stock_bajo->execute();
    $ingredientes_bajos = $stmt_stock_bajo->get_result();
    echo "<!-- DEBUG PHP: Alertas cargadas: " . $ingredientes_bajos->num_rows . " -->\n";
} catch (Exception $e) {
    echo "<!-- ERROR PHP: " . $e->getMessage() . " -->\n";
}

echo "<!-- DEBUG PHP: Comenzando HTML -->\n";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proveedores Cercanos | Salud Juárez</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* Console log CSS */
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .content-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .mapa-section { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .mapa-header { background: linear-gradient(135deg, #3498db, #5dade2); color: white; padding: 20px; text-align: center; }
        #mapa { width: 100%; height: 600px; border: 2px solid #ddd; }
        .info-section { display: flex; flex-direction: column; gap: 20px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { padding: 20px; border-bottom: 1px solid #ecf0f1; }
        .card-body { padding: 20px; }
        select { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; background: white; margin-bottom: 20px; }
        .alertas-section { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .alerta-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #ffeaa7; }
        .btn-solicitud { background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .proveedor-item { padding: 15px; border: 1px solid #ecf0f1; border-radius: 8px; margin-bottom: 15px; }
        .empty-state { text-align: center; padding: 40px 20px; color: #666; }
        .nav-link { display: inline-block; background: #6c757d; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <script>console.log('DEBUG JS: Body cargado');</script>
    
    <div class="container">
        <script>console.log('DEBUG JS: Container cargado');</script>
        
        <div class="header">
            <script>console.log('DEBUG JS: Header cargado');</script>
            <h1>📦 Proveedores Cercanos</h1>
            <p>Encuentra proveedores de insumos cerca de tus restaurantes</p>
            <a href="mis_restaurantes.php" class="nav-link">← Mis Restaurantes</a>
        </div>
        
        <?php if ($ingredientes_bajos->num_rows > 0): ?>
            <script>console.log('DEBUG JS: Hay alertas');</script>
            <div class="alertas-section">
                <h4>⚠️ Alertas de Stock Bajo</h4>
                <?php while ($ingrediente = $ingredientes_bajos->fetch_assoc()): ?>
                    <div class="alerta-item">
                        <div>
                            <strong><?php echo htmlspecialchars($ingrediente['nombre_insumo']); ?></strong><br>
                            <small><?php echo htmlspecialchars($ingrediente['nombre_res']); ?></small>
                        </div>
                        <div>
                            <?php echo $ingrediente['stock_inv']; ?> <?php echo htmlspecialchars($ingrediente['medida_inv']); ?>
                            <button class="btn-solicitud" onclick="alert('Solicitud: <?php echo $ingrediente['nombre_insumo']; ?>')">Solicitar</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        
        <div class="content-layout">
            <script>console.log('DEBUG JS: Content layout cargado');</script>
            
            <div class="mapa-section">
                <script>console.log('DEBUG JS: Mapa section cargado');</script>
                <div class="mapa-header">
                    <h2>🗺️ Mapa de Proveedores</h2>
                    <p>Proveedores y restaurantes en tu área</p>
                </div>
                <div id="mapa"></div>
                <script>console.log('DEBUG JS: Div mapa cargado');</script>
            </div>
            
            <div class="info-section">
                <script>console.log('DEBUG JS: Info section cargado');</script>
                
                <div class="card">
                    <div class="card-header">
                        <h3>🏪 Seleccionar Restaurante</h3>
                    </div>
                    <div class="card-body">
                        <select id="restauranteSelect">
                            <option value="">Selecciona un restaurante...</option>
                            <?php 
                            if ($restaurantes->num_rows > 0) {
                                $restaurantes->data_seek(0);
                                while ($restaurante = $restaurantes->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $restaurante['id_res']; ?>" 
                                        data-lat="<?php echo $restaurante['latitud']; ?>" 
                                        data-lng="<?php echo $restaurante['longitud']; ?>">
                                    <?php echo htmlspecialchars($restaurante['nombre_res']); ?>
                                </option>
                            <?php 
                                endwhile; 
                            }
                            ?>
                        </select>
                        
                        <select id="categoriaSelect">
                            <option value="todos">Todas las categorías</option>
                            <?php 
                            if ($categorias->num_rows > 0) {
                                $categorias->data_seek(0);
                                while ($categoria = $categorias->fetch_assoc()): 
                            ?>
                                <option value="<?php echo htmlspecialchars($categoria['tipo_proveedor']); ?>">
                                    <?php echo htmlspecialchars($categoria['tipo_proveedor']); ?>
                                </option>
                            <?php 
                                endwhile; 
                            }
                            ?>
                        </select>
                        
                        <div id="proveedores_list">
                            <div class="empty-state">
                                <h4>📍 Selecciona un restaurante</h4>
                                <p>Para ver los proveedores cercanos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        console.log('DEBUG JS: Scripts externos cargados');
        
        const restaurantesData = <?php 
            $restaurantes_array = [];
            if ($restaurantes->num_rows > 0) {
                $restaurantes->data_seek(0);
                while ($restaurante = $restaurantes->fetch_assoc()) {
                    $restaurantes_array[] = [
                        'id' => $restaurante['id_res'],
                        'nombre' => $restaurante['nombre_res'],
                        'lat' => $restaurante['latitud'],
                        'lng' => $restaurante['longitud']
                    ];
                }
            }
            echo json_encode($restaurantes_array); 
        ?>;
        
        console.log('DEBUG JS: restaurantesData:', restaurantesData);
        
        const proveedoresData = <?php 
            $proveedores_array = [];
            if (isset($proveedores_reales) && $proveedores_reales->num_rows > 0) {
                $proveedores_reales->data_seek(0);
                while ($proveedor = $proveedores_reales->fetch_assoc()) {
                    $proveedores_array[] = [
                        'id' => $proveedor['id_proveedor'],
                        'nombre' => $proveedor['nombre_empresa'],
                        'categoria' => $proveedor['tipo_proveedor'],
                        'lat' => $proveedor['latitud_proveedor'],
                        'lng' => $proveedor['longitud_proveedor'],
                        'telefono' => $proveedor['telefono'],
                        'direccion' => $proveedor['direccion_empresa']
                    ];
                }
            }
            echo json_encode($proveedores_array); 
        ?>;
        
        console.log('DEBUG JS: proveedoresData:', proveedoresData);
        
        let mapa = null;
        let restauranteActual = null;
        
        console.log('DEBUG JS: Variables globales definidas');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DEBUG JS: DOMContentLoaded disparado');
            inicializarMapa();
            setupEventListeners();
        });
        
        function inicializarMapa() {
            console.log('DEBUG JS: Inicializando mapa');
            try {
                mapa = L.map('mapa').setView([31.690363, -106.424548], 11);
                console.log('DEBUG JS: Mapa creado');
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(mapa);
                console.log('DEBUG JS: Tiles agregados');
                
                restaurantesData.forEach(restaurante => {
                    if (restaurante.lat && restaurante.lng) {
                        L.marker([restaurante.lat, restaurante.lng])
                            .bindPopup('<strong>' + restaurante.nombre + '</strong><br>Restaurante')
                            .addTo(mapa);
                    }
                });
                console.log('DEBUG JS: Marcadores de restaurantes agregados');
                
                proveedoresData.forEach(proveedor => {
                    if (proveedor.lat && proveedor.lng) {
                        L.marker([proveedor.lat, proveedor.lng])
                            .bindPopup('<strong>' + proveedor.nombre + '</strong><br>' + proveedor.categoria)
                            .addTo(mapa);
                    }
                });
                console.log('DEBUG JS: Marcadores de proveedores agregados');
                
            } catch (error) {
                console.error('DEBUG JS: Error inicializando mapa:', error);
            }
        }
        
        function setupEventListeners() {
            console.log('DEBUG JS: Configurando event listeners');
            const select = document.getElementById('restauranteSelect');
            if (select) {
                select.addEventListener('change', function() {
                    console.log('DEBUG JS: Restaurante seleccionado:', this.value);
                    const option = this.options[this.selectedIndex];
                    if (option.value) {
                        const lat = parseFloat(option.dataset.lat);
                        const lng = parseFloat(option.dataset.lng);
                        if (lat && lng) {
                            mapa.setView([lat, lng], 13);
                            mostrarProveedoresCercanos(lat, lng);
                        }
                    }
                });
                console.log('DEBUG JS: Event listener configurado');
            } else {
                console.error('DEBUG JS: No se encontró restauranteSelect');
            }
        }
        
        function mostrarProveedoresCercanos(lat, lng) {
            console.log('DEBUG JS: Mostrando proveedores cercanos');
            const lista = document.getElementById('proveedores_list');
            if (lista) {
                lista.innerHTML = proveedoresData.map(p => {
                    const distancia = calcularDistancia([lat, lng], [p.lat, p.lng]);
                    return `
                        <div class="proveedor-item">
                            <strong>${p.nombre}</strong><br>
                            <small>${p.categoria}</small><br>
                            📍 ${(distancia / 1000).toFixed(1)} km<br>
                            📞 ${p.telefono || 'N/A'}
                        </div>
                    `;
                }).join('');
                console.log('DEBUG JS: Lista de proveedores actualizada');
            } else {
                console.error('DEBUG JS: No se encontró proveedores_list');
            }
        }
        
        function calcularDistancia(coord1, coord2) {
            const R = 6371000;
            const lat1 = coord1[0] * Math.PI / 180;
            const lat2 = coord2[0] * Math.PI / 180;
            const deltaLat = (coord2[0] - coord1[0]) * Math.PI / 180;
            const deltaLon = (coord2[1] - coord1[1]) * Math.PI / 180;
            const a = Math.sin(deltaLat/2) * Math.sin(deltaLat/2) +
                      Math.cos(lat1) * Math.cos(lat2) *
                      Math.sin(deltaLon/2) * Math.sin(deltaLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        console.log('DEBUG JS: Funciones definidas');
    </script>
</body>
</html>
<?php
echo "<!-- DEBUG PHP: Fin del archivo -->\n";
error_log("DEBUG: Fin de proveedores_cercanos.php");
?>
