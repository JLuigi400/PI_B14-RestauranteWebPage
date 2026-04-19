<?php

session_start();
include '../PHP/navbar.php';

// Si no hay sesión, mandarlo al login
if (!isset($_SESSION['id_usu'])) {
    header("Location: ../login.php");
    exit();
}

// Verificar que sea rol 3 (Comensal/Usuario)
if ($_SESSION['id_rol'] != 3) {
    // Si no es usuario, redirigir al dashboard correspondiente
    header("Location: dashboard.php");
    exit();
}

$id_usu = $_SESSION['id_usu'];
$nombre_usuario = $_SESSION['nombre_completo'] ?? $_SESSION['nick'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Espacio | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/modal_theme.css">
    <link rel="stylesheet" href="../CSS/navbar_zindex_fix.css">
    <link rel="stylesheet" href="../CSS/dashboard_usuario.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../JS/session_check.js"></script>
</head>
<body>
    <main class="container">
        <div class="sj-dashboard-header">
            <div>
                <h1 style="margin:0;">Mi Espacio Salud Juárez</h1>
                <div style="margin-top:6px; color: var(--gris-suave);">
                    Bienvenido, <b><?php echo $nombre_usuario; ?></b> · <b style="color: var(--verde-salud);">Explorador</b>
                </div>
            </div>
        </div>

        <section class="usuario-dashboard">
            <!-- Sección de Mapa -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Mapa de Restaurantes</h2>
                    <p>Descubre restaurantes saludables cerca de ti</p>
                </div>
                <div class="mapa-container">
                    <div id="mapa-restaurantes" class="mapa-usuario"></div>
                    <div class="mapa-legend">
                        <div class="legend-item">
                            <span class="legend-icon"></span>
                            <span>Restaurantes Saludables</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-icon favorito"></span>
                            <span>Mis Favoritos</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Favoritos -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Mis Restaurantes Favoritos</h2>
                    <p>Accede rápido a tus restaurantes preferidos</p>
                </div>
                <div class="favoritos-grid" id="favoritos-container">
                    <!-- Los favoritos se cargarán dinámicamente desde mis_favoritos.php -->
                    <div class="loading-placeholder">
                        <div class="spinner"></div>
                        <p>Cargando tus favoritos...</p>
                    </div>
                </div>
            </div>
            
            <!-- Script para cargar favoritos dinámicamente -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Cargar los favoritos desde mis_favoritos.php
                    fetch('mis_favoritos.php?ajax=1')
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('favoritos-container').innerHTML = html;
                        })
                        .catch(error => {
                            console.error('Error al cargar favoritos:', error);
                            document.getElementById('favoritos-container').innerHTML = 
                                '<div class="error-message">Error al cargar favoritos</div>';
                        });
                });
            </script>

            <!-- Sección de Acciones Rápidas -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Acciones Rápidas</h2>
                    <p>Explora y descubre más opciones</p>
                </div>
                <div class="acciones-grid">
                    <a href="buscar_restaurantes.php" class="accion-card">
                        <div class="accion-icon"></div>
                        <h3>Explorar Restaurantes</h3>
                        <p>Busca nuevos lugares saludables</p>
                    </a>
                    <a href="mis_favoritos.php" class="accion-card">
                        <div class="accion-icon"></div>
                        <h3>Ver Todos los Favoritos</h3>
                        <p>Lista completa de tus favoritos</p>
                    </a>
                    <a href="mapa_proveedores.php" class="accion-card">
                        <div class="accion-icon"></div>
                        <h3>Mapa de Proveedores</h3>
                        <p>Conoce los proveedores locales</p>
                    </a>
                </div>
            </div>
        </section>
        
        <!-- Footer Global -->
        <?php include '../PHP/footer.php'; ?>
    </main>

    <!-- Scripts -->
    <script src="../JS/mapa_usuario_dashboard.js"></script>
    <script src="../JS/favoritos_usuario.js"></script>
    <script>
        // Inicializar el dashboard del usuario
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar mapa
            if (window.MapaUsuarioDashboard) {
                window.MapaUsuarioDashboard.inicializar();
            }
            
            // Cargar favoritos
            if (window.FavoritosUsuario) {
                window.FavoritosUsuario.cargarFavoritos();
            }
        });
    </script>
</body>
</html>
