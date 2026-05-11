<?php
/**
 * Script para generar versión limpia del SQL sin triggers
 * Compatible con InfinityFree (sin CREATE TRIGGER)
 */

// Archivo de entrada y salida
$input_file = '../SQL/restaurantes-mayo_08.sql';
$output_file = '../SQL/restaurantes-mayo_08_infinityfree.sql';

// Verificar que existe el archivo
if (!file_exists($input_file)) {
    die("Error: No se encontró el archivo $input_file\n");
}

// Leer contenido completo
$content = file_get_contents($input_file);
if ($content === false) {
    die("Error: No se pudo leer el archivo\n");
}

// Patrones para eliminar triggers
// Eliminar bloques completos de triggers
$patterns = [
    // Bloque 1: Trigger inventario (notificar_stock_bajo)
    '/\s*--\s*Triggers `inventario`\s*--\s*DELIMITER \$\$\s*CREATE TRIGGER `notificar_stock_bajo`.*?END\s*\$\$\s*DELIMITER ;/s',
    
    // Bloque 2: Trigger pedido_detalle (actualizar_total_productos_pedido)
    '/\s*--\s*Triggers `pedido_detalle`\s*--\s*DELIMITER \$\$\s*CREATE TRIGGER `actualizar_total_productos_pedido`.*?END\s*\$\$\s*DELIMITER ;/s',
    
    // Bloque 3: Trigger restaurante (log_validacion_restaurante)
    '/\s*--\s*Triggers `restaurante`\s*--\s*DELIMITER \$\$\s*CREATE TRIGGER `log_validacion_restaurante`.*?END\s*\$\$\s*DELIMITER ;/s',
];

// Aplicar reemplazos
foreach ($patterns as $pattern) {
    $content = preg_replace($pattern, "\n\n-- [Triggers eliminados - migrados a PHP]\n", $content);
}

// Agregar cabecera informativa al inicio
$header = "-- =============================================================================\n";
$header .= "-- BASE DE DATOS RESTAURANTES - VERSIÓN INFINITYFREE (SIN TRIGGERS)\n";
$header .= "-- =============================================================================\n";
$header .= "-- Fecha de generación: " . date('Y-m-d H:i:s') . "\n";
$header .= "-- Origen: restaurantes-mayo_08.sql\n";
$header .= "-- Modificaciones: Se eliminaron todos los CREATE TRIGGER para compatibilidad\n";
$header .= "--                  con InfinityFree (Error #1142 - TRIGGER command denied)\n";
$header .= "-- Lógica migrada a: PHP (ver documentación en README_TRIGGERS_PHP.md)\n";
$header .= "-- =============================================================================\n\n";

$content = $header . $content;

// Guardar archivo limpio
if (file_put_contents($output_file, $content)) {
    echo "✅ Archivo limpio generado exitosamente: $output_file\n";
    
    // Verificar que no quedaron triggers
    if (strpos($content, 'CREATE TRIGGER') !== false) {
        echo "⚠️  ADVERTENCIA: Aún existen bloques CREATE TRIGGER en el archivo\n";
        preg_match_all('/CREATE TRIGGER `([^`]+)`/', $content, $matches);
        if (!empty($matches[1])) {
            echo "   Triggers encontrados: " . implode(', ', $matches[1]) . "\n";
        }
    } else {
        echo "✅ Verificación: No se encontraron CREATE TRIGGER en el archivo limpio\n";
    }
    
    // Estadísticas
    $original_size = filesize($input_file);
    $new_size = filesize($output_file);
    echo "\n📊 Estadísticas:\n";
    echo "   Tamaño original: " . number_format($original_size) . " bytes\n";
    echo "   Tamaño limpio: " . number_format($new_size) . " bytes\n";
    echo "   Reducción: " . number_format((($original_size - $new_size) / $original_size) * 100, 2) . "%\n";
    
} else {
    die("Error: No se pudo guardar el archivo limpio\n");
}
?>
