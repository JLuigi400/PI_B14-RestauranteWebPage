<?php
session_start();
include '../PHP/db_config.php';

// Verificar que sea un administrador
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Obtener todos los usuarios con información adicional
$stmt_usuarios = $conn->prepare("
    SELECT u.*, r.nombre_rol,
           CASE 
               WHEN u.id_rol = 2 THEN (SELECT COUNT(*) FROM restaurante WHERE id_usu = u.id_usu AND estatus_res = 1)
               WHEN u.id_rol = 3 THEN (SELECT COUNT(*) FROM favoritos WHERE id_usu = u.id_usu)
               ELSE 0
           END as elementos_relacionados
    FROM usuarios u
    JOIN roles r ON u.id_rol = r.id_rol
    ORDER BY u.fecha_registro DESC
");
$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->get_result();

// Obtener estadísticas
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_usuarios,
        COUNT(CASE WHEN id_rol = 1 THEN 1 END) as total_admins,
        COUNT(CASE WHEN id_rol = 2 THEN 1 END) as total_duenos,
        COUNT(CASE WHEN id_rol = 3 THEN 1 END) as total_clientes,
        COUNT(CASE WHEN estatus_usu = 1 THEN 1 END) as total_activos,
        COUNT(CASE WHEN estatus_usu = 0 THEN 1 END) as total_inactivos,
        COUNT(CASE WHEN DATE(fecha_registro) = CURDATE() THEN 1 END) as registrados_hoy
    FROM usuarios
");
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    
    <style>
        .admin-container {
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
        
        .stat-card.admin { border-left-color: #e74c3c; }
        .stat-card.dueno { border-left-color: #f39c12; }
        .stat-card.cliente { border-left-color: #27ae60; }
        .stat-card.activo { border-left-color: #3498db; }
        
        .usuarios-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .filtros-container {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .filtro-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .busqueda-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 200px;
        }
        
        .usuarios-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .usuarios-table th {
            background: #3498db;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        
        .usuarios-table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .usuarios-table tr:hover {
            background: #f8f9fa;
        }
        
        .rol-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
        }
        
        .rol-admin { background: #e74c3c; }
        .rol-dueno { background: #f39c12; }
        .rol-cliente { background: #27ae60; }
        
        .estatus-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .estatus-activo { background: #d4edda; color: #155724; }
        .estatus-inactivo { background: #f8d7da; color: #721c24; }
        
        .acciones-cell {
            display: flex;
            gap: 8px;
        }
        
        .btn-accion {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-editar {
            background: #3498db;
            color: white;
        }
        
        .btn-editar:hover {
            background: #2980b9;
        }
        
        .btn-suspender {
            background: #f39c12;
            color: white;
        }
        
        .btn-suspender:hover {
            background: #e67e22;
        }
        
        .btn-activar {
            background: #27ae60;
            color: white;
        }
        
        .btn-activar:hover {
            background: #219a52;
        }
        
        .btn-eliminar {
            background: #e74c3c;
            color: white;
        }
        
        .btn-eliminar:hover {
            background: #c0392b;
        }
        
        .usuario-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .usuario-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3498db, #5dade2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .usuario-detalles {
            flex: 1;
        }
        
        .usuario-nombre {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 2px;
        }
        
        .usuario-email {
            color: #666;
            font-size: 0.9em;
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
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filtros-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .busqueda-input {
                width: 100%;
            }
            
            .usuarios-table {
                font-size: 0.9em;
            }
            
            .acciones-cell {
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
    
    <div class="admin-container">
        <div class="header-section">
            <div>
                <h1>👥 Gestión de Usuarios</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Administración de todos los usuarios del sistema</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">← Volver al Panel</a>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_usuarios']; ?></div>
                <div class="stat-label">👥 Total Usuarios</div>
            </div>
            
            <div class="stat-card admin">
                <div class="stat-number"><?php echo $stats['total_admins']; ?></div>
                <div class="stat-label">🛡️ Administradores</div>
            </div>
            
            <div class="stat-card dueno">
                <div class="stat-number"><?php echo $stats['total_duenos']; ?></div>
                <div class="stat-label">👑 Dueños</div>
            </div>
            
            <div class="stat-card cliente">
                <div class="stat-number"><?php echo $stats['total_clientes']; ?></div>
                <div class="stat-label">👤 Clientes</div>
            </div>
            
            <div class="stat-card activo">
                <div class="stat-number"><?php echo $stats['total_activos']; ?></div>
                <div class="stat-label">✅ Activos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['registrados_hoy']; ?></div>
                <div class="stat-label">📅 Registrados Hoy</div>
            </div>
        </div>
        
        <!-- Lista de Usuarios -->
        <div class="usuarios-section">
            <div class="section-header">
                <h2>📋 Lista de Usuarios</h2>
                <div class="filtros-container">
                    <input type="text" class="busqueda-input" placeholder="Buscar usuario..." id="busquedaUsuario" onkeyup="filtrarUsuarios()">
                    <select class="filtro-select" id="filtroRol" onchange="filtrarUsuarios()">
                        <option value="">Todos los roles</option>
                        <option value="1">Administradores</option>
                        <option value="2">Dueños</option>
                        <option value="3">Clientes</option>
                    </select>
                    <select class="filtro-select" id="filtroEstatus" onchange="filtrarUsuarios()">
                        <option value="">Todos los estatus</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>
            
            <div class="table-container">
                <table class="usuarios-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Elementos</th>
                            <th>Registro</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="usuariosTableBody">
                        <?php if ($usuarios->num_rows > 0): ?>
                            <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                                <tr data-rol="<?php echo $usuario['id_rol']; ?>" data-estatus="<?php echo $usuario['estatus_usu']; ?>" data-nombre="<?php echo strtolower($usuario['nombre_usu'] . ' ' . $usuario['apellido_usu'] . ' ' . $usuario['nick']); ?>">
                                    <td>
                                        <div class="usuario-info">
                                            <div class="usuario-avatar">
                                                <?php echo strtoupper(substr($usuario['nick'], 0, 1)); ?>
                                            </div>
                                            <div class="usuario-detalles">
                                                <div class="usuario-nombre"><?php echo htmlspecialchars($usuario['nombre_usu'] . ' ' . $usuario['apellido_usu']); ?></div>
                                                <div class="usuario-email"><?php echo htmlspecialchars($usuario['nick']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="rol-badge rol-<?php echo $usuario['id_rol'] == 1 ? 'admin' : ($usuario['id_rol'] == 2 ? 'dueno' : 'cliente'); ?>">
                                            <?php echo htmlspecialchars($usuario['nombre_rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($usuario['id_rol'] == 2): ?>
                                            <span style="color: #f39c12;">🏪 <?php echo $usuario['elementos_relacionados']; ?> restaurantes</span>
                                        <?php elseif ($usuario['id_rol'] == 3): ?>
                                            <span style="color: #27ae60;">❤️ <?php echo $usuario['elementos_relacionados']; ?> favoritos</span>
                                        <?php else: ?>
                                            <span style="color: #e74c3c;">🛡️ Administrador</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                        <br>
                                        <small style="color: #666;"><?php echo date('H:i', strtotime($usuario['fecha_registro'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="estatus-badge estatus-<?php echo $usuario['estatus_usu'] == 1 ? 'activo' : 'inactivo'; ?>">
                                            <?php echo $usuario['estatus_usu'] == 1 ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="acciones-cell">
                                            <button class="btn-accion btn-editar" onclick="editarUsuario(<?php echo $usuario['id_usu']; ?>)">
                                                ✏️ Editar
                                            </button>
                                            <?php if ($usuario['estatus_usu'] == 1): ?>
                                                <button class="btn-accion btn-suspender" onclick="cambiarEstatus(<?php echo $usuario['id_usu']; ?>, 0)">
                                                    ⏸️ Suspender
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-accion btn-activar" onclick="cambiarEstatus(<?php echo $usuario['id_usu']; ?>, 1)">
                                                    ▶️ Activar
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($usuario['id_rol'] != 1): ?>
                                                <button class="btn-accion btn-eliminar" onclick="eliminarUsuario(<?php echo $usuario['id_usu']; ?>)">
                                                    🗑️ Eliminar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <h3>📭 No hay usuarios registrados</h3>
                                        <p>No se encontraron usuarios en el sistema</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="../JS/editar_usuario.js"></script>
    
    <script>
        function filtrarUsuarios() {
            const busqueda = document.getElementById('busquedaUsuario').value.toLowerCase();
            const filtroRol = document.getElementById('filtroRol').value;
            const filtroEstatus = document.getElementById('filtroEstatus').value;
            const filas = document.querySelectorAll('#usuariosTableBody tr');
            
            filas.forEach(fila => {
                const nombre = fila.dataset.nombre || '';
                const rol = fila.dataset.rol || '';
                const estatus = fila.dataset.estatus || '';
                
                const coincideBusqueda = nombre.includes(busqueda);
                const coincideRol = filtroRol === '' || rol === filtroRol;
                const coincideEstatus = filtroEstatus === '' || estatus === filtroEstatus;
                
                if (coincideBusqueda && coincideRol && coincideEstatus) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        }
        
        function editarUsuario(idUsuario) {
            // Abrir modal de edición de usuario
            abrirModalEditarUsuario(idUsuario);
        }

        function abrirModalEditarUsuario(idUsuario) {
            fetch(`../DIRECCIONES/componentes/modal_editar_usuario.php?id_usu=${idUsuario}`)
                .then(response => response.text())
                .then(html => {
                    // Crear contenedor para el modal
                    const modalContainer = document.createElement('div');
                    modalContainer.innerHTML = html;
                    document.body.appendChild(modalContainer);

                    // Inicializar la clase del modal
                    if (window.editarUsuarioInstance) {
                        window.editarUsuarioInstance.destroy();
                    }
                    window.editarUsuarioInstance = new EditarUsuario();
                })
                .catch(error => {
                    console.error('Error al cargar el modal:', error);
                    alert('Error al cargar el formulario de edición');
                });
        }
        
        function cambiarEstatus(idUsuario, nuevoEstatus) {
            const accion = nuevoEstatus == 1 ? 'activar' : 'suspender';
            
            if (confirm(`¿Estás seguro de ${accion} este usuario?`)) {
                fetch('../PHP/procesar_admin_usuarios.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=cambiar_estatus&id_usu=${idUsuario}&estatus=${nuevoEstatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`✅ Usuario ${accion} correctamente`);
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
        
        function eliminarUsuario(idUsuario) {
            if (confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
                if (confirm('⚠️ ADVERTENCIA: Eliminar un usuario también eliminará todos sus datos asociados (restaurantes, favoritos, etc.). ¿Deseas continuar?')) {
                    fetch('../PHP/procesar_admin_usuarios.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `accion=eliminar&id_usu=${idUsuario}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ Usuario eliminado correctamente');
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
        }
        
        // Animación de entrada para las filas
        document.addEventListener('DOMContentLoaded', function() {
            const filas = document.querySelectorAll('#usuariosTableBody tr');
            filas.forEach((fila, index) => {
                fila.style.opacity = '0';
                fila.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    fila.style.transition = 'opacity 0.5s, transform 0.5s';
                    fila.style.opacity = '1';
                    fila.style.transform = 'translateX(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>
