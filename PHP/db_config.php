<?php
/**
 * CONFIGURACIÓN DE CONEXIÓN HÍBRIDA (Local vs. Remoto)
 * Este script detecta automáticamente si el sitio corre en tu PC (XAMPP)
 * o en el servidor de InfinityFree.
 */

// Detectamos la dirección IP del servidor
$is_local = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);

if ($is_local) {
    /* ---------------------------------------------------------
       BLOQUE 1: CONFIGURACIÓN XAMPP (LOCAL)
       Este código se activa cuando abres el proyecto en tu PC.
       --------------------------------------------------------- */
    $servername = "127.0.0.1";
    $username   = "root";
    $password   = "";             // Por defecto XAMPP no tiene contraseña
    $dbname     = "restaurantes"; 
    $port       = 3307;           // Tu puerto específico de MySQL
    
    $conn = new mysqli($servername, $username, $password, $dbname, $port);

} else {
    /* ---------------------------------------------------------
       BLOQUE 2: CONFIGURACIÓN INFINITYFREE (PRODUCCIÓN)
       Este código se activa automáticamente al subirlo al hosting.
       Datos obtenidos de tu panel de control (if0_38466650).
       --------------------------------------------------------- */
    $servername = "sql306.infinityfree.com";        // Tu Hostname de MySQL
    $username   = "if0_41350210";                  // Tu Usuario de MySQL
    $dbname     = "if0_41350210_restaurantes";     // Tu Base de Datos específica
    
    // IMPORTANTE: Aquí debes colocar la contraseña que aparece en tu 
    // panel de InfinityFree (Account Password).
    $password   = "u3h9yqCxvnvJt"; 

    $conn = new mysqli($servername, $username, $password, $dbname);
}

// Verificación de la conexión
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error);
}

// Establecer el juego de caracteres a UTF-8 para evitar problemas con tildes y la 'ñ'
$conn->set_charset("utf8");

/* NOTAS DE USO:
   1. Si ves el mensaje "Error de conexión", revisa que el $password en el Bloque 2 sea correcto.
   2. El sistema elige el Bloque 1 o 2 sin que tú tengas que mover nada cada vez que subes archivos.
*/
?>