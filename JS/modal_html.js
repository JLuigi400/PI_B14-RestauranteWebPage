/**
 * Modal del Desarrollador para versión HTML - Salud Juárez
 * Crea dinámicamente el modal cuando se hace clic en el botón
 * Versión: 1.0.0
 * Fecha: 26 de Marzo de 2026
 */

class ModalDesarrolladorHTML {
    constructor() {
        this.modal = null;
        this.isOpen = false;
        this.init();
    }

    init() {
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupEventListeners();
            });
        } else {
            this.setupEventListeners();
        }
    }

    setupEventListeners() {
        // Botón para abrir modal
        const btnOpenModal = document.getElementById('btnOpenDevModal');
        if (btnOpenModal) {
            btnOpenModal.addEventListener('click', (e) => {
                e.preventDefault();
                this.openModal();
            });
        }

        // Eventos de teclado
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeModal();
            }
        });

        // Cerrar al hacer clic fuera del modal
        document.addEventListener('click', (e) => {
            if (this.isOpen && e.target.classList.contains('dev-modal-overlay')) {
                this.closeModal();
            }
        });
    }

    createModal() {
        // Crear el modal dinámicamente
        const modalHTML = `
            <div id="devModal" class="dev-modal-overlay">
                <div class="dev-modal-container">
                    <div class="dev-modal-header">
                        <div class="dev-avatar-section">
                            <div class="dev-avatar">
                                <img src="IMG/PORTAFOLIO/artista/artista.jpeg" 
                                     alt="Jorge Anibal Espinosa Perales" 
                                     onerror="this.src='IMG/leche.png'">
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
                                    <span class="skill-icon">🌐</span>
                                    <div class="skill-info">
                                        <h5>Desarrollo Web</h5>
                                        <p>Full-Stack con PHP, MySQL, JavaScript</p>
                                    </div>
                                </div>
                                <div class="skill-card">
                                    <span class="skill-icon">🎨</span>
                                    <div class="skill-info">
                                        <h5>Arte Digital</h5>
                                        <p>Estilos Anime, Chibi, Flat Design</p>
                                    </div>
                                </div>
                                <div class="skill-card">
                                    <span class="skill-icon">📱</span>
                                    <div class="skill-info">
                                        <h5>UI/UX Design</h5>
                                        <p>Maquetación e interfaces modernas</p>
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
                                    <img src="IMG/LOGOTIPOS/ICON/github.png" alt="GitHub" class="social-icon">
                                    <div class="social-info">
                                        <span class="social-name">GitHub</span>
                                        <span class="social-handle">@JLuigi400</span>
                                    </div>
                                </a>
                                <a href="https://www.instagram.com/jorgeluigi400/" target="_blank" class="social-link instagram">
                                    <img src="IMG/LOGOTIPOS/ICON/instagram.png" alt="Instagram" class="social-icon">
                                    <div class="social-info">
                                        <span class="social-name">Instagram</span>
                                        <span class="social-handle">@jorgeluigi400</span>
                                    </div>
                                </a>
                                <a href="https://www.behance.net/jorgeaespinos1" target="_blank" class="social-link behance">
                                    <img src="IMG/LOGOTIPOS/ICON/behance.png" alt="Behance" class="social-icon">
                                    <div class="social-info">
                                        <span class="social-name">Behance</span>
                                        <span class="social-handle">Jorge Anibal Espinosa Perales</span>
                                    </div>
                                </a>
                                <a href="mailto:anibal.espinosa.perales@gmail.com" class="social-link email">
                                    <img src="IMG/LOGOTIPOS/ICON/google.png" alt="Email" class="social-icon">
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
                                    <div class="stat-number">5+</div>
                                    <div class="stat-label">Años de Experiencia</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">50+</div>
                                    <div class="stat-label">Proyectos Completados</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">10+</div>
                                    <div class="stat-label">Tecnologías Dominadas</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">100%</div>
                                    <div class="stat-label">Compromiso</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="dev-modal-footer">
                        <button id="btnCloseModal" class="btn-footer secondary">
                            ✖ Cerrar
                        </button>
                        <button id="btnDownloadCV" class="btn-footer primary">
                            📄 Descargar CV
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Agregar el modal al body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('devModal');
        
        // Configurar eventos del modal
        this.setupModalEvents();
        
        // Cargar CSS del modal si no está cargado
        this.loadModalCSS();
    }

    setupModalEvents() {
        // Botón cerrar
        const btnClose = document.getElementById('btnCloseModal');
        if (btnClose) {
            btnClose.addEventListener('click', () => {
                this.closeModal();
            });
        }

        // Botón descargar CV
        const btnDownloadCV = document.getElementById('btnDownloadCV');
        if (btnDownloadCV) {
            btnDownloadCV.addEventListener('click', () => {
                this.downloadCV();
            });
        }

        // Animación de QR
        this.setupQRAnimation();
    }

    loadModalCSS() {
        // Verificar si el CSS ya está cargado
        if (!document.getElementById('modal-theme-css')) {
            const link = document.createElement('link');
            link.id = 'modal-theme-css';
            link.rel = 'stylesheet';
            link.href = 'CSS/modal_theme.css';
            document.head.appendChild(link);
        }

        // Cargar CSS de iconos sociales
        if (!document.getElementById('modal-social-icons-css')) {
            const link = document.createElement('link');
            link.id = 'modal-social-icons-css';
            link.rel = 'stylesheet';
            link.href = 'CSS/modal_social_icons.css';
            document.head.appendChild(link);
        }

        // Cargar CSS de animaciones
        if (!document.getElementById('modal-animations-css')) {
            const link = document.createElement('link');
            link.id = 'modal-animations-css';
            link.rel = 'stylesheet';
            link.href = 'CSS/modal_animations.css';
            document.head.appendChild(link);
        }
    }

    openModal() {
        if (!this.modal) {
            this.createModal();
        }

        this.modal.classList.add('active');
        this.isOpen = true;
        document.body.style.overflow = 'hidden'; // Prevenir scroll

        // Animación de entrada
        setTimeout(() => {
            this.modal.classList.add('show');
        }, 10);
    }

    closeModal() {
        if (this.modal) {
            this.modal.classList.remove('show');
            
            setTimeout(() => {
                this.modal.classList.remove('active');
                this.isOpen = false;
                document.body.style.overflow = ''; // Restaurar scroll
            }, 300);
        }
    }

    downloadCV() {
        // Crear contenido del CV
        const cvContent = `
Jorge Anibal Espinosa Perales
================================
Desarrollador Full-Stack & UI/UX Designer

Contacto:
- Email: anibal.espinosa.perales@gmail.com
- GitHub: https://github.com/JLuigi400
- Instagram: @jorgeluigi400
- Behance: jorgeaespinos1

Experiencia:
- 5+ años en desarrollo web
- Especialista en PHP, MySQL, JavaScript
- UI/UX Design moderno
- Desarrollo de plataformas completas

Tecnologías:
- Backend: PHP, MySQL, Node.js
- Frontend: JavaScript, HTML5, CSS3
- Herramientas: Git, OpenStreetMap
- Móvil: C#, Kotlin, Swift (conocimiento básico)

Proyectos Destacados:
- Salud Juárez: Plataforma de restaurantes saludables
- Desarrollo de sistemas web completos
- Diseño de interfaces modernas

Educación:
- Creador de Contenido y Desarrollador Web
- Formación autodidacta continua

Disponibilidad: Inmediata
Compromiso: 100%
        `.trim();

        // Crear blob y descargar
        const blob = new Blob([cvContent], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'CV_JorgeAnibalEspinosa.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        // Mostrar notificación
        this.showNotification('✅ CV descargado exitosamente');
    }

    setupQRAnimation() {
        const qrImage = document.getElementById('qrCodeImage');
        if (qrImage) {
            // Actualizar QR cada 30 segundos
            setInterval(() => {
                const timestamp = new Date().getTime();
                qrImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://github.com/JLuigi400?t=${timestamp}`;
            }, 30000);
        }
    }

    showNotification(message) {
        // Crear notificación temporal
        const notification = document.createElement('div');
        notification.className = 'modal-notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #27ae60;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 10000;
            font-weight: 600;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);

        // Eliminar después de 3 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    destroy() {
        if (this.modal) {
            this.modal.remove();
            this.modal = null;
        }
        this.isOpen = false;
        document.body.style.overflow = '';
    }
}

// Inicializar el modal automáticamente
const modalHTML = new ModalDesarrolladorHTML();

// Exponer globalmente para uso externo
window.ModalDesarrolladorHTML = ModalDesarrolladorHTML;
