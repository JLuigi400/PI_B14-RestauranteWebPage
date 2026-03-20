<?php
session_start();
include '../PHP/db_config.php';

// Este archivo genera el contenido del modal para clientes
// Se llama vía AJAX desde ver_menu.php

if (!isset($_GET['id_pla'])) {
    exit('ID de platillo no especificado');
}

$id_pla = intval($_GET['id_pla']);

// Obtener información del platillo
$stmt_platillo = $conn->prepare("
    SELECT p.nombre_pla, p.descripcion_pla, p.precio_pla, p.mostrar_ing_pla,
           r.nombre_res
    FROM platillos p
    JOIN restaurante r ON p.id_res = r.id_res
    WHERE p.id_pla = ? AND p.visible = 1
");
$stmt_platillo->bind_param("i", $id_pla);
$stmt_platillo->execute();
$platillo = $stmt_platillo->get_result()->fetch_assoc();

if (!$platillo) {
    exit('Platillo no encontrado');
}

// Obtener ingredientes visibles (no secretos) con stock > 0
$stmt_ingredientes = $conn->prepare("
    SELECT i.nombre_insumo, i.stock_inv, i.medida_inv, i.img_insumo, i.alergenos,
           IFNULL(pi.cantidad_usada, 0) as cantidad_usada
    FROM inventario i
    LEFT JOIN platillo_ingredientes pi ON i.id_inv = pi.id_inv AND pi.id_pla = ?
    WHERE i.id_res = (SELECT id_res FROM platillos WHERE id_pla = ?) 
      AND i.stock_inv > 0 
      AND (i.es_ingrediente_secreto = 0 OR i.es_ingrediente_secreto IS NULL)
    ORDER BY i.nombre_insumo ASC
");
$stmt_ingredientes->bind_param("ii", $id_pla, $id_pla);
$stmt_ingredientes->execute();
$ingredientes = $stmt_ingredientes->get_result();

// Obtener alergenos únicos de todos los ingredientes
$alergenos_lista = [];
while ($row = $ingredientes->fetch_assoc()) {
    if (!empty($row['alergenos'])) {
        $alergenos_individuales = array_map('trim', explode(',', $row['alergenos']));
        $alergenos_lista = array_merge($alergenos_lista, $alergenos_individuales);
    }
}
$alergenos_unicos = array_unique(array_filter($alergenos_lista));

// Resetear puntero del resultado para volver a usarlo
$ingredientes->data_seek(0);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingredientes - <?php echo htmlspecialchars($platillo['nombre_pla']); ?></title>
    <style>
        .modal-header {
            background: linear-gradient(135deg, #2D5A27, #4a7c4a);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        
        .modal-body {
            padding: 25px;
            background: white;
        }
        
        .platillo-info {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .platillo-nombre {
            font-size: 24px;
            font-weight: bold;
            color: #2D5A27;
            margin-bottom: 8px;
        }
        
        .platillo-precio {
            font-size: 20px;
            color: #FFBF00;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .platillo-restaurante {
            color: #666;
            font-size: 14px;
        }
        
        .ingredientes-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #2D5A27;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ingredientes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .ingrediente-card {
            background: #f9f9f9;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .ingrediente-card:hover {
            border-color: #2D5A27;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 90, 39, 0.1);
        }
        
        .ingrediente-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 3px solid #e0e0e0;
        }
        
        .ingrediente-nombre {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .ingrediente-cantidad {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .ingrediente-disponible {
            font-size: 12px;
            color: #28a745;
            font-weight: bold;
        }
        
        .alergenos-alert {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .alergenos-alert h4 {
            color: #856404;
            margin: 0 0 10px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .alergenos-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .alergeno-tag {
            background: #ffc107;
            color: #856404;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            text-transform: capitalize;
        }
        
        .sin-ingredientes {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .sin-ingredientes-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .transparencia-info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1565c0;
        }
        
        .modal-footer {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
            text-align: center;
        }
        
        .btn-cerrar {
            background: #2D5A27;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }
        
        .btn-cerrar:hover {
            background: #1e3a5a;
        }
    </style>
</head>
<body>
    <div class="modal-header">
        <h2 style="margin: 0;">🥗 Información de Ingredientes</h2>
        <p style="margin: 5px 0 0 0; opacity: 0.9;">Transparencia y confianza en tu comida</p>
    </div>
    
    <div class="modal-body">
        <div class="platillo-info">
            <div class="platillo-nombre"><?php echo htmlspecialchars($platillo['nombre_pla']); ?></div>
            <div class="platillo-precio">$<?php echo number_format($platillo['precio_pla'], 2); ?></div>
            <div class="platillo-restaurante">📍 <?php echo htmlspecialchars($platillo['nombre_res']); ?></div>
        </div>
        
        <?php if (!empty($alergenos_unicos)): ?>
            <div class="alergenos-alert">
                <h4>⚠️ Advertencia de Alergenos</h4>
                <p>Este platillo contiene los siguientes alergenos. Si tienes alguna alergia, por favor ten cuidado:</p>
                <div class="alergenos-list">
                    <?php foreach ($alergenos_unicos as $alergeno): ?>
                        <span class="alergeno-tag"><?php echo htmlspecialchars(strtolower($alergeno)); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="transparencia-info">
            <strong>🔍 Nuestro compromiso con la transparencia:</strong><br>
            Mostramos todos los ingredientes principales utilizados en tu platillo. 
            Algunos ingredientes secretos de nuestra receta no se muestran para proteger nuestra propiedad intelectual, 
            pero todos cumplen con los más altos estándares de calidad y seguridad.
        </div>
        
        <div class="ingredientes-section">
            <h3 class="section-title">
                📋 Ingredientes Principales
                <span style="font-size: 14px; color: #666; font-weight: normal;">
                    (<?php echo $ingredientes->num_rows; ?> ingredientes)
                </span>
            </h3>
            
            <?php if ($ingredientes->num_rows > 0): ?>
                <div class="ingredientes-grid">
                    <?php while ($row = $ingredientes->fetch_assoc()): 
                        $img_path = !empty($row['img_insumo']) ? $row['img_insumo'] : '../IMG/default-insumo.png';
                    ?>
                        <div class="ingrediente-card">
                            <img src="<?php echo htmlspecialchars($img_path); ?>" 
                                 class="ingrediente-img" 
                                 alt="<?php echo htmlspecialchars($row['nombre_insumo']); ?>">
                            <div class="ingrediente-nombre"><?php echo htmlspecialchars($row['nombre_insumo']); ?></div>
                            <?php if ($row['cantidad_usada'] > 0): ?>
                                <div class="ingrediente-cantidad">
                                    <?php echo number_format($row['cantidad_usada'], 2); ?> <?php echo htmlspecialchars($row['medida_inv']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="ingrediente-disponible">✓ Disponible</div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="sin-ingredientes">
                    <div class="sin-ingredientes-icon">🥄</div>
                    <h3>No hay ingredientes disponibles para mostrar</h3>
                    <p>Este platillo podría estar usando ingredientes temporalmente agotados.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($platillo['mostrar_ing_pla'] == 1): ?>
            <div class="ingredientes-section">
                <h3 class="section-title">ℹ️ Información Nutricional</h3>
                <p style="color: #666; text-align: center;">
                    Para información detallada sobre valores nutricionales, por favor consulta con nuestro personal.
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="modal-footer">
        <button class="btn-cerrar" onclick="cerrarModalIngredientes()">Cerrar</button>
    </div>
    
    <script>
        function cerrarModalIngredientes() {
            // Esta función será llamada desde la página principal
            if (window.parent && window.parent.cerrarModalIngredientes) {
                window.parent.cerrarModalIngredientes();
            }
        }
        
        // Auto-ajustar tamaño del modal si está en iframe
        if (window.parent) {
            document.addEventListener('DOMContentLoaded', function() {
                const height = document.body.scrollHeight;
                window.parent.postMessage({type: 'resizeModal', height: height}, '*');
            });
        }
    </script>
</body>
</html>
