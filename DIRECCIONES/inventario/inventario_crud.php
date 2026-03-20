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

// Procesar acciones CRUD
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'crear':
            header("Location: ../PHP/procesar_insumo.php");
            exit();
            break;
            
        case 'actualizar_stock':
            $id_inv = intval($_POST['id_inv']);
            $nuevo_stock = floatval($_POST['nuevo_stock']);
            
            $stmt = $conn->prepare("UPDATE inventario SET stock_inv = ? WHERE id_inv = ? AND id_res = ?");
            $stmt->bind_param("dii", $nuevo_stock, $id_inv, $id_res);
            
            if ($stmt->execute()) {
                header("Location: inventario_crud.php?status=stock_updated");
            } else {
                header("Location: inventario_crud.php?status=error_stock");
            }
            exit();
            break;
            
        case 'eliminar':
            $id_inv = intval($_POST['id_inv']);
            
            $stmt = $conn->prepare("DELETE FROM inventario WHERE id_inv = ? AND id_res = ?");
            $stmt->bind_param("ii", $id_inv, $id_res);
            
            if ($stmt->execute()) {
                header("Location: inventario_crud.php?status=deleted");
            } else {
                header("Location: inventario_crud.php?status=error_delete");
            }
            exit();
            break;
    }
}

// Obtener lista de ingredientes con alertas de stock bajo
$stock_bajo = 10; // Umbral de stock bajo

