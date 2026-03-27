<?php
/**
 * Verificación de Footer - Salud Juárez
 * Diagnóstico de por qué el footer no se muestra en dashboard
 * Versión: 1.0.0
 * Fecha: 26 de Marzo de 2026
 */

session_start();

echo "<!DOCTYPE html>";
echo "<html lang='es'>";
echo "<head>";
echo "    <meta charset='UTF-8'>";
echo "    <title>Verificación de Footer - Salud Juárez</title>";
echo "    <style>";
echo "        body { font-family: Arial, sans-serif; margin: 20px; }";
echo "        .container { max-width: 1200px; margin: 0 auto; }";
echo "        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }";
echo "        h2 { color: #27ae60; margin-top: 30px; }";
echo "        .test-section { background: #ecf0f1; padding: 20px; margin: 20px 0; border-radius: 5px; }";
echo "        .success { color: #27ae60; font-weight: bold; }";
echo "        .error { color: #e74c3c; font-weight: bold; }";
echo "        .warning { color: #f39c12; font-weight: bold; }";
echo "        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }";
echo "        .footer-demo { border: 2px solid #3498db; margin: 20px 0; }";
echo "    </style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

echo "<h1>🔍 Verificación de Footer - Salud Juárez</h1>";

// 1. Verificar archivo footer.php
echo "<div class='test-section'>";
echo "<h2>📄 Verificación de Archivo Footer</h2>";
$footer_path = '../PHP/footer.php';
if (file_exists($footer_path)) {
    echo "<p class='success'>✅ Archivo footer.php existe en: {$footer_path}</p>";
    echo "<p>Tamaño: " . filesize($footer_path) . " bytes</p>";
    echo "<p>Última modificación: " . date('Y-m-d H:i:s', filemtime($footer_path)) . "</p>";
} else {
    echo "<p class='error'>❌ Archivo footer.php NO existe en: {$footer_path}</p>";
}
echo "</div>";

// 2. Verificar CSS del footer
echo "<div class='test-section'>";
echo "<h2>🎨 Verificación de CSS del Footer</h2>";
$css_path = '../CSS/stylesheet.css';
if (file_exists($css_path)) {
    $css_content = file_get_contents($css_path);
    if (strpos($css_content, '.footer') !== false) {
        echo "<p class='success'>✅ CSS del footer encontrado en stylesheet.css</p>";
    } else {
        echo "<p class='warning'>⚠️ CSS del footer no encontrado en stylesheet.css</p>";
    }
} else {
    echo "<p class='error'>❌ Archivo stylesheet.css NO existe</p>";
}
echo "</div>";

// 3. Probar inclusión directa del footer
echo "<div class='test-section'>";
echo "<h2>🧪 Prueba de Inclusión Directa</h2>";
echo "<h3>Footer incluido directamente:</h3>";

