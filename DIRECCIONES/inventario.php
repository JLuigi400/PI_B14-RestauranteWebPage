<?php
session_start();
include '../PHP/db_config.php';
include '../PHP/navbar.php';

// Verificación de sesión
if (!isset($_SESSION['id_usu'])) {
    header("Location: ../login.html");
    exit();
}

$id_usuario = $_SESSION['id_usu'];

// Obtenemos el id_res asociado al usuario actual (Dueño)
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Inventario | Salud Juárez</title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/platillos.css">
</head>
<body>
    <div class="container">
        <h1 class="sj-page-title">📦 Inventario</h1>
        
        <?php
        // Mostrar mensajes de estado
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
            $message = '';
            $alert_class = '';
            
            switch ($status) {
                case 'success':
                    $message = '✅ Insumo registrado correctamente';
                    $alert_class = 'sj-alert--success';
                    break;
                case 'error':
                    $message = '❌ Error al registrar el insumo';
                    $alert_class = 'sj-alert--error';
                    if (isset($_GET['msg'])) {
                        $message .= ': ' . urldecode($_GET['msg']);
                    }
                    break;
            }
            
            if (!empty($message)) {
                echo "<div class='sj-alert $alert_class' style='margin-bottom: 20px;'>$message</div>";
            }
        }
        ?>
        
        <section class="sj-card">
            <h2 style="margin:0 0 12px 0;">Registrar insumo</h2>

            <form action="../PHP/procesar_insumo.php" method="POST" enctype="multipart/form-data" class="sj-form-grid">
                <input type="hidden" name="id_res" value="<?php echo $id_res; ?>">
                
                <div class="sj-field">
                    <label>Nombre del Insumo</label>
                    <input type="text" name="nombre_insumo" placeholder="Ej: Espinaca" required>
                </div>

                <div class="sj-field">
                    <label>Cantidad (Stock)</label>
                    <input type="number" step="0.01" name="stock_inv" placeholder="0.00" required>
                </div>

                <div class="sj-field">
                    <label>Unidad de Medida</label>
                    <select name="medida_inv" required>
                        <option value="Kg">Kilogramos (Kg)</option>
                        <option value="Gr">Gramos (Gr)</option>
                        <option value="Lt">Litros (Lt)</option>
                        <option value="Mz">Mazo (Mz)</option>
                        <option value="Pza">Pieza (Pza)</option>
                    </select>
                </div>

                <div class="sj-field">
                    <label>Alergenos (separados por comas)</label>
                    <input type="text" name="alergenos" placeholder="Ej: gluten, lactosa, nueces, soja" id="alergenos_input">
                    <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">
                        📋 Separa con comas. Los alergenos comunes se sugerirán automáticamente.
                    </small>
                </div>

                <div class="sj-field">
                    <label>
                        <input type="checkbox" name="es_ingrediente_secreto" value="1" style="margin-right: 8px;">
                        ¿Es un ingrediente secreto?
                    </label>
                    <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">
                        🔒 Los ingredientes secretos no serán visibles para los clientes.
                    </small>
                </div>

                <div class="sj-field">
                    <label>Imagen del Ingrediente (opcional)</label>
                    <input type="file" name="img_insumo" accept="image/*">
                </div>

                <div class="sj-span-2">
                    <button type="submit" class="sj-btn sj-btn--primary" style="width:100%;">Registrar Insumo</button>
                </div>
            </form>
        </section>

        <hr class="sj-divider">

        <section class="sj-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="margin:0;">📋 Lista de ingredientes</h2>
                <a href="inventario/inventario_crud.php" class="sj-btn sj-btn--secondary" style="text-decoration: none; padding: 8px 16px; font-size: 14px;">
                    ⚙️ Gestión Avanzada
                </a>
            </div>
        <table class="sj-table">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Insumo</th>
                    <th>Stock</th>
                    <th>Medida</th>
                    <th>Alergenos</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_lista = "SELECT * FROM inventario WHERE id_res = ? ORDER BY nombre_insumo ASC";
                $stmt_lista = $conn->prepare($sql_lista);
                $stmt_lista->bind_param("i", $id_res);
                $stmt_lista->execute();
                $res_lista = $stmt_lista->get_result();

                while ($row = $res_lista->fetch_assoc()) {
                    $img_path = !empty($row['img_insumo']) ? $row['img_insumo'] : '../IMG/default-insumo.png';
                    $img_safe = htmlspecialchars($img_path);
                    $nom_safe = htmlspecialchars($row['nombre_insumo']);
                    $stock_safe = htmlspecialchars($row['stock_inv']);
                    $med_safe = htmlspecialchars($row['medida_inv']);
                    
                    // Procesar alergenos
                    $alergenos_raw = $row['alergenos'] ?? '';
                    $alergenos_array = !empty($alergenos_raw) ? explode(',', $alergenos_raw) : [];
                    $alergenos_badges = '';
                    foreach ($alergenos_array as $alergeno) {
                        $alergeno_clean = trim($alergeno);
                        if (!empty($alergeno_clean)) {
                            $alergenos_badges .= "<span style='background: #ff6b6b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin: 1px;'>" . htmlspecialchars($alergeno_clean) . "</span> ";
                        }
                    }
                    
                    // Estado del ingrediente
                    $es_secreto = ($row['es_ingrediente_secreto'] ?? 0) == 1;
                    $estado_badge = $es_secreto 
                        ? "<span style='background: #e74c3c; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px;'>🔒 Secreto</span>"
                        : "<span style='background: #27ae60; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px;'>✅ Público</span>";
                    
                    echo "<tr>
                            <td><img src='{$img_safe}' width='54' height='40' style='border-radius:10px; object-fit:cover;' alt='Insumo'></td>
                            <td>{$nom_safe}</td>
                            <td>{$stock_safe}</td>
                            <td>{$med_safe}</td>
                            <td>{$alergenos_badges}</td>
                            <td>{$estado_badge}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<script>
// Sistema de autocompletado de alergenos
const alergenosComunes = [
    'gluten', 'lactosa', 'nueces', 'soja', 'huevo', 'pescado', 'mariscos', 
    'cacahuates', 'mostaza', 'apio', 'sésamo', 'altramuces', 'moluscos',
    'caseína', 'frutos secos', 'cacao', 'chocolate', 'dairy', 'wheat'
];

// Almacenar alergenos personalizados del usuario
let alergenosPersonalizados = [];

// Función para inicializar el autocompletado
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('alergenos_input');
    if (!input) return;
    
    // Crear contenedor de sugerencias
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.id = 'alergenos_suggestions';
    suggestionsContainer.style.cssText = `
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        width: 100%;
        box-sizing: border-box;
    `;
    
    // Posicionar el contenedor después del input
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(suggestionsContainer);
    
    // Eventos del input
    input.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        const currentValue = value.split(',').pop().trim(); // Obtener última palabra
        
        if (currentValue.length < 2) {
            suggestionsContainer.style.display = 'none';
            return;
        }
        
        // Combinar alergenos comunes y personalizados
        const allAlergenos = [...new Set([...alergenosComunes, ...alergenosPersonalizados])];
        
        // Filtrar sugerencias
        const matches = allAlergenos.filter(alergeno => 
            alergeno.toLowerCase().includes(currentValue)
        );
        
        if (matches.length > 0) {
            showSuggestions(matches, currentValue, input, suggestionsContainer);
        } else {
            suggestionsContainer.style.display = 'none';
        }
    });
    
    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.style.display = 'none';
        }
    });
});

