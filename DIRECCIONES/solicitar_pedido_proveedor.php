<?php
session_start();
include '../PHP/navbar.php';
include '../PHP/db_config.php';

// Verificar que el usuario sea dueño (rol 2)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];

// Obtener nombre del usuario desde sesión y perfiles
$nombre_usuario = 'Usuario del Sistema';
if (isset($_SESSION['id_usu'])) {
    $sql_usuario = "SELECT u.username_usu, p.nombre_per, p.apellidos_per 
                    FROM usuarios u 
                    LEFT JOIN perfiles p ON u.id_usu = p.id_usu 
                    WHERE u.id_usu = ?";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("i", $id_usuario);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    if ($usuario_data = $result_usuario->fetch_assoc()) {
        if ($usuario_data['nombre_per']) {
            $nombre_usuario = $usuario_data['nombre_per'] . ' ' . $usuario_data['apellidos_per'];
        } else {
            $nombre_usuario = $usuario_data['username_usu'];
        }
    }
}

// Obtener nombre del restaurante
$nombre_restaurante = 'Mi Restaurante';
$sql_restaurante = "SELECT nombre_res FROM restaurante WHERE id_usu = ?";
$stmt_restaurante = $conn->prepare($sql_restaurante);
$stmt_restaurante->bind_param("i", $id_usuario);
$stmt_restaurante->execute();
$result_restaurante = $stmt_restaurante->get_result();
if ($restaurante_data = $result_restaurante->fetch_assoc()) {
    $nombre_restaurante = $restaurante_data['nombre_res'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Pedido a Proveedor | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/modal_theme.css">
    <link rel="stylesheet" href="../CSS/modal_social_icons.css">
    <style>
        /* Estilos específicos para solicitud de pedido - Tema Industrial Dark */
        .sj-solicitud-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .sj-solicitud-header {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid #27ae60;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .sj-solicitud-title {
            color: #27ae60;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-shadow: 0 0 20px rgba(39, 174, 96, 0.5);
        }

        .sj-solicitud-subtitle {
            color: #bdc3c7;
            font-size: 1rem;
            margin: 0;
        }

        /* Formulario de solicitud */
        .sj-solicitud-form {
            background: rgba(52, 73, 94, 0.8);
            border: 1px solid #34495e;
            border-radius: 12px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        .sj-form-grupo {
            margin-bottom: 20px;
        }

        .sj-form-label {
            color: #ecf0f1;
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }

        .sj-form-input,
        .sj-form-select,
        .sj-form-textarea {
            width: 100%;
            padding: 12px;
            background: #1a1a1a;
            border: 1px solid #34495e;
            border-radius: 6px;
            color: #ecf0f1;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .sj-form-input:focus,
        .sj-form-select:focus,
        .sj-form-textarea:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 10px rgba(39, 174, 96, 0.3);
        }

        .sj-form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .sj-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .sj-btn-enviar {
            background: #27ae60;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .sj-btn-enviar:hover {
            background: #219a52;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
        }

        /* Productos seleccionados */
        .sj-productos-seleccionados {
            background: rgba(52, 73, 94, 0.5);
            border: 1px solid #34495e;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .sj-producto-item {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border: 1px solid #34495e;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sj-producto-info {
            flex: 1;
        }

        .sj-producto-nombre {
            color: #ecf0f1;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sj-producto-precio {
            color: #f39c12;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .sj-producto-cantidad {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sj-cantidad-input {
            width: 80px;
            padding: 8px;
            background: #1a1a1a;
            border: 1px solid #34495e;
            border-radius: 4px;
            color: #ecf0f1;
            text-align: center;
        }

        .sj-eliminar-producto {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .sj-eliminar-producto:hover {
            background: #c0392b;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sj-form-grid {
                grid-template-columns: 1fr;
            }

            .sj-solicitud-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Elementos hidden para datos de sesión (leídos por JS para EmailJS) -->
    <input type="hidden" id="nombre_res_display" value="<?php echo htmlspecialchars($nombre_restaurante); ?>">
    <input type="hidden" id="nombre_usuario_display" value="<?php echo htmlspecialchars($nombre_usuario); ?>">
    
    <div class="sj-solicitud-container">
        <!-- Header -->
        <div class="sj-solicitud-header">
            <h1 class="sj-solicitud-title">Solicitar Pedido a Proveedor</h1>
            <p class="sj-solicitud-subtitle">Realiza pedidos directamente a tus proveedores favoritos</p>
        </div>

        <!-- Formulario de Solicitud -->
        <div class="sj-solicitud-form">
            <form id="formSolicitudPedido">
                <div class="sj-form-grupo">
                    <label class="sj-form-label" for="proveedor">Seleccionar Proveedor *</label>
                    <select class="sj-form-select" id="proveedor" name="proveedor" required>
                        <option value="">Cargando proveedores...</option>
                    </select>
                </div>

                <div class="sj-form-grid">
                    <div class="sj-form-grupo">
                        <label class="sj-form-label" for="direccionEntrega">Dirección de Entrega *</label>
                        <input type="text" class="sj-form-input" id="direccionEntrega" name="direccion_entrega" required>
                    </div>
                    <div class="sj-form-grupo">
                        <label class="sj-form-label" for="metodoPago">Método de Pago *</label>
                        <select class="sj-form-select" id="metodoPago" name="metodo_pago" required>
                            <option value="">Selecciona método</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Transferencia">Transferencia Bancaria</option>
                            <option value="Tarjeta">Tarjeta de Crédito</option>
                            <option value="PayPal">PayPal</option>
                        </select>
                    </div>
                </div>

                <div class="sj-form-grupo">
                    <label class="sj-form-label" for="notasPedido">Notas del Pedido</label>
                    <textarea class="sj-form-textarea" id="notasPedido" name="notas_pedido" placeholder="Instrucciones especiales para el proveedor..."></textarea>
                </div>

                <!-- Productos Disponibles -->
                <div class="sj-form-grupo">
                    <label class="sj-form-label">Productos Disponibles</label>
                    <div id="productosDisponibles">
                        <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                            Cargando productos del proveedor...
                        </div>
                    </div>
                </div>

                <!-- Productos Seleccionados -->
                <div class="sj-productos-seleccionados" id="productosSeleccionados" style="display: none;">
                    <h3 style="color: #27ae60; margin-bottom: 15px;">Productos Seleccionados</h3>
                    <div id="listaProductosSeleccionados"></div>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #34495e;">
                        <strong style="color: #ecf0f1;">Total del Pedido: </strong>
                        <span id="totalPedido" style="color: #f39c12; font-size: 1.3rem; font-weight: 700;">$0.00</span>
                    </div>
                </div>

                <button type="submit" class="sj-btn-enviar">Enviar Pedido</button>
            </form>
        </div>
    </div>

    <!-- EmailJS SDK v4 -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    <script>
        // Inicialización base - la llave real se pasa en cada envío
        console.log('📧 EmailJS SDK v4 cargado');
    </script>
    
    <!-- Scripts -->
    <script src="../JS/solicitud_pedido_proveedor.js"></script>
    <script src="../JS/session_check.js"></script>
</body>
</html>
