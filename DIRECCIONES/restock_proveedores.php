<?php
session_start();
include '../PHP/navbar.php';

// Verificar que el usuario sea dueño (rol 2)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Re-stock de Proveedores | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/modal_theme.css">
    <link rel="stylesheet" href="../CSS/modal_social_icons.css">
    <style>
        /* Estilos específicos para re-stock - Tema Industrial Dark */
        .sj-restock-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .sj-restock-header {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid #27ae60;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .sj-restock-title {
            color: #27ae60;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-shadow: 0 0 20px rgba(39, 174, 96, 0.5);
        }

        .sj-restock-subtitle {
            color: #bdc3c7;
            font-size: 1rem;
            margin: 0;
        }

        /* Opciones de Re-stock */
        .sj-opciones-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .sj-opcion-card {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border: 1px solid #34495e;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .sj-opcion-card:hover {
            transform: translateY(-5px);
            border-color: #27ae60;
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
        }

        .sj-opcion-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .sj-opcion-title {
            color: #ecf0f1;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .sj-opcion-description {
            color: #95a5a6;
            font-size: 0.9rem;
            margin-bottom: 20px;
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
        }

        .sj-opcion-btn:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .sj-opcion-btn.automático {
            background: #3498db;
        }

        .sj-opcion-btn.automático:hover {
            background: #2980b9;
        }

        /* Lista de Solicitudes */
        .sj-solicitudes-section {
            background: rgba(52, 73, 94, 0.8);
            border: 1px solid #34495e;
            border-radius: 12px;
            padding: 25px;
        }

        .sj-solicitudes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .sj-solicitudes-title {
            color: #ecf0f1;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .sj-solicitudes-grid {
            display: grid;
            gap: 15px;
        }

        .sj-solicitud-item {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border: 1px solid #34495e;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .sj-solicitud-item:hover {
            transform: translateY(-2px);
            border-color: #27ae60;
        }

        .sj-solicitud-info {
            flex: 1;
        }

        .sj-solicitud-producto {
            color: #ecf0f1;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .sj-solicitud-proveedor {
            color: #3498db;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .sj-solicitud-fecha {
            color: #95a5a6;
            font-size: 0.8rem;
        }

        .sj-solicitud-estado {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .sj-estado-pendiente {
            background: #f39c12;
            color: white;
        }

        .sj-estado-confirmado {
            background: #3498db;
            color: white;
        }

        .sj-estado-enviado {
            background: #9b59b6;
            color: white;
        }

        .sj-estado-entregado {
            background: #27ae60;
            color: white;
        }

        /* Empty State */
        .sj-empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .sj-empty-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .sj-empty-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .sj-empty-description {
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sj-opciones-container {
                grid-template-columns: 1fr;
            }

            .sj-solicitud-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="sj-restock-container">
        <!-- Header -->
        <div class="sj-restock-header">
            <h1 class="sj-restock-title">Re-stock de Proveedores</h1>
            <p class="sj-restock-subtitle">Gestiona tus solicitudes de re-stock de forma manual o automática</p>
        </div>

        <!-- Opciones de Re-stock -->
        <div class="sj-opciones-container">
            <div class="sj-opcion-card">
                <div class="sj-opcion-icon">🛒</div>
                <h3 class="sj-opcion-title">Re-stock Manual</h3>
                <p class="sj-opcion-description">Selecciona productos y proveedores manualmente para solicitar re-stock</p>
                <button class="sj-opcion-btn" onclick="window.location.href='solicitar_pedido_proveedor.php'">
                    Solicitar Manualmente
                </button>
            </div>

            <div class="sj-opcion-card">
                <div class="sj-opcion-icon">🤖</div>
                <h3 class="sj-opcion-title">Re-stock Automático</h3>
                <p class="sj-opcion-description">Configura umbrales mínimos y el sistema solicita automáticamente</p>
                <button class="sj-opcion-btn automático" onclick="configurarAutomatico()">
                    Configurar Automático
                </button>
            </div>

            <div class="sj-opcion-card">
                <div class="sj-opcion-icon">📊</div>
                <h3 class="sj-opcion-title">Proveedores Cercanos</h3>
                <p class="sj-opcion-description">Encuentra proveedores cerca de tus restaurantes</p>
                <button class="sj-opcion-btn" onclick="window.location.href='proveedores_cercanos.php'">
                    Ver Mapa
                </button>
            </div>
        </div>

        <!-- Lista de Solicitudes -->
        <div class="sj-solicitudes-section">
            <div class="sj-solicitudes-header">
                <h2 class="sj-solicitudes-title">Solicitudes Activas</h2>
                <button class="sj-opcion-btn" onclick="cargarSolicitudes()">
                    Actualizar
                </button>
            </div>

            <div class="sj-solicitudes-grid" id="solicitudesGrid">
                <div class="sj-empty-state">
                    <div class="sj-empty-icon">📦</div>
                    <div class="sj-empty-title">Sin solicitudes activas</div>
                    <div class="sj-empty-description">Tus solicitudes de re-stock aparecerán aquí</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../JS/restock_proveedores.js"></script>
    <script src="../JS/session_check.js"></script>
</body>
</html>