try {
    // Simular variables del dashboard
    $_SERVER['PHP_SELF'] = '/DIRECCIONES/dashboard.php';
    
    ob_start();
    include '../PHP/footer.php';
    $footer_output = ob_get_clean();
    
    if (!empty($footer_output)) {
        echo "<div class='footer-demo'>";
        echo $footer_output;
        echo "</div>";
        echo "<p class='success'>✅ Footer se incluyó correctamente</p>";
    } else {
        echo "<p class='error'>❌ Footer no produjo salida</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error al incluir footer: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 4. Verificar rutas relativas
echo "<div class='test-section'>";
echo "<h2>📁 Verificación de Rutas Relativas</h2>";
$current_path = $_SERVER['PHP_SELF'] ?? '/DIRECCIONES/dashboard.php';
$is_in_direcciones = strpos($current_path, '/DIRECCIONES/') !== false;
$path = $is_in_direcciones ? "../" : "";

echo "<p><strong>Current Path:</strong> {$current_path}</p>";
echo "<p><strong>Is in DIRECCIONES:</strong> " . ($is_in_direcciones ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Path Prefix:</strong> '{$path}'</p>";

// Verificar archivos con el path calculado
$archivos_a_verificar = [
    'index.php' => $path . 'index.php',
    'signup.php' => $path . 'signup.php',
    'login.php' => $path . 'login.php'
];

foreach ($archivos_a_verificar as $nombre => $ruta) {
    $ruta_completa = '../' . ltrim($ruta, './');
    if (file_exists($ruta_completa)) {
        echo "<p class='success'>✅ {$nombre}: {$ruta_completa} existe</p>";
    } else {
        echo "<p class='error'>❌ {$nombre}: {$ruta_completa} NO existe</p>";
    }
}
echo "</div>";

// 5. Verificar si hay errores de PHP
echo "<div class='test-section'>";
echo "<h2>🐛 Verificación de Errores PHP</h2>";
echo "<h3>Probando inclusión con captura de errores:</h3>";

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $current_path_backup = $_SERVER['PHP_SELF'] ?? '';
    $_SERVER['PHP_SELF'] = '/DIRECCIONES/dashboard.php';
    
    ob_start();
    $result = include '../PHP/footer.php';
    $output = ob_get_clean();
    
    $_SERVER['PHP_SELF'] = $current_path_backup;
    
    if ($result === false) {
        echo "<p class='error'>❌ Include devolvió false</p>";
    } else {
        echo "<p class='success'>✅ Include devolvió: " . var_export($result, true) . "</p>";
    }
    
    if (!empty($output)) {
        echo "<p>Longitud del output: " . strlen($output) . " caracteres</p>";
        echo "<details>";
        echo "<summary>Ver output completo</summary>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        echo "</details>";
    } else {
        echo "<p class='warning'>⚠️ No se generó output</p>";
    }
    
} catch (ParseError $e) {
    echo "<p class='error'>❌ Error de PHP: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p class='error'>❌ Error fatal: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Excepción: " . $e->getMessage() . "</p>";
}

echo "</div>";

// 6. Verificar dashboard.php real
echo "<div class='test-section'>";
echo "<h2>📋 Análisis del Dashboard Real</h2>";
$dashboard_path = '../DIRECCIONES/dashboard.php';
if (file_exists($dashboard_path)) {
    $dashboard_content = file_get_contents($dashboard_path);
    
    echo "<h3>Contenido del Dashboard:</h3>";
    echo "<pre>" . htmlspecialchars(substr($dashboard_content, 0, 2000)) . "...</pre>";
    
    // Buscar el include del footer
    if (strpos($dashboard_content, 'footer.php') !== false) {
        echo "<p class='success'>✅ footer.php encontrado en dashboard.php</p>";
        
        // Extraer la línea del include
        $lines = explode("\n", $dashboard_content);
        foreach ($lines as $line_num => $line) {
            if (strpos($line, 'footer.php') !== false) {
                echo "<p><strong>Línea " . ($line_num + 1) . ":</strong> " . htmlspecialchars($line) . "</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ footer.php NO encontrado en dashboard.php</p>";
    }
    
    // Verificar estructura del dashboard
    if (strpos($dashboard_content, '</main>') !== false) {
        echo "<p class='success'>✅ Etiqueta </main> encontrada</p>";
        
        // Buscar dónde está el footer
        $main_end_pos = strrpos($dashboard_content, '</main>');
        $footer_include_pos = strpos($dashboard_content, 'footer.php');
        
        if ($footer_include_pos > $main_end_pos) {
            echo "<p class='success'>✅ Footer incluido DESPUÉS de </main></p>";
        } else {
            echo "<p class='warning'>⚠️ Footer incluido ANTES de </main> - podría causar problemas</p>";
        }
    } else {
        echo "<p class='error'>❌ Etiqueta </main> NO encontrada</p>";
    }
    
} else {
    echo "<p class='error'>❌ dashboard.php no existe</p>";
}
echo "</div>";

// 7. Recomendaciones
echo "<div class='test-section'>";
echo "<h2>💡 Recomendaciones</h2>";
echo "<h3>Si el footer no se muestra:</h3>";
echo "<ol>";
echo "<li><strong>Verificar CSS:</strong> Asegúrate que los estilos del footer estén cargados</li>";
echo "<li><strong>Verificar rutas:</strong> Confirma que las rutas relativas sean correctas</li>";
echo "<li><strong>Verificar estructura HTML:</strong> El footer debe estar después de </main></li>";
echo "<li><strong>Verificar errores PHP:</strong> Revisa el log de errores</li>";
echo "<li><strong>Probar en navegador:</strong> Usa las herramientas de desarrollador para inspeccionar</li>";
echo "</ol>";

echo "<h3>Código sugerido para dashboard.php:</h3>";
echo "<pre>";
echo htmlspecialchars('<?php
session_start();
include \'../PHP/navbar.php\';

// ... resto del código del dashboard ...

?>
</main>

<!-- Footer Global - DESPUÉS de </main> -->
<?php include \'../PHP/footer.php\'; ?>

<script src="../JS/modal_desarrollador.js"></script>
<script src="../JS/main_interactions.js"></script>
</body>
</html>');
echo "</pre>";

echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
