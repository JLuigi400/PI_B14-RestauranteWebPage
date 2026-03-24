<?php
session_start();
include '../PHP/db_config.php';

if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];
$id_pla = isset($_GET['id_pla']) ? intval($_GET['id_pla']) : 0;

// Restaurante del dueño
$query_res = $conn->prepare("SELECT id_res FROM restaurante WHERE id_usu = ?");
$query_res->bind_param("i", $id_usuario);
$query_res->execute();
$res_data = $query_res->get_result()->fetch_assoc();

if (!$res_data) {
    die("Error: No tienes un restaurante registrado. Contacta al administrador.");
}
$id_res = (int)$res_data['id_res'];

// Validar que el platillo pertenezca al restaurante (si se mandó id_pla)
if ($id_pla > 0) {
    $stmt_val = $conn->prepare("SELECT id_pla FROM platillos WHERE id_pla = ? AND id_res = ? LIMIT 1");
    $stmt_val->bind_param("ii", $id_pla, $id_res);
    $stmt_val->execute();
    $ok = $stmt_val->get_result()->fetch_assoc();
    if (!$ok) {
        header("Location: gestion_platillos.php");
        exit();
    }
}

// Obtener información del platillo
$stmt_pla = $conn->prepare("
    SELECT p.*, c.nombre_cat
    FROM platillos p
    LEFT JOIN categorias c ON c.id_cat = p.id_cat
    WHERE p.id_pla = ? AND p.id_res = ?
");
$stmt_pla->bind_param("ii", $id_pla, $id_res);
$stmt_pla->execute();
$platillo = $stmt_pla->get_result()->fetch_assoc();

if (!$platillo) {
    header("Location: gestion_platillos.php");
    exit();
}

// Obtener ingredientes actuales del platillo
$stmt_ing_actuales = $conn->prepare("
    SELECT pi.*, i.nombre_insumo, i.medida_inv, i.es_ingrediente_secreto, i.alergenos, i.stock_inv
    FROM platillo_ingredientes pi
    JOIN inventario i ON i.id_inv = pi.id_inv
    WHERE pi.id_pla = ?
    ORDER BY i.nombre_insumo ASC
");
$stmt_ing_actuales->bind_param("i", $id_pla);
$stmt_ing_actuales->execute();
$ingredientes_actuales = $stmt_ing_actuales->get_result();

if (!$ingredientes_actuales || $ingredientes_actuales->num_rows === 0) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Ingredientes | Salud Juárez</title>
    </head>
    <body>
        <script>
            alert("Este platillo no tiene ingredientes asignados.");
            window.location.href = "gestion_platillos.php";
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingredientes disponibles | Salud Juárez</title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
</head>
<body>
    <?php include '../PHP/navbar.php'; ?>

    <div class="container" style="max-width: 900px; margin: 30px auto; padding: 0 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h1 style="margin: 0;">🧾 Ingredientes del Platillo</h1>
                <p style="color: #666; margin: 5px 0 0 0;">
                    <strong><?php echo htmlspecialchars($platillo['nombre_pla']); ?></strong>
                </p>
            </div>
            <a href="gestion_platillos.php" class="sj-btn sj-btn--secondary" style="text-decoration: none;">
                ← Volver a Menú
            </a>
        </div>

        <?php if ($ingredientes_actuales && $ingredientes_actuales->num_rows > 0): ?>
            <div class="sj-card">
                <h3 style="margin: 0 0 15px 0; color: #2c3e50;">📋 Ingredientes Actuales</h3>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Ingrediente</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Cantidad</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Stock Actual</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Estado</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_kcal = 0;
                        while ($row = $ingredientes_actuales->fetch_assoc()): 
                            $stock_bajo = $row['stock_inv'] <= 5;
                            $secreto_class = $row['es_ingrediente_secreto'] ? 'secreto' : 'publico';
                            
                            // Calcular calorías estimadas (simplificado)
                            $kcal_estimadas = $row['cantidad_usada'] * 50; // 50 kcal por unidad base
                            $total_kcal += $kcal_estimadas;
                            
                            // Procesar alergenos
                            $alergenos_badges = '';
                            if (!empty($row['alergenos'])) {
                                $alergenos_array = explode(',', $row['alergenos']);
                                foreach ($alergenos_array as $alergeno) {
                                    $alergeno_clean = trim($alergeno);
                                    if (!empty($alergeno_clean)) {
                                        $alergenos_badges .= "<span style='background: #ff6b6b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin: 1px;'>" . htmlspecialchars($alergeno_clean) . "</span> ";
                                    }
                                }
                            }
                        ?>
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 12px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <strong><?php echo htmlspecialchars($row['nombre_insumo']); ?></strong>
                                        <?php if ($row['es_ingrediente_secreto']): ?>
                                            <span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px;">🔒 Secreto</span>
                                        <?php endif; ?>
                                        <?php echo $alergenos_badges; ?>
                                    </div>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="font-weight: bold; color: #2c3e50;">
                                        <?php echo number_format($row['cantidad_usada'], 2); ?> <?php echo htmlspecialchars($row['unidad_usada']); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="color: <?php echo $stock_bajo ? '#e74c3c' : '#27ae60'; ?>; font-weight: bold;">
                                        <?php echo number_format($row['stock_inv'], 2); ?> <?php echo htmlspecialchars($row['medida_inv']); ?>
                                    </span>
                                    <?php if ($stock_bajo): ?>
                                        <br><small style="color: #e74c3c;">⚠️ Stock bajo</small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($row['es_ingrediente_secreto']): ?>
                                        <span style="color: #e74c3c; font-weight: bold;">No visible</span>
                                    <?php else: ?>
                                        <span style="color: #27ae60; font-weight: bold;">Visible</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <button onclick="editarCantidad(<?php echo $row['id_detalle']; ?>, <?php echo $row['cantidad_usada']; ?>)" 
                                            style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; margin-right: 5px;">
                                        ✏️
                                    </button>
                                    <button onclick="eliminarIngrediente(<?php echo $row['id_detalle']; ?>)" 
                                            style="background: #e74c3c; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #3498db;">
                    <h4 style="margin: 0 0 10px 0; color: #2c3e50;">📊 Información Nutricional Estimada</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <strong>Calorías Totales:</strong> 
                            <span style="color: #e74c3c; font-size: 18px; font-weight: bold;">
                                <?php echo number_format($total_kcal, 0); ?> kcal
                            </span>
                        </div>
                        <div>
                            <strong>Total Ingredientes:</strong> 
                            <span style="color: #2c3e50; font-weight: bold;">
                                <?php echo $ingredientes_actuales->num_rows; ?>
                            </span>
                        </div>
                        <div>
                            <strong>Ingredientes Secretos:</strong> 
                            <span style="color: #e74c3c; font-weight: bold;">
                                <?php 
                                $secretos = 0;
                                $ingredientes_actuales->data_seek(0); // Reset pointer
                                while ($row = $ingredientes_actuales->fetch_assoc()) {
                                    if ($row['es_ingrediente_secreto']) $secretos++;
                                }
                                echo $secretos;
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="sj-card" style="margin-top: 20px;">
                <h3 style="margin: 0 0 15px 0; color: #2c3e50;">➕ Agregar Ingredientes</h3>
                
                <?php
                // Obtener ingredientes disponibles que no están en el platillo
                $ingredientes_existentes = [];
                $ingredientes_actuales->data_seek(0);
                while ($row = $ingredientes_actuales->fetch_assoc()) {
                    $ingredientes_existentes[] = $row['id_inv'];
                }
                
                $placeholders = implode(',', array_fill(0, count($ingredientes_existentes), '?'));
                $stmt_disponibles = $conn->prepare("
                    SELECT id_inv, nombre_insumo, stock_inv, medida_inv, es_ingrediente_secreto, alergenos
                    FROM inventario 
                    WHERE id_res = ? AND stock_inv > 0 
                    " . (count($ingredientes_existentes) > 0 ? "AND id_inv NOT IN ($placeholders)" : "") . "
                    ORDER BY nombre_insumo ASC
                ");
                
                $params = array_merge([$id_res], $ingredientes_existentes);
                $stmt_disponibles->bind_param(str_repeat('i', count($params)), ...$params);
                $stmt_disponibles->execute();
                $disponibles = $stmt_disponibles->get_result();
                
                if ($disponibles && $disponibles->num_rows > 0):
                ?>
                    <form method="POST" action="../PHP/agregar_ingrediente_platillo.php">
                        <input type="hidden" name="id_pla" value="<?php echo $id_pla; ?>">
                        
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 100px; gap: 10px; margin-bottom: 15px;">
                            <div><strong>Ingrediente</strong></div>
                            <div><strong>Cantidad</strong></div>
                            <div><strong>Unidad</strong></div>
                            <div><strong>Acción</strong></div>
                        </div>
                        
                        <?php while ($ing = $disponibles->fetch_assoc()): ?>
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 100px; gap: 10px; align-items: center; padding: 8px; background: #f8f9fa; border-radius: 6px; margin-bottom: 8px;">
                                <div>
                                    <?php echo htmlspecialchars($ing['nombre_insumo']); ?>
                                    <?php if ($ing['es_ingrediente_secreto']): ?>
                                        <span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">🔒</span>
                                    <?php endif; ?>
                                    <?php if (!empty($ing['alergenos'])): ?>
                                        <span style="background: #ff6b6b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">🚨</span>
                                    <?php endif; ?>
                                    <input type="hidden" name="ingredientes[<?php echo $ing['id_inv']; ?>][id_inv]" value="<?php echo $ing['id_inv']; ?>">
                                </div>
                                <input type="number" 
                                       name="ingredientes[<?php echo $ing['id_inv']; ?>][cantidad]" 
                                       placeholder="Cantidad" 
                                       step="0.01" 
                                       min="0.01" 
                                       required
                                       style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                                <select name="ingredientes[<?php echo $ing['id_inv']; ?>][unidad]" 
                                        style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                                    <option value="<?php echo $ing['medida_inv']; ?>" selected><?php echo $ing['medida_inv']; ?></option>
                                    <option value="Kg">Kg</option>
                                    <option value="Gr">Gr</option>
                                    <option value="Lt">Lt</option>
                                    <option value="Ml">Ml</option>
                                    <option value="Pza">Pza</option>
                                    <option value="Cucharada">Cucharada</option>
                                    <option value="Cucharadita">Cucharadita</option>
                                </select>
                                <button type="submit" 
                                        style="background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                                    + Agregar
                                </button>
                            </div>
                        <?php endwhile; ?>
                    </form>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">
                        📦 No hay ingredientes disponibles para agregar.
                    </p>
                <?php endif; ?>
            </div>
        
        <?php else: ?>
            <div class="sj-card">
                <p style="text-align: center; color: #666; padding: 30px;">
                    🥗 Este platillo no tiene ingredientes registrados.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<script>
function editarCantidad(id_detalle, cantidad_actual) {
    const nuevaCantidad = prompt(`Editar cantidad (actual: ${cantidad_actual}):`, cantidad_actual);
    
    if (nuevaCantidad === null) return;
    
    const cantidad = parseFloat(nuevaCantidad);
    if (isNaN(cantidad) || cantidad <= 0) {
        alert('Por favor ingresa una cantidad válida mayor a 0');
        return;
    }
    
    // Enviar solicitud AJAX para actualizar
    fetch('../PHP/editar_ingrediente_platillo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id_detalle=${id_detalle}&cantidad=${cantidad}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error al actualizar la cantidad: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor intenta nuevamente.');
    });
}

function eliminarIngrediente(id_detalle) {
    if (!confirm('¿Estás seguro de que deseas eliminar este ingrediente del platillo?')) {
        return;
    }
    
    // Enviar solicitud AJAX para eliminar
    fetch('../PHP/eliminar_ingrediente_platillo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id_detalle=${id_detalle}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error al eliminar el ingrediente: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor intenta nuevamente.');
    });
}

// Mostrar información nutricional detallada al hacer hover
document.addEventListener('DOMContentLoaded', function() {
    const infoNutricional = document.querySelector('[style*="border-left: 4px solid #3498db"]');
    if (infoNutricional) {
        infoNutricional.style.cursor = 'pointer';
        infoNutricional.title = 'Clic para ver desglose detallado de calorías';
        
        infoNutricional.addEventListener('click', function() {
            alert('📊 Información Nutricional\n\nLas calorías son un cálculo estimado basado en:\n• 50 kcal por unidad base de ingrediente\n• Proporción según cantidad utilizada\n\nPara información precisa, consulta con un nutricionista.');
        });
    }
});
</script>
