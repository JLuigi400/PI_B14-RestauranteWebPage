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

// Procesar solicitud de re-stock
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'crear_pedido') {
        $proveedor = $_POST['proveedor'];
        $fecha_estimada = $_POST['fecha_estimada'];
        $notas = $_POST['notas'] ?? '';
        
        // Crear el pedido
        $stmt_pedido = $conn->prepare("
            INSERT INTO pedidos_restock (id_res, proveedor, fecha_estimada, notas, estado, fecha_creacion)
            VALUES (?, ?, ?, ?, 'pendiente', NOW())
        ");
        $stmt_pedido->bind_param("isss", $id_res, $proveedor, $fecha_estimada, $notas);
        
        if ($stmt_pedido->execute()) {
            $id_pedido = $conn->insert_id;
            
            // Agregar productos al pedido
            if (isset($_POST['productos']) && is_array($_POST['productos'])) {
                foreach ($_POST['productos'] as $id_inv => $cantidad) {
                    if ($cantidad > 0) {
                        $stmt_detalle = $conn->prepare("
                            INSERT INTO pedido_detalle (id_pedido, id_inv, cantidad_solicitada)
                            VALUES (?, ?, ?)
                        ");
                        $stmt_detalle->bind_param("iid", $id_pedido, $id_inv, $cantidad);
                        $stmt_detalle->execute();
                    }
                }
            }
            
            header("Location: restock_inventario.php?status=pedido_creado");
            exit();
        }
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
</body>
</html>
