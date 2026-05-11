<?php
session_start();
include '../PHP/db_config.php';
include '../PHP/navbar.php';

// Verificación de sesión (solo dueños)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];

// Obtener restaurante del dueño
$query_res = $conn->prepare("SELECT id_res, nombre_res FROM restaurante WHERE id_usu = ?");
$query_res->bind_param("i", $id_usuario);
$query_res->execute();
$result_res = $query_res->get_result();
$restaurante = $result_res->fetch_assoc();

if (!$restaurante) {
    echo "Error: No se encontró un restaurante asociado a esta cuenta.";
    exit();
}

$id_res = $restaurante['id_res'];
$nombre_restaurante = $restaurante['nombre_res'];

// Función auxiliar para detectar si es una petición AJAX
function esAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Procesar solicitud de re-stock
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    // =================================================================
    // ACCIÓN: Crear Pedido B2B (Transaccional - Reemplazo de Triggers)
    // =================================================================
    if ($action == 'crear_pedido_b2b') {
        $id_proveedor = intval($_POST['id_proveedor'] ?? 0);
        $proveedor_nombre = $_POST['proveedor_nombre'] ?? '';
        $notas_pedido = $_POST['notas'] ?? '';
        $direccion_entrega = $_POST['direccion_entrega'] ?? '';
        
        $respuesta = [];
        
        // Validar datos mínimos
        if ($id_proveedor <= 0) {
            $respuesta = [
                'success' => false,
                'message' => 'ID de proveedor no válido'
            ];
        } else {
            // INICIAR TRANSACCIÓN
            $conn->begin_transaction();
            
            try {
                // 1. INSERT EN pedidos_proveedor
                $estado_inicial = 'Pendiente';
                $subtotal = 0.00;
                $total = 0.00;
                
                $stmt_pedido = $conn->prepare("
                    INSERT INTO pedidos_proveedor 
                    (id_proveedor, id_restaurante, id_usuario_solicitante, estado_pedido, 
                     subtotal_productos, total_pedido, direccion_entrega, notas_pedido, fecha_solicitud)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt_pedido->bind_param("iiisddsss", 
                    $id_proveedor, 
                    $id_res, 
                    $id_usuario,
                    $estado_inicial,
                    $subtotal,
                    $total,
                    $direccion_entrega,
                    $notas_pedido
                );
                
                if (!$stmt_pedido->execute()) {
                    throw new Exception("Error insertando pedido: " . $stmt_pedido->error);
                }
                
                // 2. OBTENER ID INSERTADO
                $id_pedido = $conn->insert_id;
                
                // 3. INSERT MASIVO EN detalles_pedido_proveedor
                $productos_insertados = 0;
                $detalles_pedido = [];
                $lista_productos_email = [];
                $subtotal_acumulado = 0;
                
                if (isset($_POST['productos']) && is_array($_POST['productos'])) {
                    $stmt_detalle = $conn->prepare("
                        INSERT INTO detalles_pedido_proveedor 
                        (id_pedido, id_producto, cantidad_solicitada, precio_unitario_pedido, subtotal_detalle)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    foreach ($_POST['productos'] as $id_producto => $datos_producto) {
                        $cantidad = floatval($datos_producto['cantidad'] ?? 0);
                        $precio = floatval($datos_producto['precio'] ?? 0);
                        $nombre_producto = $datos_producto['nombre'] ?? 'Producto';
                        
                        if ($cantidad > 0) {
                            $subtotal_detalle = $cantidad * $precio;
                            $precio_unitario = $precio > 0 ? $precio : 0;
                            
                            $stmt_detalle->bind_param("iiddd", 
                                $id_pedido, 
                                $id_producto, 
                                $cantidad,
                                $precio_unitario,
                                $subtotal_detalle
                            );
                            
                            if (!$stmt_detalle->execute()) {
                                throw new Exception("Error insertando detalle: " . $stmt_detalle->error);
                            }
                            
                            $productos_insertados++;
                            $subtotal_acumulado += $subtotal_detalle;
                            
                            $detalles_pedido[] = [
                                'id_producto' => $id_producto,
                                'nombre' => $nombre_producto,
                                'cantidad' => $cantidad,
                                'precio_unitario' => $precio_unitario,
                                'subtotal' => $subtotal_detalle
                            ];
                            
                            // Formatear para EmailJS
                            $lista_productos_email[] = "{$nombre_producto}: {$cantidad} x $" . number_format($precio_unitario, 2);
                        }
                    }
                }
                
                // Actualizar totales en pedido
                $stmt_update = $conn->prepare("
                    UPDATE pedidos_proveedor 
                    SET subtotal_productos = ?, total_pedido = ?
                    WHERE id_pedido = ?
                ");
                $stmt_update->bind_param("ddi", $subtotal_acumulado, $subtotal_acumulado, $id_pedido);
                $stmt_update->execute();
                
                // 4. COMMIT - Todo exitoso
                $conn->commit();
                
                // Preparar datos para EmailJS
                $datos_email = [
                    'proveedor_nombre' => $proveedor_nombre,
                    'restaurante_nombre' => $nombre_restaurante,
                    'lista_productos' => implode("\n", $lista_productos_email),
                    'total_pedido' => number_format($subtotal_acumulado, 2),
                    'id_pedido' => $id_pedido,
                    'notas' => $notas_pedido
                ];
                
                // Respuesta exitosa
                $respuesta = [
                    'success' => true,
                    'message' => 'Pedido B2B creado exitosamente',
                    'id_pedido' => $id_pedido,
                    'datos_email' => $datos_email,
                    'total_productos' => $productos_insertados,
                    'total_pedido' => $subtotal_acumulado,
                    'detalles' => $detalles_pedido,
                    'fecha_creacion' => date('Y-m-d H:i:s')
                ];
                
            } catch (Exception $e) {
                // ROLLBACK - Algo falló
                $conn->rollback();
                
                $respuesta = [
                    'success' => false,
                    'message' => 'Error en transacción: ' . $e->getMessage()
                ];
                
                error_log("Error transaccional en pedido B2B: " . $e->getMessage());
            }
        }
        
        // Retornar JSON (siempre AJAX para B2B)
        header('Content-Type: application/json');
        echo json_encode($respuesta);
        exit();
    }
    
    // =================================================================
    // ACCIÓN: Crear Pedido de Re-stock Interno (Original)
    // =================================================================
    if ($action == 'crear_pedido') {
        $proveedor = $_POST['proveedor'];
        $fecha_estimada = $_POST['fecha_estimada'];
        $notas = $_POST['notas'] ?? '';
        
        // Crear el pedido
        $stmt_pedido = $conn->prepare("
            INSERT INTO pedidos_restock (id_res, proveedor, fecha_estimada, notas, estado, fecha_creacion, creado_por)
            VALUES (?, ?, ?, ?, 'pendiente', NOW(), ?)
        ");
        $stmt_pedido->bind_param("isssi", $id_res, $proveedor, $fecha_estimada, $notas, $id_usuario);
        
        $respuesta = [];
        
        if ($stmt_pedido->execute()) {
            $id_pedido = $conn->insert_id;
            
            // Agregar productos al pedido
            $productos_insertados = 0;
            $detalles_pedido = [];
            if (isset($_POST['productos']) && is_array($_POST['productos'])) {
                foreach ($_POST['productos'] as $id_inv => $cantidad) {
                    if ($cantidad > 0) {
                        // Obtener nombre del insumo
                        $stmt_nombre = $conn->prepare("SELECT nombre_insumo FROM inventario WHERE id_inv = ?");
                        $stmt_nombre->bind_param("i", $id_inv);
                        $stmt_nombre->execute();
                        $result_nombre = $stmt_nombre->get_result();
                        $nombre_insumo = $result_nombre->fetch_assoc()['nombre_insumo'] ?? 'Insumo';
                        
                        $stmt_detalle = $conn->prepare("
                            INSERT INTO pedido_detalle (id_pedido, id_inv, cantidad_solicitada)
                            VALUES (?, ?, ?)
                        ");
                        $stmt_detalle->bind_param("iid", $id_pedido, $id_inv, $cantidad);
                        if ($stmt_detalle->execute()) {
                            $productos_insertados++;
                            $detalles_pedido[] = [
                                'nombre' => $nombre_insumo,
                                'cantidad' => $cantidad
                            ];
                        }
                    }
                }
            }
            
            // TRIGGER REPLICADO EN PHP: actualizar_total_productos_pedido
            $stmt_update_total = $conn->prepare("
                UPDATE pedidos_restock 
                SET total_productos = (
                    SELECT COUNT(*) 
                    FROM pedido_detalle 
                    WHERE id_pedido = ?
                )
                WHERE id_pedido = ?
            ");
            $stmt_update_total->bind_param("ii", $id_pedido, $id_pedido);
            if (!$stmt_update_total->execute()) {
                error_log("Error actualizando total_productos para pedido $id_pedido: " . $conn->error);
            }
            
            // Preparar respuesta exitosa
            $respuesta = [
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'id_pedido' => $id_pedido,
                'proveedor' => $proveedor,
                'fecha_estimada' => $fecha_estimada,
                'notas' => $notas,
                'total_productos' => $productos_insertados,
                'detalles' => $detalles_pedido,
                'fecha_creacion' => date('Y-m-d H:i:s')
            ];
            
        } else {
            // Error al crear el pedido
            $respuesta = [
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $conn->error
            ];
        }
        
        // Si es AJAX, retornar JSON
        if (esAjax()) {
            header('Content-Type: application/json');
            echo json_encode($respuesta);
            exit();
        }
        
        // Si no es AJAX, hacer redirect tradicional
        if ($respuesta['success']) {
            header("Location: restock_inventario.php?status=pedido_creado");
        } else {
            header("Location: restock_inventario.php?status=error&msg=" . urlencode($respuesta['message']));
        }
        exit();
    }
}

// Obtener ingredientes con stock bajo
$stock_bajo = 10;
$stmt_bajo = $conn->prepare("
    SELECT id_inv, nombre_insumo, stock_inv, medida_inv 
    FROM inventario 
    WHERE id_res = ? AND stock_inv <= ?
    ORDER BY stock_inv ASC
");
$stmt_bajo->bind_param("id", $id_res, $stock_bajo);
$stmt_bajo->execute();
$ingredientes_bajo = $stmt_bajo->get_result();

// Obtener todos los ingredientes para selección
$stmt_todos = $conn->prepare("
    SELECT id_inv, nombre_insumo, stock_inv, medida_inv 
    FROM inventario 
    WHERE id_res = ?
    ORDER BY nombre_insumo ASC
");
$stmt_todos->bind_param("i", $id_res);
$stmt_todos->execute();
$ingredientes_todos = $stmt_todos->get_result();

// Obtener pedidos recientes
$stmt_pedidos = $conn->prepare("
    SELECT p.*, COUNT(pd.id_detalle) as total_productos
    FROM pedidos_restock p
    LEFT JOIN pedido_detalle pd ON p.id_pedido = pd.id_pedido
    WHERE p.id_res = ?
    ORDER BY p.fecha_creacion DESC
    LIMIT 5
");
$stmt_pedidos->bind_param("i", $id_res);
$stmt_pedidos->execute();
$pedidos_recientes = $stmt_pedidos->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Re-stock | <?php echo htmlspecialchars($nombre_restaurante); ?></title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/platillos.css">
    <style>
        .alerta-emergente {
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }
        
        .pedido-form {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .producto-card {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background: #f9f9f9;
            transition: all 0.3s ease;
        }
        
        .producto-card:hover {
            border-color: #2D5A27;
            transform: translateY(-2px);
        }
        
        .producto-card.stock-bajo {
            border-color: #ff6b6b;
            background: #fff5f5;
        }
        
        .producto-header {
            font-weight: bold;
            margin-bottom: 10px;
            color: #2D5A27;
        }
        
        .stock-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .cantidad-input {
            width: 80px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            text-align: center;
        }
        
        .pedidos-list {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .pedido-item {
            border-left: 4px solid #2D5A27;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
            border-radius: 0 8px 8px 0;
        }
        
        .estado-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .estado-enviado {
            background: #cce5ff;
            color: #004085;
        }
        
        .estado-recibido {
            background: #d4edda;
            color: #155724;
        }
        
        .proveedor-select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2D5A27;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .urgente-tag {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="sj-page-title">🛒 Gestión de Re-stock</h1>
        <p style="color: #666; margin-bottom: 20px;">
            <strong><?php echo htmlspecialchars($nombre_restaurante); ?></strong> · 
            Gestiona tus pedidos de reabastecimiento
        </p>
        
        <?php if ($ingredientes_bajo->num_rows > 0): ?>
            <div class="alerta-emergente">
                <h3>🚨 ¡Alerta de Stock Bajo!</h3>
                <p>Tienes <strong><?php echo $ingredientes_bajo->num_rows; ?></strong> ingrediente(s) que necesitan re-stock urgente.</p>
                <p>Te recomendamos crear un pedido inmediatamente para evitar interrupciones en tu servicio.</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'pedido_creado'): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ✅ Pedido de re-stock creado exitosamente. Puedes ver su estado en la lista de pedidos.
            </div>
        <?php endif; ?>
        
        <!-- Formulario de nuevo pedido -->
        <div class="pedido-form">
            <h2>📦 Crear Nuevo Pedido de Re-stock</h2>
            
            <form method="POST" action="restock_inventario.php">
                <input type="hidden" name="action" value="crear_pedido">
                
                <div class="form-group">
                    <label for="proveedor">Proveedor</label>
                    <select id="proveedor" name="proveedor" class="proveedor-select" required>
                        <option value="">Seleccionar proveedor...</option>
                        <option value="Costco">Costco</option>
                        <option value="Sams Club">Sams Club</option>
                        <option value="Smart">Smart</option>
                        <option value="Walmart">Walmart</option>
                        <option value="Cash & Carry">Cash & Carry</option>
                        <option value="Makro">Makro</option>
                        <option value="Proveedor Local">Proveedor Local</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_estimada">Fecha Estimada de Entrega</label>
                        <input type="date" id="fecha_estimada" name="fecha_estimada" required
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="prioridad">Prioridad</label>
                        <select id="prioridad" name="prioridad">
                            <option value="normal">Normal</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notas">Notas Adicionales</label>
                    <textarea id="notas" name="notas" rows="3" placeholder="Instrucciones especiales para el proveedor..."></textarea>
                </div>
                
                <h3 style="margin: 25px 0 15px 0;">📋 Seleccionar Productos</h3>
                
                <div class="productos-grid">
                    <?php while ($row = $ingredientes_todos->fetch_assoc()): 
                        $es_bajo = $row['stock_inv'] <= $stock_bajo;
                    ?>
                        <div class="producto-card <?php echo $es_bajo ? 'stock-bajo' : ''; ?>">
                            <div class="producto-header">
                                <?php echo htmlspecialchars($row['nombre_insumo']); ?>
                                <?php if ($es_bajo): ?>
                                    <span class="urgente-tag">Urgente</span>
                                <?php endif; ?>
                            </div>
                            <div class="stock-info">
                                Stock actual: <strong><?php echo number_format($row['stock_inv'], 2); ?> <?php echo htmlspecialchars($row['medida_inv']); ?></strong>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label style="font-size: 14px;">Pedir:</label>
                                <input type="number" 
                                       name="productos[<?php echo $row['id_inv']; ?>]" 
                                       class="cantidad-input" 
                                       min="0" 
                                       step="0.01" 
                                       placeholder="0">
                                <span style="font-size: 14px;"><?php echo htmlspecialchars($row['medida_inv']); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="sj-btn sj-btn--primary" style="flex: 1;">
                        🛒 Crear Pedido
                    </button>
                    <a href="inventario_crud.php" class="sj-btn sj-btn--outline" style="flex: 1; text-align: center;">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Lista de pedidos recientes -->
        <div class="pedidos-list">
            <h2>📋 Pedidos Recientes</h2>
            
            <?php if ($pedidos_recientes->num_rows > 0): ?>
                <?php while ($pedido = $pedidos_recientes->fetch_assoc()): 
                    $estado_class = 'estado-' . $pedido['estado'];
                    $estado_text = ucfirst($pedido['estado']);
                ?>
                    <div class="pedido-item">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h4 style="margin: 0 0 8px 0;">Pedido #<?php echo $pedido['id_pedido']; ?></h4>
                                <p style="margin: 4px 0; color: #666;">
                                    <strong>Proveedor:</strong> <?php echo htmlspecialchars($pedido['proveedor']); ?>
                                </p>
                                <p style="margin: 4px 0; color: #666;">
                                    <strong>Fecha estimada:</strong> <?php echo date('d/m/Y', strtotime($pedido['fecha_estimada'])); ?>
                                </p>
                                <p style="margin: 4px 0; color: #666;">
                                    <strong>Productos:</strong> <?php echo $pedido['total_productos']; ?>
                                </p>
                                <?php if (!empty($pedido['notas'])): ?>
                                    <p style="margin: 4px 0; color: #666;">
                                        <strong>Notas:</strong> <?php echo htmlspecialchars($pedido['notas']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <span class="estado-badge <?php echo $estado_class; ?>">
                                    <?php echo $estado_text; ?>
                                </span>
                                <br>
                                <small style="color: #666;">
                                    Creado: <?php echo date('d/m/Y', strtotime($pedido['fecha_creacion'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 20px;">
                    No tienes pedidos recientes. Crea tu primer pedido de re-stock.
                </p>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="inventario_crud.php" class="sj-btn sj-btn--outline">
                📦 Volver a Inventario
            </a>
        </div>
    </div>
    
    <!-- EmailJS SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    
    <!-- Notificaciones y manejo de pedidos -->
    <script>
    /**
     * =========================================================
     * MÓDULO DE CREACIÓN DE PEDIDOS - SALUD JUÁREZ
     * =========================================================
     * Conexión Frontend → Backend con EmailJS condicional
     */
    
    // Configuración de EmailJS
    const EMAILJS_CONFIG = {
        serviceID: 'service_kchdp9f',
        templateID: 'template_tnrferf',
        publicKey: 'VkhEAneBLv5m5rOgO'
    };
    
    // Inicializar EmailJS
    emailjs.init(EMAILJS_CONFIG.publicKey);
    
    /**
     * Sistema de Notificaciones Flotantes (sj-notificacion)
     * @param {string} mensaje - Texto a mostrar
     * @param {string} tipo - 'success', 'warning', 'error', 'info'
     * @param {number} duracion - Milisegundos visible (default: 4000)
     */
    function mostrarNotificacion(mensaje, tipo = 'info', duracion = 4000) {
        // Colores según tipo
        const colores = {
            success: { bg: '#27ae60', border: '#1e8449', icon: '✅' },
            warning: { bg: '#f39c12', border: '#d68910', icon: '⚠️' },
            error: { bg: '#e74c3c', border: '#c0392b', icon: '❌' },
            info: { bg: '#3498db', border: '#2980b9', icon: 'ℹ️' }
        };
        
        const estilo = colores[tipo] || colores.info;
        
        // Crear elemento de notificación
        const notificacion = document.createElement('div');
        notificacion.className = 'sj-notificacion sj-notificacion-' + tipo;
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${estilo.bg};
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            border-left: 5px solid ${estilo.border};
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            font-family: inherit;
            font-weight: 500;
            z-index: 10000;
            max-width: 350px;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 12px;
        `;
        
        notificacion.innerHTML = `
            <span style="font-size: 20px;">${estilo.icon}</span>
            <span>${mensaje}</span>
        `;
        
        document.body.appendChild(notificacion);
        
        // Animación de entrada
        requestAnimationFrame(() => {
            notificacion.style.transform = 'translateX(0)';
        });
        
        // Animación de salida
        setTimeout(() => {
            notificacion.style.transform = 'translateX(120%)';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 400);
        }, duracion);
    }
    
    /**
     * Enviar correo vía EmailJS
     * @param {Object} datosPedido - Datos del pedido para el template
     * @returns {Promise<Object>} - Resultado del envío
     */
    async function enviarEmailProveedor(datosPedido) {
        const templateParams = {
            to_name: datosPedido.proveedor,
            to_email: datosPedido.email_proveedor || 'proveedor@ejemplo.com',
            restaurant_name: datosPedido.nombre_restaurante,
            pedido_id: datosPedido.id_pedido,
            fecha_pedido: new Date().toLocaleDateString('es-MX'),
            productos_lista: datosPedido.productos_lista,
            total_productos: datosPedido.total_productos,
            notas: datosPedido.notas || 'Sin notas adicionales'
        };
        
        try {
            const response = await emailjs.send(
                EMAILJS_CONFIG.serviceID,
                EMAILJS_CONFIG.templateID,
                templateParams
            );
            return { success: true, response };
        } catch (error) {
            console.error('Error enviando email:', error);
            return { success: false, error };
        }
    }
    
    /**
     * Crear tarjeta de pedido dinámicamente
     * @param {Object} pedido - Datos del pedido
     */
    function agregarPedidoAlDOM(pedido) {
        const listaPedidos = document.querySelector('.pedidos-list');
        if (!listaPedidos) return;
        
        // Crear elemento del pedido
        const pedidoDiv = document.createElement('div');
        pedidoDiv.className = 'pedido-item';
        pedidoDiv.style.cssText = `
            border-left: 4px solid #2D5A27;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
            border-radius: 0 8px 8px 0;
            animation: fadeInSlide 0.5s ease;
        `;
        
        // Formatear fecha
        const fecha = new Date(pedido.fecha_creacion).toLocaleDateString('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        
        // Generar HTML de productos
        let productosHTML = '';
        if (pedido.detalles && pedido.detalles.length > 0) {
            productosHTML = pedido.detalles.map(d => 
                `<li>${d.nombre}: ${d.cantidad}</li>`
            ).join('');
        }
        
        pedidoDiv.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <strong style="color: #2D5A27; font-size: 16px;">
                    📦 Pedido #${pedido.id_pedido} - ${pedido.proveedor}
                </strong>
                <span class="estado-badge estado-pendiente">PENDIENTE</span>
            </div>
            <div style="color: #666; font-size: 14px; margin-bottom: 8px;">
                <strong>Fecha:</strong> ${fecha} | 
                <strong>Entrega estimada:</strong> ${pedido.fecha_estimada}
            </div>
            <div style="color: #666; font-size: 14px; margin-bottom: 8px;">
                <strong>Productos (${pedido.total_productos}):</strong>
                <ul style="margin: 5px 0; padding-left: 20px;">
                    ${productosHTML}
                </ul>
            </div>
            ${pedido.notas ? `<div style="color: #888; font-size: 13px; font-style: italic;">📝 ${pedido.notas}</div>` : ''}
        `;
        
        // Agregar al inicio de la lista
        const titulo = listaPedidos.querySelector('h2');
        if (titulo && titulo.nextSibling) {
            listaPedidos.insertBefore(pedidoDiv, titulo.nextSibling);
        } else {
            listaPedidos.appendChild(pedidoDiv);
        }
        
        // Agregar animación CSS si no existe
        if (!document.getElementById('animaciones-pedidos')) {
            const style = document.createElement('style');
            style.id = 'animaciones-pedidos';
            style.textContent = `
                @keyframes fadeInSlide {
                    from {
                        opacity: 0;
                        transform: translateX(-30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    /**
     * Enviar correo B2B vía EmailJS - Template Especializado
     * Template: "Alerta de Solicitud de Re-stock B2B"
     * @param {Object} datosEmail - Datos del pedido B2B
     * @returns {Promise<Object>} - Resultado del envío
     */
    async function enviarEmailB2B(datosEmail) {
        // Template específico para B2B (puede ser diferente al general)
        const templateB2B = 'template_tnrferf'; // Template de Alerta B2B
        
        const templateParams = {
            // Variables mapeadas para el template EmailJS
            proveedor_nombre: datosEmail.proveedor_nombre,
            restaurante_nombre: datosEmail.restaurante_nombre,
            lista_productos: datosEmail.lista_productos,
            total_pedido: datosEmail.total_pedido,
            id_pedido: datosEmail.id_pedido,
            notas: datosEmail.notas || 'Sin notas adicionales',
            
            // Variables adicionales útiles
            fecha_pedido: new Date().toLocaleDateString('es-MX', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            }),
            hora_pedido: new Date().toLocaleTimeString('es-MX', {
                hour: '2-digit',
                minute: '2-digit'
            })
        };
        
        try {
            const response = await emailjs.send(
                EMAILJS_CONFIG.serviceID,
                templateB2B,
                templateParams
            );
            return { success: true, response };
        } catch (error) {
            console.error('Error enviando email B2B:', error);
            return { success: false, error };
        }
    }
    
    /**
     * Manejar envío del formulario de pedido B2B (Transaccional)
     * @param {Event} e - Evento del formulario
     */
    async function manejarEnvioPedidoB2B(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const btnTextoOriginal = submitBtn.innerHTML;
        
        // Agregar action B2B
        formData.append('action', 'crear_pedido_b2b');
        
        // Validar que haya al menos un producto seleccionado
        let hayProductos = false;
        let productosCount = 0;
        formData.forEach((value, key) => {
            if (key.startsWith('productos[') && parseFloat(value) > 0) {
                hayProductos = true;
                productosCount++;
            }
        });
        
        if (!hayProductos) {
            mostrarNotificacion('Debes seleccionar al menos un producto para el pedido B2B', 'warning');
            return;
        }
        
        // Deshabilitar botón y mostrar estado de carga
        submitBtn.disabled = true;
        submitBtn.innerHTML = '📤 Procesando pedido B2B...';
        
        try {
            // 1. ENVIAR PETICIÓN AL BACKEND (PHP) - CON TRANSACCIÓN
            const response = await fetch('restock_inventario.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const resultado = await response.json();
            
            // 2. VERIFICAR RESPUESTA DEL PHP (success: true/false)
            if (resultado.success === true) {
                // Pedido guardado exitosamente en BD
                
                // 3. DISPARAR EMAILJS CONDICIONALMENTE (solo si PHP success)
                let emailEnviado = false;
                if (resultado.datos_email) {
                    const emailResult = await enviarEmailB2B(resultado.datos_email);
                    emailEnviado = emailResult.success;
                }
                
                // 4. MOSTRAR NOTIFICACIÓN SEGÚN ESTADO
                if (emailEnviado) {
                    // ÉXITO TOTAL: Pedido guardado + Email enviado
                    mostrarNotificacion(
                        '✅ Pedido guardado y proveedor notificado', 
                        'success', 
                        5000
                    );
                } else {
                    // ÉXITO PARCIAL: Pedido guardado pero email falló
                    mostrarNotificacion(
                        '⚠️ Pedido guardado, pero falló la notificación al proveedor', 
                        'warning', 
                        6000
                    );
                }
                
                // 5. ACTUALIZAR DOM DINÁMICAMENTE (sin F5)
                agregarPedidoB2BAlDOM(resultado);
                
                // 6. LIMPIAR FORMULARIO
                form.reset();
                document.querySelectorAll('.cantidad-input').forEach(input => {
                    input.value = '';
                });
                
            } else {
                // ERROR DEL PHP: Transacción falló o datos inválidos
                mostrarNotificacion(
                    '❌ Error al procesar la solicitud: ' + (resultado.message || 'Error desconocido'), 
                    'error', 
                    6000
                );
            }
            
        } catch (error) {
            console.error('Error en envío B2B:', error);
            mostrarNotificacion(
                '❌ Error de conexión. Verifica tu conexión e intenta de nuevo.', 
                'error', 
                6000
            );
        } finally {
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = btnTextoOriginal;
        }
    }
    
    /**
     * Crear tarjeta de pedido B2B dinámicamente en el DOM
     * @param {Object} pedido - Datos del pedido B2B
     */
    function agregarPedidoB2BAlDOM(pedido) {
        const listaPedidos = document.querySelector('.pedidos-list');
        if (!listaPedidos) return;
        
        // Crear elemento del pedido
        const pedidoDiv = document.createElement('div');
        pedidoDiv.className = 'pedido-item pedido-b2b-nuevo';
        pedidoDiv.style.cssText = `
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #fff5f5, #ffeaea);
            border-radius: 0 8px 8px 0;
            animation: fadeInSlide 0.5s ease;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.15);
        `;
        
        // Formatear fecha
        const fecha = new Date().toLocaleDateString('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
        
        // Generar HTML de productos para B2B
        let productosHTML = '';
        if (pedido.detalles && pedido.detalles.length > 0) {
            productosHTML = pedido.detalles.map(d => 
                `<li>${d.nombre}: ${d.cantidad} x $${parseFloat(d.precio_unitario).toFixed(2)}</li>`
            ).join('');
        }
        
        const totalFormateado = parseFloat(pedido.total_pedido || 0).toFixed(2);
        
        pedidoDiv.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <strong style="color: #c0392b; font-size: 16px;">
                    🏢 Pedido B2B #${pedido.id_pedido}
                </strong>
                <span class="estado-badge estado-pendiente">PENDIENTE</span>
            </div>
            <div style="color: #666; font-size: 14px; margin-bottom: 8px;">
                <strong>Proveedor:</strong> ${pedido.datos_email?.proveedor_nombre || 'Proveedor B2B'}
            </div>
            <div style="color: #666; font-size: 14px; margin-bottom: 8px;">
                <strong>Productos (${pedido.total_productos}):</strong>
                <ul style="margin: 5px 0; padding-left: 20px;">
                    ${productosHTML}
                </ul>
            </div>
            <div style="color: #27ae60; font-size: 16px; font-weight: bold; margin-bottom: 8px;">
                Total: $${totalFormateado}
            </div>
            <div style="color: #888; font-size: 12px;">
                Creado: ${fecha} • Transacción completada
            </div>
        `;
        
        // Agregar al inicio de la lista
        const titulo = listaPedidos.querySelector('h2');
        if (titulo && titulo.nextSibling) {
            listaPedidos.insertBefore(pedidoDiv, titulo.nextSibling);
        } else {
            listaPedidos.appendChild(pedidoDiv);
        }
        
        // Scroll suave al nuevo pedido
        pedidoDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    /**
     * Manejar envío del formulario de pedido (Original - Re-stock interno)
     * @param {Event} e - Evento del formulario
     */
    async function manejarEnvioPedido(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const btnTextoOriginal = submitBtn.innerHTML;
        
        // Validar que haya al menos un producto seleccionado
        const productos = formData.getAll('productos[]');
        let hayProductos = false;
        formData.forEach((value, key) => {
            if (key.startsWith('productos[') && parseFloat(value) > 0) {
                hayProductos = true;
            }
        });
        
        if (!hayProductos) {
            mostrarNotificacion('Debes seleccionar al menos un producto para el pedido', 'warning');
            return;
        }
        
        // Deshabilitar botón y mostrar estado de carga
        submitBtn.disabled = true;
        submitBtn.innerHTML = '📤 Enviando pedido...';
        
        try {
            // 1. ENVIAR PETICIÓN AL BACKEND (PHP)
            const response = await fetch('restock_inventario.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const resultado = await response.json();
            
            // 2. VERIFICAR RESPUESTA DEL PHP
            if (resultado.success) {
                // Pedido guardado exitosamente
                
                // Preparar datos para EmailJS
                const datosEmail = {
                    proveedor: resultado.proveedor,
                    email_proveedor: formData.get('email_proveedor') || '',
                    nombre_restaurante: '<?php echo htmlspecialchars($nombre_restaurante); ?>',
                    id_pedido: resultado.id_pedido,
                    productos_lista: resultado.detalles 
                        ? resultado.detalles.map(d => `${d.nombre}: ${d.cantidad}`).join('\n')
                        : 'Ver detalles en el sistema',
                    total_productos: resultado.total_productos,
                    notas: resultado.notas
                };
                
                // 3. DISPARAR EMAILJS CONDICIONALMENTE
                const emailResult = await enviarEmailProveedor(datosEmail);
                
                if (emailResult.success) {
                    // ÉXITO TOTAL: Pedido guardado + Email enviado
                    mostrarNotificacion(
                        '✅ Pedido guardado y proveedor notificado correctamente', 
                        'success', 
                        5000
                    );
                } else {
                    // ÉXITO PARCIAL: Pedido guardado pero email falló
                    mostrarNotificacion(
                        '⚠️ Pedido guardado, pero falló la notificación por correo', 
                        'warning', 
                        6000
                    );
                }
                
                // 4. ACTUALIZAR DOM DINÁMICAMENTE (sin F5)
                agregarPedidoAlDOM(resultado);
                
                // Limpiar formulario
                form.reset();
                
                // Resetear inputs de cantidad
                document.querySelectorAll('.cantidad-input').forEach(input => {
                    input.value = '';
                });
                
            } else {
                // Error del PHP
                mostrarNotificacion(
                    '❌ Error al procesar la solicitud: ' + resultado.message, 
                    'error', 
                    6000
                );
            }
            
        } catch (error) {
            console.error('Error en envío:', error);
            mostrarNotificacion(
                '❌ Error de conexión. Verifica tu conexión a internet e intenta de nuevo.', 
                'error', 
                6000
            );
        } finally {
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = btnTextoOriginal;
        }
    }
    
    // Configurar event listeners cuando DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Formulario de re-stock interno (original)
        const formPedido = document.querySelector('form[action="restock_inventario.php"]:not([data-mode="b2b"])');
        if (formPedido) {
            formPedido.addEventListener('submit', manejarEnvioPedido);
        }
        
        // 2. Formulario B2B (transaccional con EmailJS)
        const formPedidoB2B = document.querySelector('form[data-mode="b2b"]');
        if (formPedidoB2B) {
            formPedidoB2B.addEventListener('submit', manejarEnvioPedidoB2B);
        }
        
        // Debug: Mostrar qué formularios se detectaron
        console.log('📋 Formularios detectados:', {
            interno: !!formPedido,
            b2b: !!formPedidoB2B
        });
    });
    </script>
</body>
</html>
