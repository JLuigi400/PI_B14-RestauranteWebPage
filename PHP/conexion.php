<?php
/**
 * Conexión a Base de Datos - Salud Juárez
 * Configuración para conexión con MySQL/MariaDB
 * Versión: 1.0.0
 * Fecha: 27 de Marzo de 2026
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '3307'); // Puerto configurado en el dump
define('DB_NAME', 'restaurantes');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración de charset
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', 'utf8_spanish_ci');

// Crear conexión
try {
    $conn = new mysqli(
        DB_HOST . ':' . DB_PORT,
        DB_USER,
        DB_PASS,
        DB_NAME
    );
    
    // Verificar conexión
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    // Establecer charset
    if (!$conn->set_charset(DB_CHARSET)) {
        throw new Exception("Error cargando charset " . DB_CHARSET);
    }
    
    // Modo de error
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
} catch (Exception $e) {
    // En producción, registrar error en lugar de mostrarlo
    error_log("Error de conexión a BD: " . $e->getMessage());
    
    // En desarrollo, mostrar error
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die("Error de conexión: " . $e->getMessage());
    } else {
        die("Error temporal del sistema. Por favor intente más tarde.");
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
?>
