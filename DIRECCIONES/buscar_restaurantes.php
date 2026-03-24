<?php
session_start();
include '../PHP/db_config.php';

// Validar que el usuario haya iniciado sesión
if (!isset($_SESSION['id_usu'])) {
    header("Location: ../login.php");
    exit();
}

// Capturamos lo que el usuario busque en la barra (si existe)
$busqueda = $_GET['q'] ?? '';

// Construimos la consulta SQL
$sql = "SELECT id_res, nombre_res, direccion_res, sector_res, telefono_res, latitud, longitud FROM restaurante WHERE estatus_res = 1";

// Si el usuario escribió algo en el buscador, filtramos
if (!empty($busqueda)) {
    $busqueda_segura = $conn->real_escape_string($busqueda);
    $sql .= " AND (nombre_res LIKE '%$busqueda_segura%' OR sector_res LIKE '%$busqueda_segura%')";
}

$sql .= " ORDER BY nombre_res ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Explorar Restaurantes | Salud Juárez</title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <style>
        /* Estilos rápidos para las tarjetas de restaurantes */
        .grid-restaurantes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card-res {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card-res h3 { margin-top: 0; color: #2ecc71; }
        .tag-sector {
            display: inline-block;
            background: #f1c40f;
            color: #333;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-bottom: 10px;
        }
        .barra-busqueda {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .barra-busqueda input { flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ccc; }
        
        /* Estilos para el mapa */
        .mapa-container {
            margin: 20px 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        #mapa {
            width: 100%;
            height: 500px;
            border: 2px solid #ddd;
        }
        .mapa-header {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .mapa-header h2 {
            margin: 0 0 10px 0;
            font-size: 1.8em;
        }
        .mapa-header p {
            margin: 0;
            opacity: 0.9;
        }
        .badge-certificacion {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }
        .badge-oro { background: #ffd700; color: #333; }
        .badge-plata { background: #c0c0c0; color: #333; }
        .badge-bronce { background: #cd7f32; color: white; }
        
        .vista-toggle {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .toggle-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .toggle-btn.active {
            background: #27ae60;
            color: white;
        }
        .toggle-btn:not(.active) {
            background: #ecf0f1;
            color: #2c3e50;
        }
        .toggle-btn:not(.active):hover {
            background: #bdc3c7;
        }
    </style>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
    <header>
        <h1>🔍 Descubre Opciones Saludables</h1>
        <div class="user-info">
            <a href="dashboard.php" class="btn" style="background: #34495e; color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px;">⬅ Volver al Dashboard</a>
        </div>
    </header>

    <main style="max-width: 1000px; margin: 0 auto; padding: 20px;">
        
        <form method="GET" action="buscar_restaurantes.php" class="barra-busqueda">
            <input type="text" name="q" placeholder="Buscar por nombre o sector (Ej. Centro, Las Misiones)..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" class="btn" style="background: #27ae60;">Buscar</button>
            <?php if(!empty($busqueda)): ?>
                <a href="buscar_restaurantes.php" class="btn" style="background: #e74c3c; text-decoration:none;">Limpiar</a>
            <?php endif; ?>
        </form>

        <h2>Restaurantes Disponibles</h2>
        
        <!-- Toggle de Vista -->
        <div class="vista-toggle">
            <button class="toggle-btn active" onclick="mostrarVista('lista')">
                📋 Vista Lista
            </button>
            <button class="toggle-btn" onclick="mostrarVista('mapa')">
                🗺️ Vista Mapa
            </button>
        </div>
        
        <!-- Vista Lista -->
        <div id="vista-lista" class="grid-restaurantes">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="card-res">
                        <h3>
                            <?php echo htmlspecialchars($row['nombre_res']); ?>
                            <?php if ($row['latitud'] && $row['longitud']): ?>
                                <span class="badge-certificacion badge-bronce">📍 En Mapa</span>
                            <?php endif; ?>
                        </h3>
                        <span class="tag-sector">📍 <?php echo htmlspecialchars($row['sector_res']); ?></span>
                        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($row['direccion_res']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($row['telefono_res']); ?></p>
                        
                        <a href="ver_menu.php?id=<?php echo $row['id_res']; ?>" class="btn" style="display: block; text-align: center; margin-top: 15px;">Ver Menú</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No se encontraron restaurantes con esa búsqueda. ¡Prueba con otra palabra!</p>
            <?php endif; ?>
        </div>
        
        <!-- Vista Mapa -->
        <div id="vista-mapa" class="mapa-container" style="display: none;">
            <div class="mapa-header">
                <h2>🗺️ Restaurantes en Ciudad Juárez</h2>
                <p>Haz clic en los marcadores para ver la información del restaurante</p>
            </div>
            <div id="mapa"></div>
        </div>

    </main>
</body>
</html>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../JS/mapa_salud_juarez.js"></script>

<script>
let mapa = null;
let restaurantesData = [];

// Datos de restaurantes desde PHP
const restaurantesPHP = <?php
$restaurantes_array = [];
if ($result->num_rows > 0) {
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        $restaurantes_array[] = [
            'id_res' => $row['id_res'],
            'nombre_res' => $row['nombre_res'],
            'direccion_res' => $row['direccion_res'],
            'sector_res' => $row['sector_res'],
            'telefono_res' => $row['telefono_res'],
            'latitud' => $row['latitud'],
            'longitud' => $row['longitud']
        ];
    }
}
echo json_encode($restaurantes_array);
?>;

restaurantesData = restaurantesPHP;

// Función para cambiar entre vistas
function mostrarVista(vista) {
    const vistaLista = document.getElementById('vista-lista');
    const vistaMapa = document.getElementById('vista-mapa');
    const botones = document.querySelectorAll('.toggle-btn');
    
    // Quitar clase active de todos los botones
    botones.forEach(btn => btn.classList.remove('active'));
    
    if (vista === 'lista') {
        vistaLista.style.display = 'grid';
        vistaMapa.style.display = 'none';
        botones[0].classList.add('active');
    } else if (vista === 'mapa') {
        vistaLista.style.display = 'none';
        vistaMapa.style.display = 'block';
        botones[1].classList.add('active');
        
        // Inicializar mapa si no está inicializado
        if (!mapa) {
            inicializarMapaRestaurantes();
        }
    }
}

// Inicializar mapa con restaurantes
function inicializarMapaRestaurantes() {
    // Crear instancia del mapa
    mapa = new MapaSaludJuarez();
    
    // Inicializar mapa centrado en Ciudad Juárez
    mapa.inicializarMapa('mapa', {
        center: [31.7200, -106.4600],
        zoom: 12
    });
    
    // Filtrar restaurantes que tienen coordenadas
    const restaurantesConUbicacion = restaurantesData.filter(r => 
        r.latitud && r.longitud
    );
    
    if (restaurantesConUbicacion.length > 0) {
        // Agregar restaurantes al mapa
        restaurantesConUbicacion.forEach(restaurante => {
            const coordenadas = [parseFloat(restaurante.latitud), parseFloat(restaurante.longitud)];
            
            // Crear popup personalizado
            const popupContent = `
                <div style="min-width: 200px; font-family: Arial, sans-serif;">
                    <h4 style="margin: 0 0 8px 0; color: #2c3e50;">
                        ${restaurante.nombre_res}
                    </h4>
                    <p style="margin: 4px 0; color: #666; font-size: 14px;">
                        📍 ${restaurante.direccion_res || 'Dirección no disponible'}
                    </p>
                    <p style="margin: 4px 0; color: #666; font-size: 14px;">
                        📞 ${restaurante.telefono_res || 'Teléfono no disponible'}
                    </p>
                    <p style="margin: 4px 0; color: #666; font-size: 14px;">
                        🏪 ${restaurante.sector_res || 'Sector no disponible'}
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
            
            mapa.agregarMarcador(
                coordenadas, 
                restaurante.nombre_res, 
                mapa.iconos.restaurante, 
                popupContent
            );
        });
        
        // Ajustar vista para mostrar todos los restaurantes
        const bounds = restaurantesConUbicacion.map(r => [
            parseFloat(r.latitud), 
            parseFloat(r.longitud)
        ]);
        
        if (bounds.length > 0) {
            mapa.mapa.fitBounds(bounds, { padding: [50, 50] });
        }
    } else {
        // Mostrar mensaje si no hay restaurantes con ubicación
        const centroCdJuarez = [31.7200, -106.4600];
        mapa.establecerVista(centroCdJuarez, 12);
        
        // Agregar marcador informativo
        mapa.agregarMarcador(
            centroCdJuarez,
            'Ciudad Juárez',
            mapa.iconos.usuario,
            'No hay restaurantes con ubicación registrada. Los restaurantes están agregando sus coordenadas.'
        );
    }
}

// Obtener ubicación del usuario si está disponible
function obtenerUbicacionUsuario() {
    if (navigator.geolocation && mapa) {
        mapa.obtenerUbicacionUsuario()
            .then(coordenadas => {
                // Centrar mapa en ubicación del usuario
                mapa.establecerVista(coordenadas, 14);
                
                // Agregar marcador del usuario
                mapa.agregarMarcador(
                    coordenadas,
                    'Tu ubicación',
                    mapa.iconos.usuario,
                    'Estás aquí'
                );
                
                // Mostrar restaurantes cercanos
                mostrarRestaurantesCercanos(coordenadas);
            })
            .catch(error => {
                console.log('No se pudo obtener la ubicación:', error);
            });
    }
}

// Mostrar restaurantes cercanos a una ubicación
function mostrarRestaurantesCercanos(ubicacionUsuario) {
    const restaurantesConUbicacion = restaurantesData.filter(r => 
        r.latitud && r.longitud
    );
    
    const restaurantesCercanos = mapa.encontrarProveedoresCercanos(
        restaurantesConUbicacion,
        ubicacionUsuario,
        10 // Radio de 10km
    );
    
    console.log('Restaurantes cercanos:', restaurantesCercanos);
    
    // Actualizar lista para mostrar los más cercanos primero
    if (restaurantesCercanos.length > 0) {
        actualizarListaRestaurantes(restaurantesCercanos);
    }
}

// Actualizar lista de restaurantes para mostrar los más cercanos
function actualizarListaRestaurantes(restaurantesOrdenados) {
    const vistaLista = document.getElementById('vista-lista');
    
    // Limpiar lista actual
    vistaLista.innerHTML = '';
    
    // Agregar restaurantes ordenados por distancia
    restaurantesOrdenados.forEach(restaurante => {
        const distanciaKm = (restaurante.distancia / 1000).toFixed(1);
        
        const card = document.createElement('div');
        card.className = 'card-res';
        card.innerHTML = `
            <h3>
                ${restaurante.nombre_res}
                <span class="badge-certificacion badge-bronce">📍 ${distanciaKm} km</span>
            </h3>
            <span class="tag-sector">📍 ${restaurante.sector_res}</span>
            <p><strong>Dirección:</strong> ${restaurante.direccion_res}</p>
            <p><strong>Teléfono:</strong> ${restaurante.telefono_res}</p>
            <a href="ver_menu.php?id=${restaurante.id_res}" class="btn" style="display: block; text-align: center; margin-top: 15px;">Ver Menú</a>
        `;
        
        vistaLista.appendChild(card);
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Si hay parámetro de búsqueda, mantener vista lista
    const busquedaActual = new URLSearchParams(window.location.search).get('q');
    
    if (!busquedaActual) {
        // Si no hay búsqueda, verificar si hay suficientes restaurantes con ubicación
        const restaurantesConUbicacion = restaurantesData.filter(r => r.latitud && r.longitud);
        
        if (restaurantesConUbicacion.length > 0) {
            // Mostrar mapa por defecto si hay restaurantes con ubicación
            setTimeout(() => {
                mostrarVista('mapa');
            }, 500);
        }
    }
});
</script>