<?php
/**
 * =========================================================
 * VISUALIZADOR DE LOGS DE ERROR
 * =========================================================
 * Muestra los últimos errores del log de Apache/PHP
 */

session_start();

// Ruta típica del log de errores de XAMPP
$log_paths = [
    'C:/xampp/apache/logs/error.log',
    'C:/xampp/php/logs/php_error_log.txt',
    'C:/xampp/php/logs/error.log',
    ini_get('error_log') // Configuración de php.ini
];

$log_path = null;
foreach ($log_paths as $path) {
    if ($path && file_exists($path) && is_readable($path)) {
        $log_path = $path;
        break;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Logs de Error - Salud Juárez</title>
    <style>
        body { 
            font-family: 'Consolas', 'Monaco', monospace; 
            background: #0d1117; 
            color: #c9d1d9; 
            padding: 20px; 
            margin: 0;
        }
        h1 { color: #f85149; border-bottom: 2px solid #f85149; padding-bottom: 10px; }
        h2 { color: #58a6ff; margin-top: 30px; }
        .log-container { 
            background: #161b22; 
            padding: 20px; 
            border-radius: 8px; 
            overflow-x: auto;
            border: 1px solid #30363d;
        }
        .log-entry { 
            padding: 8px 0; 
            border-bottom: 1px solid #21262d;
            line-height: 1.6;
        }
        .log-entry:last-child { border-bottom: none; }
        .timestamp { color: #8b949e; }
        .debug { color: #58a6ff; }
        .error { color: #f85149; }
        .warning { color: #d29922; }
        .info { color: #3fb950; }
        .controls {
            background: #21262d;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        button {
            background: #238636;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 10px;
        }
        button:hover { background: #2ea043; }
        .alert {
            background: #da3633;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success-box {
            background: #238636;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        input[type="number"] {
            background: #0d1117;
            border: 1px solid #30363d;
            color: #c9d1d9;
            padding: 8px;
            border-radius: 6px;
            width: 80px;
        }
    </style>
</head>
<body>
    <h1>📋 VISUALIZADOR DE LOGS</h1>
    
    <?php if (!$log_path): ?>
        <div class="alert">
            <h2>⚠️ NO SE ENCONTRÓ EL ARCHIVO DE LOGS</h2>
            <p>Rutas buscadas:</p>
            <ul>
                <?php foreach ($log_paths as $path): ?>
                    <li><?php echo htmlspecialchars($path); ?> - <?php echo file_exists($path) ? '✅ Existe' : '❌ No existe'; ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Configura <code>error_log</code> en tu php.ini</p>
        </div>
    <?php else: ?>
        <div class="success-box">
            ✅ Archivo de logs encontrado: <code><?php echo htmlspecialchars($log_path); ?></code>
        </div>
        
        <div class="controls">
            <form method="GET">
                <label>Mostrar últimas 
                    <input type="number" name="lineas" value="<?php echo $_GET['lineas'] ?? 100; ?>" min="10" max="1000"> 
                    líneas
                </label>
                <button type="submit">🔄 Actualizar</button>
                <button type="button" onclick="window.location.reload()">🔄 Recargar página</button>
            </form>
        </div>
        
        <h2>📝 ÚLTIMOS LOGS (filtrados por palabras clave)</h2>
        <div class="log-container">
            <?php
            $lineas = intval($_GET['lineas'] ?? 100);
            
            // Leer las últimas N líneas
            $output = shell_exec("tail -n $lineas " . escapeshellarg($log_path) . " 2>&1");
            
            if ($output === null) {
                // Intentar con PHP nativo si shell_exec falla
                $lines = file($log_path);
                $lines = array_slice($lines, -$lineas);
                $output = implode('', $lines);
            }
            
            if (!$output) {
                echo "<p>No se pudo leer el archivo de logs.</p>";
            } else {
                $log_lines = explode("\n", $output);
                
                // Palabras clave de nuestro diagnóstico
                $keywords = ['DEBUG', 'DIAGNÓSTICO', 'cargar_proveedores', 'Error', 'Fatal', 'Warning'];
                
                $found_relevant = false;
                
                foreach (array_reverse($log_lines) as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    // Resaltar líneas relevantes
                    $class = '';
                    if (stripos($line, '[DEBUG]') !== false) {
                        $class = 'debug';
                        $found_relevant = true;
                    } elseif (stripos($line, 'Error') !== false || stripos($line, 'Fatal') !== false) {
                        $class = 'error';
                        $found_relevant = true;
                    } elseif (stripos($line, 'Warning') !== false) {
                        $class = 'warning';
                        $found_relevant = true;
                    } elseif (stripos($line, 'DIAGNÓSTICO') !== false) {
                        $class = 'info';
                        $found_relevant = true;
                    }
                    
                    // Si es relevante, mostrarla
                    if ($found_relevant || stripos($line, 'proveedor') !== false) {
                        echo "<div class='log-entry $class'>" . htmlspecialchars($line) . "</div>";
                        $found_relevant = false; // Reset para no mostrar todo
                    }
                }
                
                if (!$found_relevant && count($log_lines) > 0) {
                    echo "<p style='color: #8b949e;'>Mostrando últimas líneas (sin filtros):</p>";
                    foreach (array_slice(array_reverse($log_lines), 0, 50) as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            echo "<div class='log-entry'>" . htmlspecialchars($line) . "</div>";
                        }
                    }
                }
            }
            ?>
        </div>
        
        <h2>🔍 INSTRUCCIONES</h2>
        <div class="log-container">
            <p>1. Abre el formulario de pedidos B2B: <a href="../DIRECCIONES/solicitar_pedido_proveedor.php" style="color: #58a6ff;">solicitar_pedido_proveedor.php</a></p>
            <p>2. Abre la consola del navegador (F12 → Console)</p>
            <p>3. Recarga esta página para ver los logs del servidor</p>
            <p>4. Los logs con <span class="debug">[DEBUG]</span> son de nuestro diagnóstico</p>
        </div>
        
    <?php endif; ?>
    
    <hr style="border-color: #30363d; margin: 30px 0;">
    <p style="text-align: center; color: #8b949e;">
        Salud Juárez - Sistema de Diagnóstico | <?php echo date('Y-m-d H:i:s'); ?>
    </p>
</body>
</html>
