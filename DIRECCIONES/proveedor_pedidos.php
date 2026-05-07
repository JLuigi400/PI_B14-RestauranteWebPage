<?php
session_start();
include '../PHP/navbar.php';

// Verificar que el usuario sea proveedor (rol 4)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 4) {
    header("Location: ../login.php");
    exit();
}

// Obtener id_proveedor desde la tabla proveedores
$id_usu = $_SESSION['id_usu'];
include '../PHP/db_config.php';

$sql_proveedor = "SELECT id_proveedor FROM proveedores WHERE id_usu = ?";
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/modal_theme.css">
    <link rel="stylesheet" href="../CSS/modal_social_icons.css">
    <style>
        /* Estilos específicos para gestión de pedidos - Tema Industrial Dark */
        .sj-pedidos-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .sj-pedidos-header {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid #27ae60;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .sj-pedidos-title {
            color: #27ae60;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-shadow: 0 0 20px rgba(39, 174, 96, 0.5);
        }

        .sj-pedidos-subtitle {
            color: #bdc3c7;
            font-size: 1rem;
            margin: 0;
        }

        /* Filtros de pedidos */
        .sj-filtros-pedidos {
            background: rgba(52, 73, 94, 0.8);
            border: 1px solid #34495e;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .sj-filtro-select {
            background: #2c3e50;
            color: #ecf0f1;
            border: 1px solid #34495e;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            min-width: 150px;
        }

        .sj-filtro-select:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 10px rgba(39, 174, 96, 0.3);
        }

        /* Grid de pedidos */
        .sj-pedidos-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .sj-pedido-card {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border: 1px solid #34495e;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .sj-pedido-card:hover {
            transform: translateY(-5px);
            border-color: #27ae60;
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
        }

        .sj-pedido-header {
            padding: 15px 20px;
            border-bottom: 1px solid #34495e;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sj-pedido-estado {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
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

        .sj-estado-cancelado {
            background: #e74c3c;
            color: white;
        }

        .sj-pedido-id {
            color: #ecf0f1;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .sj-pedido-body {
            padding: 20px;
        }

        .sj-pedido-info {
            color: #95a5a6;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .sj-pedido-info-label {
            color: #7f8c8d;
            font-weight: 500;
        }

        .sj-pedido-info-value {
            color: #ecf0f1;
            font-weight: 600;
        }

        .sj-pedido-restaurante {
            color: #3498db;
            font-weight: 600;
        }

        .sj-pedido-total {
            color: #f39c12;
            font-size: 1.2rem;
            font-weight: 700;
            margin: 10px 0;
            text-align: center;
        }

        .sj-pedido-acciones {
            padding: 15px 20px;
            border-top: 1px solid #34495e;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .sj-accion-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .sj-accion-btn:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .sj-accion-btn.confirmar {
            background: #27ae60;
        }

        .sj-accion-btn.confirmar:hover {
            background: #219a52;
        }

        .sj-accion-btn.enviar {
            background: #f39c12;
        }

        .sj-accion-btn.enviar:hover {
            background: #e67e22;
        }

        .sj-accion-btn.entregado {
            background: #2ecc71;
        }

        .sj-accion-btn.entregado:hover {
            background: #27ae60;
        }

        .sj-accion-btn.cancelar {
            background: #e74c3c;
        }

        .sj-accion-btn.cancelar:hover {
            background: #c0392b;
        }

        /* Modal de detalles */
        .sj-modal-pedido {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .sj-modal-content {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border: 1px solid #34495e;
            border-radius: 12px;
            padding: 30px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .sj-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .sj-modal-title {
            color: #27ae60;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .sj-modal-close {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }

        .sj-productos-lista {
            margin: 20px 0;
        }

        .sj-producto-item {
            background: rgba(52, 73, 94, 0.5);
            border: 1px solid #34495e;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sj-producto-nombre {
            color: #ecf0f1;
            font-weight: 500;
            flex: 1;
        }

        .sj-producto-cantidad {
            color: #f39c12;
            font-weight: 600;
            margin: 0 15px;
        }

        .sj-producto-precio {
            color: #27ae60;
            font-weight: 700;
            min-width: 80px;
            text-align: right;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sj-filtros-pedidos {
                justify-content: center;
            }

            .sj-pedido-acciones {
                flex-direction: column;
                align-items: stretch;
            }

            .sj-accion-btn {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="sj-pedidos-container">
        <!-- Header -->
        <div class="sj-pedidos-header">
            <h1 class="sj-pedidos-title">Gestión de Pedidos</h1>
            <p class="sj-pedidos-subtitle">Administra los pedidos recibidos de restaurantes</p>
        </div>

        <!-- Filtros -->
        <div class="sj-filtros-pedidos">
            <span style="color: #ecf0f1; font-weight: 500;">Filtrar por:</span>
            <select class="sj-filtro-select" id="filtroEstado">
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendientes</option>
                <option value="Confirmado">Confirmados</option>
                <option value="Enviado">Enviados</option>
                <option value="Entregado">Entregados</option>
                <option value="Cancelado">Cancelados</option>
            </select>
            
            <select class="sj-filtro-select" id="filtroFecha">
                <option value="">Todas las fechas</option>
                <option value="hoy">Hoy</option>
                <option value="semana">Esta semana</option>
                <option value="mes">Este mes</option>
            </select>

            <button class="sj-accion-btn" onclick="cargarPedidos()">Actualizar</button>
        </div>

        <!-- Grid de Pedidos -->
        <div class="sj-pedidos-grid" id="pedidosGrid">
            <!-- Los pedidos se cargarán dinámicamente con JavaScript -->
        </div>

        <!-- Modal de Detalles -->
        <div class="sj-modal-pedido" id="modalDetalles">
            <div class="sj-modal-content">
                <div class="sj-modal-header">
                    <h2 class="sj-modal-title">Detalles del Pedido</h2>
                    <button class="sj-modal-close" onclick="cerrarModal()">×</button>
                </div>
                
                <div id="detallesContenido">
                    <!-- Los detalles se cargarán dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../JS/gestion_pedidos_proveedor.js"></script>
    <script src="../JS/session_check.js"></script>
</body>
</html>
