/**
 * Main Interactions - Salud Juárez
 * Script centralizado para interacciones principales
 * Versión: 2.0.0
 * Fecha: 26 de Marzo de 2026
 */

// Clase principal para manejar interacciones
class SaludJuarezInteractions {
    constructor() {
        this.init();
    }

    init() {
        this.setupModalDesarrollador();
        this.setupLazyLoading();
        this.setupSmoothScroll();
        this.setupConnectionDetection();
        this.setupPerformanceMonitoring();
        this.setupErrorHandling();
        
        console.log('🚀 Salud Juárez - Interacciones principales inicializadas');
    }

    // Modal del Desarrollador
    setupModalDesarrollador() {
        const openBtn = document.getElementById('btnOpenDevModal');
        const closeBtn = document.getElementById('btnCloseModal');
        const modal = document.getElementById('devModal');
        const downloadBtn = document.getElementById('btnDownloadCV');

        if (openBtn && modal) {
            openBtn.addEventListener('click', () => this.openModal(modal));
        }

        if (closeBtn && modal) {
            closeBtn.addEventListener('click', () => this.closeModal(modal));
        }

        if (downloadBtn) {
            downloadBtn.addEventListener('click', () => this.downloadCV());
        }

        // Cerrar modal al hacer clic fuera
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                }
            });
        }

        // Cerrar con tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
                this.closeModal(modal);
            }
        });
    }

    openModal(modal) {
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Cargar QR Code dinámicamente
            this.loadQRCode();
            
            // Animación de entrada
            this.animateModalEntrance(modal);
        }
    }

    closeModal(modal) {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    animateModalEntrance(modal) {
        const container = modal.querySelector('.dev-modal-container');
        if (container) {
            container.style.opacity = '0';
            container.style.transform = 'scale(0.9) translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.3s ease';
                container.style.opacity = '1';
                container.style.transform = 'scale(1) translateY(0)';
            }, 50);
        }
    }

    loadQRCode() {
        const qrImage = document.getElementById('qrCodeImage');
        if (qrImage) {
            const timestamp = Date.now();
            const portfolioURL = 'https://github.com/JLuigi400';
            qrImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(portfolioURL)}&format=png&color=000000&bgcolor=FFFFFF&t=${timestamp}`;
        }
    }

    downloadCV() {
        const cvContent = `
CV - Jorge Anibal Espinosa Perales
================================

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
- Arte Digital e Ilustración: Estilos Anime, Chibi, Flat Design
- Diseño UI/UX: Maquetación e interfaces (Flat Design)
- Desarrollo de Videojuegos: Desarrollo indie (Novato)

Stack Tecnológico:
- Principales: PHP, MySQL, JavaScript, HTML, CSS, Node.JS, C, C#
- En práctica/Básicos: Kotlin, Swift

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

    // Lazy Loading para imágenes
    setupLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        if (images.length === 0) return;
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }

    // Navegación suave
    setupSmoothScroll() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Detección de conexión
    setupConnectionDetection() {
        const indicator = this.createConnectionIndicator();
        
        window.addEventListener('online', () => this.updateConnectionStatus(indicator, true));
        window.addEventListener('offline', () => this.updateConnectionStatus(indicator, false));
    }

    createConnectionIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'connection-indicator';
        indicator.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 15px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            display: none;
            transition: all 0.3s ease;
        `;
        document.body.appendChild(indicator);
        return indicator;
    }

    updateConnectionStatus(indicator, online) {
        indicator.textContent = online ? '🟢 Conectado' : '🔴 Sin conexión';
        indicator.style.background = online ? '#27ae60' : '#e74c3c';
        indicator.style.display = 'block';
        
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 3000);
    }

    // Monitoreo de rendimiento
    setupPerformanceMonitoring() {
        window.addEventListener('load', () => {
            const loadTime = performance.now();
            console.log(`⏱️ Tiempo de carga: ${loadTime.toFixed(2)}ms`);
            
            // Enviar a analytics si está disponible
            if (typeof gtag !== 'undefined') {
                gtag('event', 'page_load_time', {
                    custom_parameter: loadTime
                });
            }
        });
    }

    // Manejo de errores
    setupErrorHandling() {
        window.addEventListener('error', (e) => {
            console.error('❌ Error en la aplicación:', e.error);
            
            // Enviar a servicio de análisis si está disponible
            if (typeof gtag !== 'undefined') {
                gtag('event', 'exception', {
                    description: e.error.message,
                    fatal: false
                });
            }
        });

        window.addEventListener('unhandledrejection', (e) => {
            console.error('❌ Promesa rechazada:', e.reason);
            e.preventDefault();
        });
    }

    // Prefetch de recursos críticos
    prefetchCriticalResources() {
        const resources = [
            'IMG/UPLOADS/hero-bg.jpg',
            'IMG/UPLOADS/placeholder-restaurant.jpg'
        ];
        
        resources.forEach(src => {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.as = 'image';
            link.href = src;
            document.head.appendChild(link);
        });
    }

    // Animaciones al hacer scroll
    setupScrollAnimations() {
        const animatedElements = document.querySelectorAll('.info-card, .skill-card, .social-link');
        
        if (animatedElements.length === 0) return;
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1
        });
        
        animatedElements.forEach(element => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(30px)';
            element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(element);
        });
    }

    // Utilidad para mostrar notificaciones
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            max-width: 300px;
            word-wrap: break-word;
        `;
        
        // Colores según tipo
        switch(type) {
            case 'success':
                notification.style.background = '#27ae60';
                break;
            case 'error':
                notification.style.background = '#e74c3c';
                break;
            case 'warning':
                notification.style.background = '#f39c12';
                break;
            default:
                notification.style.background = '#3498db';
        }
        
        document.body.appendChild(notification);
        
        // Animación de entrada
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto-eliminación
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    // Inicialización de componentes adicionales
    initAdditionalComponents() {
        this.prefetchCriticalResources();
        this.setupScrollAnimations();
    }
}

