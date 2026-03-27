<?php
/**
 * Diagnóstico de Includes - Salud Juárez
 * Verifica qué archivos tienen navbar y footer
 * Versión: 1.0.0
 * Fecha: 26 de Marzo de 2026
 */

// Configuración
$directorio_direcciones = '../DIRECCIONES';
$archivos_a_verificar = [
    'dashboard.php',
    'buscar_restaurantes.php',
    'mis_favoritos.php',
    'gestion_platillos.php',
    'inventario.php',
    'mis_restaurantes.php',
    'mapa_proveedores.php',
    'admin_usuarios.php',
    'perfil.php',
    'notificaciones.php'
];

// Función para verificar includes en un archivo
function verificarIncludes($ruta_archivo) {
    $contenido = file_get_contents($ruta_archivo);
    $tiene_navbar = strpos($contenido, 'navbar.php') !== false;
    $tiene_footer = strpos($contenido, 'footer.php') !== false;
    $tiene_modal = strpos($contenido, 'modal_desarrollador.php') !== false;
    
    return [
        'navbar' => $tiene_navbar,
        'footer' => $tiene_footer,
        'modal' => $tiene_modal
    ];
}

// Función para verificar botón desarrollador
function verificarBotonDesarrollador($ruta_archivo) {
    $contenido = file_get_contents($ruta_archivo);
    return strpos($contenido, 'btnOpenDevModal') !== false || 
           strpos($contenido, 'Acerca del Desarrollador') !== false;
}

// Iniciar diagnóstico
echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "    <meta charset='UTF-8'>";
echo "    <title>Diagnóstico de Includes - Salud Juárez</title>";
echo "    <style>";
echo "        body { font-family: Arial, sans-serif; margin: 20px; }";
echo "        .container { max-width: 1200px; margin: 0 auto; }";
echo "        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }";
echo "        h2 { color: #27ae60; margin-top: 30px; }";
echo "        table { width: 100%; border-collapse: collapse; margin: 20px 0; }";
echo "        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }";
echo "        th { background-color: #3498db; color: white; }";
echo "        .si { color: #27ae60; font-weight: bold; }";
echo "        .no { color: #e74c3c; font-weight: bold; }";
echo "        .warning { color: #f39c12; font-weight: bold; }";
echo "        .summary { background: #ecf0f1; padding: 20px; border-radius: 5px; margin: 20px 0; }";
echo "    </style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔍 Diagnóstico de Includes - Salud Juárez</h1>";

// Resumen general
echo "<div class='summary'>";
echo "<h2>📊 Resumen General</h2>";
echo "<p><strong>Directorio analizado:</strong> {$directorio_direcciones}</p>";
echo "<p><strong>Archivos verificados:</strong> " . count($archivos_a_verificar) . "</p>";
echo "<p><strong>Fecha del diagnóstico:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";

// Tabla principal de resultados
echo "<h2>📋 Resultados por Archivo</h2>";
echo "<table>";
echo "<tr>";
echo "<th>Archivo</th>";
echo "<th>Navbar</th>";
echo "<th>Footer</th>";
echo "<th>Modal</th>";
echo "<th>Botón Desarrollador</th>";
echo "<th>Estado</th>";
echo "</tr>";

$total_con_navbar = 0;
$total_con_footer = 0;
$total_con_modal = 0;
$total_con_boton = 0;
$archivos_completos = 0;

