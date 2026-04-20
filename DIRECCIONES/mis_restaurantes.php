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
    SELECT r.*, 
           COUNT(DISTINCT p.id_pla) as total_platillos,
           COUNT(DISTINCT CASE WHEN p.visible = 1 THEN p.id_pla END) as platillos_visibles,
           CASE 
               WHEN r.latitud IS NULL OR r.longitud IS NULL THEN 0
               ELSE 1
           END as tiene_ubicacion,
           CASE 
               WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 500 AND COUNT(DISTINCT i.es_ingrediente_secreto) = 0 THEN 'oro'
               WHEN AVG(pi.cantidad_usada * i.calorias_base) <= 800 AND COUNT(DISTINCT i.es_ingrediente_secreto) <= 2 THEN 'plata'
               ELSE 'bronce'
           END as certificacion
    FROM restaurante r
    LEFT JOIN platillos p ON r.id_res = p.id_res
    LEFT JOIN platillo_ingredientes pi ON p.id_pla = pi.id_pla
    LEFT JOIN inventario i ON pi.id_inv = i.id_inv
    WHERE r.id_usu = ?
    GROUP BY r.id_res
    ORDER BY r.nombre_res ASC
");
$stmt_restaurantes->bind_param("i", $id_usuario);
$stmt_restaurantes->execute();
$restaurantes = $stmt_restaurantes->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Restaurantes | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    
    <style>
        .restaurantes-container {
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
        
        .restaurantes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .restaurante-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .restaurante-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 20px;
            position: relative;
        }
        
        .card-header.inactivo {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }
        
        .card-header h3 {
            margin: 0 0 5px 0;
            font-size: 1.4em;
        }
        
        .card-header .estatus {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .certificacion-badge {
            position: absolute;
            top: 15px;
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
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
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
        
        .btn-nuevo-restaurante {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
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
        
        .btn-nuevo-restaurante:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
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
            .restaurantes-grid {
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
    
    <!-- Leaflet para mapa del modal -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <?php include '../PHP/navbar.php'; ?>
    
    <div class="restaurantes-container">
        <div class="header-section">
            <div>
                <h1>🏪 Mis Restaurantes</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Gestiona tus establecimientos y ubicaciones</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">← Volver al Panel</a>
        </div>
        
        <?php if ($restaurantes->num_rows > 0): ?>
            <div class="restaurantes-grid">
                <?php while ($restaurante = $restaurantes->fetch_assoc()): ?>
                    <div class="restaurante-card">
                        <div class="card-header <?php echo $restaurante['estatus_res'] != 1 ? 'inactivo' : ''; ?>">
                            <h3><?php echo htmlspecialchars($restaurante['nombre_res']); ?></h3>
                            <div class="estatus">
                                <?php echo $restaurante['estatus_res'] == 1 ? '✅ Activo' : '⏸️ Inactivo'; ?>
                            </div>
                            
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
                                <span class="info-label">📍 Ubicación</span>
                                <span class="info-value">
                                    <?php if ($restaurante['tiene_ubicacion']): ?>
                                        <span class="ubicacion-indicator ubicacion-ok">✅ Configurada</span>
                                    <?php else: ?>
                                        <span class="ubicacion-indicator ubicacion-faltante">⚠️ Sin configurar</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
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
                                <button class="btn-action btn-primary" onclick="abrirModalEditarRestaurante(<?php echo $restaurante['id_res']; ?>)">
                                    📝 Editar Perfil
                                </button>
                                <a href="gestion_platillos.php?id=<?php echo $restaurante['id_res']; ?>" class="btn-action btn-success">
                                    🍽️ Platillos
                                </a>
                                <a href="inventario.php?id=<?php echo $restaurante['id_res']; ?>" class="btn-action btn-warning">
                                    📦 Inventario
                                </a>
                                <a href="ver_menu.php?id=<?php echo $restaurante['id_res']; ?>" class="btn-action btn-secondary">
                                    👁️ Vista Pública
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>🏪 No tienes restaurantes registrados</h3>
                <p>Comienza registrando tu primer restaurante para ofrecer tus platillos saludables.</p>
                <div style="margin-top: 30px;">
                    <a href="#" class="btn-nuevo-restaurante" onclick="alert('Función de registro próximamente disponible')">
                        ➕ Registrar Nuevo Restaurante
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Animación de entrada para las tarjetas
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.restaurante-card');
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
        
        // Función para mostrar alertas informativas
        function mostrarInfo(titulo, mensaje) {
            if (confirm(`${titulo}\n\n${mensaje}\n\n¿Deseas continuar?`)) {
                return true;
            }
            return false;
        }
    </script>
<!-- Scripts -->
    <script src="../JS/editar_restaurante.js"></script>
    <script>
        function abrirModalEditarRestaurante(idRes) {
            const url = `componentes/modal_editar_restaurante.php?id_res=${idRes}`;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    // Verificar si el HTML contiene el modal
                    if (!html.includes('modalEditarRestaurante')) {
                        console.error('Error al cargar modal:', html.substring(0, 500));
                        alert('Error al cargar el formulario de edición');
                        return;
                    }
                    
                    // Crear contenedor para el modal
                    const modalContainer = document.createElement('div');
                    modalContainer.innerHTML = html;
                    document.body.appendChild(modalContainer);

                    // Limpiar instancia anterior si existe
                    if (window.editarRestauranteInstance) {
                        window.editarRestauranteInstance.destroy();
                        window.editarRestauranteInstance = null;
                    }

                    // Dar tiempo al navegador de procesar el DOM antes de inicializar
                    setTimeout(() => {
                        window.editarRestauranteInstance = new EditarRestaurante();
                    }, 100);
                })
                .catch(error => {
                    console.error('Error al cargar el modal:', error);
                    alert('Error al cargar el formulario de edición: ' + error.message);
                });
        }

        // Verificar si se debe abrir el modal automáticamente
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const modal = urlParams.get('modal');
            const id = urlParams.get('id');
            
            if (modal === 'editar' && id) {
                setTimeout(() => {
                    abrirModalEditarRestaurante(id);
                }, 500);
            }
        });
    </script>
</body>
</html>
