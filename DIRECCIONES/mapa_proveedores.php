<?php
session_start();
include '../PHP/db_config.php';

// Verificar que sea un administrador
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Obtener todos los restaurantes activos
$stmt_restaurantes = $conn->prepare("
    SELECT r.*, u.username_usu as propietario,
           COUNT(DISTINCT p.id_pla) as total_platillos,
           CASE 
               WHEN r.latitud IS NULL OR r.longitud IS NULL THEN 0
               ELSE 1
           END as tiene_ubicacion
    FROM restaurante r
    JOIN usuarios u ON r.id_usu = u.id_usu
    LEFT JOIN platillos p ON r.id_res = p.id_res
    WHERE r.estatus_res = 1
    GROUP BY r.id_res
    ORDER BY r.nombre_res ASC
");
$stmt_restaurantes->execute();
$restaurantes = $stmt_restaurantes->get_result();

// Obtener todos los proveedores
$stmt_proveedores = $conn->prepare("
    SELECT * FROM proveedores_insumos 
    WHERE estatus_proveedor = 1 
    ORDER BY nombre_tienda ASC
");
$stmt_proveedores->execute();
$proveedores = $stmt_proveedores->get_result();

// Obtener estadísticas
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(DISTINCT r.id_res) as total_restaurantes,
        COUNT(DISTINCT CASE WHEN r.latitud IS NOT NULL AND r.longitud IS NOT NULL THEN r.id_res END) as restaurantes_con_ubicacion,
        COUNT(DISTINCT p.id_proveedor) as total_proveedores,
        COUNT(DISTINCT p.categoria_insumo) as categorias_proveedores
    FROM restaurante r
    CROSS JOIN proveedores_insumos p
    WHERE r.estatus_res = 1 AND p.estatus_proveedor = 1
");
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mapa de Proveedores | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        .mapa-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #3498db;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-weight: bold;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        .mapa-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .mapa-header {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .mapa-header h2 {
            margin: 0 0 10px 0;
            font-size: 1.5em;
        }
        
        #mapa {
            width: 100%;
            height: 700px;
            border: 2px solid #ddd;
        }
        
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .panel {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .panel-header {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
            background: #f8f9fa;
        }
        
        .panel-header h3 {
            margin: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .panel-body {
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .legend-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        
        .legend-text {
            font-size: 0.9em;
            color: #2c3e50;
        }
        
        .list-item {
            padding: 12px;
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .list-item:hover {
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }
        
        .list-item-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .list-item-subtitle {
            color: #666;
            font-size: 0.9em;
        }
        
        .list-item-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .badge-restaurante {
            background: #27ae60;
            color: white;
        }
        
        .badge-proveedor {
            background: #3498db;
            color: white;
        }
        
        .badge-sin-ubicacion {
            background: #e74c3c;
            color: white;
        }
        
        .filters-section {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .filter-group {
            margin-bottom: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .toggle-switch {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .toggle-btn {
            flex: 1;
            padding: 8px;
            border: 1px solid #3498db;
            background: white;
            color: #3498db;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            font-size: 0.9em;
            text-align: center;
        }
        
        .toggle-btn.active {
            background: #3498db;
            color: white;
        }
        
        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            
            #mapa {
                height: 500px;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .sidebar {
                grid-template-columns: 1fr;
            }
            
            .header-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include '../PHP/navbar.php'; ?>
    
    <div class="mapa-container">
        <div class="header-section">
            <div>
                <h1>🗺️ Mapa de Proveedores</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Vista general de restaurantes y proveedores del sistema</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">← Volver al Panel</a>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_restaurantes']; ?></div>
                <div class="stat-label">🍽️ Restaurantes Totales</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['restaurantes_con_ubicacion']; ?></div>
                <div class="stat-label">📍 Con Ubicación</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_proveedores']; ?></div>
                <div class="stat-label">📦 Proveedores</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['categorias_proveedores']; ?></div>
                <div class="stat-label">📂 Categorías</div>
            </div>
        </div>
        
        <!-- Contenido Principal -->
        <div class="main-content">
            <!-- Mapa -->
            <div class="mapa-section">
                <div class="mapa-header">
                    <h2>🗺️ Vista Geográfica del Sistema</h2>
                    <p>Restaurantes y proveedores en Ciudad Juárez</p>
                </div>
                <div id="mapa"></div>
            </div>
            
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Panel de Filtros -->
                <div class="panel">
                    <div class="panel-header">
                        <h3>🔍 Filtros y Controles</h3>
                    </div>
                    <div class="filters-section">
                        <div class="toggle-switch">
                            <button class="toggle-btn active" onclick="toggleLayer('restaurantes')">Restaurantes</button>
                            <button class="toggle-btn active" onclick="toggleLayer('proveedores')">Proveedores</button>
                        </div>
                        
                        <div class="filter-group">
                            <label for="categoria_filter">Categoría de Proveedor:</label>
                            <select id="categoria_filter" onchange="filtrarCategoria()">
                                <option value="todos">Todas las categorías</option>
                                <?php
                                $stmt_cats = $conn->prepare("SELECT DISTINCT categoria_insumo FROM proveedores_insumos WHERE estatus_proveedor = 1 ORDER BY categoria_insumo");
                                $stmt_cats->execute();
                                while ($cat = $stmt_cats->get_result()->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($cat['categoria_insumo']) . "'>" . htmlspecialchars($cat['categoria_insumo']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="search_filter">Buscar:</label>
                            <input type="text" id="search_filter" placeholder="Nombre de restaurante o proveedor..." onkeyup="buscar()">
                        </div>
                    </div>
                    
                    <div class="panel-body">
                        <h4 style="margin: 0 0 15px 0; color: #2c3e50;">📍 Leyenda</h4>
                        <div class="legend-item">
                            <div class="legend-icon" style="background: #27ae60;">🍽️</div>
                            <div class="legend-text">Restaurantes Activos</div>
                        </div>
                        <div class="legend-item">
                            <div class="legend-icon" style="background: #e74c3c;">⚠️</div>
                            <div class="legend-text">Restaurantes sin Ubicación</div>
                        </div>
                        <div class="legend-item">
                            <div class="legend-icon" style="background: #3498db;">📦</div>
                            <div class="legend-text">Proveedores de Insumos</div>
                        </div>
                        <div class="legend-item">
                            <div class="legend-icon" style="background: #f39c12;">📍</div>
                            <div class="legend-text">Tu Ubicación</div>
                        </div>
                    </div>
                </div>
                
                <!-- Panel de Restaurantes -->
                <div class="panel">
                    <div class="panel-header">
                        <h3>🍽️ Restaurantes</h3>
                    </div>
                    <div class="panel-body" id="restaurantes_list">
                        <?php while ($restaurante = $restaurantes->fetch_assoc()): ?>
                            <div class="list-item" onclick="centrarEnRestaurante(<?php echo $restaurante['id_res']; ?>)">
                                <div class="list-item-title">
                                    <?php echo htmlspecialchars($restaurante['nombre_res']); ?>
                                    <?php if (!$restaurante['tiene_ubicacion']): ?>
                                        <span class="list-item-badge badge-sin-ubicacion">Sin ubicación</span>
                                    <?php endif; ?>
                                </div>
                                <div class="list-item-subtitle">
                                    👤 <?php echo htmlspecialchars($restaurante['propietario']); ?> • 
                                    🍽️ <?php echo $restaurante['total_platillos']; ?> platillos
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Panel de Proveedores -->
                <div class="panel">
                    <div class="panel-header">
                        <h3>📦 Proveedores</h3>
                    </div>
                    <div class="panel-body" id="proveedores_list">
                        <?php while ($proveedor = $proveedores->fetch_assoc()): ?>
                            <div class="list-item" onclick="centrarEnProveedor(<?php echo $proveedor['id_proveedor']; ?>)">
                                <div class="list-item-title">
                                    <?php echo htmlspecialchars($proveedor['nombre_tienda']); ?>
                                    <span class="list-item-badge badge-proveedor">Proveedor</span>
                                </div>
                                <div class="list-item-subtitle">
                                    📦 <?php echo htmlspecialchars($proveedor['categoria_insumo']); ?> • 
                                    📞 <?php echo htmlspecialchars($proveedor['telefono']); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Función simplificada para inicializar el mapa directamente
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar si el contenedor del mapa existe
            const mapaContainer = document.getElementById('mapa');
            if (!mapaContainer) {
                console.error('No se encontró el contenedor del mapa');
                return;
            }

            // Inicializar mapa Leaflet
            const mapa = L.map('mapa').setView([31.7200, -106.4600], 11);

            // Agregar capa de OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(mapa);

            // Datos de restaurantes (simulados desde PHP)
            const restaurantesData = <?php 
                $restaurantes_array = [];
                while ($restaurante = mysqli_fetch_assoc($restaurantes)) {
                    if ($restaurante['latitud'] && $restaurante['longitud']) {
                        $restaurantes_array[] = [
                            'id_res' => $restaurante['id_res'],
                            'nombre_res' => $restaurante['nombre_res'],
                            'latitud' => $restaurante['latitud'],
                            'longitud' => $restaurante['longitud'],
                            'propietario' => $restaurante['propietario'],
                            'total_platillos' => $restaurante['total_platillos']
                        ];
                    }
                }
                echo json_encode($restaurantes_array); 
            ?>;

            // Datos de proveedores (simulados desde PHP)
            const proveedoresData = <?php 
                $proveedores_array = [];
                while ($proveedor = mysqli_fetch_assoc($proveedores)) {
                    if ($proveedor['latitud'] && $proveedor['longitud']) {
                        $proveedores_array[] = [
                            'id_proveedor' => $proveedor['id_proveedor'],
                            'nombre_tienda' => $proveedor['nombre_tienda'],
                            'latitud' => $proveedor['latitud'],
                            'longitud' => $proveedor['longitud'],
                            'categoria_insumo' => $proveedor['categoria_insumo']
                        ];
                    }
                }
                echo json_encode($proveedores_array); 
            ?>;

            // Iconos personalizados
            const iconoRestaurante = L.divIcon({
                html: '<div style="background: #27ae60; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-weight: bold;">🍽</div>',
                iconSize: [20, 20],
                className: 'restaurante-marker'
            });

            const iconoProveedor = L.divIcon({
                html: '<div style="background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-weight: bold;">📦</div>',
                iconSize: [20, 20],
                className: 'proveedor-marker'
            });

            // Agregar marcadores de restaurantes
            restaurantesData.forEach(restaurante => {
                if (restaurante.latitud && restaurante.longitud) {
                    const marcador = L.marker([restaurante.latitud, restaurante.longitud], { icon: iconoRestaurante })
                        .bindPopup(`
                            <div style="padding: 10px;">
                                <strong>${restaurante.nombre_res}</strong><br>
                                <small>Propietario: ${restaurante.propietario}</small><br>
                                <small>Platillos: ${restaurante.total_platillos}</small><br>
                                <a href="../restaurantes.php?id=${restaurante.id_res}" style="color: #27ae60;">Ver detalles</a>
                            </div>
                        `)
                        .addTo(mapa);
                }
            });

            // Agregar marcadores de proveedores
            proveedoresData.forEach(proveedor => {
                if (proveedor.latitud && proveedor.longitud) {
                    const marcador = L.marker([proveedor.latitud, proveedor.longitud], { icon: iconoProveedor })
                        .bindPopup(`
                            <div style="padding: 10px;">
                                <strong>${proveedor.nombre_tienda}</strong><br>
                                <small>Categoría: ${proveedor.categoria_insumo}</small><br>
                                <a href="../proveedores.php?id=${proveedor.id_proveedor}" style="color: #e74c3c;">Ver detalles</a>
                            </div>
                        `)
                        .addTo(mapa);
                }
            });

            // Control de capas
            const overlayMaps = {
                "Restaurantes": L.layerGroup(),
                "Proveedores": L.layerGroup()
            };

            // Agregar todos los marcadores a sus capas correspondientes
            mapa.eachLayer(layer => {
                if (layer.options.icon && layer.options.icon.options.className === 'restaurante-marker') {
                    overlayMaps["Restaurantes"].addLayer(layer);
                } else if (layer.options.icon && layer.options.icon.options.className === 'proveedor-marker') {
                    overlayMaps["Proveedores"].addLayer(layer);
                }
            });

            // Agregar control de capas
            L.control.layers(overlayMaps).addTo(mapa);

            // Ajustar vista para mostrar todos los marcadores
            const todosLosMarcadores = [];
            restaurantesData.forEach(r => {
                if (r.latitud && r.longitud) {
                    todosLosMarcadores.push([r.latitud, r.longitud]);
                }
            });
            proveedoresData.forEach(p => {
                if (p.latitud && p.longitud) {
                    todosLosMarcadores.push([p.latitud, p.longitud]);
                }
            });

            if (todosLosMarcadores.length > 0) {
                mapa.fitBounds(todosLosMarcadores, { padding: [50, 50] });
            }

            // Obtener ubicación del usuario
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        
                        // Agregar marcador del usuario
                        const iconoUsuario = L.divIcon({
                            html: '<div style="background: #3498db; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-weight: bold;">👤</div>',
                            iconSize: [20, 20],
                            className: 'usuario-marker'
                        });

                        L.marker([userLat, userLng], { icon: iconoUsuario })
                            .bindPopup('Tu ubicación actual')
                            .addTo(mapa);
                    },
                    error => {
                        console.warn('No se pudo obtener la ubicación del usuario:', error);
                    }
                );
            }

            console.log('Mapa de proveedores inicializado correctamente');
        });
    </script>
    
    <script>
        let mapa = null;
        let restaurantesData = [];
        let proveedoresData = [];
        let marcadoresRestaurantes = [];
        let marcadoresProveedores = [];
        let capaRestaurantesActiva = true;
        let capaProveedoresActiva = true;
        
        // Cargar datos desde PHP
        <?php
        // Cargar restaurantes
        $restaurantes->data_seek(0);
        $restaurantes_array = [];
        while ($rest = $restaurantes->get_result()->fetch_assoc()) {
            $restaurantes_array[] = $rest;
        }
        echo "restaurantesData = " . json_encode($restaurantes_array) . ";";
        
        // Cargar proveedores
        $proveedores->data_seek(0);
        $proveedores_array = [];
        while ($prov = $proveedores->get_result()->fetch_assoc()) {
            $proveedores_array[] = $prov;
        }
        echo "proveedoresData = " . json_encode($proveedores_array) . ";";
        ?>
        
        // Inicializar mapa cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            inicializarMapa();
        });
        
        function inicializarMapa() {
            mapa = new MapaSaludJuarez();
            
            // Inicializar mapa centrado en Ciudad Juárez
            mapa.inicializarMapa('mapa', {
                center: [31.7200, -106.4600],
                zoom: 11
            });
            
            // Agregar todos los marcadores iniciales
            agregarTodosLosMarcadores();
            
            // Ajustar vista para mostrar todos los puntos
            ajustarVistaGeneral();
        }
        
        function agregarTodosLosMarcadores() {
            // Agregar restaurantes
            restaurantesData.forEach(restaurante => {
                if (restaurante.latitud && restaurante.longitud) {
                    const coordenadas = [parseFloat(restaurante.latitud), parseFloat(restaurante.longitud)];
                    
                    const popupContent = `
                        <div style="min-width: 200px; font-family: Arial, sans-serif;">
                            <h4 style="margin: 0 0 8px 0; color: #2c3e50;">
                                ${restaurante.nombre_res}
                            </h4>
                            <p style="margin: 4px 0; color: #666; font-size: 14px;">
                                👤 Propietario: ${restaurante.propietario}
                            </p>
                            <p style="margin: 4px 0; color: #666; font-size: 14px;">
                                📍 ${restaurante.direccion_res || 'Dirección no disponible'}
                            </p>
                            <p style="margin: 4px 0; color: #666; font-size: 14px;">
                                🍽️ ${restaurante.total_platillos} platillos
                            </p>
                            <p style="margin: 4px 0; color: #666; font-size: 14px;">
                                📞 ${restaurante.telefono_res || 'Teléfono no disponible'}
                            </p>
                            <button onclick="window.location.href='ver_menu.php?id=${restaurante.id_res}'" 
                                    style="
                                        background: #27ae60;
                                        color: white;
                                        border: none;
                                        padding: 8px 16px;
                                        border-radius: 4px;
                                        cursor: pointer;
                                        margin-top: 8px;
                                        width: 100%;
                                        font-weight: bold;
                                    " onmouseover="this.style.background='#219a52'" 
                                       onmouseout="this.style.background='#27ae60'">
                                Ver Menú
                            </button>
                        </div>
                    `;
                    
                    const marcador = mapa.agregarMarcador(
                        coordenadas,
                        restaurante.nombre_res,
                        mapa.iconos.restaurante,
                        popupContent
                    );
                    
                    marcadoresRestaurantes.push({
                        marcador: marcador,
                        data: restaurante
                    });
                }
            });
            
            // Agregar proveedores
            proveedoresData.forEach(proveedor => {
                const coordenadas = [proveedor.latitud, proveedor.longitud];
                
                const popupContent = `
                    <div style="min-width: 200px; font-family: Arial, sans-serif;">
                        <h4 style="margin: 0 0 8px 0; color: #2c3e50;">
                            ${proveedor.nombre_tienda}
                        </h4>
                        <p style="margin: 4px 0; color: #666; font-size: 14px;">
                            📦 ${proveedor.categoria_insumo}
                        </p>
                        <p style="margin: 4px 0; color: #666; font-size: 14px;">
                            📍 ${proveedor.direccion_texto}
                        </p>
                        <p style="margin: 4px 0; color: #666; font-size: 14px;">
                            📞 ${proveedor.telefono}
                        </p>
                        <p style="margin: 4px 0; color: #666; font-size: 14px;">
                            ⏰ ${proveedor.horario_atencion}
                        </p>
                    </div>
                `;
                
                const marcador = mapa.agregarMarcador(
                    coordenadas,
                    proveedor.nombre_tienda,
                    mapa.iconos.proveedor,
                    popupContent
                );
                
                marcadoresProveedores.push({
                    marcador: marcador,
                    data: proveedor
                });
            });
        }
        
        function toggleLayer(tipo) {
            if (tipo === 'restaurantes') {
                capaRestaurantesActiva = !capaRestaurantesActiva;
                marcadoresRestaurantes.forEach(item => {
                    if (capaRestaurantesActiva) {
                        item.marcador.addTo(mapa.mapa);
                    } else {
                        mapa.mapa.removeLayer(item.marcador);
                    }
                });
            } else if (tipo === 'proveedores') {
                capaProveedoresActiva = !capaProveedoresActiva;
                marcadoresProveedores.forEach(item => {
                    if (capaProveedoresActiva) {
                        item.marcador.addTo(mapa.mapa);
                    } else {
                        mapa.mapa.removeLayer(item.marcador);
                    }
                });
            }
            
            // Actualizar botones
            event.target.classList.toggle('active');
        }
        
        function filtrarCategoria() {
            const categoria = document.getElementById('categoria_filter').value;
            
            marcadoresProveedores.forEach(item => {
                if (categoria === 'todos' || item.data.categoria_insumo === categoria) {
                    if (capaProveedoresActiva) {
                        item.marcador.addTo(mapa.mapa);
                    }
                } else {
                    mapa.mapa.removeLayer(item.marcador);
                }
            });
        }
        
        function buscar() {
            const termino = document.getElementById('search_filter').value.toLowerCase();
            
            // Filtrar restaurantes
            marcadoresRestaurantes.forEach(item => {
                const coincide = item.data.nombre_res.toLowerCase().includes(termino) ||
                               item.data.propietario.toLowerCase().includes(termino);
                
                if (coincide && capaRestaurantesActiva) {
                    item.marcador.addTo(mapa.mapa);
                } else {
                    mapa.mapa.removeLayer(item.marcador);
                }
            });
            
            // Filtrar proveedores
            marcadoresProveedores.forEach(item => {
                const coincide = item.data.nombre_tienda.toLowerCase().includes(termino) ||
                               item.data.categoria_insumo.toLowerCase().includes(termino);
                
                if (coincide && capaProveedoresActiva) {
                    item.marcador.addTo(mapa.mapa);
                } else {
                    mapa.mapa.removeLayer(item.marcador);
                }
            });
        }
        
        function centrarEnRestaurante(idRestaurante) {
            const restaurante = restaurantesData.find(r => r.id_res == idRestaurante);
            if (restaurante && restaurante.latitud && restaurante.longitud) {
                mapa.establecerVista([parseFloat(restaurante.latitud), parseFloat(restaurante.longitud)], 15);
            }
        }
        
        function centrarEnProveedor(idProveedor) {
            const proveedor = proveedoresData.find(p => p.id_proveedor == idProveedor);
            if (proveedor) {
                mapa.establecerVista([proveedor.latitud, proveedor.longitud], 15);
            }
        }
        
        function ajustarVistaGeneral() {
            const todosLosPuntos = [];
            
            // Agregar restaurantes con ubicación
            restaurantesData.forEach(restaurante => {
                if (restaurante.latitud && restaurante.longitud) {
                    todosLosPuntos.push([parseFloat(restaurante.latitud), parseFloat(restaurante.longitud)]);
                }
            });
            
            // Agregar proveedores
            proveedoresData.forEach(proveedor => {
                todosLosPuntos.push([proveedor.latitud, proveedor.longitud]);
            });
            
            if (todosLosPuntos.length > 0) {
                mapa.mapa.fitBounds(todosLosPuntos, { padding: [50, 50] });
            }
        }
        
        // Obtener ubicación del usuario
        function obtenerMiUbicacion() {
            if (navigator.geolocation) {
                mapa.obtenerUbicacionUsuario()
                    .then(coordenadas => {
                        mapa.establecerVista(coordenadas, 14);
                        mapa.agregarMarcador(
                            coordenadas,
                            'Tu ubicación',
                            mapa.iconos.usuario,
                            'Estás aquí'
                        );
                    })
                    .catch(error => {
                        console.log('No se pudo obtener la ubicación:', error);
                    });
            }
        }
    </script>
</body>
</html>
