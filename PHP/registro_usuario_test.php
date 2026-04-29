<?php
// Versión ultra simplificada para testing
error_log("=== TEST BACKEND INICIADO ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("REQUEST_METHOD: " . $_SERVER["REQUEST_METHOD"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("=== POST DETECTADO ===");
    error_log("POST data: " . print_r($_POST, true));
    
    // Respuesta simple para probar que funciona
    echo "<script>alert('Backend funciona correctamente. Datos recibidos: " . count($_POST) . " campos.'); window.location.href='../login.php';</script>";
} else {
    error_log("=== NO ES POST ===");
    echo "<script>alert('Error: No es una solicitud POST'); window.location.href='../signup.php';</script>";
}

error_log("=== TEST BACKEND FINALIZADO ===");
?>
