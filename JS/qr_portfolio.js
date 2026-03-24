/**
 * Sistema de QR Code para Portafolio - Salud Juárez
 * Genera y muestra QR Codes dinámicamente para el portafolio del desarrollador
 * Versión: 1.0.0
 * Fecha: 2026-03-23
 */

class QRPortfolioSystem {
    constructor() {
        this.portfolioUrl = 'https://github.com/tu-usuario/portfolio'; // Reemplazar con URL real
        this.vCardData = null;
        this.modalQR = null;
        this.qrCode = null;
        this.init();
    }

    /**
     * Inicializar el sistema de QR
     */
    async init() {
        try {
            // Cargar librería QRCode.js
            await this.cargarQRCode();
            
            // Crear modal para QR
            this.crearModalQR();
            
            // Configurar V-Card del desarrollador
            this.configurarVCard();
            
            // Agregar botón QR al navbar
            this.agregarBotonQR();
            
        } catch (error) {
            console.error('Error inicializando sistema QR:', error);
        }
    }

    /**
     * Cargar librería QRCode.js dinámicamente
     */
    cargarQRCode() {
        return new Promise((resolve, reject) => {
            if (window.QRCode) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Crear modal para mostrar QR Code
     */
    crearModalQR() {
        const modalHTML = `
            <div id="modalQR" class="modal-qr">
                <div class="modal-qr-content">
                    <div class="modal-qr-header">
                        <h3 class="modal-qr-title">
                            <span class="qr-icon">📱</span>
                            Escanea para ver mi portafolio
                        </h3>
                        <button class="modal-qr-close" onclick="qrSystem.cerrarModalQR()">×</button>
                    </div>
                    
                    <div class="modal-qr-body">
                        <div class="qr-container">
                            <div id="qrCodeElement" class="qr-code"></div>
                            <div class="qr-loading">
                                <div class="qr-spinner"></div>
                                <p>Generando código QR...</p>
                            </div>
                        </div>
                        
                        <div class="qr-info">
                            <div class="qr-description">
                                <h4>👨‍💻 Sobre el Desarrollador</h4>
                                <p><strong>Nombre:</strong> [Tu Nombre Completo]</p>
                                <p><strong>Rol:</strong> Lead Developer & UI/UX Designer</p>
                                <p><strong>Formación:</strong> Estudiante de Diseño Digital (DDMI)</p>
                                <p><strong>Especialidades:</strong> Ilustración Digital, Desarrollo Full-stack</p>
                            </div>
                            
                            <div class="qr-actions">
                                <button class="btn-qr btn-qr-download" onclick="qrSystem.descargarQR()">
                                    <span class="btn-icon">⬇️</span>
                                    Descargar QR
                                </button>
                                <button class="btn-qr btn-qr-copy" onclick="qrSystem.copiarURL()">
                                    <span class="btn-icon">📋</span>
                                    Copiar URL
                                </button>
                            </div>
                            
                            <div class="qr-links">
                                <a href="https://github.com/tu-usuario" target="_blank" class="qr-link">
                                    <span class="link-icon">🐙</span>
                                    GitHub
                                </a>
                                <a href="https://linkedin.com/in/tu-perfil" target="_blank" class="qr-link">
                                    <span class="link-icon">💼</span>
                                    LinkedIn
                                </a>
                                <a href="mailto:tu-email@dominio.com" class="qr-link">
                                    <span class="link-icon">📧</span>
                                    Email
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-qr-footer">
                        <p class="qr-footer-text">
                            <span class="footer-icon">✨</span>
                            Desarrollado con ❤️ para Salud Juárez
                        </p>
                    </div>
                </div>
                <div class="modal-qr-backdrop" onclick="qrSystem.cerrarModalQR()"></div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modalQR = document.getElementById('modalQR');
        
        // Agregar estilos
        this.agregarEstilosModal();
    }

    /**
     * Configurar datos V-Card del desarrollador
     */
    configurVCard() {
        this.vCardData = {
            // Datos básicos
            fn: '[Tu Nombre Completo]',
            n: {
                familyName: '[Apellido]',
                givenName: '[Nombre]',
                middleName: '[Segundo Nombre]'
            },
            
            // Rol y organización
            org: 'Salud Juárez',
            title: 'Lead Developer & UI/UX Designer',
            role: 'Full-stack Developer',
            
            // Contacto
            tel: '+52 656 123 4567',
            email: 'tu-email@dominio.com',
            url: this.portfolioUrl,
            
            // Dirección
            adr: {
                streetAddress: 'Calle Ejemplo #123',
                locality: 'Ciudad Juárez',
                region: 'Chihuahua',
                postalCode: '32310',
                country: 'México'
            },
            
            // Redes sociales
            social: {
                github: 'https://github.com/tu-usuario',
                linkedin: 'https://linkedin.com/in/tu-perfil',
                portfolio: this.portfolioUrl
            },
            
            // Habilidades
            categories: [
                'Diseño Digital',
                'Desarrollo Web',
                'Ilustración Digital',
                'UI/UX Design',
                'PHP',
                'MySQL',
                'JavaScript',
                'CSS3'
            ],
            
            // Notas
            note: 'Especialista en desarrollo de sistemas web saludables con enfoque en UX/UI y diseño de interfaces modernas.'
        };
    }

    /**
     * Agregar botón QR al navbar
     */
    agregarBotonQR() {
        const navLinks = document.querySelector('.nav-links');
        if (navLinks) {
            const botonQR = document.createElement('li');
            botonQR.innerHTML = `
                <button class="nav-qr-btn" onclick="qrSystem.mostrarModalQR()" title="Ver portafolio del desarrollador">
                    <span class="qr-btn-icon">📱</span>
                    <span class="qr-btn-text">Portafolio</span>
                </button>
            `;
            navLinks.appendChild(botonQR);
        }
    }

    /**
     * Mostrar modal QR
     */
    async mostrarModalQR() {
        if (!this.modalQR) return;
        
        // Mostrar modal
        this.modalQR.classList.add('modal-qr--active');
        
        // Generar QR Code
        await this.generarQRCode();
        
        // Animar entrada
        setTimeout(() => {
            this.modalQR.querySelector('.modal-qr-content').classList.add('modal-qr-content--active');
        }, 100);
    }

    /**
     * Cerrar modal QR
     */
    cerrarModalQR() {
        if (!this.modalQR) return;
        
        // Animar salida
        this.modalQR.querySelector('.modal-qr-content').classList.remove('modal-qr-content--active');
        
        setTimeout(() => {
            this.modalQR.classList.remove('modal-qr--active');
        }, 300);
    }

    /**
     * Generar QR Code
     */
    async generarQRCode() {
        try {
            const qrElement = document.getElementById('qrCodeElement');
            const loading = document.querySelector('.qr-loading');
            
            // Ocultar loading cuando se genere
            loading.style.display = 'flex';
            qrElement.innerHTML = '';
            
            // Esperar a que QRCode esté disponible
            if (!window.QRCode) {
                await this.cargarQRCode();
            }
            
            // Generar V-Card URL
            const vCardURL = this.generarVCardURL();
            
            // Crear QR Code
            this.qrCode = new QRCode(qrElement, {
                text: vCardURL,
                width: 256,
                height: 256,
                colorDark: '#ffffff',
                colorLight: '#2a2a2a',
                correctLevel: QRCode.CorrectLevel.H,
                
                // Opciones de diseño
                quietZone: 2,
                logo: {
                    src: 'IMG/UPLOADS/developer-logo.png', // Logo del desarrollador
                    width: 40,
                    height: 40,
                    excavate: true
                }
            });
            
            // Ocultar loading
            setTimeout(() => {
                loading.style.display = 'none';
                qrElement.style.display = 'block';
            }, 1000);
            
        } catch (error) {
            console.error('Error generando QR:', error);
            document.querySelector('.qr-loading').innerHTML = `
                <p style="color: #e74c3c;">❌ Error al generar QR Code</p>
            `;
        }
    }

    /**
     * Generar URL para V-Card
     */
    generarVCardURL() {
        // Crear V-Card string
        let vCard = 'BEGIN:VCARD\nVERSION:3.0\n';
        vCard += `FN:${this.vCardData.fn}\n`;
        vCard += `N:${this.vCardData.n.familyName};${this.vCardData.n.givenName};${this.vCardData.n.middleName};;\n`;
        vCard += `ORG:${this.vCardData.org}\n`;
        vCard += `TITLE:${this.vCardData.title}\n`;
        vCard += `ROLE:${this.vCardData.role}\n`;
        vCard += `TEL:${this.vCardData.tel}\n`;
        vCard += `EMAIL:${this.vCardData.email}\n`;
        vCard += `URL:${this.vCardData.url}\n`;
        vCard += `ADR:;;${this.vCardData.adr.streetAddress};${this.vCardData.adr.locality};${this.vCardData.adr.region};${this.vCardData.adr.postalCode};${this.vCardData.adr.country}\n`;
        vCard += `CATEGORIES:${this.vCardData.categories.join(',')}\n`;
        vCard += `NOTE:${this.vCardData.note}\n`;
        vCard += 'END:VCARD';
        
        // Codificar para URL
        return 'data:text/vcard;charset=utf-8,' + encodeURIComponent(vCard);
    }

    /**
     * Descargar QR Code como imagen
     */
    descargarQR() {
        try {
            const canvas = document.querySelector('#qrCodeElement canvas');
            if (canvas) {
                const link = document.createElement('a');
                link.download = 'portfolio-qr-code.png';
                link.href = canvas.toDataURL();
                link.click();
                
                this.mostrarNotificación('QR Code descargado', 'success');
            }
        } catch (error) {
            console.error('Error descargando QR:', error);
            this.mostrarNotificación('Error al descargar QR Code', 'error');
        }
    }

    /**
     * Copiar URL al portapapeles
     */
    async copiarURL() {
        try {
            await navigator.clipboard.writeText(this.portfolioUrl);
            this.mostrarNotificación('URL copiada al portapapeles', 'success');
        } catch (error) {
            console.error('Error copiando URL:', error);
            this.mostrarNotificación('Error al copiar URL', 'error');
        }
    }

    /**
     * Mostrar notificación
     */
    mostrarNotificación(mensaje, tipo) {
        const notificacion = document.createElement('div');
        notificacion.className = `qr-notificacion qr-notificacion--${tipo}`;
        notificacion.innerHTML = `
            <span class="notificacion-icon">${tipo === 'success' ? '✅' : '❌'}</span>
            <span class="notificacion-text">${mensaje}</span>
        `;
        
        document.body.appendChild(notificacion);
        
        setTimeout(() => notificacion.classList.add('qr-notificacion--show'), 100);
        setTimeout(() => {
            notificacion.classList.remove('qr-notificacion--show');
            setTimeout(() => notificacion.remove(), 300);
        }, 3000);
    }

    /**
     * Agregar estilos del modal
     */
    agregarEstilosModal() {
        const estilo = document.createElement('style');
        estilo.textContent = `
            /* Modal QR Code */
            .modal-qr {
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

            .modal-qr--active {
                display: flex;
            }

            .modal-qr-backdrop {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.8);
                backdrop-filter: blur(5px);
            }

            .modal-qr-content {
                position: relative;
                background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
                border: 1px solid #444;
                border-radius: 16px;
                max-width: 600px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                transform: scale(0.9) translateY(20px);
                opacity: 0;
                transition: all 0.3s ease;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            }

            .modal-qr-content--active {
                transform: scale(1) translateY(0);
                opacity: 1;
            }

            .modal-qr-header {
                padding: 20px;
                border-bottom: 1px solid #444;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .modal-qr-title {
                color: #ffffff;
                font-size: 1.2rem;
                font-weight: 600;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .qr-icon {
                font-size: 1.5rem;
            }

            .modal-qr-close {
                background: none;
                border: none;
                color: #999;
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

            .modal-qr-close:hover {
                background: rgba(231, 76, 60, 0.2);
                color: #e74c3c;
            }

            .modal-qr-body {
                padding: 20px;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }

            .qr-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .qr-code {
                background: white;
                padding: 20px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                display: none;
            }

            .qr-loading {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 40px;
                color: #999;
            }

            .qr-spinner {
                width: 40px;
                height: 40px;
                border: 3px solid #444;
                border-top: 3px solid #27ae60;
                border-radius: 50%;
                animation: qr-spin 1s linear infinite;
                margin-bottom: 15px;
            }

            @keyframes qr-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .qr-info {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            .qr-description h4 {
                color: #27ae60;
                margin: 0 0 10px 0;
                font-size: 1rem;
            }

            .qr-description p {
                color: #b0b0b0;
                margin: 5px 0;
                font-size: 0.9rem;
            }

            .qr-actions {
                display: flex;
                gap: 10px;
            }

            .btn-qr {
                flex: 1;
                padding: 10px 15px;
                border: none;
                border-radius: 8px;
                font-size: 0.9rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
            }

            .btn-qr-download {
                background: linear-gradient(135deg, #27ae60, #2ecc71);
                color: white;
            }

            .btn-qr-download:hover {
                background: linear-gradient(135deg, #229954, #27ae60);
                transform: translateY(-2px);
            }

            .btn-qr-copy {
                background: rgba(255, 255, 255, 0.1);
                color: white;
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .btn-qr-copy:hover {
                background: rgba(52, 152, 219, 0.3);
                border-color: #3498db;
            }

            .qr-links {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .qr-link {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #3498db;
                text-decoration: none;
                padding: 8px 12px;
                border-radius: 6px;
                transition: all 0.3s ease;
            }

            .qr-link:hover {
                background: rgba(52, 152, 219, 0.2);
                color: #5dade2;
            }

            .modal-qr-footer {
                padding: 15px 20px;
                border-top: 1px solid #444;
                text-align: center;
            }

            .qr-footer-text {
                color: #999;
                font-size: 0.8rem;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
            }

            /* Botón QR en navbar */
            .nav-qr-btn {
                background: linear-gradient(135deg, #9b59b6, #8e44ad);
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

            .nav-qr-btn:hover {
                background: linear-gradient(135deg, #8e44ad, #7d3c98);
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(155, 89, 182, 0.4);
            }

            .qr-btn-icon {
                font-size: 1rem;
            }

            /* Notificaciones QR */
            .qr-notificacion {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 10001;
                display: flex;
                align-items: center;
                gap: 8px;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            }

            .qr-notificacion--success {
                background: #27ae60;
            }

            .qr-notificacion--error {
                background: #e74c3c;
            }

            .qr-notificacion--show {
                transform: translateX(0);
            }

            /* Responsive */
            @media (max-width: 768px) {
                .modal-qr-body {
                    grid-template-columns: 1fr;
                }
                
                .nav-qr-btn .qr-btn-text {
                    display: none;
                }
                
                .nav-qr-btn {
                    padding: 8px 12px;
                }
            }
        `;
        document.head.appendChild(estilo);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.qrSystem = new QRPortfolioSystem();
});