$stmt_lista = $conn->prepare("
    SELECT id_inv, nombre_insumo, stock_inv, medida_inv, img_insumo,
           CASE WHEN stock_inv <= ? THEN 1 ELSE 0 END as alerta_bajo
    FROM inventario 
    WHERE id_res = ? 
    ORDER BY alerta_bajo DESC, nombre_insumo ASC
");
$stmt_lista->bind_param("di", $stock_bajo, $id_res);
$stmt_lista->execute();
$res_lista = $stmt_lista->get_result();

// Contar ingredientes con stock bajo
$stmt_bajo = $conn->prepare("SELECT COUNT(*) as total FROM inventario WHERE id_res = ? AND stock_inv <= ?");
$stmt_bajo->bind_param("id", $id_res, $stock_bajo);
$stmt_bajo->execute();
$result_bajo = $stmt_bajo->get_result();
$total_bajo = $result_bajo->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Inventario | <?php echo htmlspecialchars($nombre_restaurante); ?></title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/platillos.css">
    <style>
        .alerta-stock {
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            color: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }
        
        .stock-bajo {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .stock-critico {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .acciones-rapidas {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-accion {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .btn-editar {
            background: #2E5A88;
            color: white;
        }
        
        .btn-editar:hover {
            background: #1e3a5a;
        }
        
        .btn-eliminar {
            background: #dc3545;
            color: white;
        }
        
        .btn-eliminar:hover {
            background: #c82333;
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
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="sj-page-title">📦 Gestión de Inventario</h1>
        <p style="color: #666; margin-bottom: 20px;">
            <strong><?php echo htmlspecialchars($nombre_restaurante); ?></strong> · 
            Total de ingredientes: <?php echo $res_lista->num_rows; ?>
        </p>
        
        <?php if ($total_bajo > 0): ?>
            <div class="alerta-stock">
                ⚠️ ¡Atención! Tienes <?php echo $total_bajo; ?> ingrediente(s) con stock bajo (≤ <?php echo $stock_bajo; ?>)
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 15px;">
                <?php
                switch($_GET['status']) {
                    case 'stock_updated':
                        echo "✅ Stock actualizado correctamente";
                        break;
                    case 'deleted':
                        echo "✅ Ingrediente eliminado correctamente";
                        break;
                    case 'success':
                        echo "✅ Ingrediente registrado correctamente";
                        break;
                    case 'error_stock':
                    case 'error_delete':
                        echo "❌ Error al realizar la operación";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulario de registro -->
        <section class="sj-card">
            <h2 style="margin:0 0 12px 0;">📝 Registrar Nuevo Insumo</h2>
            
            <form action="../PHP/procesar_insumo.php" method="POST" enctype="multipart/form-data" class="sj-form-grid">
                <input type="hidden" name="id_res" value="<?php echo $id_res; ?>">
                
                <div class="sj-field">
                    <label>Nombre del Insumo</label>
                    <input type="text" name="nombre_insumo" placeholder="Ej: Espinaca fresca" required>
                </div>

                <div class="sj-field">
                    <label>Cantidad (Stock)</label>
                    <input type="number" step="0.01" name="stock_inv" placeholder="0.00" required>
                </div>

                <div class="sj-field">
                    <label>Unidad de Medida</label>
                    <select name="medida_inv" required>
                        <option value="">Seleccionar...</option>
                        <option value="Kg">Kilogramos (Kg)</option>
                        <option value="Gr">Gramos (Gr)</option>
                        <option value="Lt">Litros (Lt)</option>
                        <option value="Ml">Mililitros (Ml)</option>
                        <option value="Mz">Mazo (Mz)</option>
                        <option value="Pza">Pieza (Pza)</option>
                        <option value="Lata">Lata</option>
                        <option value="Botella">Botella</option>
                    </select>
                </div>

                <div class="sj-field">
                    <label>Imagen (opcional)</label>
                    <input type="file" name="img_insumo" accept="image/*">
                </div>

                <div class="sj-span-2">
                    <button type="submit" class="sj-btn sj-btn--primary" style="width:100%;">📦 Registrar Insumo</button>
                </div>
            </form>
        </section>

        <hr class="sj-divider">

        <!-- Lista de ingredientes con CRUD -->
        <section>
            <h2 style="margin:0 0 12px 0;">📋 Lista de Insumos</h2>
            
            <div class="sj-table-container">
                <table class="sj-table">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Insumo</th>
                            <th>Stock Actual</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $res_lista->fetch_assoc()): 
                            $img_path = !empty($row['img_insumo']) ? $row['img_insumo'] : '../IMG/default-insumo.png';
                            $stock_class = '';
                            $estado_text = 'Normal';
                            
                            if ($row['stock_inv'] <= 5) {
                                $stock_class = 'stock-critico';
                                $estado_text = '🔴 Crítico';
                            } elseif ($row['stock_inv'] <= $stock_bajo) {
                                $stock_class = 'stock-bajo';
                                $estado_text = '🟡 Bajo';
                            } else {
                                $estado_text = '🟢 Normal';
                            }
                        ?>
                            <tr class="<?php echo $stock_class; ?>">
                                <td>
                                    <img src="<?php echo htmlspecialchars($img_path); ?>" 
                                         width="54" height="40" 
                                         style="border-radius:10px; object-fit:cover;" 
                                         alt="Insumo">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['nombre_insumo']); ?></strong>
                                    <br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($row['medida_inv']); ?></small>
                                </td>
                                <td>
                                    <span style="font-size: 18px; font-weight: bold;">
                                        <?php echo number_format($row['stock_inv'], 2); ?>
                                    </span>
                                    <br>
                                    <small><?php echo htmlspecialchars($row['medida_inv']); ?></small>
                                </td>
                                <td>
                                    <span class="estado-stock" style="font-weight: bold;">
                                        <?php echo $estado_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="acciones-rapidas">
                                        <button class="btn-accion btn-editar" 
                                                onclick="abrirModalStock(<?php echo $row['id_inv']; ?>, '<?php echo htmlspecialchars($row['nombre_insumo']); ?>', <?php echo $row['stock_inv']; ?>)">
                                            📊 Stock
                                        </button>
                                        <button class="btn-accion btn-editar" 
                                                onclick="window.location.href='editar_ingrediente.php?id=<?php echo $row['id_inv']; ?>'">
                                            ✏️ Editar
                                        </button>
                                        <button class="btn-accion btn-eliminar" 
                                                onclick="confirmarEliminar(<?php echo $row['id_inv']; ?>, '<?php echo htmlspecialchars($row['nombre_insumo']); ?>')">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- Acciones adicionales -->
        <div style="margin-top: 30px; text-align: center;">
            <a href="restock_inventario.php" class="sj-btn sj-btn--secondary" style="margin: 5px;">
                🛒 Pedir Re-stock
            </a>
            <a href="dashboard.php" class="sj-btn sj-btn--outline" style="margin: 5px;">
                🏠 Volver al Dashboard
            </a>
        </div>
    </div>

    <!-- Modal para actualizar stock -->
    <div id="modalStock" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalStock()">&times;</span>
            <h3>📊 Actualizar Stock</h3>
            <p id="nombreIngrediente"></p>
            
            <form method="POST" action="inventario_crud.php">
                <input type="hidden" name="action" value="actualizar_stock">
                <input type="hidden" id="idInv" name="id_inv">
                
                <div class="form-group">
                    <label for="nuevo_stock">Nuevo Stock:</label>
                    <input type="number" step="0.01" id="nuevo_stock" name="nuevo_stock" required>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="sj-btn sj-btn--primary">Actualizar</button>
                    <button type="button" class="sj-btn sj-btn--outline" onclick="cerrarModalStock()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalStock(idInv, nombre, stockActual) {
            document.getElementById('idInv').value = idInv;
            document.getElementById('nombreIngrediente').textContent = 'Ingrediente: ' + nombre;
            document.getElementById('nuevo_stock').value = stockActual;
            document.getElementById('modalStock').style.display = 'block';
        }
        
        function cerrarModalStock() {
            document.getElementById('modalStock').style.display = 'none';
        }
        
        function confirmarEliminar(idInv, nombre) {
            if (confirm('¿Estás seguro de eliminar "' + nombre + '"? Esta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'inventario_crud.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'eliminar';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id_inv';
                idInput.value = idInv;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalStock');
            if (event.target == modal) {
                cerrarModalStock();
            }
        }
    </script>
</body>
</html>
