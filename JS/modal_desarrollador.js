/**
 * Modal del Desarrollador - Salud Juárez
 * Sistema de modal moderno (Flat Design) para mostrar información del desarrollador
 * Versión: 1.0.0
 * Fecha: 2026-03-25
 */

class ModalDesarrollador {
    constructor() {
        this.modal = null;
        this.isOpen = false;
        this.init();
    }

    /**
     * Inicializar el modal
     */
    init() {
        this.crearModal();
        this.crearBotonNav();
        this.agregarEstilos();
        this.cargarQRCode();
    }

    /**
     * Crear estructura del modal
     */
    crearModal() {
        const modalHTML = `
            <div id="modalDesarrollador" class="modal-desarrollador">
                <div class="modal-backdrop" onclick="modalDev.cerrarModal()"></div>
                <div class="modal-container">
                    <div class="modal-header">
                        <h2 class="modal-title">
                            <span class="title-icon">👨‍💻</span>
                            Acerca del Desarrollador
                        </h2>
                        <button class="modal-close" onclick="modalDev.cerrarModal()">
                            <span class="close-icon">×</span>
                        </button>
                    </div>
                    
                    <div class="modal-content">
                        <div class="dev-profile">
                            <div class="profile-header">
                                <div class="avatar-section">
                                    <div class="dev-avatar">
                                        <img src="IMG/UPLOADS/developer-avatar.jpg" alt="Avatar del Desarrollador" 
                                             onerror="this.src='IMG/default-avatar.png'">
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
                            
                            <div class="profile-content">
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
                                            <img src="../IMG/github.png" alt="GitHub" class="social-icon">
                                            <div class="social-info">
                                                <span class="social-name">GitHub</span>
                                                <span class="social-handle">@JLuigi400</span>
                                            </div>
                                        </a>
                                        <a href="https://www.instagram.com/jorgeluigi400/" target="_blank" class="social-link instagram">
                                            <img src="../IMG/instagram.png" alt="Instagram" class="social-icon">
                                            <div class="social-info">
                                                <span class="social-name">Instagram</span>
                                                <span class="social-handle">@jorgeluigi400</span>
                                            </div>
                                        </a>
                                        <a href="https://www.behance.net/jorgeaespinos1" target="_blank" class="social-link behance">
                                            <img src="../IMG/behance.png" alt="Behance" class="social-icon">
                                            <div class="social-info">
                                                <span class="social-name">Behance</span>
                                                <span class="social-handle">jorgeaespinos1</span>
                                            </div>
                                        </a>
                                        <a href="mailto:anibal.espinosa.perales@gmail.com" class="social-link email">
                                            <img src="../IMG/google.png" alt="Email" class="social-icon">
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
                                    <div class="project-cards">
                                        <div class="project-card">
                                            <div class="project-image">
                                                <img src="IMG/UPLOADS/salud-juarez-project.jpg" 
                                                     alt="Salud Juárez Project"
                                                     onerror="this.src='IMG/default-project.jpg'">
                                            </div>
                                            <div class="project-info">
                                                <h5>Salud Juárez</h5>
                                                <p>Plataforma de restaurantes saludables con certificación nutricional</p>
                                                <div class="project-tech">
                                                    <span class="tech-mini">PHP</span>
                                                    <span class="tech-mini">MySQL</span>
                                                    <span class="tech-mini">JS</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="project-card">
                                            <div class="project-image">
                                                <img src="IMG/UPLOADS/otro-proyecto.jpg" 
                                                     alt="Otro Proyecto"
                                                     onerror="this.src='IMG/default-project.jpg'">
                                            </div>
                                            <div class="project-info">
                                                <h5>[Otro Proyecto]</h5>
                                                <p>Descripción breve del proyecto</p>
                                                <div class="project-tech">
                                                    <span class="tech-mini">React</span>
                                                    <span class="tech-mini">Node.js</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <div class="footer-stats">
                            <div class="stat-item">
                                <span class="stat-number">50+</span>
                                <span class="stat-label">Proyectos</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">3+</span>
                                <span class="stat-label">Años de Experiencia</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">100%</span>
                                <span class="stat-label">Dedicación</span>
                            </div>
                        </div>
                        <div class="footer-actions">
                            <button class="btn-primary" onclick="modalDev.descargarCV()">
                                <span class="btn-icon">📄</span>
                                Descargar CV
                            </button>
                            <button class="btn-secondary" onclick="modalDev.cerrarModal()">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('modalDesarrollador');
    }

    /**
     * Crear botón en el navbar
     */
    crearBotonNav() {
        const navLinks = document.querySelector('.nav-links');
        if (navLinks) {
            const botonDev = document.createElement('li');
            botonDev.innerHTML = `
                <button class="nav-dev-btn" onclick="modalDev.abrirModal()" title="Acerca del Desarrollador">
                    <span class="dev-btn-icon">👨‍💻</span>
                    <span class="dev-btn-text">Desarrollador</span>
                </button>
            `;
            navLinks.appendChild(botonDev);
        }
    }

    /**
     * Abrir modal
     */
    abrirModal() {
        if (!this.modal) return;
        
        this.modal.classList.add('modal-desarrollador--active');
        this.isOpen = true;
        
        // Prevenir scroll del body
        document.body.style.overflow = 'hidden';
        
        // Animación de entrada
        setTimeout(() => {
            this.modal.querySelector('.modal-container').classList.add('modal-container--active');
        }, 10);
        
        // Actualizar QR code
        this.actualizarQRCode();
    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        if (!this.modal) return;
        
        this.modal.querySelector('.modal-container').classList.remove('modal-container--active');
        
        setTimeout(() => {
            this.modal.classList.remove('modal-desarrollador--active');
            this.isOpen = false;
            document.body.style.overflow = '';
        }, 300);
    }

    /**
     * Cargar QR Code dinámicamente
     */
    cargarQRCode() {
        const qrImage = document.getElementById('qrCodeImage');
        if (qrImage) {
            // Usar API gratuita de QR Server
            const portfolioURL = 'https://github.com/JLuigi400';
            qrImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(portfolioURL)}&format=png&color=000000&bgcolor=FFFFFF`;
        }
    }

    /**
     * Actualizar QR Code
     */
    actualizarQRCode() {
        const qrImage = document.getElementById('qrCodeImage');
        if (qrImage) {
            // Agregar timestamp para evitar caché
            const timestamp = Date.now();
            const portfolioURL = 'https://github.com/JLuigi400';
            qrImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(portfolioURL)}&format=png&color=000000&bgcolor=FFFFFF&t=${timestamp}`;
        }
    }

    /**
     * Descargar CV
     */
    descargarCV() {
        // Crear y descargar un CV simple
        const cvContent = `
            CV - Jorge Anibal Espinosa Perales
            ===============================
            
            Información Personal:
            - Nombre: Jorge Anibal Espinosa Perales
            - Rol: Desarrollador Full-Stack & UI/UX Designer
            - Email: anibal.espinosa.perales@gmail.com
            - GitHub: https://github.com/JLuigi400
            - Instagram: https://www.instagram.com/jorgeluigi400/
            
            Descripción:
            Creador de Contenido y joven que siempre intento mejorar y ofrecer una calidad decente en todo lo que poseo, en Arte, Programacion y creacion de Paginas Web y Diseño de Videojuegos (Novato). Hablante en Español e Ingles.
            
            Especialidades:
            - Desarrollo Web Full-Stack: Creación de sistemas a medida y bases de datos
            - Arte Digital e Ilustración: Estilos Anime, Chibi y Flat Design
            - Diseño UI/UX: Maquetación e interfaces (Flat Design)
            - Desarrollo de Videojuegos: Desarrollo indie (Novato)
            
            Stack Tecnológico:
            - Principales: PHP, MySQL, JavaScript, HTML, CSS, Node.JS, C, C#
            - En práctica/Básicos: Kotlin, Swift
            
            Software de Diseño:
            - Adobe Creative Cloud (Photoshop, Illustrator)
            - Medibang Paint Pro (Lineart / Color)
            - Krita (Bocetos y Drafts)
            
            Proyectos:
            - Salud Juárez (2025-2026)
              Plataforma web para restaurantes saludables con sistema de certificación nutricional
              Tecnologías: PHP, MySQL, JavaScript, OpenStreetMap, CSS3
            
            Contacto:
            - Email: anibal.espinosa.perales@gmail.com
            - GitHub: https://github.com/JLuigi400
            - Instagram: https://www.instagram.com/jorgeluigi400/
            - Behance: https://www.behance.net/jorgeaespinos1
        `;
        
        const blob = new Blob([cvContent], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'CV_JorgeAnibalEspinosa.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    /**
     * Agregar estilos del modal
     */
    agregarEstilos() {
        const estilo = document.createElement('style');
        estilo.textContent = `
            /* Modal Desarrollador - Flat Design */
            .modal-desarrollador {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 10000;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .modal-desarrollador--active {
                display: flex;
            }

            .modal-backdrop {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: blur(5px);
            }

            .modal-container {
                position: relative;
                background: #ffffff;
                border-radius: 16px;
                max-width: 900px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                transform: scale(0.9) translateY(20px);
                opacity: 0;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            }

            .modal-container--active {
                transform: scale(1) translateY(0);
                opacity: 1;
            }

            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 24px 32px;
                border-bottom: 1px solid #e5e7eb;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 16px 16px 0 0;
            }

            .modal-title {
                color: white;
                font-size: 1.5rem;
                font-weight: 600;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .title-icon {
                font-size: 1.8rem;
            }

            .modal-close {
                background: rgba(255, 255, 255, 0.2);
                border: none;
                color: white;
                font-size: 2rem;
                cursor: pointer;
                padding: 0;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.3s ease;
            }

            .modal-close:hover {
                background: rgba(255, 255, 255, 0.3);
                transform: scale(1.1);
            }

            .modal-content {
                padding: 32px;
            }

            .profile-header {
                display: grid;
                grid-template-columns: 1fr auto;
                gap: 32px;
                margin-bottom: 32px;
            }

            .avatar-section {
                display: flex;
                align-items: center;
                gap: 20px;
            }

            .dev-avatar {
                position: relative;
                width: 100px;
                height: 100px;
                border-radius: 50%;
                overflow: hidden;
                border: 4px solid #667eea;
            }

            .dev-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .status-indicator {
                position: absolute;
                bottom: 8px;
                right: 8px;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                border: 3px solid white;
            }

            .status-indicator.online {
                background: #10b981;
            }

            .avatar-info h3 {
                color: #1f2937;
                margin: 0 0 8px 0;
                font-size: 1.3rem;
                font-weight: 600;
            }

            .avatar-info p {
                color: #6b7280;
                margin: 4px 0;
            }

            .dev-role {
                color: #667eea !important;
                font-weight: 600;
            }

            .qr-section {
                text-align: center;
            }

            .qr-container {
                position: relative;
                display: inline-block;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }

            .qr-container img {
                display: block;
                width: 150px;
                height: 150px;
            }

            .qr-overlay {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 8px;
                font-size: 0.75rem;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .qr-container:hover .qr-overlay {
                opacity: 1;
            }

            .qr-description {
                color: #6b7280;
                font-size: 0.85rem;
                margin-top: 8px;
            }

            .section-title {
                color: #1f2937;
                font-size: 1.2rem;
                font-weight: 600;
                margin: 0 0 20px 0;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .skills-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 16px;
                margin-bottom: 32px;
            }

            .skill-card {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 20px;
                background: #f9fafb;
                border-radius: 12px;
                border: 1px solid #e5e7eb;
                transition: all 0.3s ease;
            }

            .skill-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                border-color: #667eea;
            }

            .skill-icon {
                font-size: 2rem;
            }

            .skill-info h5 {
                color: #1f2937;
                margin: 0 0 4px 0;
                font-size: 1rem;
                font-weight: 600;
            }

            .skill-info p {
                color: #6b7280;
                margin: 0;
                font-size: 0.85rem;
            }

            .tech-tags {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 32px;
            }

            .tech-tag {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
                color: white;
            }

            .tech-tag.php { background: #777bb4; }
            .tech-tag.mysql { background: #4479a1; }
            .tech-tag.js { background: #f7df1e; color: #000; }
            .tech-tag.css { background: #1572b6; }
            .tech-tag.html { background: #e34c26; }
            .tech-tag.git { background: #f05032; }
            .tech-tag.node { background: #339933; }
            .tech-tag.csharp { background: #239120; }
            .tech-tag.kotlin { background: #7F52FF; }
            .tech-tag.swift { background: #FA7343; }

            .social-links {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 16px;
                margin-bottom: 32px;
            }

            .social-link {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 20px;
                background: #f9fafb;
                border-radius: 12px;
                border: 1px solid #e5e7eb;
                text-decoration: none;
                transition: all 0.3s ease;
            }

            .social-link:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }

            .social-link.github:hover { border-color: #777bb4; }
            .social-link.instagram:hover { border-color: #E4405F; }
            .social-link.behance:hover { border-color: #1769FF; }
            .social-link.email:hover { border-color: #ea4335; }

            .social-icon {
                font-size: 2rem;
            }

            .social-info {
                display: flex;
                flex-direction: column;
            }

            .social-name {
                color: #1f2937;
                font-weight: 600;
                font-size: 1rem;
            }

            .social-handle {
                color: #6b7280;
                font-size: 0.85rem;
            }

            .project-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }

            .project-card {
                background: #f9fafb;
                border-radius: 12px;
                border: 1px solid #e5e7eb;
                overflow: hidden;
                transition: all 0.3s ease;
            }

            .project-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }

            .project-image {
                height: 150px;
                overflow: hidden;
            }

            .project-image img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .project-info {
                padding: 16px;
            }

            .project-info h5 {
                color: #1f2937;
                margin: 0 0 8px 0;
                font-size: 1rem;
                font-weight: 600;
            }

            .project-info p {
                color: #6b7280;
                margin: 0 0 12px 0;
                font-size: 0.85rem;
                line-height: 1.4;
            }

            .project-tech {
                display: flex;
                gap: 6px;
            }

            .tech-mini {
                background: #e5e7eb;
                color: #374151;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 0.7rem;
                font-weight: 600;
            }

            .modal-footer {
                padding: 24px 32px;
                border-top: 1px solid #e5e7eb;
                background: #f9fafb;
                border-radius: 0 0 16px 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .footer-stats {
                display: flex;
                gap: 32px;
            }

            .stat-item {
                text-align: center;
            }

            .stat-number {
                display: block;
                color: #667eea;
                font-size: 1.5rem;
                font-weight: 700;
            }

            .stat-label {
                color: #6b7280;
                font-size: 0.85rem;
            }

            .footer-actions {
                display: flex;
                gap: 12px;
            }

            .btn-primary, .btn-secondary {
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                border: none;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }

            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.4);
            }

            .btn-secondary {
                background: #e5e7eb;
                color: #374151;
            }

            .btn-secondary:hover {
                background: #d1d5db;
            }

            /* Botón en navbar */
            .nav-dev-btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 0.9rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 6px;
                text-decoration: none;
            }

            .nav-dev-btn:hover {
                background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.4);
            }

            .dev-btn-icon {
                font-size: 1rem;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .modal-container {
                    margin: 10px;
                    max-height: calc(100vh - 20px);
                }

                .modal-header, .modal-content, .modal-footer {
                    padding: 20px;
                }

                .profile-header {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }

                .avatar-section {
                    flex-direction: column;
                    text-align: center;
                }

                .skills-grid, .social-links, .project-cards {
                    grid-template-columns: 1fr;
                }

                .modal-footer {
                    flex-direction: column;
                    gap: 20px;
                }

                .nav-dev-btn .dev-btn-text {
                    display: none;
                }
            }
        `;
        document.head.appendChild(estilo);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.modalDev = new ModalDesarrollador();
});
