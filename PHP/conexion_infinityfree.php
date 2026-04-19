<?php
/**
 * Conexión a Base de Datos - InfinityFree Compatible
 * Configuración para conexión con MySQL/MariaDB en InfinityFree
 * Versión: 4.0.0 - InfinityFree Compatible
 * Fecha: 3 de Abril de 2026
 */

// Configuración de la base de datos para InfinityFree
define('DB_HOST', 'sql311.infinityfree.com');  // Servidor InfinityFree
define('DB_PORT', '3306');                    // Puerto estándar
define('DB_NAME', 'if0_41350210_restaurantes'); // Nombre de BD
define('DB_USER', 'if0_41350210');            // Usuario InfinityFree
define('DB_PASS', 'TuPasswordAqui');          // Cambiar por tu contraseña

// Configuración de charset
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', 'utf8_spanish_ci');

// Crear conexión
try {
    $conn = new mysqli(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        DB_PORT
    );
    
    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    // Establecer charset
    if (!$conn->set_charset(DB_CHARSET)) {
        throw new Exception("Error cargando charset " . DB_CHARSET);
    }
    
    // Modo de error para desarrollo (ajustar en producción)
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    } else {
        // Modo silencioso para producción
        mysqli_report(MYSQLI_REPORT_OFF);
    }
    
    // Configuración adicional para InfinityFree
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    $conn->options(MYSQLI_OPT_READ_TIMEOUT, 30);
    $conn->options(MYSQLI_OPT_WRITE_TIMEOUT, 30);
    
} catch (Exception $e) {
    // En producción, registrar error en lugar de mostrarlo
    error_log("Error de conexión a BD: " . $e->getMessage());
    
    // En desarrollo, mostrar error
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die("Error de conexión: " . $e->getMessage());
    } else {
        // Mensaje amigable para producción
        die("El sistema está en mantenimiento. Por favor intente más tarde.");
    }
}

// Función para ejecutar consultas seguras
function ejecutarConsulta($conn, $query, $params = [], $types = '') {
    try {
        $stmt = $conn->prepare($query);
        
        if (!empty($params)) {
            if ($types) {
                $stmt->bind_param($types, ...$params);
            } else {
                $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            }
        }
        
        $stmt->execute();
        return $stmt;
    } catch (Exception $e) {
        error_log("Error en consulta: " . $e->getMessage());
        return false;
    }
}

// Función para sanitizar entradas
function sanitizar($dato) {
    return htmlspecialchars(trim($dato), ENT_QUOTES, 'UTF-8');
}

// Función para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para generar hash seguro
function generarHash($password) {
    return password_hash($password, PASSWORD_ARGON2ID, ['cost' => 12]);
}

// Función para verificar password
function verificarPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Función para verificar si la conexión está activa
function verificarConexion($conn) {
    return $conn && $conn->ping();
}

// Función para reintentar conexión
function reintentarConexion($max_reintentos = 3) {
    global $conn;
    
    for ($i = 0; $i < $max_reintentos; $i++) {
        try {
            if (verificarConexion($conn)) {
                return true;
            }
            
            // Cerrar conexión actual
            if ($conn) {
                $conn->close();
            }
            
            // Reintentar conexión
            $conn = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                DB_PORT
            );
            
            if ($conn->connect_error) {
                throw new Exception("Error de conexión: " . $conn->connect_error);
            }
            
            $conn->set_charset(DB_CHARSET);
            return true;
            
        } catch (Exception $e) {
            error_log("Intento " . ($i + 1) . " fallido: " . $e->getMessage());
            if ($i < $max_reintentos - 1) {
                sleep(1); // Esperar 1 segundo antes de reintentar
            }
        }
    }
    
    return false;
}

// Función para manejar errores de conexión
function manejarErrorConexion($error) {
    error_log("Error de conexión persistente: " . $error);
    
    // Enviar email de notificación (opcional)
    if (defined('ADMIN_EMAIL') && ADMIN_EMAIL) {
        $asunto = "Error de conexión - Salud Juárez";
        $mensaje = "El sistema no puede conectar a la base de datos.\n\nError: " . $error . "\n\nFecha: " . date('Y-m-d H:i:s');
        mail(ADMIN_EMAIL, $asunto, $mensaje);
    }
    
    // Mostrar página de mantenimiento
    include 'mantenimiento.php';
    exit;
}

// Verificar conexión al inicio
if (!verificarConexion($conn)) {
    if (!reintentarConexion()) {
        manejarErrorConexion("No se pudo establecer conexión después de múltiples intentos");
    }
}

// Configuración para InfinityFree
ini_set('max_execution_time', 30);  // Límite de tiempo de ejecución
ini_set('memory_limit', '256M');    // Límite de memoria
ini_set('upload_max_filesize', '10M'); // Límite de subida

// Configuración de errores para InfinityFree
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Crear directorio de logs si no existe
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}
?>
