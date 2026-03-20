<?php
session_start();
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identificador = $_POST['identificador'] ?? ''; 
    $password = $_POST['password_usu'] ?? '';

    // Buscamos al usuario uniendo: usuarios + perfiles + roles
    $sql = "SELECT u.id_usu, u.username_usu, u.password_usu, u.id_rol, r.nombre_rol, p.nombre_per, p.apellidos_per 
            FROM usuarios u
            INNER JOIN perfiles p ON u.id_usu = p.id_usu 
            INNER JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.correo_usu = '$identificador' OR u.username_usu = '$identificador'";
    
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password_usu'])) {
            // Guardamos las variables de sesión EXACTAMENTE como el dashboard las pide
            $_SESSION['id_usu']          = $user['id_usu'];
            $_SESSION['id_rol']          = $user['id_rol']; // Útil para validaciones PHP (Ej: if id_rol == 1)
            $_SESSION['rol']             = $user['nombre_rol']; // El dashboard imprime esto
            $_SESSION['nick']            = $user['username_usu'];
            $_SESSION['nombre_completo'] = $user['nombre_per'] . " " . $user['apellidos_per'];

            // Redirección al Dashboard
            header("Location: ../DIRECCIONES/dashboard.php");
            exit();
        } else {
            echo "<script>alert('❌ Contraseña incorrecta'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('❌ Usuario no encontrado'); window.history.back();</script>";
    }
}
?>