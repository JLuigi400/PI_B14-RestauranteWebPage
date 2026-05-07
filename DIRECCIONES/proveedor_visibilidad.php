<?php
session_start();
include '../PHP/navbar.php';

// Verificar que el usuario sea proveedor (rol 4)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 4) {
    header("Location: ../login.php");
    exit();
}

// Obtener id_proveedor desde la tabla proveedores
include '../PHP/db_config.php';
$id_usu = $_SESSION['id_usu'];

$sql_proveedor = "SELECT id_proveedor, nombre_empresa, latitud_proveedor, longitud_proveedor FROM proveedores WHERE id_usu = ?";
$stmt_proveedor = $conn->prepare($sql_proveedor);
$stmt_proveedor->bind_param("i", $id_usu);
$stmt_proveedor->execute();
$result_proveedor = $stmt_proveedor->get_result();

if ($result_proveedor->num_rows === 0) {
    header("Location: ../login.php");
    exit();
}

$proveedor_data = $result_proveedor->fetch_assoc();
$id_proveedor = $proveedor_data['id_proveedor'];
$nombre_empresa = $proveedor_data['nombre_empresa'];
$latitud = $proveedor_data['latitud_proveedor'];
$longitud = $proveedor_data['longitud_proveedor'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visibilidad | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/modal_theme.css">
    <link rel="stylesheet" href="../CSS/modal_social_icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        /* Estilos específicos para visibilidad - Tema Industrial Dark */
        .sj-visibilidad-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .sj-visibilidad-header {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid #27ae60;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .sj-visibilidad-title {
            color: #27ae60;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-shadow: 0 0 20px rgba(39, 174, 96, 0.5);
        }

        .sj-visibilidad-subtitle {
            color: #bdc3c7;
            font-size: 1rem;
            margin: 0;
        }

        /* Estadísticas de visibilidad */
        .sj-estadisticas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .sj-estadistica-card {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border: 1px solid #34495e;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .sj-estadistica-card:hover {
            transform: translateY(-5px);
            border-color: #27ae60;
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
        }

        .sj-estadistica-number {
            color: #27ae60;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 0 15px rgba(39, 174, 96, 0.5);
        }

        .sj-estadistica-label {
            color: #bdc3c7;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Mapa */
        .sj-mapa-section {
            background: rgba(52, 73, 94, 0.8);
            border: 1px solid #34495e;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .sj-mapa-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .sj-mapa-title {
            color: #ecf0f1;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .sj-mapa-controls {
            display: flex;
            gap: 10px;
        }

        .sj-mapa-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .sj-mapa-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .sj-mapa-btn.actualizar {
            background: #27ae60;
        }

        .sj-mapa-btn.actualizar:hover {
            background: #219a52;
        }

        #mapaProveedor {
            height: 500px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #34495e;
        }

        /* Opciones de visibilidad */
        .sj-opciones-visibilidad {
            background: rgba(52, 73, 94, 0.8);
            border: 1px solid #34495e;
            border-radius: 12px;
            padding: 20px;
        }

        .sj-opciones-title {
            color: #ecf0f1;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .sj-opciones-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .sj-opcion-card {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border: 1px solid #34495e;
            border-radius: 8px;
            padding: 15px;
        }

        .sj-opcion-label {
            color: #bdc3c7;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }

        .sj-opcion-input,
        .sj-opcion-select {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #34495e;
            border-radius: 6px;
            color: #ecf0f1;
            font-size: 1rem;
        }

        .sj-opcion-input:focus,
        .sj-opcion-select:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 10px rgba(39, 174, 96, 0.3);
        }

        .sj-opcion-btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }

        .sj-opcion-btn:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sj-estadisticas-grid {
                grid-template-columns: 1fr;
            }

            .sj-opciones-grid {
                grid-template-columns: 1fr;
            }

            .sj-mapa-header {
                flex-direction: column;
                gap: 15px;
            }

            #mapaProveedor {
                height: 400px;
            }
        }
    </style>
</head>
<body>
    <div class="sj-visibilidad-container">
        <!-- Header -->
        <div class="sj-visibilidad-header">
            <h1 class="sj-visibilidad-title">Visibilidad de tu Negocio</h1>
            <p class="sj-visibilidad-subtitle">Monitorea tu presencia en el mapa y optimiza tu visibilidad para atraer más clientes</p>
        </div>

        <!-- Estadísticas de Visibilidad -->
        <div class="sj-estadisticas-grid">
            <div class="sj-estadistica-card">
                <div class="sj-estadistica-number" id="vistasSemana">0</div>
                <div class="sj-estadistica-label">Vistas esta semana</div>
            </div>
            <div class="sj-estadistica-card">
                <div class="sj-estadistica-number" id="contactosSemana">0</div>
                <div class="sj-estadistica-label">Contactos esta semana</div>
            </div>
            <div class="sj-estadistica-card">
                <div class="sj-estadistica-number" id="vistasTotales">0</div>
                <div class="sj-estadistica-label">Vistas totales</div>
            </div>
            <div class="sj-estadistica-card">
                <div class="sj-estadistica-number" id="productosActivos">0</div>
                <div class="sj-estadistica-label">Productos activos</div>
            </div>
        </div>

        <!-- Mapa -->
        <div class="sj-mapa-section">
            <div class="sj-mapa-header">
                <h2 class="sj-mapa-title">Tu Ubicación en el Mapa</h2>
                <div class="sj-mapa-controls">
                    <button class="sj-mapa-btn actualizar" onclick="actualizarPosicion()">Actualizar Posición</button>
                    <button class="sj-mapa-btn" onclick="centrarMapa()">Centrar Mapa</button>
                </div>
            </div>
            <div id="mapaProveedor"></div>
        </div>

        <!-- Opciones de Visibilidad -->
        <div class="sj-opciones-visibilidad">
            <h2 class="sj-opciones-title">Opciones de Visibilidad</h2>
            <div class="sj-opciones-grid">
                <div class="sj-opcion-card">
                    <label class="sj-opcion-label">Estado de Visibilidad</label>
                    <select class="sj-opcion-select" id="estadoVisibilidad">
                        <option value="activo">Activo y Visible</option>
                        <option value="inactivo">Inactivo y Oculto</option>
                        <option value="destacado">Destacado</option>
                    </select>
                </div>
                
                <div class="sj-opcion-card">
                    <label class="sj-opcion-label">Radio de Búsqueda (km)</label>
                    <input type="number" class="sj-opcion-input" id="radioBusqueda" value="10" min="1" max="100" step="1">
                </div>
                
                <div class="sj-opcion-card">
                    <label class="sj-opcion-label">Descripción Destacada</label>
                    <textarea class="sj-opcion-input" id="descripcionDestacada" rows="3" placeholder="Describe por qué los restaurantes deberían elegirte..."></textarea>
                </div>
                
                <div class="sj-opcion-card">
                    <button class="sj-opcion-btn" onclick="guardarOpcionesVisibilidad()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../JS/visibilidad_proveedor.js"></script>
    <script src="../JS/session_check.js"></script>
</body>
</html>