foreach ($archivos_a_verificar as $archivo) {
    $ruta_completa = $directorio_direcciones . '/' . $archivo;
    
    if (file_exists($ruta_completa)) {
        $includes = verificarIncludes($ruta_completa);
        $tiene_boton = verificarBotonDesarrollador($ruta_completa);
        
        // Contadores
        if ($includes['navbar']) $total_con_navbar++;
        if ($includes['footer']) $total_con_footer++;
        if ($includes['modal']) $total_con_modal++;
        if ($tiene_boton) $total_con_boton++;
        
        // Verificar si está completo
        $es_completo = $includes['navbar'] && $includes['footer'] && $tiene_boton;
        if ($es_completo) $archivos_completos++;
        
        echo "<tr>";
        echo "<td><strong>{$archivo}</strong></td>";
        echo "<td class='" . ($includes['navbar'] ? 'si' : 'no') . "'>" . ($includes['navbar'] ? '✅ Sí' : '❌ No') . "</td>";
        echo "<td class='" . ($includes['footer'] ? 'si' : 'no') . "'>" . ($includes['footer'] ? '✅ Sí' : '❌ No') . "</td>";
        echo "<td class='" . ($includes['modal'] ? 'si' : 'no') . "'>" . ($includes['modal'] ? '✅ Sí' : '❌ No') . "</td>";
        echo "<td class='" . ($tiene_boton ? 'si' : 'no') . "'>" . ($tiene_boton ? '✅ Sí' : '❌ No') . "</td>";
        echo "<td class='" . ($es_completo ? 'si' : 'warning') . "'>" . ($es_completo ? '✅ Completo' : '⚠️ Incompleto') . "</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td><strong>{$archivo}</strong></td>";
        echo "<td colspan='5' class='no'>❌ Archivo no existe</td>";
        echo "</tr>";
    }
}

echo "</table>";

// Estadísticas finales
echo "<div class='summary'>";
echo "<h2>📈 Estadísticas Finales</h2>";
echo "<ul>";
echo "<li>Archivos con Navbar: <strong class='si'>{$total_con_navbar}/" . count($archivos_a_verificar) . "</strong></li>";
echo "<li>Archivos con Footer: <strong class='si'>{$total_con_footer}/" . count($archivos_a_verificar) . "</strong></li>";
echo "<li>Archivos con Modal: <strong class='si'>{$total_con_modal}/" . count($archivos_a_verificar) . "</strong></li>";
echo "<li>Archivos con Botón Desarrollador: <strong class='si'>{$total_con_boton}/" . count($archivos_a_verificar) . "</strong></li>";
echo "<li>Archivos Completos: <strong class='si'>{$archivos_completos}/" . count($archivos_a_verificar) . "</strong></li>";
echo "</ul>";

// Porcentaje de completitud
$porcentaje_completitud = round(($archivos_completos / count($archivos_a_verificar)) * 100, 2);
echo "<p><strong>Porcentaje de Completitud:</strong> <span class='" . ($porcentaje_completitud >= 80 ? 'si' : 'warning') . "'>{$porcentaje_completitud}%</span></p>";

if ($porcentaje_completitud >= 80) {
    echo "<p class='si'>🎉 ¡Buen trabajo! La mayoría de los archivos están completos.</p>";
} else {
    echo "<p class='warning'>⚠️ Se necesita mejorar la consistencia de los includes.</p>";
}

echo "</div>";

// Recomendaciones
echo "<h2>💡 Recomendaciones</h2>";
echo "<div class='summary'>";
echo "<h3>Archivos que necesitan atención:</h3>";

foreach ($archivos_a_verificar as $archivo) {
    $ruta_completa = $directorio_direcciones . '/' . $archivo;
    
    if (file_exists($ruta_completa)) {
        $includes = verificarIncludes($ruta_completa);
        $tiene_boton = verificarBotonDesarrollador($ruta_completa);
        $es_completo = $includes['navbar'] && $includes['footer'] && $tiene_boton;
        
        if (!$es_completo) {
            echo "<p><strong>{$archivo}:</strong> ";
            $faltantes = [];
            if (!$includes['navbar']) $faltantes[] = 'navbar';
            if (!$includes['footer']) $faltantes[] = 'footer';
            if (!$tiene_boton) $faltantes[] = 'botón desarrollador';
            echo "Falta: " . implode(', ', $faltantes);
            echo "</p>";
        }
    }
}

echo "<h3>Código a agregar:</h3>";
echo "<pre>";
echo "// Al inicio del archivo (después de session_start):";
echo "include '../PHP/navbar.php';";
echo "";
echo "// Al final del archivo (antes de </body>):";
echo "include '../PHP/footer.php';";
echo "";
echo "// Scripts adicionales:";
echo "&lt;script src='../JS/modal_desarrollador.js'&gt;&lt;/script&gt;";
echo "&lt;script src='../JS/main_interactions.js'&gt;&lt;/script&gt;";
echo "</pre>";

echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
