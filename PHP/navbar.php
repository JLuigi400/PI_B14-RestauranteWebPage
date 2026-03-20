<?php
// Aseguramos que la sesión esté disponible para verificar el estado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detectar desde dónde se está incluyendo
$current_path = $_SERVER['PHP_SELF'];
$is_in_direcciones = strpos($current_path, '/DIRECCIONES/') !== false;

// Calcular prefijo de ruta
$path = $is_in_direcciones ? "../" : "";

// Variables de sesión
$id_rol = $_SESSION['id_rol'] ?? 0;
$nombre_usuario = $_SESSION['nick'] ?? 'Invitado';
$is_logged = isset($_SESSION['id_usu']);
?>

<nav class="main-navbar">
    <div class="nav-container">
        <a href="<?php echo $path; ?>index.html" class="nav-logo">🥗 Salud Juárez</a>
        
        <ul class="nav-links">
            <?php if ($is_logged): ?>
                <li><a href="<?php echo $path; ?>DIRECCIONES/dashboard.php">Inicio</a></li>

                <?php if ($id_rol == 1): // Administrador ?>
                    <li><a href="<?php echo $path; ?>DIRECCIONES/admin_usuarios.php">Usuarios</a></li>
                    <li><a href="<?php echo $path; ?>DIRECCIONES/validar_negocios.php">Validar</a></li>
                <?php elseif ($id_rol == 2): // Dueño de Restaurante ?>
                    <li><a href="<?php echo $path; ?>DIRECCIONES/gestion_platillos.php">Mi Menú</a></li>
                    <li><a href="<?php echo $path; ?>DIRECCIONES/inventario.php">Inventario</a></li>
                <?php elseif ($id_rol == 3): // Comensal ?>
                    <li><a href="<?php echo $path; ?>DIRECCIONES/buscar_restaurantes.php">Explorar</a></li>
                    <li><a href="<?php echo $path; ?>DIRECCIONES/mis_favoritos.php">Favoritos</a></li>
                <?php endif; ?>
                
                <li class="user-menu">
                    <span class="user-nick">👤 <?php echo $nombre_usuario; ?></span>
                    <a href="<?php echo $path; ?>PHP/logout.php" class="btn-salir">Cerrar Sesión</a>
                </li>

            <?php else: ?>
                <li><a href="<?php echo $path; ?>login.php">Iniciar Sesión</a></li>
                <li><a href="<?php echo $path; ?>signup.php" class="btn-nav-signup">Crear Cuenta</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Debug Info (temporal) -->
<!-- Current Path: <?php echo $current_path; ?> -->
<!-- Is in DIRECCIONES: <?php echo $is_in_direcciones ? 'Yes' : 'No'; ?> -->
<!-- Path Prefix: <?php echo $path; ?> -->