function showSuggestions(matches, currentValue, input, container) {
    container.innerHTML = '';
    
    matches.forEach(match => {
        const item = document.createElement('div');
        item.style.cssText = `
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        `;
        
        // Resaltar la parte que coincide
        const regex = new RegExp(`(${currentValue})`, 'gi');
        item.innerHTML = match.replace(regex, '<strong>$1</strong>');
        
        item.addEventListener('click', function() {
            // Reemplazar la última palabra con la selección
            const currentValueArray = input.value.split(',');
            currentValueArray[currentValueArray.length - 1] = match;
            input.value = currentValueArray.join(', ');
            
            // Agregar a personalizados si no existe
            if (!alergenosPersonalizados.includes(match)) {
                alergenosPersonalizados.push(match);
                // Guardar en localStorage para persistencia
                localStorage.setItem('alergenos_personalizados', JSON.stringify(alergenosPersonalizados));
            }
            
            container.style.display = 'none';
            input.focus();
        });
        
        item.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f5f5f5';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'white';
        });
        
        container.appendChild(item);
    });
    
    container.style.display = 'block';
}

// Cargar alergenos personalizados del localStorage
document.addEventListener('DOMContentLoaded', function() {
    const stored = localStorage.getItem('alergenos_personalizados');
    if (stored) {
        try {
            alergenosPersonalizados = JSON.parse(stored);
        } catch (e) {
            console.error('Error loading alergenos personalizados:', e);
        }
    }
});
</script>