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
$id_inv = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_inv <= 0) {
    header("Location: inventario_crud.php");
    exit();
}

// Obtener restaurante del dueño
$query_res = $conn->prepare("SELECT id_res FROM restaurante WHERE id_usu = ?");
$query_res->bind_param("i", $id_usuario);
$query_res->execute();
$result_res = $query_res->get_result();
$restaurante = $result_res->fetch_assoc();

if (!$restaurante) {
    echo "Error: No se encontró un restaurante asociado a esta cuenta.";
    exit();
}

$id_res = $restaurante['id_res'];

// Obtener datos del ingrediente
$stmt_ing = $conn->prepare("SELECT * FROM inventario WHERE id_inv = ? AND id_res = ?");
$stmt_ing->bind_param("ii", $id_inv, $id_res);
$stmt_ing->execute();
$result_ing = $stmt_ing->get_result();

if ($result_ing->num_rows === 0) {
    header("Location: inventario_crud.php?error=not_found");
    exit();
}

$ingrediente = $result_ing->fetch_assoc();

// Procesar actualización
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_insumo = $_POST['nombre_insumo'];
    $stock_inv = floatval($_POST['stock_inv']);
    $medida_inv = $_POST['medida_inv'];
    $es_ingrediente_secreto = isset($_POST['es_ingrediente_secreto']) ? 1 : 0;
    $alergenos = $_POST['alergenos'] ?? '';
    
    $ruta_final = $ingrediente['img_insumo']; // Mantener imagen actual por defecto
    
    // Procesar nueva imagen si se sube
    if (isset($_FILES['img_insumo']) && $_FILES['img_insumo']['error'] == 0) {
        $directorio = "../UPLOADS/INSUMOS/";
        
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $extension = pathinfo($_FILES['img_insumo']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = "insumo_" . time() . "_" . uniqid() . "." . $extension;
        $ruta_destino = $directorio . $nombre_archivo;

        if (move_uploaded_file($_FILES['img_insumo']['tmp_name'], $ruta_destino)) {
            // Eliminar imagen anterior si existe
            if (!empty($ingrediente['img_insumo']) && file_exists($ingrediente['img_insumo'])) {
                unlink($ingrediente['img_insumo']);
            }
            $ruta_final = $ruta_destino;
        }
    }

    // Actualizar en base de datos
    try {
        $stmt = $conn->prepare("
            UPDATE inventario 
            SET nombre_insumo = ?, stock_inv = ?, medida_inv = ?, img_insumo = ?, 
                es_ingrediente_secreto = ?, alergenos = ?, fecha_actualizacion = NOW()
            WHERE id_inv = ? AND id_res = ?
        ");
        
        $stmt->bind_param("sdsssii", $nombre_insumo, $stock_inv, $medida_inv, $ruta_final, 
                         $es_ingrediente_secreto, $alergenos, $id_inv, $id_res);

        if ($stmt->execute()) {
            header("Location: inventario_crud.php?status=updated");
            exit();
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        $error_msg = urlencode($e->getMessage());
        header("Location: editar_ingrediente.php?id=$id_inv&status=error&msg=$error_msg");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Ingrediente | Salud Juárez</title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/platillos.css">
    <style>
        .form-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-field {
            margin-bottom: 15px;
        }
        
        .form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2D5A27;
        }
        
        .form-field input,
        .form-field select,
        .form-field textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {
            outline: none;
            border-color: #2D5A27;
        }
        
        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .checkbox-field input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        
        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            margin-top: 10px;
        }
        
        .alert-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .span-2 {
            grid-column: span 2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="sj-page-title">✏️ Editar Ingrediente</h1>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 15px;">
                ❌ Error al actualizar el ingrediente. Intente nuevamente.
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <div class="form-section">
                <h3>📋 Información Básica</h3>
                
                <div class="form-field">
                    <label for="nombre_insumo">Nombre del Insumo *</label>
                    <input type="text" id="nombre_insumo" name="nombre_insumo" 
                           value="<?php echo htmlspecialchars($ingrediente['nombre_insumo']); ?>" required>
                </div>

                <div class="form-field">
                    <label for="stock_inv">Cantidad (Stock) *</label>
                    <input type="number" id="stock_inv" name="stock_inv" 
                           value="<?php echo htmlspecialchars($ingrediente['stock_inv']); ?>" 
                           step="0.01" required>
                </div>

                <div class="form-field">
                    <label for="medida_inv">Unidad de Medida *</label>
                    <select id="medida_inv" name="medida_inv" required>
                        <option value="Kg" <?php echo $ingrediente['medida_inv'] == 'Kg' ? 'selected' : ''; ?>>Kilogramos (Kg)</option>
                        <option value="Gr" <?php echo $ingrediente['medida_inv'] == 'Gr' ? 'selected' : ''; ?>>Gramos (Gr)</option>
                        <option value="Lt" <?php echo $ingrediente['medida_inv'] == 'Lt' ? 'selected' : ''; ?>>Litros (Lt)</option>
                        <option value="Ml" <?php echo $ingrediente['medida_inv'] == 'Ml' ? 'selected' : ''; ?>>Mililitros (Ml)</option>
                        <option value="Mz" <?php echo $ingrediente['medida_inv'] == 'Mz' ? 'selected' : ''; ?>>Mazo (Mz)</option>
                        <option value="Pza" <?php echo $ingrediente['medida_inv'] == 'Pza' ? 'selected' : ''; ?>>Pieza (Pza)</option>
                        <option value="Lata" <?php echo $ingrediente['medida_inv'] == 'Lata' ? 'selected' : ''; ?>>Lata</option>
                        <option value="Botella" <?php echo $ingrediente['medida_inv'] == 'Botella' ? 'selected' : ''; ?>>Botella</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="img_insumo">Imagen del Ingrediente</label>
                    <input type="file" id="img_insumo" name="img_insumo" accept="image/*">
                    <?php if (!empty($ingrediente['img_insumo'])): ?>
                        <img src="<?php echo htmlspecialchars($ingrediente['img_insumo']); ?>" 
                             class="image-preview" alt="Imagen actual">
                        <p><small>Imagen actual. Sube una nueva para reemplazarla.</small></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-section">
                <h3>🔒 Configuración de Visibilidad</h3>
                
                <div class="alert-info">
                    <strong>ℹ️ Información importante:</strong><br>
                    - <strong>Ingrediente Secreto:</strong> No se mostrará a los clientes en el menú público<br>
                    - <strong>Alergenos:</strong> Se mostrarán como advertencia para clientes con alergias
                </div>
                
                <div class="checkbox-field">
                    <input type="checkbox" id="es_ingrediente_secreto" name="es_ingrediente_secreto" 
                           <?php echo ($ingrediente['es_ingrediente_secreto'] ?? 0) == 1 ? 'checked' : ''; ?>>
                    <label for="es_ingrediente_secreto">
                        🕵️ Marcar como ingrediente secreto (no visible para clientes)
                    </label>
                </div>

                <div class="form-field">
                    <label for="alergenos">Alergenos (separados por comas)</label>
                    <textarea id="alergenos" name="alergenos" rows="3" 
                              placeholder="Ej: gluten, lactosa, frutos secos, mariscos"><?php echo htmlspecialchars($ingrediente['alergenos'] ?? ''); ?></textarea>
                    <small>Estos alergenos se mostrarán como advertencia a los clientes.</small>
                </div>
            </div>

            <div class="btn-group span-2">
                <button type="submit" class="sj-btn sj-btn--primary" style="flex: 1;">
                    💾 Guardar Cambios
                </button>
                <a href="inventario_crud.php" class="sj-btn sj-btn--outline" style="flex: 1; text-align: center;">
                    ❌ Cancelar
                </a>
            </div>
        </form>
    </div>
</body>
</html>
