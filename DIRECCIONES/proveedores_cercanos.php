<?php
session_start();
include '../PHP/db_config.php';

// Verificar que sea un dueño de restaurante
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];

// Obtener restaurantes del usuario
$stmt_restaurantes = $conn->prepare("
    SELECT id_res, nombre_res, latitud, longitud 
    FROM restaurante 
    WHERE id_usu = ? AND estatus_res = 1
");
$stmt_restaurantes->bind_param("i", $id_usuario);
$stmt_restaurantes->execute();
$restaurantes = $stmt_restaurantes->get_result();

// Obtener categorías de proveedores
$stmt_categorias = $conn->prepare("
    SELECT DISTINCT categoria_insumo 
    FROM proveedores_insumos 
    WHERE estatus_proveedor = 1 
    ORDER BY categoria_insumo ASC
");
$stmt_categorias->execute();
$categorias = $stmt_categorias->get_result();

// Obtener ingredientes con stock bajo para alertas
$stmt_stock_bajo = $conn->prepare("
    SELECT i.*, r.nombre_res
    FROM inventario i
    JOIN restaurante r ON i.id_res = r.id_res
    WHERE r.id_usu = ? AND i.stock_inv <= 10 AND i.stock_inv > 0
    ORDER BY i.stock_inv ASC
");
$stmt_stock_bajo->bind_param("i", $id_usuario);
$stmt_stock_bajo->execute();
$ingredientes_bajos = $stmt_stock_bajo->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proveedores Cercanos | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        .proveedores-container {
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
        
        .content-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .mapa-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .mapa-header {
            background: linear-gradient(135deg, #3498db, #5dade2);
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
            height: 600px;
            border: 2px solid #ddd;
        }
        
        .info-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .card-header h3 {
            margin: 0;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .restaurante-selector {
            margin-bottom: 20px;
        }
        
        .restaurante-selector select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background: white;
        }
        
        .categoria-filtros {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .categoria-btn {
            padding: 8px 16px;
            border: 2px solid #3498db;
            background: white;
            color: #3498db;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .categoria-btn.active,
        .categoria-btn:hover {
            background: #3498db;
            color: white;
        }
        
        .proveedores-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .proveedor-item {
            padding: 15px;
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .proveedor-item:hover {
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }
        
        .proveedor-nombre {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .proveedor-categoria {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-bottom: 8px;
        }
        
        .proveedor-info {
            color: #666;
            font-size: 0.9em;
            line-height: 1.4;
        }
        
        .proveedor-distancia {
            font-weight: bold;
            color: #27ae60;
            margin-top: 8px;
        }
        
        .alertas-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alertas-section h4 {
            color: #856404;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .alerta-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #ffeaa7;
        }
        
        .alerta-item:last-child {
            border-bottom: none;
        }
        
        .alerta-info {
            flex: 1;
        }
        
        .alerta-nombre {
            font-weight: bold;
            color: #856404;
        }
        
        .alerta-stock {
            color: #dc3545;
            font-weight: bold;
        }
        
        .btn-solicitud {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            transition: all 0.3s;
        }
        
        .btn-solicitud:hover {
            background: #c0392b;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        @media (max-width: 1024px) {
            .content-layout {
                grid-template-columns: 1fr;
            }
            
            #mapa {
                height: 400px;
            }
        }
        
        @media (max-width: 768px) {
            .header-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .categoria-filtros {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../PHP/navbar.php'; ?>
    
    <div class="proveedores-container">
        <div class="header-section">
            <div>
                <h1>📦 Proveedores Cercanos</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Encuentra proveedores de insumos cerca de tus restaurantes</p>
            </div>
            <a href="mis_restaurantes.php" class="btn-secondary">← Mis Restaurantes</a>
        </div>
        
        <?php if ($ingredientes_bajos->num_rows > 0): ?>
            <div class="alertas-section">
                <h4>⚠️ Alertas de Stock Bajo</h4>
                <?php while ($ingrediente = $ingredientes_bajos->fetch_assoc()): ?>
                    <div class="alerta-item">
                        <div class="alerta-info">
                            <div class="alerta-nombre"><?php echo htmlspecialchars($ingrediente['nombre_insumo']); ?></div>
                            <div class="alerta-restaurante"><?php echo htmlspecialchars($ingrediente['nombre_res']); ?></div>
                        </div>
                        <div class="alerta-stock"><?php echo $ingrediente['stock_inv']; ?> <?php echo htmlspecialchars($ingrediente['medida_inv']); ?></div>
                        <button class="btn-solicitud" onclick="crearSolicitudRapida(<?php echo $ingrediente['id_inv']; ?>)">
                            Solicitar
                        </button>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        
        <div class="content-layout">
            <!-- Sección del Mapa -->
            <div class="mapa-section">
                <div class="mapa-header">
                    <h2>🗺️ Mapa de Proveedores</h2>
                    <p>Proveedores y restaurantes en tu área</p>
                </div>
                <div id="mapa"></div>
            </div>
            
            <!-- Sección de Información -->
            <div class="info-section">
                <!-- Selector de Restaurante -->
                <div class="card">
                    <div class="card-header">
                        <h3>🏪 Seleccionar Restaurante</h3>
                    </div>
                    <div class="card-body">
                        <div class="restaurante-selector">
                            <select id="restaurante_select" onchange="actualizarVista()">
                                <option value="">Selecciona un restaurante...</option>
                                <?php while ($restaurante = $restaurantes->fetch_assoc()): ?>
                                    <option value="<?php echo $restaurante['id_res']; ?>" 
                                            data-lat="<?php echo $restaurante['latitud']; ?>" 
                                            data-lng="<?php echo $restaurante['longitud']; ?>">
                                        <?php echo htmlspecialchars($restaurante['nombre_res']); ?>
                                        <?php if (!$restaurante['latitud'] || !$restaurante['longitud']): ?>
                                            (Sin ubicación)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <!-- Filtros de Categoría -->
                        <div class="categoria-filtros">
                            <button class="categoria-btn active" onclick="filtrarCategoria('todos')">Todos</button>
                            <?php while ($categoria = $categorias->fetch_assoc()): ?>
                                <button class="categoria-btn" onclick="filtrarCategoria('<?php echo htmlspecialchars($categoria['categoria_insumo']); ?>')">
                                    <?php echo htmlspecialchars($categoria['categoria_insumo']); ?>
                                </button>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Lista de Proveedores -->
                        <div class="proveedores-list" id="proveedores_list">
                            <div class="empty-state">
                                <h4>📍 Selecciona un restaurante</h4>
                                <p>Para ver los proveedores cercanos</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información de Re-stock -->
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Solicitudes de Re-stock</h3>
                    </div>
                    <div class="card-body">
                        <div id="solicitudes_info">
                            <div class="empty-state">
                                <h4>📦 Sin solicitudes activas</h4>
                                <p>Tus solicitudes aparecerán aquí</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../JS/mapa_salud_juarez.js"></script>
    
    <script>
        let mapa = null;
        let proveedoresData = [];
        let restaurantesData = [];
        let categoriaActual = 'todos';
        let restauranteActual = null;
        
        // Cargar datos iniciales
        <?php
        // Cargar proveedores
        $stmt_proveedores = $conn->prepare("SELECT * FROM proveedores_insumos WHERE estatus_proveedor = 1");
        $stmt_proveedores->execute();
        $proveedores_array = [];
        while ($prov = $stmt_proveedores->get_result()->fetch_assoc()) {
            $proveedores_array[] = $prov;
        }
        echo "proveedoresData = " . json_encode($proveedores_array) . ";";
        
        // Cargar restaurantes
        $restaurantes->data_seek(0);
        $restaurantes_array = [];
        while ($rest = $restaurantes->get_result()->fetch_assoc()) {
            $restaurantes_array[] = $rest;
        }
        echo "restaurantesData = " . json_encode($restaurantes_array) . ";";
        ?>
        
        // Inicializar mapa cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            inicializarMapa();
        });
        
        function inicializarMapa() {
            mapa = new MapaSaludJuarez();
            
            // Centrar en Ciudad Juárez
            mapa.inicializarMapa('mapa', {
                center: [31.7200, -106.4600],
                zoom: 11
            });
            
            // Agregar todos los proveedores al mapa
            actualizarProveedoresMapa();
        }
        
        function actualizarVista() {
            const select = document.getElementById('restaurante_select');
            const option = select.options[select.selectedIndex];
            
            if (select.value) {
                restauranteActual = {
                    id: select.value,
                    nombre: option.text,
                    latitud: parseFloat(option.dataset.lat),
                    longitud: parseFloat(option.dataset.lng)
                };
                
                if (restauranteActual.latitud && restauranteActual.longitud) {
                    // Centrar mapa en el restaurante
                    mapa.establecerVista([restauranteActual.latitud, restauranteActual.longitud], 13);
                    
                    // Agregar marcador del restaurante
                    mapa.limpiarTodosLosMarcadores();
                    mapa.agregarMarcador(
                        [restauranteActual.latitud, restauranteActual.longitud],
                        '📍 Tu Restaurante',
                        mapa.iconos.seleccionado,
                        restauranteActual.nombre
                    );
                    
                    // Mostrar proveedores cercanos
                    mostrarProveedoresCercanos();
                } else {
                    alert('Este restaurante no tiene ubicación configurada. Por favor, edita el perfil del restaurante primero.');
                }
            } else {
                restauranteActual = null;
                limpiarVista();
            }
        }
        
        function mostrarProveedoresCercanos() {
            if (!restauranteActual || !restauranteActual.latitud || !restauranteActual.longitud) {
                return;
            }
            
            const coordenadasRestaurante = [restauranteActual.latitud, restauranteActual.longitud];
            
            // Filtrar proveedores por categoría y calcular distancia
            let proveedoresFiltrados = proveedoresData.filter(p => {
                return categoriaActual === 'todos' || p.categoria_insumo === categoriaActual;
            });
            
            // Calcular distancia y ordenar
            proveedoresFiltrados = proveedoresFiltrados.map(p => ({
                ...p,
                distancia: mapa.calcularDistancia(coordenadasRestaurante, [p.latitud, p.longitud])
            })).sort((a, b) => a.distancia - b.distancia);
            
            // Filtrar por radio de 20km
            proveedoresFiltrados = proveedoresFiltrados.filter(p => p.distancia <= 20000);
            
            // Actualizar lista
            actualizarListaProveedores(proveedoresFiltrados);
            
            // Agregar proveedores al mapa
            proveedoresFiltrados.forEach(proveedor => {
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
                        <p style="margin: 4px 0; color: #27ae60; font-weight: bold;">
                            📍 ${(proveedor.distancia / 1000).toFixed(1)} km
                        </p>
                        <button onclick="crearSolicitud(${proveedor.id_proveedor})" 
                                style="
                                    background: #3498db;
                                    color: white;
                                    border: none;
                                    padding: 8px 16px;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    margin-top: 8px;
                                    width: 100%;
                                    font-weight: bold;
                                " onmouseover="this.style.background='#2980b9'" 
                                   onmouseout="this.style.background='#3498db'">
                            Solicitar Re-stock
                        </button>
                    </div>
                `;
                
                mapa.agregarMarcador(
                    coordenadas,
                    proveedor.nombre_tienda,
                    mapa.iconos.proveedor,
                    popupContent
                );
            });
        }
        
        function actualizarListaProveedores(proveedores) {
            const lista = document.getElementById('proveedores_list');
            
            if (proveedores.length === 0) {
                lista.innerHTML = `
                    <div class="empty-state">
                        <h4>🔍 No hay proveedores cercanos</h4>
                        <p>No se encontraron proveedores de esta categoría dentro de 20km</p>
                    </div>
                `;
                return;
            }
            
            lista.innerHTML = proveedores.map(proveedor => `
                <div class="proveedor-item">
                    <div class="proveedor-nombre">${proveedor.nombre_tienda}</div>
                    <div class="proveedor-categoria">${proveedor.categoria_insumo}</div>
                    <div class="proveedor-info">
                        📍 ${proveedor.direccion_texto}<br>
                        📞 ${proveedor.telefono}<br>
                        ⏰ ${proveedor.horario_atencion}
                    </div>
                    <div class="proveedor-distancia">
                        📍 ${(proveedor.distancia / 1000).toFixed(1)} km de distancia
                    </div>
                    <button class="btn-solicitud" onclick="crearSolicitud(${proveedor.id_proveedor})">
                        Solicitar Re-stock
                    </button>
                </div>
            `).join('');
        }
        
        function actualizarProveedoresMapa() {
            proveedoresData.forEach(proveedor => {
                const coordenadas = [proveedor.latitud, proveedor.longitud];
                mapa.agregarMarcador(
                    coordenadas,
                    proveedor.nombre_tienda,
                    mapa.iconos.proveedor,
                    `${proveedor.nombre_tienda}<br>${proveedor.categoria_insumo}`
                );
            });
        }
        
        function filtrarCategoria(categoria) {
            categoriaActual = categoria;
            
            // Actualizar botones
            document.querySelectorAll('.categoria-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Actualizar vista si hay restaurante seleccionado
            if (restauranteActual) {
                mostrarProveedoresCercanos();
            }
        }
        
        function limpiarVista() {
            document.getElementById('proveedores_list').innerHTML = `
                <div class="empty-state">
                    <h4>📍 Selecciona un restaurante</h4>
                    <p>Para ver los proveedores cercanos</p>
                </div>
            `;
            
            mapa.limpiarTodosLosMarcadores();
            actualizarProveedoresMapa();
        }
        
        function crearSolicitud(idProveedor) {
            // Redirigir a página de creación de solicitud
            window.location.href = `crear_solicitud_restock.php?id_proveedor=${idProveedor}&id_res=${restauranteActual.id}`;
        }
        
        function crearSolicitudRapida(idInv) {
            // Crear solicitud rápida para ingrediente con stock bajo
            fetch('../PHP/procesar_geolocalizacion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=crear_solicitud_restock&id_inv=${idInv}&cantidad=20&unidad=unidad&observaciones=Solicitud automática por stock bajo`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Solicitud de re-stock creada correctamente');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error de conexión');
            });
        }
    </script>
</body>
</html>
