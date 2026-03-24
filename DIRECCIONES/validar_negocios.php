<?php
session_start();
include '../PHP/db_config.php';

// Verificar que sea un administrador
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Obtener restaurantes pendientes de validación
$stmt_pendientes = $conn->prepare("
    SELECT r.*, u.nombre_usu, u.apellido_usu, u.email_usu, u.telefono_usu
    FROM restaurante r
    JOIN usuarios u ON r.id_usu = u.id_usu
    WHERE r.estatus_res = 0 OR r.estatus_res IS NULL
    ORDER BY r.fecha_registro DESC
");
$stmt_pendientes->execute();
$pendientes = $stmt_pendientes->get_result();

// Obtener restaurantes ya validados
$stmt_validados = $conn->prepare("
    SELECT r.*, u.nombre_usu, u.apellido_usu,
           COUNT(DISTINCT p.id_pla) as total_platillos,
           COUNT(DISTINCT CASE WHEN p.visible = 1 THEN p.id_pla END) as platillos_visibles,
           CASE 
               WHEN r.latitud IS NULL OR r.longitud IS NULL THEN 0
               ELSE 1
           END as tiene_ubicacion
    FROM restaurante r
    JOIN usuarios u ON r.id_usu = u.id_usu
    LEFT JOIN platillos p ON r.id_res = p.id_res
    WHERE r.estatus_res = 1
    GROUP BY r.id_res
    ORDER BY r.fecha_validacion DESC
");
$stmt_validados->execute();
$validados = $stmt_validados->get_result();

// Obtener estadísticas
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_restaurantes,
        COUNT(CASE WHEN estatus_res = 1 THEN 1 END) as total_validados,
        COUNT(CASE WHEN estatus_res = 0 OR estatus_res IS NULL THEN 1 END) as total_pendientes,
        COUNT(CASE WHEN DATE(fecha_registro) = CURDATE() THEN 1 END) as registrados_hoy,
        COUNT(CASE WHEN DATE(fecha_validacion) = CURDATE() THEN 1 END) as validados_hoy
    FROM restaurante
");
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Validar Negocios | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    
    <style>
        .validacion-container {
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
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-weight: bold;
        }
        
        .stat-card.pendiente { border-left-color: #f39c12; }
        .stat-card.validado { border-left-color: #27ae60; }
        .stat-card.hoy { border-left-color: #e74c3c; }
        
        .tabs-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .tabs-header {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .tab-button {
            flex: 1;
            padding: 15px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: bold;
            color: #666;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab-button.active {
            color: #3498db;
            border-bottom-color: #3498db;
            background: white;
        }
        
        .tab-button:hover {
            color: #2c3e50;
        }
        
        .tab-content {
            display: none;
            padding: 20px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .restaurante-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #f39c12;
            transition: all 0.3s;
        }
        
        .restaurante-card.validado {
            border-left-color: #27ae60;
        }
        
        .restaurante-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .card-title {
            flex: 1;
        }
        
        .card-title h3 {
            margin: 0 0 5px 0;
            color: #2c3e50;
            font-size: 1.3em;
        }
        
        .card-title p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        
        .card-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
        }
        
        .badge-pendiente { background: #f39c12; }
        .badge-validado { background: #27ae60; }
        .badge-ubicacion { background: #3498db; }
        .badge-platillos { background: #9b59b6; }
        
        .card-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .info-section h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1em;
        }
        
        .info-item {
            margin-bottom: 8px;
            color: #666;
            font-size: 0.9em;
        }
        
        .info-item strong {
            color: #2c3e50;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn-accion {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-validar {
            background: #27ae60;
            color: white;
        }
        
        .btn-validar:hover {
            background: #219a52;
        }
        
        .btn-rechazar {
            background: #e74c3c;
            color: white;
        }
        
        .btn-rechazar:hover {
            background: #c0392b;
        }
        
        .btn-ver {
            background: #3498db;
            color: white;
        }
        
        .btn-ver:hover {
            background: #2980b9;
        }
        
        .btn-detener {
            background: #f39c12;
            color: white;
        }
        
        .btn-detener:hover {
            background: #e67e22;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .modal {
            display: none;
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
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
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
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            resize: vertical;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .card-body {
                grid-template-columns: 1fr;
            }
            
            .card-actions {
                flex-direction: column;
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
    
    <div class="validacion-container">
        <div class="header-section">
            <div>
                <h1>✅ Validar Negocios</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Revisión y aprobación de restaurantes</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">← Volver al Panel</a>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_restaurantes']; ?></div>
                <div class="stat-label">🏪 Total Restaurantes</div>
            </div>
            
            <div class="stat-card validado">
                <div class="stat-number"><?php echo $stats['total_validados']; ?></div>
                <div class="stat-label">✅ Validados</div>
            </div>
            
            <div class="stat-card pendiente">
                <div class="stat-number"><?php echo $stats['total_pendientes']; ?></div>
                <div class="stat-label">⏳ Pendientes</div>
            </div>
            
            <div class="stat-card hoy">
                <div class="stat-number"><?php echo $stats['validados_hoy']; ?></div>
                <div class="stat-label">📅 Validados Hoy</div>
            </div>
        </div>
        
        <!-- Tabs de Validación -->
        <div class="tabs-container">
            <div class="tabs-header">
                <button class="tab-button active" onclick="mostrarTab('pendientes')">
                    ⏳ Pendientes de Validación (<?php echo $pendientes->num_rows; ?>)
                </button>
                <button class="tab-button" onclick="mostrarTab('validados')">
                    ✅ Restaurantes Validados (<?php echo $validados->num_rows; ?>)
                </button>
            </div>
            
            <!-- Tab Pendientes -->
            <div id="pendientes" class="tab-content active">
                <?php if ($pendientes->num_rows > 0): ?>
                    <?php while ($restaurante = $pendientes->fetch_assoc()): ?>
                        <div class="restaurante-card">
                            <div class="card-header">
                                <div class="card-title">
                                    <h3><?php echo htmlspecialchars($restaurante['nombre_res']); ?></h3>
                                    <p>
                                        👤 <?php echo htmlspecialchars($restaurante['nombre_usu'] . ' ' . $restaurante['apellido_usu']); ?> • 
                                        📧 <?php echo htmlspecialchars($restaurante['email_usu']); ?>
                                    </p>
                                </div>
                                <div class="card-badges">
                                    <span class="badge badge-pendiente">⏳ Pendiente</span>
                                    <span class="badge badge-ubicacion">
                                        <?php echo $restaurante['latitud'] && $restaurante['longitud'] ? '📍 Con Ubicación' : '📍 Sin Ubicación'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div class="info-section">
                                    <h4>📋 Información del Restaurante</h4>
                                    <div class="info-item">
                                        <strong>Dirección:</strong> <?php echo htmlspecialchars($restaurante['direccion_res'] ?? 'No especificada'); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Sector:</strong> <?php echo htmlspecialchars($restaurante['sector_res'] ?? 'No especificado'); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Teléfono:</strong> <?php echo htmlspecialchars($restaurante['telefono_res'] ?? 'No especificado'); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Web:</strong> <?php echo htmlspecialchars($restaurante['url_web'] ?? 'No especificada'); ?>
                                    </div>
                                </div>
                                
                                <div class="info-section">
                                    <h4>👤 Información del Propietario</h4>
                                    <div class="info-item">
                                        <strong>Nombre:</strong> <?php echo htmlspecialchars($restaurante['nombre_usu'] . ' ' . $restaurante['apellido_usu']); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Email:</strong> <?php echo htmlspecialchars($restaurante['email_usu']); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Teléfono:</strong> <?php echo htmlspecialchars($restaurante['telefono_usu'] ?? 'No especificado'); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Registro:</strong> <?php echo date('d/m/Y H:i', strtotime($restaurante['fecha_registro'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-actions">
                                <button class="btn-accion btn-ver" onclick="verDetalles(<?php echo $restaurante['id_res']; ?>)">
                                    👁️ Ver Detalles
                                </button>
                                <button class="btn-accion btn-validar" onclick="validarRestaurante(<?php echo $restaurante['id_res']; ?>)">
                                    ✅ Validar
                                </button>
                                <button class="btn-accion btn-rechazar" onclick="rechazarRestaurante(<?php echo $restaurante['id_res']; ?>)">
                                    ❌ Rechazar
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>🎉 No hay restaurantes pendientes</h3>
                        <p>Todos los restaurantes han sido validados</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab Validados -->
            <div id="validados" class="tab-content">
                <?php if ($validados->num_rows > 0): ?>
                    <?php while ($restaurante = $validados->fetch_assoc()): ?>
                        <div class="restaurante-card validado">
                            <div class="card-header">
                                <div class="card-title">
                                    <h3><?php echo htmlspecialchars($restaurante['nombre_res']); ?></h3>
                                    <p>
                                        👤 <?php echo htmlspecialchars($restaurante['nombre_usu'] . ' ' . $restaurante['apellido_usu']); ?> • 
                                        ✅ Validado el <?php echo date('d/m/Y', strtotime($restaurante['fecha_validacion'])); ?>
                                    </p>
                                </div>
                                <div class="card-badges">
                                    <span class="badge badge-validado">✅ Validado</span>
                                    <span class="badge badge-ubicacion">
                                        <?php echo $restaurante['tiene_ubicacion'] ? '📍 Con Ubicación' : '📍 Sin Ubicación'; ?>
                                    </span>
                                    <span class="badge badge-platillos">
                                        🍽️ <?php echo $restaurante['total_platillos']; ?> platillos
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <div class="info-section">
                                    <h4>📋 Información del Restaurante</h4>
                                    <div class="info-item">
                                        <strong>Dirección:</strong> <?php echo htmlspecialchars($restaurante['direccion_res'] ?? 'No especificada'); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Sector:</strong> <?php echo htmlspecialchars($restaurante['sector_res'] ?? 'No especificado'); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Teléfono:</strong> <?php echo htmlspecialchars($restaurante['telefono_res'] ?? 'No especificado'); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Validación:</strong> <?php echo date('d/m/Y H:i', strtotime($restaurante['fecha_validacion'])); ?>
                                    </div>
                                </div>
                                
                                <div class="info-section">
                                    <h4>📊 Estadísticas</h4>
                                    <div class="info-item">
                                        <strong>Total Platillos:</strong> <?php echo $restaurante['total_platillos']; ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Platillos Visibles:</strong> <?php echo $restaurante['platillos_visibles']; ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Propietario:</strong> <?php echo htmlspecialchars($restaurante['nombre_usu'] . ' ' . $restaurante['apellido_usu']); ?>
                                    </div>
                                    <div class="info-item">
                                        <strong>Registro:</strong> <?php echo date('d/m/Y', strtotime($restaurante['fecha_registro'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-actions">
                                <button class="btn-accion btn-ver" onclick="verMenu(<?php echo $restaurante['id_res']; ?>)">
                                    👁️ Ver Menú
                                </button>
                                <button class="btn-accion btn-detener" onclick="detenerRestaurante(<?php echo $restaurante['id_res']; ?>)">
                                    ⏸️ Detener
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>📭 No hay restaurantes validados</h3>
                        <p>Aún no se ha validado ningún restaurante</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal de Rechazo -->
    <div id="modalRechazo" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>❌ Rechazar Restaurante</h3>
                <span class="close" onclick="cerrarModal('modalRechazo')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="formRechazo">
                    <input type="hidden" id="rechazo_id_res" name="id_res">
                    
                    <div class="form-group">
                        <label for="motivo_rechazo">Motivo del rechazo:</label>
                        <textarea id="motivo_rechazo" name="motivo_rechazo" rows="4" required
                                  placeholder="Explica por qué se rechaza este restaurante..."></textarea>
                    </div>
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn-accion btn-secondary" onclick="cerrarModal('modalRechazo')" style="margin-right: 10px;">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-accion btn-rechazar">
                            ❌ Rechazar Definitivamente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function mostrarTab(tabName) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Quitar clase active de todos los botones
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar tab seleccionado
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function validarRestaurante(idRestaurante) {
            if (confirm('¿Estás seguro de validar este restaurante? Una vez validado, estará visible para todos los clientes.')) {
                fetch('../PHP/procesar_validacion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=validar&id_res=${idRestaurante}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Restaurante validado correctamente');
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
        
        function rechazarRestaurante(idRestaurante) {
            document.getElementById('rechazo_id_res').value = idRestaurante;
            document.getElementById('modalRechazo').style.display = 'block';
        }
        
        function detenerRestaurante(idRestaurante) {
            if (confirm('¿Estás seguro de detener este restaurante? Ya no será visible para los clientes pero sus datos se conservarán.')) {
                fetch('../PHP/procesar_validacion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=detener&id_res=${idRestaurante}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('⏸️ Restaurante detenido correctamente');
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
        
        function verDetalles(idRestaurante) {
            window.open(`../DIRECCIONES/ver_menu.php?id=${idRestaurante}`, '_blank');
        }
        
        function verMenu(idRestaurante) {
            window.open(`../DIRECCIONES/ver_menu.php?id=${idRestaurante}`, '_blank');
        }
        
        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Procesar formulario de rechazo
        document.getElementById('formRechazo').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const idRes = document.getElementById('rechazo_id_res').value;
            const motivo = document.getElementById('motivo_rechazo').value;
            
            fetch('../PHP/procesar_validacion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `accion=rechazar&id_res=${idRes}&motivo=${encodeURIComponent(motivo)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('❌ Restaurante rechazado y eliminado');
                    cerrarModal('modalRechazo');
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error de conexión');
            });
        });
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
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
    </script>
</body>
</html>
