<?php

session_start();
include '../PHP/navbar.php';

// Si no hay sesión, mandarlo al login
if (!isset($_SESSION['id_usu'])) {
    header("Location: ../login.html");
    exit();
}

$id_rol = $_SESSION['id_rol'];
$rol_nombre = $_SESSION['rol_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Control | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
</head>
<body>
    <main class="container">
        <div class="sj-dashboard-header">
            <div>
                <h1 style="margin:0;">🥗 Salud Juárez</h1>
                <div style="margin-top:6px; color: var(--gris-suave);">
                    Bienvenido, <b><?php echo $_SESSION['nick']; ?></b> · Rol: <b style="color: var(--verde-salud);"><?php echo $rol_nombre; ?></b>
                </div>
            </div>
        </div>

        <section>
            <h2 style="margin: 8px 0 0 0;">Hola, <?php echo $_SESSION['nombre_completo']; ?></h2>
            
            <?php if ($id_rol == 1): // VISTA ADMINISTRADOR ?>
                <div class="sj-dashboard-cards">
                    <div class="sj-dash-card">
                        <h3>🛠️ Consola de Administración</h3>
                        <p>Tienes acceso total al sistema. Puedes gestionar usuarios, validar nuevos restaurantes y revisar reportes.</p>
                        <div class="sj-dash-actions">
                            <a href="admin_usuarios.php" class="sj-action">👥 Usuarios</a>
                            <a href="admin_reportes.php" class="sj-action">📊 Reportes</a>
                        </div>
                    </div>
                </div>

            <?php elseif ($id_rol == 2): // VISTA DUEÑO / CHEF ?>
                <div class="sj-dashboard-cards">
                    <div class="sj-dash-card">
                        <h3>👨‍🍳 Gestión de Sucursal</h3>
                        <p>Administra tu menú, revisa tu inventario y mantén tu información al día.</p>
                        <div class="sj-dash-actions">
                            <a href="gestion_platillos.php" class="sj-action">🍽️ Mi Menú</a>
                            <a href="inventario.php" class="sj-action">📦 Inventario</a>
                        </div>
                    </div>
                </div>

            <?php else: // VISTA COMENSAL (USUARIO 3) ?>
                <div class="sj-dashboard-cards">
                    <div class="sj-dash-card">
                        <h3>🔍 Explorar Salud Juárez</h3>
                        <p>Busca restaurantes saludables y descubre sus platillos disponibles.</p>
                        <div class="sj-dash-actions">
                            <a href="buscar_restaurantes.php" class="sj-action">🗺️ Explorar</a>
                            <a href="mis_favoritos.php" class="sj-action">⭐ Favoritos</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>