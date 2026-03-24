<?php
session_start();
include '../PHP/db_config.php';

// 1. Verificación de sesión y rol de Dueño (2)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];

// 2. Obtener el ID del restaurante asociado a este Chef
$query_res = $conn->prepare("SELECT id_res FROM restaurante WHERE id_usu = ?");
$query_res->bind_param("i", $id_usuario);
$query_res->execute();
$res_data = $query_res->get_result()->fetch_assoc();

if (!$res_data) {
    die("Error: No tienes un restaurante registrado. Contacta al administrador.");
}
$id_res = $res_data['id_res'];

// 2.1. Obtener catálogo de categorías (para selector)
// Nota: aunque exista res_categorias, aquí listamos el catálogo completo para poder elegir cualquiera.
$stmt_cats = $conn->prepare("SELECT id_cat, nombre_cat FROM categorias ORDER BY nombre_cat ASC");
$stmt_cats->execute();
$cats_result = $stmt_cats->get_result();

// 3. Procesar acciones (Borrar o Cambiar Visibilidad)
if (isset($_GET['accion']) && isset($_GET['id_pla'])) {
    $id_pla = intval($_GET['id_pla']);
    
    if ($_GET['accion'] == 'borrar') {
        $del = $conn->prepare("DELETE FROM platillos WHERE id_pla = ? AND id_res = ?");
        $del->bind_param("ii", $id_pla, $id_res);
        $del->execute();
    } elseif ($_GET['accion'] == 'toggle') {
        $upd = $conn->prepare("UPDATE platillos SET visible = NOT visible WHERE id_pla = ? AND id_res = ?");
        $upd->bind_param("ii", $id_pla, $id_res);
        $upd->execute();
    }
    // Limpiar la URL después de la acción
    header("Location: gestion_platillos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Platillos | Salud Juárez</title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/platillos.css">
</head>
<body>
    <?php include '../PHP/navbar.php'; ?>

    <div class="container">
        <h1 class="sj-page-title">🍽️ Gestión de Menú</h1>

        <section class="sj-card">
            <h2 style="margin:0 0 12px 0;">Registrar nuevo platillo</h2>
            <form action="../PHP/procesar_platillo.php" method="POST" enctype="multipart/form-data" class="sj-form-grid">
                <input type="hidden" name="id_res" value="<?php echo (int)$id_res; ?>">

                <div class="sj-field">
                    <label>Nombre del platillo</label>
                    <input type="text" name="nombre_pla" required>
                </div>

                <div class="sj-field">
                    <label>Categoría</label>
                    <select name="id_cat" required>
                        <?php if ($cats_result && $cats_result->num_rows > 0): ?>
                            <?php while ($cat = $cats_result->fetch_assoc()): ?>
                                <option value="<?php echo (int)$cat['id_cat']; ?>">
                                    <?php echo htmlspecialchars($cat['nombre_cat']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="0">Sin categorías asignadas</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="sj-field sj-span-2">
                    <label>Descripción</label>
                    <textarea name="descripcion_pla" rows="3"></textarea>
                </div>

                <div class="sj-field">
                    <label>Precio ($)</label>
                    <input type="number" step="0.01" name="precio_pla" required>
                </div>

                <div class="sj-field">
                    <label>Imagen (opcional)</label>
                    <input type="file" name="img_pla" accept="image/*">
                </div>

                <div class="sj-span-2">
                    <label class="sj-check">
                        <input type="checkbox" name="mostrar_ing_pla" value="1">
                        Mostrar ingredientes en la ficha pública
                    </label>
                </div>

                <div class="sj-span-2">
                    <h3 style="margin: 20px 0 10px 0; color: #2c3e50;">🥗 Ingredientes del Platillo</h3>
                    <p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
                        Selecciona los ingredientes que utiliza este platillo y sus cantidades.
                    </p>
                    
                    <div id="ingredientes_container">
                        <div class="ingredientes-header">
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 80px; gap: 10px; font-weight: bold; color: #34495e; padding: 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 10px;">
                                <span>Ingrediente</span>
                                <span>Cantidad</span>
                                <span>Unidad</span>
                                <span>Acción</span>
                            </div>
                        </div>
                        
                        <div id="ingredientes_list">
                            <!-- Los ingredientes seleccionados se agregarán aquí dinámicamente -->
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <select id="ingrediente_selector" style="width: 250px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="">Seleccionar ingrediente...</option>
                                <?php
                                // Obtener ingredientes disponibles del inventario
                                $stmt_inv = $conn->prepare("
                                    SELECT id_inv, nombre_insumo, stock_inv, medida_inv, es_ingrediente_secreto, alergenos
                                    FROM inventario 
                                    WHERE id_res = ? AND stock_inv > 0 
                                    ORDER BY nombre_insumo ASC
                                ");
                                $stmt_inv->bind_param("i", $id_res);
                                $stmt_inv->execute();
                                $result_inv = $stmt_inv->get_result();
                                
                                while ($ing = $result_inv->fetch_assoc()) {
                                    $secreto_class = $ing['es_ingrediente_secreto'] ? ' (secreto)' : '';
                                    $alergenos_badge = !empty($ing['alergenos']) ? ' 🚨' : '';
                                    echo "<option value='{$ing['id_inv']}|{$ing['nombre_insumo']}|{$ing['medida_inv']}|{$ing['es_ingrediente_secreto']}|{$ing['alergenos']}'>";
                                    echo htmlspecialchars($ing['nombre_insumo']) . $secreto_class . $alergenos_badge;
                                    echo "</option>";
                                }
                                ?>
                            </select>
                            <button type="button" onclick="agregarIngrediente()" style="margin-left: 10px; padding: 8px 15px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                + Agregar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="sj-span-2">
                    <button type="submit" class="sj-btn sj-btn--primary" style="width:100%;">Guardar platillo</button>
                </div>
            </form>
        </section>

        <hr class="sj-divider">

        <h2 style="margin:0 0 10px 0;">Tu menú actual</h2>
        <?php
        // Intento 1 (nuevo): platillos con categoria por id_cat
        $sql = "
            SELECT p.*, c.nombre_cat
            FROM platillos p
            LEFT JOIN categorias c ON c.id_cat = p.id_cat
            WHERE p.id_res = ?
            ORDER BY p.id_pla DESC
        ";
        $stmt = null;
        try {
            $stmt = $conn->prepare($sql);
        } catch (mysqli_sql_exception $e) {
            // Si la BD aún no tiene platillos.id_cat, caemos al fallback sin romper la vista
            $stmt = null;
        }

        // Fallback (compatibilidad): si todavía no existe platillos.id_cat o categorias no está disponible
        if (!$stmt) {
            $sql = "SELECT * FROM platillos WHERE id_res = ? ORDER BY id_pla DESC";
            $stmt = $conn->prepare($sql);
        }

        if (!$stmt) {
            die("Error preparando consulta de platillos: " . $conn->error);
        }
        $stmt->bind_param("i", $id_res);
        if (!$stmt->execute()) {
            die("Error ejecutando consulta de platillos: " . $stmt->error);
        }
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0): ?>
            <div class="sj-grid">
                <?php while ($platillo = $resultado->fetch_assoc()): 
                    $clase_oculto = $platillo['visible'] ? '' : 'tarjeta-oculta';
                    $foto = !empty($platillo['img_pla']) ? $platillo['img_pla'] : '../IMG/default-food.png';
                    $nombre_cat = 'Sin categoría';
                    if (!empty($platillo['nombre_cat'])) {
                        $nombre_cat = $platillo['nombre_cat'];
                    } elseif (!empty($platillo['tipo_comida'])) {
                        // Compatibilidad con BD vieja (cuando existía tipo_comida en platillos)
                        $nombre_cat = $platillo['tipo_comida'];
                    }
                ?>
                    <article class="sj-menu-card <?php echo $clase_oculto; ?>">
                        <span class="sj-badge"><?php echo htmlspecialchars($nombre_cat); ?></span>

                        <div class="sj-media">
                            <img src="<?php echo htmlspecialchars($foto); ?>" alt="Imagen">
                        </div>

                        <div class="sj-body">
                            <h3 class="sj-title"><?php echo htmlspecialchars($platillo['nombre_pla']); ?></h3>
                            <p class="sj-desc"><?php echo htmlspecialchars($platillo['descripcion_pla']); ?></p>

                            <div class="sj-row">
                                <div class="sj-price">$<?php echo number_format((float)$platillo['precio_pla'], 2); ?></div>
                                <?php if(!empty($platillo['mostrar_ing_pla'])): ?>
                                    <div class="sj-pill">👁️ Con ingredientes</div>
                                <?php endif; ?>
                            </div>

                            <div style="margin-top: 12px; display:flex; gap:8px; flex-wrap: wrap;">
                                <a href="editar_platillo.php?id=<?php echo (int)$platillo['id_pla']; ?>" class="sj-btn sj-btn--accent" style="padding:10px 12px;">Editar</a>
                                <a href="revisar_ingredientes.php?id_pla=<?php echo (int)$platillo['id_pla']; ?>" class="sj-btn sj-btn--accent" style="padding:10px 12px; border-color: rgba(255,191,0,0.35); background: rgba(255,191,0,0.14);">Ingredientes</a>
                                <a href="?accion=toggle&id_pla=<?php echo (int)$platillo['id_pla']; ?>" class="sj-btn sj-btn--accent" style="padding:10px 12px;">
                                    <?php echo $platillo['visible'] ? 'Ocultar' : 'Mostrar'; ?>
                                </a>
                                <a href="?accion=borrar&id_pla=<?php echo (int)$platillo['id_pla']; ?>"
                                   onclick="return confirm('¿Seguro que deseas eliminar este platillo?')"
                                   class="sj-btn sj-btn--accent"
                                   style="padding:10px 12px; border-color: rgba(231,76,60,0.35); background: rgba(231,76,60,0.12);">
                                    Borrar
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--gris-suave); margin-top: 30px;">Aún no tienes platillos registrados.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<script>
let ingredientesSeleccionados = [];
let ingredienteIdCounter = 0;

function agregarIngrediente() {
    const selector = document.getElementById('ingrediente_selector');
    const selectedValue = selector.value;
    
    if (!selectedValue) {
        alert('Por favor selecciona un ingrediente');
        return;
    }
    
    // Parsear los datos del ingrediente
    const [id_inv, nombre, medida, es_secreto, alergenos] = selectedValue.split('|');
    
    // Verificar si ya fue agregado
    if (ingredientesSeleccionados.find(ing => ing.id_inv === id_inv)) {
        alert('Este ingrediente ya fue agregado');
        return;
    }
    
    const ingredienteId = `ing_${ingredienteIdCounter++}`;
    
    // Crear objeto de ingrediente
    const ingrediente = {
        id: ingredienteId,
        id_inv: id_inv,
        nombre: nombre,
        medida: medida,
        es_secreto: es_secreto,
        alergenos: alergenos,
        cantidad: '',
        unidad: medida
    };
    
    ingredientesSeleccionados.push(ingrediente);
    
    // Agregar a la lista visual
    const ingredientesList = document.getElementById('ingredientes_list');
    const ingredienteDiv = document.createElement('div');
    ingredienteDiv.id = ingredienteId;
    ingredienteDiv.style.cssText = `
        display: grid; 
        grid-template-columns: 2fr 1fr 1fr 80px; 
        gap: 10px; 
        align-items: center; 
        padding: 10px; 
        background: #f8f9fa; 
        border-radius: 8px; 
        margin-bottom: 8px;
        border: 1px solid #e9ecef;
    `;
    
    // Indicadores visuales
    const secretoIndicator = es_secreto === '1' ? '<span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">🔒</span>' : '';
    const alergenosIndicator = alergenos ? '<span style="background: #ff6b6b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">🚨</span>' : '';
    
    ingredienteDiv.innerHTML = `
        <div>
            <strong>${nombre}</strong>
            ${secretoIndicator}
            ${alergenosIndicator}
            <input type="hidden" name="ingredientes[${ingredienteId}][id_inv]" value="${id_inv}">
            <input type="hidden" name="ingredientes[${ingredienteId}][nombre]" value="${nombre}">
            <input type="hidden" name="ingredientes[${ingredienteId}][es_secreto]" value="${es_secreto}">
            <input type="hidden" name="ingredientes[${ingredienteId}][alergenos]" value="${alergenos}">
        </div>
        <input type="number" 
               name="ingredientes[${ingredienteId}][cantidad]" 
               placeholder="Cantidad" 
               step="0.01" 
               min="0.01" 
               required
               style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;"
               onchange="actualizarIngrediente('${ingredienteId}', 'cantidad', this.value)">
        <select name="ingredientes[${ingredienteId}][unidad]" 
                onchange="actualizarIngrediente('${ingredienteId}', 'unidad', this.value)"
                style="width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="${medida}" selected>${medida}</option>
            <option value="Kg">Kg</option>
            <option value="Gr">Gr</option>
            <option value="Lt">Lt</option>
            <option value="Ml">Ml</option>
            <option value="Pza">Pza</option>
            <option value="Cucharada">Cucharada</option>
            <option value="Cucharadita">Cucharadita</option>
        </select>
        <button type="button" 
                onclick="eliminarIngrediente('${ingredienteId}')"
                style="background: #e74c3c; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer;">
            ✕
        </button>
    `;
    
    ingredientesList.appendChild(ingredienteDiv);
    
    // Resetear selector
    selector.value = '';
    selector.focus();
}

function actualizarIngrediente(ingredienteId, campo, valor) {
    const ingrediente = ingredientesSeleccionados.find(ing => ing.id === ingredienteId);
    if (ingrediente) {
        ingrediente[campo] = valor;
    }
}

function eliminarIngrediente(ingredienteId) {
    // Eliminar del array
    ingredientesSeleccionados = ingredientesSeleccionados.filter(ing => ing.id !== ingredienteId);
    
    // Eliminar del DOM
    const elemento = document.getElementById(ingredienteId);
    if (elemento) {
        elemento.remove();
    }
}

// Validación antes de enviar el formulario
document.querySelector('form').addEventListener('submit', function(e) {
    if (ingredientesSeleccionados.length === 0) {
        e.preventDefault();
        alert('Por favor agrega al menos un ingrediente al platillo');
        return;
    }
    
    // Validar que todos los ingredientes tengan cantidad
    for (let ingrediente of ingredientesSeleccionados) {
        if (!ingrediente.cantidad || parseFloat(ingrediente.cantidad) <= 0) {
            e.preventDefault();
            alert(`Por favor especifica una cantidad válida para: ${ingrediente.nombre}`);
            return;
        }
    }
});

// Mostrar información de alergenos al pasar el mouse
document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('ingrediente_selector');
    
    selector.addEventListener('change', function() {
        const selectedValue = this.value;
        if (selectedValue) {
            const [id_inv, nombre, medida, es_secreto, alergenos] = selectedValue.split('|');
            
            if (alergenos) {
                const alergenosList = alergenos.split(',').map(a => a.trim()).join(', ');
                const mensaje = `⚠️ Este ingrediente contiene: ${alergenosList}`;
                
                // Mostrar tooltip temporal
                const tooltip = document.createElement('div');
                tooltip.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    color: #856404;
                    padding: 10px 15px;
                    border-radius: 6px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    z-index: 1000;
                    max-width: 300px;
                    font-size: 14px;
                `;
                tooltip.innerHTML = mensaje;
                document.body.appendChild(tooltip);
                
                setTimeout(() => {
                    if (tooltip.parentNode) {
                        tooltip.parentNode.removeChild(tooltip);
                    }
                }, 3000);
            }
        }
    });
});
</script>