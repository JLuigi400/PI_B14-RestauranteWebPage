<?php
session_start();
include '../PHP/db_config.php';

// Verificar que sea un cliente
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 3) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];

// Obtener restaurantes favoritos del usuario
$stmt_favoritos = $conn->prepare("
    SELECT r.*, 
           COUNT(DISTINCT p.id_pla) as total_platillos,
           COUNT(DISTINCT CASE WHEN p.visible = 1 THEN p.id_pla END) as platillos_visibles,
           CASE 
               WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 500 AND COUNT(DISTINCT i.es_ingrediente_secreto) = 0 THEN 'oro'
               WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 800 AND COUNT(DISTINCT i.es_ingrediente_secreto) <= 2 THEN 'plata'
               ELSE 'bronce'
           END as certificacion,
           f.fecha_agregado as fecha_favorito
    FROM favoritos f
    JOIN restaurante r ON f.id_res = r.id_res
    LEFT JOIN platillos p ON r.id_res = p.id_res
    LEFT JOIN platillo_ingredientes pi ON p.id_pla = pi.id_pla
    LEFT JOIN inventario i ON pi.id_inv = i.id_inv
    WHERE f.id_usu = ? AND r.estatus_res = 1
    GROUP BY r.id_res, f.fecha_agregado
    ORDER BY f.fecha_agregado DESC
");
$stmt_favoritos->bind_param("i", $id_usuario);
$stmt_favoritos->execute();
$favoritos = $stmt_favoritos->get_result();

// Obtener restaurantes recomendados (basados en favoritos similares)
$stmt_recomendados = $conn->prepare("
    SELECT r.*, 
           COUNT(DISTINCT p.id_pla) as total_platillos,
           COUNT(DISTINCT CASE WHEN p.visible = 1 THEN p.id_pla END) as platillos_visibles,
           CASE 
               WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 500 AND COUNT(DISTINCT i.es_ingrediente_secreto) = 0 THEN 'oro'
               WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 800 AND COUNT(DISTINCT i.es_ingrediente_secreto) <= 2 THEN 'plata'
               ELSE 'bronce'
           END as certificacion
    FROM restaurante r
    LEFT JOIN platillos p ON r.id_res = p.id_res
    LEFT JOIN platillo_ingredientes pi ON p.id_pla = pi.id_pla
    LEFT JOIN inventario i ON pi.id_inv = i.id_inv
    WHERE r.estatus_res = 1 
      AND r.id_res NOT IN (
          SELECT id_res FROM favoritos WHERE id_usu = ?
      )
    GROUP BY r.id_res
    ORDER BY RAND()
    LIMIT 6
");
$stmt_recomendados->bind_param("i", $id_usuario);
$stmt_recomendados->execute();
$recomendados = $stmt_recomendados->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Favoritos | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    
    <style>
        .favoritos-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .favoritos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .restaurante-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .restaurante-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 20px;
            position: relative;
        }
        
        .card-header h3 {
            margin: 0 0 5px 0;
            font-size: 1.4em;
        }
        
        .card-header .fecha-favorito {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .favorite-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .certificacion-badge {
            position: absolute;
            bottom: 15px;
            right: 15px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .badge-oro { background: #ffd700; color: #333; }
        .badge-plata { background: #c0c0c0; color: #333; }
        .badge-bronce { background: #cd7f32; color: white; }
        
        .card-body {
            padding: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-label {
            color: #7f8c8d;
            font-weight: bold;
        }
        
        .info-value {
            color: #2c3e50;
            text-align: right;
        }
        
        .ubicacion-indicator {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .ubicacion-ok {
            background: #d4edda;
            color: #155724;
        }
        
        .ubicacion-faltante {
            background: #f8d7da;
            color: #721c24;
        }
        
        .card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-action {
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.9em;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #219a52;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .btn-explorar {
            background: linear-gradient(135deg, #3498db, #5dade2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-explorar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .recomendados-section {
            margin-top: 50px;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .section-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .section-header p {
            color: #666;
        }
        
        .recomendados-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .recomendado-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .recomendado-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .recomendado-header {
            background: linear-gradient(135deg, #3498db, #5dade2);
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .recomendado-body {
            padding: 20px;
        }
        
        .stats-mini {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-item {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .stat-number {
            font-size: 1.5em;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 0.8em;
            color: #7f8c8d;
            margin-top: 2px;
        }
        
        @media (max-width: 768px) {
            .favoritos-grid,
            .recomendados-grid {
                grid-template-columns: 1fr;
            }
            
            .card-actions {
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
    
    <div class="favoritos-container">
        <div class="header-section">
            <div>
                <h1>❤️ Mis Favoritos</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Tus restaurantes preferidos</p>
            </div>
            <a href="buscar_restaurantes.php" class="btn-explorar">🔍 Explorar Restaurantes</a>
        </div>
        
        <?php if ($favoritos->num_rows > 0): ?>
            <div class="favoritos-grid">
                <?php while ($restaurante = $favoritos->fetch_assoc()): ?>
                    <div class="restaurante-card">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($restaurante['nombre_res']); ?></h3>
                            <div class="fecha-favorito">
                                ❤️ Agregado el <?php echo date('d/m/Y', strtotime($restaurante['fecha_favorito'])); ?>
                            </div>
                            <div class="favorite-badge">FAVORITO</div>
                            
                            <?php if ($restaurante['certificacion']): ?>
                                <div class="certificacion-badge badge-<?php echo $restaurante['certificacion']; ?>">
                                    <?php
                                    $certificaciones = [
                                        'oro' => '🏆 Oro',
                                        'plata' => '🥈 Plata', 
                                        'bronce' => '🥉 Bronce'
                                    ];
                                    echo $certificaciones[$restaurante['certificacion']];
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <div class="info-row">
                                <span class="info-label">📍 Sector</span>
                                <span class="info-value"><?php echo htmlspecialchars($restaurante['sector_res'] ?? 'No especificado'); ?></span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">📞 Teléfono</span>
                                <span class="info-value"><?php echo htmlspecialchars($restaurante['telefono_res'] ?? 'No registrado'); ?></span>
                            </div>
                            
                            <div class="stats-mini">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $restaurante['total_platillos']; ?></div>
                                    <div class="stat-label">Total Platillos</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $restaurante['platillos_visibles']; ?></div>
                                    <div class="stat-label">Visibles</div>
                                </div>
                            </div>
                            
                            <div class="card-actions">
                                <a href="ver_menu.php?id=<?php echo $restaurante['id_res']; ?>" class="btn-action btn-primary">
                                    🍽️ Ver Menú
                                </a>
                                <a href="#" class="btn-action btn-danger" onclick="eliminarFavorito(<?php echo $restaurante['id_res']; ?>)">
                                    💔 Eliminar
                                </a>
                                <a href="#" class="btn-action btn-success" onclick="mostrarMapa(<?php echo $restaurante['id_res']; ?>)">
                                    📍 Ubicación
                                </a>
                                <a href="#" class="btn-action btn-secondary" onclick="compartirRestaurante(<?php echo $restaurante['id_res']; ?>)">
                                    📤 Compartir
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>💔 No tienes favoritos aún</h3>
                <p>Explora restaurantes y agrega tus favoritos para verlos aquí</p>
                <div style="margin-top: 30px;">
                    <a href="buscar_restaurantes.php" class="btn-explorar">
                        🔍 Explorar Restaurantes
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recomendaciones -->
        <?php if ($recomendados->num_rows > 0): ?>
            <div class="recomendados-section">
                <div class="section-header">
                    <h2>✨ Restaurantes Recomendados para Ti</h2>
                    <p>Basado en tus preferencias y restaurantes similares</p>
                </div>
                
                <div class="recomendados-grid">
                    <?php while ($restaurante = $recomendados->fetch_assoc()): ?>
                        <div class="recomendado-card">
                            <div class="recomendado-header">
                                <h4><?php echo htmlspecialchars($restaurante['nombre_res']); ?></h4>
                            </div>
                            <div class="recomendado-body">
                                <div class="info-row">
                                    <span class="info-label">📍 Sector</span>
                                    <span class="info-value"><?php echo htmlspecialchars($restaurante['sector_res'] ?? 'No especificado'); ?></span>
                                </div>
                                
                                <div class="stats-mini">
                                    <div class="stat-item">
                                        <div class="stat-number"><?php echo $restaurante['total_platillos']; ?></div>
                                        <div class="stat-label">Platillos</div>
                                    </div>
                                </div>
                                
                                <div class="card-actions">
                                    <a href="ver_menu.php?id=<?php echo $restaurante['id_res']; ?>" class="btn-action btn-primary">
                                        👁️ Ver Menú
                                    </a>
                                    <a href="#" class="btn-action btn-success" onclick="agregarFavorito(<?php echo $restaurante['id_res']; ?>)">
                                        ❤️ Agregar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal para mostrar mapa -->
    <div id="mapaModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 800px; margin: 50px auto;">
            <div class="modal-header">
                <h3>📍 Ubicación del Restaurante</h3>
                <span class="close" onclick="cerrarMapa()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="mapaModalContent" style="width: 100%; height: 400px;"></div>
            </div>
        </div>
    </div>
    
    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 20px;
        }
    </style>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../JS/mapa_salud_juarez.js"></script>
    
    <script>
        let mapaModal = null;
        
        function agregarFavorito(idRestaurante) {
            fetch('../PHP/procesar_favoritos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=agregar&id_res=${idRestaurante}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Restaurante agregado a favoritos');
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
        
        function eliminarFavorito(idRestaurante) {
            if (confirm('¿Estás seguro de eliminar este restaurante de tus favoritos?')) {
                fetch('../PHP/procesar_favoritos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=eliminar&id_res=${idRestaurante}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('💔 Restaurante eliminado de favoritos');
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
        }
        
        function mostrarMapa(idRestaurante) {
            // Obtener información del restaurante
            fetch(`../PHP/procesar_geolocalizacion.php?accion=obtener_restaurante&id_res=${idRestaurante}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.restaurante.latitud && data.restaurante.longitud) {
                    document.getElementById('mapaModal').style.display = 'block';
                    
                    // Inicializar mapa en el modal
                    setTimeout(() => {
                        mapaModal = new MapaSaludJuarez();
                        mapaModal.inicializarMapa('mapaModalContent', {
                            center: [data.restaurante.latitud, data.restaurante.longitud],
                            zoom: 16
                        });
                        
                        // Agregar marcador del restaurante
                        mapaModal.agregarMarcador(
                            [data.restaurante.latitud, data.restaurante.longitud],
                            data.restaurante.nombre_res,
                            mapaModal.iconos.restaurante,
                            data.restaurante.nombre_res
                        );
                    }, 100);
                } else {
                    alert('❌ Este restaurante no tiene ubicación registrada');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error al cargar la ubicación');
            });
        }
        
        function cerrarMapa() {
            document.getElementById('mapaModal').style.display = 'none';
            if (mapaModal) {
                mapaModal.mapa.remove();
                mapaModal = null;
            }
        }
        
        function compartirRestaurante(idRestaurante) {
            // Simular compartir
            if (navigator.share) {
                navigator.share({
                    title: 'Salud Juárez - Restaurante Recomendado',
                    text: 'Mira este restaurante saludable que encontré',
                    url: window.location.origin + '/DIRECCIONES/ver_menu.php?id=' + idRestaurante
                });
            } else {
                // Copiar al portapapeles
                const url = window.location.origin + '/DIRECCIONES/ver_menu.php?id=' + idRestaurante;
                navigator.clipboard.writeText(url).then(() => {
                    alert('📋 Enlace copiado al portapapeles');
                });
            }
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('mapaModal');
            if (event.target == modal) {
                cerrarMapa();
            }
        }
        
        // Animación de entrada para las tarjetas
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.restaurante-card, .recomendado-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s, transform 0.5s';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
