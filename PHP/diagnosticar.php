<?php
// Archivo de diagnóstico para identificar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico del Sistema Restaurantes</h1>";

// 1. Verificar configuración de PHP
echo "<h2>⚙️ Configuración PHP</h2>";
echo "Versión PHP: " . phpversion() . "<br>";
echo "Límite de memoria: " . ini_get('memory_limit') . "<br>";
echo "Max execution time: " . ini_get('max_execution_time') . "<br>";
echo "Display errors: " . ini_get('display_errors') . "<br>";

// 2. Verificar extensiónes necesarias
echo "<h2>🔌 Extensiones PHP</h2>";
$extensiones = ['mysqli', 'pdo', 'pdo_mysql', 'session', 'json'];
foreach ($extensiones as $ext) {
    $status = extension_loaded($ext) ? "✅ Activa" : "❌ Inactiva";
    echo "$ext: $status<br>";
}

// 3. Verificar conexión a base de datos
echo "<h2>🗄️ Conexión a Base de Datos</h2>";
try {
    // Intentar incluir el archivo de configuración
    if (file_exists('db_config.php')) {
        echo "✅ Archivo db_config.php encontrado<br>";
        include 'db_config.php';
        
        if (isset($conn) && $conn->ping()) {
            echo "✅ Conexión a MySQL exitosa<br>";
            echo "Servidor: " . $conn->server_info . "<br>";
            echo "Versión cliente: " . $conn->client_info . "<br>";
            
            // Verificar si la base de datos existe
            $result = $conn->query("SHOW DATABASES LIKE 'restaurantes'");
            if ($result->num_rows > 0) {
                echo "✅ Base de datos 'restaurantes' existe<br>";
                
                // Verificar tablas principales
                $tablas = ['usuarios', 'perfiles', 'roles', 'restaurante'];
                foreach ($tablas as $tabla) {
                    $result = $conn->query("SHOW TABLES LIKE '$tabla'");
                    $status = $result->num_rows > 0 ? "✅" : "❌";
                    echo "$status Tabla '$tabla'<br>";
                }
            } else {
                echo "❌ Base de datos 'restaurantes' NO existe<br>";
            }
        } else {
            echo "❌ Error en conexión a MySQL<br>";
        }
    } else {
        echo "❌ Archivo db_config.php NO encontrado<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 4. Verificar archivos críticos
echo "<h2>📁 Archivos Críticos</h2>";
$archivos = [
    '../index.html' => 'Página principal',
    '../login.php' => 'Login',
    '../signup.php' => 'Registro',
    'navbar.php' => 'Navegación',
    'db_config.php' => 'Config DB',
    'validar_login.php' => 'Validación login',
    '../DIRECCIONES/dashboard.php' => 'Dashboard'
];

foreach ($archivos as $archivo => $descripcion) {
    $status = file_exists($archivo) ? "✅" : "❌";
    echo "$status $archivo - $descripcion<br>";
}

// 5. Verificar permisos de escritura
echo "<h2>📂 Permisos de Escritura</h2>";
$carpetas = ['../UPLOADS', '../IMG', '../SaveStates'];
foreach ($carpetas as $carpeta) {
    if (file_exists($carpeta)) {
        $writable = is_writable($carpeta) ? "✅" : "❌";
        echo "$writable $carpeta (writable)<br>";
    } else {
        echo "⚠️ $carpeta (no existe)<br>";
    }
}

// 6. Verificar configuración de .htaccess
echo "<h2>🔒 Configuración .htaccess</h2>";
if (file_exists('../.htaccess')) {
    echo "✅ Archivo .htaccess existe<br>";
    $contenido = file_get_contents('../.htaccess');
    if (strpos($contenido, 'Options -Indexes') !== false) {
        echo "⚠️ Tiene 'Options -Indexes' (puede causar problemas)<br>";
    }
    if (strpos($contenido, 'php_flag') !== false) {
        echo "✅ Tiene configuración PHP<br>";
    }
} else {
    echo "❌ Archivo .htaccess NO existe<br>";
}

// 7. Verificar sesión
echo "<h2>🔐 Sesión PHP</h2>";
if (session_status() === PHP_SESSION_NONE) {
    echo "⚠️ Sesión no iniciada<br>";
} else {
    echo "✅ Sesión activa<br>";
}
echo "Session save path: " . session_save_path() . "<br>";

echo "<h2>🎯 Prueba de Inclusión</h2>";
try {
    echo "Probando incluir navbar.php...<br>";
    include 'navbar.php';
    echo "✅ navbar.php incluido sin errores<br>";
} catch (ParseError $e) {
    echo "❌ Error de sintaxis en navbar.php: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "❌ Error en navbar.php: " . $e->getMessage() . "<br>";
} catch (Exception $e) {
    echo "❌ Excepción en navbar.php: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>🚀 Recomendaciones</h2>";
echo "<ol>";
echo "<li>Si hay errores de sintaxis, revisa los archivos PHP marcados con ❌</li>";
echo "<li>Si la conexión a DB falla, verifica que XAMPP esté corriendo</li>";
echo "<li>Si .htaccess causa problemas, renómbralo temporalmente</li>";
echo "<li>Verifica que los archivos tengan las codificaciones correctas (UTF-8)</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Después de solucionar los problemas, ejecuta:</strong></p>";
echo "<code>ren .htaccess.complejo .htaccess</code>";
echo "<p>para restaurar la configuración completa.</p>";
?>
