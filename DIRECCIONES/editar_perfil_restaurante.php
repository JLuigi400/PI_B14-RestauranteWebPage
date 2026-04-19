<?php
session_start();
include '../PHP/db_config.php';

// Verificar que sea un dueño de restaurante
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];

// Obtener información del restaurante
$id_res_get = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt_restaurante = $conn->prepare("
    SELECT * FROM restaurante WHERE id_usu = ?" . ($id_res_get > 0 ? " AND id_res = ?" : "");
if ($id_res_get > 0) {
    $stmt_restaurante->bind_param("ii", $id_usuario, $id_res_get);
} else {
    $stmt_restaurante->bind_param("i", $id_usuario);
}
$stmt_restaurante->execute();
$restaurante = $stmt_restaurante->get_result()->fetch_assoc();

if (!$restaurante) {
    header("Location: mis_restaurantes.php?error=no_restaurante");
    exit();
}

// Si no se especificó ID, redirigir al modal de edición más completo
if ($id_res_get == 0) {
    header("Location: mis_restaurantes.php?modal=editar&id=" . $restaurante['id_res']);
    exit();
}

// Procesar actualización del perfil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_res = $_POST['nombre_res'];
    $direccion_res = $_POST['direccion_res'];
    $telefono_res = $_POST['telefono_res'];
    $url_web = $_POST['url_web'];
    $descripcion_res = $_POST['descripcion_res'];
    $latitud = !empty($_POST['latitud']) ? floatval($_POST['latitud']) : null;
    $longitud = !empty($_POST['longitud']) ? floatval($_POST['longitud']) : null;
    
    // Manejo de logo
    $ruta_logo = $restaurante['logo_res'];
    if (isset($_FILES['logo_res']) && $_FILES['logo_res']['error'] == 0) {
        $dir = "../UPLOADS/RESTAURANTES/";
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        
        $extension = pathinfo($_FILES['logo_res']['name'], PATHINFO_EXTENSION);
        $nom_archivo = "logo_" . $id_usuario . "_" . time() . "." . $extension;
        $ruta_final = $dir . $nom_archivo;
        
        if (move_uploaded_file($_FILES['logo_res']['tmp_name'], $ruta_final)) {
            $ruta_logo = $ruta_final;
        }
    }
    
    // Actualizar restaurante
    $stmt_actualizar = $conn->prepare("
        UPDATE restaurante 
        SET nombre_res = ?, direccion_res = ?, telefono_res = ?, url_web = ?, 
            descripcion_res = ?, logo_res = ?, latitud = ?, longitud = ?
        WHERE id_res = ?
    ");
    $stmt_actualizar->bind_param("sssssddi", 
        $nombre_res, $direccion_res, $telefono_res, $url_web, 
        $descripcion_res, $ruta_logo, $latitud, $longitud, 
        $restaurante['id_res']
    );
    
    if ($stmt_actualizar->execute()) {
        header("Location: editar_perfil_restaurante.php?status=success");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil del Restaurante | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        .perfil-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .perfil-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .perfil-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .logo-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
            margin-bottom: 15px;
        }
        
        #mapa {
            width: 100%;
            height: 400px;
            border-radius: 8px;
            margin-top: 15px;
            border: 2px solid #ddd;
        }
        
        .coordenadas-display {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .mapa-instructions {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #2196f3;
        }
        
        .mapa-instructions h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        
        .mapa-instructions ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .mapa-instructions li {
            margin-bottom: 5px;
            color: #424242;
        }
        
        @media (max-width: 768px) {
            .perfil-form {
                grid-template-columns: 1fr;
            }
            
            .perfil-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include '../PHP/navbar.php'; ?>
    
    <div class="perfil-container">
        <div class="perfil-header">
            <div>
                <h1 style="margin: 0;">🏪 Editar Perfil del Restaurante</h1>
                <p style="color: #666; margin: 5px 0 0 0;">Actualiza la información de tu negocio</p>
            </div>
            <a href="dashboard.php" class="btn-secondary">← Volver al Panel</a>
        </div>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert-success">
                ✅ Perfil actualizado correctamente
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="perfil-form">
            <!-- Sección Información Básica -->
            <div class="form-section">
                <h3>📋 Información Básica</h3>
                
                <div class="form-group">
                    <label for="nombre_res">Nombre del Restaurante *</label>
                    <input type="text" id="nombre_res" name="nombre_res" required
                           value="<?php echo htmlspecialchars($restaurante['nombre_res']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="direccion_res">Dirección *</label>
                    <input type="text" id="direccion_res" name="direccion_res" required
                           value="<?php echo htmlspecialchars($restaurante['direccion_res']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="telefono_res">Teléfono *</label>
                    <input type="tel" id="telefono_res" name="telefono_res" required
                           value="<?php echo htmlspecialchars($restaurante['telefono_res']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="url_web">Sitio Web (opcional)</label>
                    <input type="url" id="url_web" name="url_web"
                           value="<?php echo htmlspecialchars($restaurante['url_web']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="descripcion_res">Descripción</label>
                    <textarea id="descripcion_res" name="descripcion_res"
                              placeholder="Describe tu restaurante, especialidades, etc..."><?php echo htmlspecialchars($restaurante['descripcion_res']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="logo_res">Logo del Restaurante</label>
                    <?php if (!empty($restaurante['logo_res'])): ?>
                        <img src="<?php echo htmlspecialchars($restaurante['logo_res']); ?>" 
                             alt="Logo" class="logo-preview">
                    <?php endif; ?>
                    <input type="file" id="logo_res" name="logo_res" accept="image/*">
                    <small style="color: #666;">Formatos: JPG, PNG, GIF. Máximo 2MB.</small>
                </div>
            </div>
            
            <!-- Sección Ubicación -->
            <div class="form-section">
                <h3>📍 Ubicación en el Mapa</h3>
                
                <div class="mapa-instructions">
                    <h4>🗺️ ¿Cómo establecer tu ubicación?</h4>
                    <ul>
                        <li>Haz clic en cualquier punto del mapa para marcar tu restaurante</li>
                        <li>Usa el buscador para encontrar una dirección específica</li>
                        <li>Arrastra el marcador si necesitas ajustar la posición</li>
                        <li>Las coordenadas se actualizarán automáticamente</li>
                    </ul>
                </div>
                
                <div class="form-group">
                    <label for="busqueda_direccion">Buscar Dirección</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="busqueda_direccion" placeholder="Ej: Av. Tecnológico 1235, Cd. Juárez">
                        <button type="button" onclick="buscarDireccion()" class="btn-primary" style="padding: 10px 20px;">
                            🔍 Buscar
                        </button>
                    </div>
                </div>
                
                <div id="mapa"></div>
                
                <div class="coordenadas-display">
                    <strong>Coordenadas actuales:</strong><br>
                    Latitud: <span id="latitud_display"><?php echo $restaurante['latitud'] ?? 'No establecida'; ?></span><br>
                    Longitud: <span id="longitud_display"><?php echo $restaurante['longitud'] ?? 'No establecida'; ?></span>
                </div>
                
                <input type="hidden" id="latitud" name="latitud" value="<?php echo $restaurante['latitud'] ?? ''; ?>">
                <input type="hidden" id="longitud" name="longitud" value="<?php echo $restaurante['longitud'] ?? ''; ?>">
            </div>
            
            <!-- Botones -->
            <div style="grid-column: 1 / -1; text-align: center; margin-top: 30px;">
                <button type="submit" class="btn-primary" style="padding: 15px 40px; font-size: 16px;">
                    💾 Guardar Cambios
                </button>
            </div>
        </form>
    </div>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../JS/mapa_salud_juarez.js"></script>
    
    <script>
        let mapa = null;
        let marcadorRestaurante = null;
        
        // Inicializar mapa cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            inicializarMapaRestaurante();
        });
        
        function inicializarMapaRestaurante() {
            // Crear instancia del mapa
            mapa = new MapaSaludJuarez();
            
            // Coordenadas por defecto (centro de Ciudad Juárez)
            let coordenadasIniciales = [31.7200, -106.4600];
            
            // Si el restaurante ya tiene coordenadas, usar esas
            const latitudExistente = parseFloat(document.getElementById('latitud').value);
            const longitudExistente = parseFloat(document.getElementById('longitud').value);
            
            if (!isNaN(latitudExistente) && !isNaN(longitudExistente)) {
                coordenadasIniciales = [latitudExistente, longitudExistente];
            }
            
            // Inicializar mapa
            mapa.inicializarMapa('mapa', {
                center: coordenadasIniciales,
                zoom: 15
            });
            
            // Agregar marcador inicial si existen coordenadas
            if (!isNaN(latitudExistente) && !isNaN(longitudExistente)) {
                marcadorRestaurante = mapa.agregarMarcador(
                    coordenadasIniciales,
                    '📍 Tu Restaurante',
                    mapa.iconos.seleccionado,
                    'Tu restaurante está ubicado aquí'
                );
            }
            
            // Manejar clic en el mapa
            mapa.alClicMapa(function(coordenadas, evento) {
                actualizarMarcador(coordenadas);
            });
        }
        
        function actualizarMarcador(coordenadas) {
            // Eliminar marcador anterior si existe
            if (marcadorRestaurante) {
                mapa.mapa.removeLayer(marcadorRestaurante);
            }
            
            // Agregar nuevo marcador
            marcadorRestaurante = mapa.agregarMarcador(
                coordenadas,
                '📍 Tu Restaurante',
                mapa.iconos.seleccionado,
                'Tu restaurante está ubicado aquí'
            );
            
            // Actualizar campos ocultos
            document.getElementById('latitud').value = coordenadas[0];
            document.getElementById('longitud').value = coordenadas[1];
            
            // Actualizar display
            document.getElementById('latitud_display').textContent = coordenadas[0].toFixed(8);
            document.getElementById('longitud_display').textContent = coordenadas[1].toFixed(8);
        }
        
        async function buscarDireccion() {
            const direccion = document.getElementById('busqueda_direccion').value.trim();
            
            if (!direccion) {
                alert('Por favor ingresa una dirección para buscar');
                return;
            }
            
            try {
                const resultado = await mapa.buscarDireccion(direccion);
                
                if (resultado) {
                    actualizarMarcador([resultado.latitud, resultado.longitud]);
                    mapa.establecerVista([resultado.latitud, resultado.longitud], 16);
                    
                    // Actualizar campo de dirección con el resultado formateado
                    document.getElementById('direccion_res').value = resultado.direccion;
                } else {
                    alert('No se encontró la dirección. Intenta con una búsqueda más específica.');
                }
            } catch (error) {
                console.error('Error buscando dirección:', error);
                alert('Ocurrió un error al buscar la dirección. Por favor intenta nuevamente.');
            }
        }
        
        // Permitir buscar con Enter
        document.getElementById('busqueda_direccion').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarDireccion();
            }
        });
    </script>
</body>
</html>
