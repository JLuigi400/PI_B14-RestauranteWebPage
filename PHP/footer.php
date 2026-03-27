<?php
/**
 * Footer Global - Salud Juárez
 * Incluye el modal del desarrollador y scripts comunes
 * Versión: 2.0.0
 * Fecha: 26 de Marzo de 2026
 */

// Detectar ruta para recursos
$current_path = $_SERVER['PHP_SELF'] ?? '';
$is_in_direcciones = strpos($current_path, '/DIRECCIONES/') !== false;
$path = $is_in_direcciones ? "../" : "";
?>

<!-- Footer Global -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>🥗 Salud Juárez</h3>
                <p>Plataforma de restaurantes saludables en Ciudad Juárez</p>
                <ul class="footer-links">
                    <li><a href="<?php echo $path; ?>index.php">Inicio</a></li>
                    <li><a href="<?php echo $path; ?>signup.php">Registrarse</a></li>
                    <li><a href="<?php echo $path; ?>login.php">Iniciar Sesión</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Funcionalidades</h3>
                <ul class="footer-links">
                    <li><a href="<?php echo $path; ?>DIRECCIONES/buscar_restaurantes.php">Explorar Restaurantes</a></li>
                    <li><a href="<?php echo $path; ?>DIRECCIONES/mis_favoritos.php">Mis Favoritos</a></li>
                    <li><a href="<?php echo $path; ?>DIRECCIONES/mapa_proveedores.php">Mapa de Proveedores</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Para Restaurantes</h3>
                <ul class="footer-links">
                    <li><a href="<?php echo $path; ?>DIRECCIONES/gestion_platillos.php">Gestionar Menú</a></li>
                    <li><a href="<?php echo $path; ?>DIRECCIONES/inventario.php">Control de Inventario</a></li>
                    <li><a href="<?php echo $path; ?>DIRECCIONES/mis_restaurantes.php">Mis Restaurantes</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Tecnologías</h3>
                <div class="footer-tech">
                    <span class="tech-tag">PHP</span>
                    <span class="tech-tag">MySQL</span>
                    <span class="tech-tag">JavaScript</span>
                    <span class="tech-tag">OpenStreetMap</span>
                    <span class="tech-tag">CSS3</span>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 Salud Juárez. Desarrollado por <a href="https://github.com/JLuigi400" target="_blank">Jorge Anibal Espinosa Perales</a></p>
            <p>🌐 Ciudad Juárez, Chihuahua, México</p>
        </div>
    </div>
</footer>

<!-- Incluir Modal del Desarrollador -->
<?php include_once 'modal_desarrollador.php'; ?>

<!-- Scripts Globales -->
<script src="<?php echo $path; ?>JS/modal_desarrollador.js"></script>

<!-- Scripts de rendimiento y análisis -->
<script>
// Optimización de rendimiento
document.addEventListener('DOMContentLoaded', function() {
    // Preload de recursos críticos
    const criticalResources = [
        '<?php echo $path; ?>IMG/UPLOADS/hero-bg.jpg',
        '<?php echo $path; ?>IMG/UPLOADS/placeholder-restaurant.jpg'
    ];
    
    criticalResources.forEach(src => {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'image';
        link.href = src;
        document.head.appendChild(link);
    });
    
    // Lazy loading para imágenes
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
    
    console.log('🚀 Salud Juárez - Sistema optimizado cargado');
});

// Manejo de errores global
window.addEventListener('error', function(e) {
    console.error('Error en la aplicación:', e.error);
    
    // Enviar a servicio de análisis si está disponible
    if (typeof gtag !== 'undefined') {
        gtag('event', 'exception', {
            description: e.error.message,
            fatal: false
        });
    }
});
</script>