// Clase para manejo del mapa
class SaludJuarezMap {
    constructor() {
        this.map = null;
        this.markers = [];
        this.layers = {
            restaurantes: true,
            proveedores: false,
            premium: false
        };
    }

    init(containerId) {
        if (typeof L === 'undefined') {
            console.warn('❌ Leaflet no está cargado');
            return;
        }

        const container = document.getElementById(containerId);
        if (!container) {
            console.warn('❌ Contenedor del mapa no encontrado');
            return;
        }

        // Coordenadas centrales de Ciudad Juárez
        this.map = L.map(containerId).setView([31.6904, -106.4245], 12);
        
        // Capa base
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);
        
        this.loadRestaurantData();
        this.setupMapControls();
        console.log('🗺️ Mapa inicializado');
    }

    loadRestaurantData() {
        // Datos de ejemplo
        const restaurants = [
            {
                id: 1,
                nombre: "Restaurante Saludable 1",
                lat: 31.6904,
                lng: -106.4245,
                direccion: "Av. Principal 123",
                certificacion: "Oro"
            },
            {
                id: 2,
                nombre: "Restaurante Saludable 2",
                lat: 31.7000,
                lng: -106.4300,
                direccion: "Calle Secundaria 456",
                certificacion: "Plata"
            }
        ];
        
        restaurants.forEach(restaurant => {
            this.addRestaurantMarker(restaurant);
        });
    }

    addRestaurantMarker(restaurant) {
        const icon = this.createRestaurantIcon(restaurant.certificacion);
        
        const marker = L.marker([restaurant.lat, restaurant.lng], { icon })
            .addTo(this.map)
            .bindPopup(`
                <div class="popup-restaurante">
                    <h4>${restaurant.nombre}</h4>
                    <p><strong>Dirección:</strong> ${restaurant.direccion}</p>
                    <p><strong>Certificación:</strong> ${restaurant.certificacion}</p>
                    <button class="btn-popup" onclick="window.location.href='DIRECCIONES/ver_menu.php?id=${restaurant.id}'">
                        Ver Menú
                    </button>
                </div>
            `);
        
        this.markers.push(marker);
    }

    createRestaurantIcon(certificacion) {
        let color = '#3498db';
        
        switch(certificacion) {
            case 'Oro':
                color = '#f39c12';
                break;
            case 'Plata':
                color = '#95a5a6';
                break;
            case 'Bronce':
                color = '#cd7f32';
                break;
        }
        
        return L.divIcon({
            html: `<div style="
                background-color: ${color};
                width: 30px;
                height: 30px;
                border-radius: 50%;
                border: 3px solid white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                font-size: 12px;
            ">🍽️</div>`,
            className: 'marcador-restaurante',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15]
        });
    }

    setupMapControls() {
        const controlsHTML = `
            <div class="mapa-controls">
                <button class="mapa-btn active" data-layer="restaurantes">
                    🍽️ Restaurantes
                </button>
                <button class="mapa-btn" data-layer="proveedores">
                    📦 Proveedores
                </button>
                <button class="mapa-btn" data-layer="premium">
                    ⭐ Solo Premium
                </button>
            </div>
        `;
        
        const mapContainer = this.map.getContainer();
        mapContainer.insertAdjacentHTML('beforebegin', controlsHTML);
        
        // Event listeners
        document.querySelectorAll('.mapa-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const layer = e.target.dataset.layer;
                this.toggleLayer(layer);
                
                // Actualizar botones
                document.querySelectorAll('.mapa-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
            });
        });
    }

    toggleLayer(layer) {
        this.layers[layer] = !this.layers[layer];
        console.log(`Capa ${layer}: ${this.layers[layer] ? 'activada' : 'desactivada'}`);
        // Aquí iría la lógica para mostrar/ocultar capas
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar interacciones principales
    const interactions = new SaludJuarezInteractions();
    interactions.initAdditionalComponents();
    
    // Inicializar mapa si existe el contenedor
    if (document.getElementById('mapaSalud')) {
        const mapa = new SaludJuarezMap();
        
        // Esperar a que Leaflet esté disponible
        if (typeof L !== 'undefined') {
            mapa.init('mapaSalud');
        } else {
            // Cargar Leaflet dinámicamente
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = () => mapa.init('mapaSalud');
            document.head.appendChild(script);
            
            // Cargar CSS de Leaflet
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);
        }
    }
    
    // Hacer disponible globalmente para uso externo
    window.SaludJuarez = {
        interactions,
        showNotification: (message, type) => interactions.showNotification(message, type)
    };
    
    console.log('✅ Salud Juárez - Sistema completamente inicializado');
});
