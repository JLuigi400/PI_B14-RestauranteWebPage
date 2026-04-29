<?php

session_start();
include '../PHP/navbar.php';

// Si no hay sesión, mandarlo al login
if (!isset($_SESSION['id_usu'])) {
    header("Location: ../login.php");
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
    <link rel="stylesheet" href="../CSS/modal_theme.css">
    <link rel="stylesheet" href="../CSS/modal_social_icons.css">
    <script src="../JS/session_check.js"></script>
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
                <?php include '../PHP/alertas/dashboard_alerts.php'; ?>
                
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
                <?php include '../PHP/alertas/dashboard_alerts.php'; ?>
                
                <div class="sj-dashboard-cards">
                    <div class="sj-dash-card">
                        <h3>👨‍🍳 Gestión de Sucursal</h3>
                        <p>Administra tu menú, revisa tu inventario y mantén tu información al día.</p>
                        <div class="sj-dash-actions">
                            <a href="gestion_platillos.php" class="sj-action">🍽️ Mi Menú</a>
                            <a href="inventario/inventario_crud.php" class="sj-action">📦 Inventario</a>
                            <a href="inventario/restock_inventario.php" class="sj-action">🛒 Re-stock</a>
                        </div>
                    </div>
                </div>

            <?php elseif ($id_rol == 4): // VISTA PROVEEDOR (ROL 4) ?>
                <?php include '../PHP/alertas/dashboard_alerts.php'; ?>
                
                <div class="sj-dashboard-cards">
                    <div class="sj-dash-card">
                        <h3>📦 Gestión de Productos</h3>
                        <p>Administra tu catálogo de productos, precios y disponibilidad para los restaurantes.</p>
                        <div class="sj-dash-actions">
                            <a href="proveedor_productos.php" class="sj-action">📋 Mis Productos</a>
                            <a href="proveedor_pedidos.php" class="sj-action">🛒 Pedidos</a>
                            <a href="proveedor_perfil.php" class="sj-action">⚙️ Mi Perfil</a>
                        </div>
                    </div>
                    <div class="sj-dash-card">
                        <h3>📊 Estadísticas</h3>
                        <p>Revisa el rendimiento de tus productos y el estado de tus pedidos.</p>
                        <div class="sj-dash-actions">
                            <a href="proveedor_estadisticas.php" class="sj-action">📈 Reportes</a>
                            <a href="proveedores_cercanos.php" class="sj-action">🗺️ Visibilidad</a>
                        </div>
                    </div>
                </div>

            <?php else: // VISTA COMENSAL (USUARIO 3) ?>
                <!-- Redirigir al dashboard especializado para usuarios -->
                <script>
                    window.location.href = 'dashboard_usuario.php';
                </script>
                <noscript>
                    <div class="sj-dashboard-cards">
                        <div class="sj-dash-card">
                            <h3>🔍 Explorar Salud Juárez</h3>
                            <p>Busca restaurantes saludables y descubre sus platillos disponibles.</p>
                            <div class="sj-dash-actions">
                                <a href="dashboard_usuario.php" class="sj-action">🗺️ Mi Espacio</a>
                                <a href="buscar_restaurantes.php" class="sj-action"> Explorar</a>
                                <a href="mis_favoritos.php" class="sj-action">⭐ Favoritos</a>
                            </div>
                        </div>
                    </div>
                </noscript>
            <?php endif; ?>
        </section>
        
        

                <div class="footer-bottom">
                    <p>&copy; 2026 Salud Juárez. Desarrollado por <a href="https://github.com/JLuigi400" target="_blank">Jorge Anibal Espinosa Perales</a></p>
                    <p>🌐 Ciudad Juárez, Chihuahua, México</p>
                </div>
            </div>
        </footer>
    </main>

    <!-- Scripts del Modal -->
    <script src="../JS/modal_desarrollador.js"></script>
    <script src="../JS/main_interactions.js"></script>
    <script src="../JS/modal_html.js"></script>
    
    <!-- Script para corregir modal duplicado (solo para roles que lo necesitan) -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Solo ejecutar limpieza si hay modal de desarrollador
        const modalDev = document.getElementById('devModal');
        const openBtn = document.getElementById('btnOpenDevModal');
        
        if (modalDev && openBtn) {
            // Eliminar modales duplicados si existen
            const modals = document.querySelectorAll('.dev-modal-overlay');
            if (modals.length > 1) {
                // Mantener solo el primer modal
                for (let i = 1; i < modals.length; i++) {
                    modals[i].remove();
                }
                console.log('🔧 Modal duplicado eliminado');
            }
            
            // Asegurar que solo exista un botón de abrir modal
            const buttons = document.querySelectorAll('#btnOpenDevModal');
            if (buttons.length > 1) {
                // Mantener solo el primer botón
                for (let i = 1; i < buttons.length; i++) {
                    buttons[i].remove();
                }
                console.log('🔧 Botón duplicado eliminado');
            }
            
            // Reinicializar el modal si es necesario
            if (typeof window.initModal === 'function') {
                window.initModal();
            }
        }
    });
    </script>
</body>
</html>