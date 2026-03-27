<?php
/**
 * Modal del Desarrollador - Salud Juárez
 * Archivo separado para mantener el HTML limpio
 * Versión: 2.0.0
 * Fecha: 26 de Marzo de 2026
 */

// Detectar ruta para recursos
$current_path = $_SERVER['PHP_SELF'] ?? '';
$is_in_direcciones = strpos($current_path, '/DIRECCIONES/') !== false;
$path = $is_in_direcciones ? "../" : "";
?>

<!-- Modal del Desarrollador -->
<div id="devModal" class="dev-modal-overlay">
    <div class="dev-modal-container">
        <div class="dev-modal-header">
            <div class="dev-avatar-section">
                <div class="dev-avatar">
                    <img src="<?php echo $path; ?>IMG/PORTAFOLIO/artista/artista.jpeg" 
                         alt="Jorge Anibal Espinosa Perales" 
                         onerror="this.src='<?php echo $path; ?>IMG/leche.png'">
                    <div class="status-indicator online"></div>
                </div>
                <div class="avatar-info">
                    <h3 class="dev-name">Jorge Anibal Espinosa Perales</h3>
                    <p class="dev-role">Desarrollador Full-Stack & UI/UX Designer</p>
                    <p class="dev-education">🎓 Creador de Contenido y Desarrollador Web</p>
                </div>
            </div>
            
            <div class="qr-section">
                <div class="qr-container">
                    <img id="qrCodeImage" 
                         src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://github.com/JLuigi400" 
                         alt="QR Code del Portafolio">
                    <div class="qr-overlay">
                        <span class="qr-text">Escanea para ver mi portafolio</span>
                    </div>
                </div>
                <p class="qr-description">QR dinámico actualizado en tiempo real</p>
            </div>
        </div>
        
        <div class="dev-modal-content">
            <div class="skills-section">
                <h4 class="section-title">
                    <span class="title-icon">🎯</span>
                    Especialidades Técnicas
                </h4>
                <div class="skills-grid">
                    <div class="skill-card">
                        <span class="skill-icon">🎨</span>
                        <div class="skill-info">
                            <h5>Arte Digital</h5>
                            <p>Estilos Anime, Chibi, Flat Design</p>
                        </div>
                    </div>
                    <div class="skill-card">
                        <span class="skill-icon">💻</span>
                        <div class="skill-info">
                            <h5>Desarrollo Web</h5>
                            <p>PHP, MySQL, JavaScript, HTML, CSS</p>
                        </div>
                    </div>
                    <div class="skill-card">
                        <span class="skill-icon">📱</span>
                        <div class="skill-info">
                            <h5>Diseño UI/UX</h5>
                            <p>Maquetación e interfaces (Flat Design)</p>
                        </div>
                    </div>
                    <div class="skill-card">
                        <span class="skill-icon">🎮</span>
                        <div class="skill-info">
                            <h5>Videojuegos</h5>
                            <p>Desarrollo indie (Novato)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tech-stack-section">
                <h4 class="section-title">
                    <span class="title-icon">🔧</span>
                    Stack Tecnológico
                </h4>
                <div class="tech-tags">
                    <span class="tech-tag php">PHP</span>
                    <span class="tech-tag mysql">MySQL</span>
                    <span class="tech-tag js">JavaScript</span>
                    <span class="tech-tag css">CSS3</span>
                    <span class="tech-tag html">HTML5</span>
                    <span class="tech-tag git">Git</span>
                    <span class="tech-tag node">Node.JS</span>
                    <span class="tech-tag csharp">C#</span>
                    <span class="tech-tag kotlin">Kotlin</span>
                    <span class="tech-tag swift">Swift</span>
                </div>
            </div>
            
            <div class="social-section">
                <h4 class="section-title">
                    <span class="title-icon">🌐</span>
                    Conecta Conmigo
                </h4>
                <div class="social-links">
                    <a href="https://github.com/JLuigi400" target="_blank" class="social-link github">
                        <img src="<?php echo $path; ?>IMG/LOGOTIPOS/ICON/github.png" alt="GitHub" class="social-icon">
                        <div class="social-info">
                            <span class="social-name">GitHub</span>
                            <span class="social-handle">@JLuigi400</span>
                        </div>
                    </a>
                    <a href="https://www.instagram.com/jorgeluigi400/" target="_blank" class="social-link instagram">
                        <img src="<?php echo $path; ?>IMG/LOGOTIPOS/ICON/instagram.png" alt="Instagram" class="social-icon">
                        <div class="social-info">
                            <span class="social-name">Instagram</span>
                            <span class="social-handle">@jorgeluigi400</span>
                        </div>
                    </a>
                    <a href="https://www.behance.net/jorgeaespinos1" target="_blank" class="social-link behance">
                        <img src="<?php echo $path; ?>IMG/LOGOTIPOS/ICON/behance.png" alt="Behance" class="social-icon">
                        <div class="social-info">
                            <span class="social-name">Behance</span>
                            <span class="social-handle">jorgeaespinos1</span>
                        </div>
                    </a>
                    <a href="mailto:anibal.espinosa.perales@gmail.com" class="social-link email">
                        <img src="<?php echo $path; ?>IMG/LOGOTIPOS/ICON/google.png" alt="Email" class="social-icon">
                        <div class="social-info">
                            <span class="social-name">Email</span>
                            <span class="social-handle">anibal.espinosa.perales@gmail.com</span>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="projects-section">
                <h4 class="section-title">
                    <span class="title-icon">🏆</span>
                    Proyectos Destacados
                </h4>
                <div class="projects-grid">
                    <div class="project-card">
                        <div class="project-header">
                            <h5 class="project-name">Salud Juárez</h5>
                            <span class="project-badge active">Activo</span>
                        </div>
                        <p class="project-description">
                            Plataforma web para restaurantes saludables con sistema de certificación nutricional.
                        </p>
                        <div class="project-tech">
                            <span class="tech-mini-tag">PHP</span>
                            <span class="tech-mini-tag">MySQL</span>
                            <span class="tech-mini-tag">JavaScript</span>
                            <span class="tech-mini-tag">OpenStreetMap</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stats-section">
                <h4 class="section-title">
                    <span class="title-icon">📊</span>
                    Estadísticas
                </h4>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">2+</div>
                        <div class="stat-label">Años de Experiencia</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">10+</div>
                        <div class="stat-label">Proyectos Completados</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">5+</div>
                        <div class="stat-label">Tecnologías Dominadas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">∞</div>
                        <div class="stat-label">Cupones de Café ☕</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dev-modal-footer">
            <button id="btnDownloadCV" class="btn-footer primary">
                <span class="btn-icon">📄</span>
                Descargar CV
            </button>
            <button id="btnCloseModal" class="btn-footer secondary">
                <span class="btn-icon">✖</span>
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Scripts del Modal (se moverán a archivo externo) -->
<script>
// Event listener para el botón del navbar
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('btnOpenDevModal');
    const closeBtn = document.getElementById('btnCloseModal');
    const modal = document.getElementById('devModal');
    const downloadBtn = document.getElementById('btnDownloadCV');
    
    if (openBtn) {
        openBtn.addEventListener('click', function() {
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
    
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            // Lógica para descargar CV
            window.open('README/README_DESARROLLADOR.md', '_blank');
        });
    }
    
    // Cerrar modal al hacer clic fuera
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
    
    // Cerrar con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});
</script>
