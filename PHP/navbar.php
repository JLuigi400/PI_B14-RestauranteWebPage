<?php
// Aseguramos que la sesión esté disponible para verificar el estado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_rol = $_SESSION['id_rol'] ?? 0;
$nombre_usuario = $_SESSION['nick'] ?? 'Invitado';
$is_logged = isset($_SESSION['id_usu']);

// Prefijo para rutas (ajusta según tu estructura de carpetas)
$path = ""; 
?>

<nav class="main-navbar">
    <div class="nav-container">
        <a href="<?php echo $path; ?>index.html" class="nav-logo">🥗 Salud Juárez</a>
        
        <ul class="nav-links">
            <?php if ($is_logged): ?>
                <li><a href="<?php echo $path; ?>dashboard.php">Inicio</a></li>

                <?php if ($id_rol == 1): // Administrador ?>
                    <li><a href="<?php echo $path; ?>admin_usuarios.php">Usuarios</a></li>
                    <li><a href="<?php echo $path; ?>validar_negocios.php">Validar</a></li>
                <?php elseif ($id_rol == 2): // Dueño de Restaurante ?>
                    <li><a href="<?php echo $path; ?>gestion_platillos.php">Mi Menú</a></li>
                    <li><a href="<?php echo $path; ?>inventario/inventario_crud.php">Inventario</a></li>
                <?php elseif ($id_rol == 3): // Comensal ?>
                    <li><a href="<?php echo $path; ?>buscar_restaurantes.php">Explorar</a></li>
                    <li><a href="<?php echo $path; ?>mis_favoritos.php">Favoritos</a></li>
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