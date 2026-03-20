<?php
/**
 * Archivo de configuración centralizada
 * Sistema de Gestión para Restaurantes - Ciudad Juárez
 */

// Evitar acceso directo al archivo
if (!defined('ACCESO_PERMITIDO')) {
    exit('Acceso directo no permitido');
}

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'restaurantes');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración del sistema
define('SITIO_NOMBRE', 'Restaurantes Saludables - Cd. Juárez');
define('SITIO_URL', 'http://localhost/restaurantes/');
define('SITIO_VERSION', '1.0.0');

// Configuración de rutas
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/restaurantes/');
define('UPLOADS_PATH', ROOT_PATH . 'UPLOADS/');
define('IMG_PATH', ROOT_PATH . 'IMG/');

// Configuración de sesión
define('SESSION_NAME', 'restaurantes_session');
define('SESSION_LIFETIME', 86400); // 24 horas en segundos

// Configuración de subida de archivos
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Configuración de seguridad
define('HASH_ALGORITHM', PASSWORD_DEFAULT);
define('SALT_LENGTH', 32);

// Configuración de errores
define('DEBUG_MODE', true); // Cambiar a false en producción
define('LOG_ERRORS', true);
define('LOG_FILE', ROOT_PATH . 'logs/error.log');

// Configuración de roles
define('ROL_ADMIN', 1);
define('ROL_DUEÑO', 2);
define('ROL_COMENSAL', 3);

/**
 * Conexión a la base de datos
 */
function conectarDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Error de conexión: " . $e->getMessage());
        } else {
            die("Error en el sistema. Intente más tarde.");
        }
    }
}

/**
 * Función para limpiar y validar datos de entrada
 */
function limpiarDato($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    return $dato;
}

/**
 * Función para generar token CSRF
 */
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Función para verificar token CSRF
 */
function verificarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('Error: Token CSRF inválido');
    }
}

// Establecer zona horaria
date_default_timezone_set('America/Mexico_City');
?>
