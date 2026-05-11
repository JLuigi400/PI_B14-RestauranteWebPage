<?php
// Visualizador de logs de Apache/PHP para XAMPP
?>
<!DOCTYPE html>
<html>
<head>
    <title>Apache/PHP Error Logs</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        h1 { color: #fff; }
        .log-container { background: #252526; padding: 15px; border-radius: 8px; max-height: 80vh; overflow-y: auto; }
        .log-line { padding: 2px 0; border-bottom: 1px solid #333; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .debug { color: #4ec9b0; }
        .timestamp { color: #858585; }
    </style>
</head>
<body>
    <h1>🐛 Apache/PHP Error Logs</h1>
    
    <?php
    $log_paths = [
        'C:/xampp/apache/logs/error.log',
        'C:/xampp/php/logs/php_error_log.txt', 
        'C:/xampp/php/logs/error.log',
        __DIR__ . '/diagnostico_errores.log'
    ];
    
    foreach ($log_paths as $path):
        if (file_exists($path)):
            $lines = file($path);
            $last_lines = array_slice($lines, -50); // últimas 50 líneas
    ?>
        <h2>📁 <?php echo htmlspecialchars($path); ?></h2>
        <div class="log-container">
            <?php foreach ($last_lines as $line): 
                $class = '';
                if (stripos($line, 'error') !== false) $class = 'error';
                elseif (stripos($line, 'warning') !== false) $class = 'warning';
                elseif (stripos($line, 'debug') !== false) $class = 'debug';
            ?>
                <div class="log-line <?php echo $class; ?>"><?php echo htmlspecialchars($line); ?></div>
            <?php endforeach; ?>
        </div>
    <?php 
        endif;
    endforeach; 
    ?>
    
    <p><a href="test_diagnostico.php" style="color:#4ec9b0;">🧪 Ejecutar Test de Diagnóstico</a></p>
    <p><a href="javascript:location.reload()" style="color:#4ec9b0;">🔄 Recargar Logs</a></p>
</body>
</html>
