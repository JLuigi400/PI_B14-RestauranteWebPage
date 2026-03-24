<?php
session_start();
include '../PHP/db_config.php';

// Solo administradores pueden acceder
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 1) {
    header("Location: ../login.php");
    exit();
}

$id_admin = $_SESSION['id_usu'];

// Obtener filtros
$filtro_restaurante = isset($_GET['filtro_restaurante']) ? intval($_GET['filtro_restaurante']) : 0;
$filtro_stock = isset($_GET['filtro_stock']) ? $_GET['filtro_stock'] : 'todos';
$filtro_alergenos = isset($_GET['filtro_alergenos']) ? $_GET['filtro_alergenos'] : 'todos';

// Obtener lista de restaurantes para el filtro
$stmt_restaurantes = $conn->prepare("
    SELECT r.id_res, r.nombre_res, u.username_usu as propietario
    FROM restaurante r
    JOIN usuarios u ON r.id_usu = u.id_usu
    WHERE r.estatus_res = 1
    ORDER BY r.nombre_res ASC
");
$stmt_restaurantes->execute();
$restaurantes = $stmt_restaurantes->get_result();

// Construir consulta principal
$sql = "
    SELECT 
        i.*,
        r.nombre_res as restaurante,
        u.username_usu as propietario,
        CASE 
            WHEN i.stock_inv <= 5 THEN 'crítico'
            WHEN i.stock_inv <= 10 THEN 'bajo'
            ELSE 'normal'
        END as nivel_stock
    FROM inventario i
    JOIN restaurante r ON i.id_res = r.id_res
    JOIN usuarios u ON r.id_usu = u.id_usu
    WHERE 1=1
";

$params = [];
$types = "";

// Aplicar filtros
if ($filtro_restaurante > 0) {
    $sql .= " AND i.id_res = ?";
    $params[] = $filtro_restaurante;
    $types .= "i";
}

if ($filtro_stock == 'bajo') {
    $sql .= " AND i.stock_inv <= 10";
} elseif ($filtro_stock == 'critico') {
    $sql .= " AND i.stock_inv <= 5";
}

if ($filtro_alergenos == 'con') {
    $sql .= " AND i.alergenos IS NOT NULL AND i.alergenos != ''";
} elseif ($filtro_alergenos == 'sin') {
    $sql .= " AND (i.alergenos IS NULL OR i.alergenos = '')";
}

$sql .= " ORDER BY r.nombre_res ASC, i.nombre_insumo ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$ingredientes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración de Ingredientes | Salud Juárez</title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/platillos.css">
    <style>
        .admin-filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .filter-row label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            display: block;
        }
        .filter-row select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .ingredientes-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ingredientes-table th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .ingredientes-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .ingredientes-table tr:hover {
            background: #f8f9fa;
        }
        .badge-secret {
            background: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
        .badge-public {
            background: #27ae60;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
        .badge-alergeno {
            background: #ff6b6b;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            margin: 1px;
            display: inline-block;
        }
        .stock-critico {
            color: #e74c3c;
            font-weight: bold;
        }
        .stock-bajo {
            color: #f39c12;
            font-weight: bold;
        }
        .stock-normal {
            color: #27ae60;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include '../PHP/navbar.php'; ?>

    <div class="container">
        <h1 class="sj-page-title">🔍 Administración de Ingredientes</h1>
        
        <!-- Estadísticas generales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $ingredientes->num_rows; ?></div>
                <div class="stat-label">Total Ingredientes</div>
            </div>
            <?php
            // Calcular estadísticas
            $ingredientes->data_seek(0);
            $criticos = 0;
            $secretos = 0;
            $con_alergenos = 0;
            
            while ($row = $ingredientes->fetch_assoc()) {
                if ($row['stock_inv'] <= 5) $criticos++;
                if ($row['es_ingrediente_secreto']) $secretos++;
                if (!empty($row['alergenos'])) $con_alergenos++;
            }
            $ingredientes->data_seek(0);
            ?>
            <div class="stat-card">
                <div class="stat-number stock-critico"><?php echo $criticos; ?></div>
                <div class="stat-label">Stock Crítico</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $secretos; ?></div>
                <div class="stat-label">Ingredientes Secretos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $con_alergenos; ?></div>
                <div class="stat-label">Con Alergenos</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="admin-filters">
            <h3 style="margin: 0 0 15px 0; color: #2c3e50;">🔍 Filtros de Búsqueda</h3>
            <form method="GET" action="">
                <div class="filter-row">
                    <div>
                        <label for="filtro_restaurante">Restaurante:</label>
                        <select name="filtro_restaurante" id="filtro_restaurante">
                            <option value="0">Todos los restaurantes</option>
                            <?php while ($rest = $restaurantes->fetch_assoc()): ?>
                                <option value="<?php echo $rest['id_res']; ?>" <?php echo $filtro_restaurante == $rest['id_res'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rest['nombre_res']); ?> (<?php echo htmlspecialchars($rest['propietario']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="filtro_stock">Nivel de Stock:</label>
                        <select name="filtro_stock" id="filtro_stock">
                            <option value="todos" <?php echo $filtro_stock == 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="normal" <?php echo $filtro_stock == 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="bajo" <?php echo $filtro_stock == 'bajo' ? 'selected' : ''; ?>>Bajo (≤10)</option>
                            <option value="critico" <?php echo $filtro_stock == 'critico' ? 'selected' : ''; ?>>Crítico (≤5)</option>
                        </select>
                    </div>
                    <div>
                        <label for="filtro_alergenos">Alergenos:</label>
                        <select name="filtro_alergenos" id="filtro_alergenos">
                            <option value="todos" <?php echo $filtro_alergenos == 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="con" <?php echo $filtro_alergenos == 'con' ? 'selected' : ''; ?>>Con alergenos</option>
                            <option value="sin" <?php echo $filtro_alergenos == 'sin' ? 'selected' : ''; ?>>Sin alergenos</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="sj-btn sj-btn--primary">Aplicar Filtros</button>
                <a href="admin_ingredientes.php" class="sj-btn sj-btn--secondary" style="margin-left: 10px;">Limpiar Filtros</a>
            </form>
        </div>

        <!-- Tabla de ingredientes -->
        <?php if ($ingredientes && $ingredientes->num_rows > 0): ?>
            <table class="ingredientes-table">
                <thead>
                    <tr>
                        <th>Restaurante</th>
                        <th>Ingrediente</th>
                        <th>Stock Actual</th>
                        <th>Estado</th>
                        <th>Alergenos</th>
                        <th>Secreto</th>
                        <th>Calorías Base</th>
                        <th>Última Actualización</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $ingredientes->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($row['restaurante']); ?></strong>
                                    <br><small style="color: #666;"><?php echo htmlspecialchars($row['propietario']); ?></small>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['nombre_insumo']); ?></strong>
                                <?php if (!empty($row['img_insumo']) && $row['img_insumo'] != 'default_insumo.png'): ?>
                                    <br><small style="color: #666;">🖼️ Con imagen</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="stock-<?php echo $row['nivel_stock']; ?>">
                                    <?php echo number_format($row['stock_inv'], 2); ?> <?php echo htmlspecialchars($row['medida_inv']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['nivel_stock'] == 'crítico'): ?>
                                    <span style="color: #e74c3c; font-weight: bold;">⚠️ Crítico</span>
                                <?php elseif ($row['nivel_stock'] == 'bajo'): ?>
                                    <span style="color: #f39c12; font-weight: bold;">⚠️ Bajo</span>
                                <?php else: ?>
                                    <span style="color: #27ae60; font-weight: bold;">✅ Normal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['alergenos'])): ?>
                                    <?php 
                                    $alergenos_array = explode(',', $row['alergenos']);
                                    foreach ($alergenos_array as $alergeno) {
                                        $alergeno_clean = trim($alergeno);
                                        if (!empty($alergeno_clean)) {
                                            echo "<span class='badge-alergeno'>" . htmlspecialchars($alergeno_clean) . "</span>";
                                        }
                                    }
                                    ?>
                                <?php else: ?>
                                    <span style="color: #666; font-style: italic;">Sin alergenos</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['es_ingrediente_secreto']): ?>
                                    <span class="badge-secret">🔒 Secreto</span>
                                <?php else: ?>
                                    <span class="badge-public">✅ Público</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color: #2c3e50; font-weight: bold;">
                                    <?php echo number_format($row['calorias_base'] ?? 50, 0); ?> kcal
                                </span>
                            </td>
                            <td>
                                <?php 
                                $fecha = new DateTime($row['fecha_actualizacion']);
                                echo $fecha->format('d/m/Y H:i');
                                ?>
                            </td>
                            <td>
                                <button onclick="editarCalorias(<?php echo $row['id_inv']; ?>, <?php echo $row['calorias_base'] ?? 50; ?>)" 
                                        style="background: #3498db; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; margin-right: 5px;">
                                    📊 Editar kcal
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="sj-card">
                <p style="text-align: center; color: #666; padding: 30px;">
                    📦 No se encontraron ingredientes con los filtros seleccionados.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<script>
function editarCalorias(id_inv, calorias_actuales) {
    const nuevasCalorias = prompt(`Editar calorías base por unidad:\n\nIngrediente ID: ${id_inv}\nCalorías actuales: ${calorias_actuales} kcal\n\nNuevas calorías:`, calorias_actuales);
    
    if (nuevasCalorias === null) return;
    
    const calorias = parseFloat(nuevasCalorias);
    if (isNaN(calorias) || calorias < 0) {
        alert('Por favor ingresa un valor válido mayor o igual a 0');
        return;
    }
    
    // Enviar solicitud AJAX para actualizar
    fetch('../PHP/editar_calorias_ingrediente.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id_inv=${id_inv}&calorias=${calorias}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Calorías actualizadas correctamente');
            location.reload();
        } else {
            alert('Error al actualizar las calorías: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Por favor intenta nuevamente.');
    });
}
</script>
