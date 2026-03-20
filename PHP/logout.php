<?php
// Iniciar la sesión para poder acceder a los datos actuales
session_start();

// Vaciar todas las variables de sesión (id_usu, id_rol, nick, etc.)
$_SESSION = array();

// Si se desea destruir la sesión completamente, borramos también la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir al index estático en la carpeta principal
header("Location: ../index.html");
exit();
?>