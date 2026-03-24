/**
 * Carrusel Dinámico - Salud Juárez
 * Implementación con Swiper.js para restaurantes con certificación Oro
 * Versión: 1.0.0
 * Fecha: 2026-03-23
 */

class CarruselSaludJuarez {
    constructor() {
        this.swiper = null;
        this.restaurantesOro = [];
        this.init();
    }

    /**
     * Inicializar el carrusel
     */
    async init() {
        try {
            // Cargar Swiper.js
            await this.cargarSwiper();
            
            // Obtener restaurantes con certificación Oro
            await this.cargarRestaurantesOro();
            
            // Inicializar Swiper
            this.inicializarSwiper();
            
            // Aplicar tema Industrial Dark
            this.aplicarTemaIndustrial();
            
        } catch (error) {
            console.error('Error al inicializar carrusel:', error);
            this.mostrarError();
        }
    }

    /**
     * Cargar Swiper.js dinámicamente
     */
    cargarSwiper() {
        return new Promise((resolve, reject) => {
            if (window.Swiper) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js';
            script.onload = () => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css';
                document.head.appendChild(link);
                resolve();
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Cargar restaurantes con certificación Oro desde la base de datos
     */
    async cargarRestaurantesOro() {
        try {
            const response = await fetch('PHP/procesar_carrusel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'accion=cargar_restaurantes_oro'
            });

            if (!response.ok) throw new Error('Error al cargar restaurantes');
            
            const data = await response.json();
            this.restaurantesOro = data.restaurantes || [];
            
        } catch (error) {
            console.error('Error cargando restaurantes:', error);
            // Usar datos de muestra si hay error
            this.restaurantesOro = this.getDatosMuestra();
        }
    }

    /**
     * Datos de muestra para el carrusel
     */
    getDatosMuestra() {
        return [
            {
                id_res: 1,
                nombre_res: "Ensaladas el Oasis",
                logo_res: "IMG/UPLOADS/RESTAURANTES/default_logo.png",
                banner_res: "IMG/UPLOADS/RESTAURANTES/default_banner.png",
                certificacion: "oro",
                descripcion_res: "Especialistas en ensaladas frescas y bowls nutritivos"
            },
            {
                id_res: 2,
                nombre_res: "Vida Verde",
                logo_res: "IMG/UPLOADS/RESTAURANTES/default_logo.png",
                banner_res: "IMG/UPLOADS/RESTAURANTES/default_banner.png",
                certificacion: "oro",
                descripcion_res: "Cocina vegana y vegetariana con ingredientes orgánicos"
            },
            {
                id_res: 3,
                nombre_res: "NutriBowl",
                logo_res: "IMG/UPLOADS/RESTAURANTES/default_logo.png",
                banner_res: "IMG/UPLOADS/RESTAURANTES/default_banner.png",
                certificacion: "oro",
                descripcion_res: "Bowls personalizados y jugos naturales"
            },
            {
                id_res: 4,
                nombre_res: "Green Kitchen",
                logo_res: "IMG/UPLOADS/RESTAURANTES/default_logo.png",
                banner_res: "IMG/UPLOADS/RESTAURANTES/default_banner.png",
                certificacion: "oro",
                descripcion_res: "Comida saludable con sabor internacional"
            },
            {
                id_res: 5,
                nombre_res: "Sano y Sabroso",
                logo_res: "IMG/UPLOADS/RESTAURANTES/default_logo.png",
                banner_res: "IMG/UPLOADS/RESTAURANTES/default_banner.png",
                certificacion: "oro",
                descripcion_res: "Platillos balanceados sin sacrificar el sabor"
            }
        ];
    }

    /**
     * Inicializar Swiper
     */
    inicializarSwiper() {
        // Generar slides HTML
        const slidesHTML = this.generarSlides();
        document.getElementById('swiper-wrapper').innerHTML = slidesHTML;

        // Inicializar Swiper
        this.swiper = new Swiper('.swiper', {
            // Configuración principal
            direction: 'horizontal',
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            
            // Efectos y animación
            effect: 'coverflow',
            coverflowEffect: {
                rotate: 50,
                stretch: 0,
                depth: 100,
                modifier: 1,
                slideShadows: true,
            },
            
            // Navegación
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            
            // Paginación
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            
            // Responsive
            breakpoints: {
                320: {
                    slidesPerView: 1,
                    spaceBetween: 20
                },
                640: {
                    slidesPerView: 2,
                    spaceBetween: 30
                },
                1024: {
                    slidesPerView: 3,
                    spaceBetween: 40
                },
                1440: {
                    slidesPerView: 4,
                    spaceBetween: 50
                }
            },
            
            // Lazy loading
            lazy: {
                loadPrevNext: true,
                loadPrevNextAmount: 2,
            },
            
            // Eventos
            on: {
                init: () => {
                    console.log('Carrusel inicializado con', this.restaurantesOro.length, 'restaurantes');
                },
                slideChange: (swiper) => {
                    this.actualizarSlideActivo(swiper.activeIndex);
                }
            }
        });
    }

    /**
     * Generar HTML para los slides
     */
    generarSlides() {
        return this.restaurantesOro.map((restaurante, index) => `
            <div class="swiper-slide" data-restaurante-id="${restaurante.id_res}">
                <div class="carrusel-slide">
                    <!-- Imagen del restaurante -->
                    <div class="carrusel-imagen">
                        <img src="${restaurante.banner_res}" 
                             alt="${restaurante.nombre_res}" 
                             loading="lazy"
                             onerror="this.src='IMG/UPLOADS/RESTAURANTES/default_banner.png'">
                        <div class="carrusel-overlay"></div>
                    </div>
                    
                    <!-- Contenido del slide -->
                    <div class="carrusel-contenido">
                        <!-- Badge de certificación -->
                        <div class="badge-certificacion badge-oro">
                            <span class="badge-icon">🏆</span>
                            <span class="badge-text">Certificación Oro</span>
                        </div>
                        
                        <!-- Información del restaurante -->
                        <div class="restaurante-info">
                            <h3 class="restaurante-nombre">${restaurante.nombre_res}</h3>
                            <p class="restaurante-descripcion">${restaurante.descripcion_res}</p>
                        </div>
                        
                        <!-- Botón de acción -->
                        <div class="carrusel-acciones">
                            <button class="btn-carrusel btn-ver-mas" 
                                    onclick="verRestaurante(${restaurante.id_res})">
                                <span>Ver Más</span>
                                <span class="btn-icon">→</span>
                            </button>
                            <button class="btn-carrusel btn-favorito" 
                                    onclick="agregarFavorito(${restaurante.id_res})">
                                <span class="btn-icon">❤️</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Indicador de slide activo -->
                    <div class="slide-indicator" data-index="${index}"></div>
                </div>
            </div>
        `).join('');
    }

    /**
     * Aplicar tema Industrial Dark
     */
    aplicarTemaIndustrial() {
        const estilo = document.createElement('style');
        estilo.textContent = `
            /* Carrusel Industrial Dark */
            .carrusel-container {
                background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
                border: 1px solid #333;
                border-radius: 16px;
                padding: 20px;
                margin: 30px 0;
                position: relative;
                overflow: hidden;
            }

            .carrusel-container::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 2px;
                background: linear-gradient(90deg, #27ae60, #f39c12, #e74c3c);
                animation: slideGradient 3s ease-in-out infinite;
            }

            @keyframes slideGradient {
                0%, 100% { transform: translateX(-100%); }
                50% { transform: translateX(100%); }
            }

            .swiper {
                padding: 20px 0;
            }

            .carrusel-slide {
                position: relative;
                height: 400px;
                border-radius: 12px;
                overflow: hidden;
                background: #2a2a2a;
                border: 1px solid #444;
                transition: all 0.3s ease;
            }

            .carrusel-slide:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
                border-color: #27ae60;
            }

            .carrusel-imagen {
                position: relative;
                height: 200px;
                overflow: hidden;
            }

            .carrusel-imagen img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.5s ease;
            }

            .carrusel-slide:hover .carrusel-imagen img {
                transform: scale(1.1);
            }

            .carrusel-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(to bottom, 
                    rgba(0,0,0,0.2) 0%, 
                    rgba(0,0,0,0.6) 100%);
            }

            .carrusel-contenido {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                padding: 20px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                z-index: 2;
            }

            .badge-certificacion {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                align-self: flex-start;
                backdrop-filter: blur(10px);
            }

            .badge-oro {
                background: linear-gradient(135deg, #f39c12, #f1c40f);
                color: #2c3e50;
                border: 1px solid #f39c12;
                box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
            }

            .badge-icon {
                font-size: 14px;
            }

            .restaurante-info {
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .restaurante-nombre {
                color: #ffffff;
                font-size: 1.4rem;
                font-weight: 700;
                margin: 0 0 8px 0;
                text-shadow: 0 2px 4px rgba(0,0,0,0.5);
            }

            .restaurante-descripcion {
                color: #b0b0b0;
                font-size: 0.9rem;
                line-height: 1.4;
                margin: 0;
                text-shadow: 0 1px 2px rgba(0,0,0,0.5);
            }

            .carrusel-acciones {
                display: flex;
                gap: 10px;
                justify-content: space-between;
            }

            .btn-carrusel {
                padding: 10px 16px;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .btn-ver-mas {
                background: linear-gradient(135deg, #27ae60, #2ecc71);
                color: white;
                flex: 1;
            }

            .btn-ver-mas:hover {
                background: linear-gradient(135deg, #229954, #27ae60);
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
            }

            .btn-favorito {
                background: rgba(255, 255, 255, 0.1);
                color: white;
                border: 1px solid rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(10px);
            }

            .btn-favorito:hover {
                background: rgba(231, 76, 60, 0.8);
                border-color: #e74c3c;
                transform: scale(1.1);
            }

            .slide-indicator {
                position: absolute;
                bottom: 10px;
                right: 10px;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transition: all 0.3s ease;
            }

            .slide-indicator.active {
                background: #27ae60;
                width: 24px;
                border-radius: 4px;
            }

            /* Navegación Swiper personalizada */
            .swiper-button-next,
            .swiper-button-prev {
                color: #27ae60 !important;
                background: rgba(0, 0, 0, 0.5);
                width: 40px;
                height: 40px;
                border-radius: 50%;
                border: 1px solid #27ae60;
            }

            .swiper-button-next:hover,
            .swiper-button-prev:hover {
                background: rgba(39, 174, 96, 0.2);
            }

            .swiper-pagination-bullet {
                background: rgba(255, 255, 255, 0.5) !important;
                opacity: 1 !important;
            }

            .swiper-pagination-bullet-active {
                background: #27ae60 !important;
            }
        `;
        document.head.appendChild(estilo);
    }

    /**
     * Actualizar slide activo
     */
    actualizarSlideActivo(activeIndex) {
        document.querySelectorAll('.slide-indicator').forEach((indicator, index) => {
            indicator.classList.toggle('active', index === activeIndex);
        });
    }

    /**
     * Mostrar error si falla la carga
     */
    mostrarError() {
        const container = document.querySelector('.carrusel-container');
        if (container) {
            container.innerHTML = `
                <div class="carrusel-error">
                    <h3>🔄 No se pudieron cargar los restaurantes</h3>
                    <p>Por favor, intenta recargar la página</p>
                </div>
            `;
        }
    }

    /**
     * Ver restaurante
     */
    verRestaurante(idRestaurante) {
        window.location.href = `DIRECCIONES/ver_menu.php?id=${idRestaurante}`;
    }

    /**
     * Agregar a favoritos
     */
    agregarFavorito(idRestaurante) {
        // Implementar lógica de favoritos
        fetch('PHP/procesar_favoritos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=agregar&id_res=${idRestaurante}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.mostrarNotificación('¡Agregado a favoritos!', 'success');
            } else {
                this.mostrarNotificación(data.message || 'Error al agregar a favoritos', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.mostrarNotificación('Error de conexión', 'error');
        });
    }

    /**
     * Mostrar notificación
     */
    mostrarNotificación(mensaje, tipo) {
        // Implementar sistema de notificaciones
        const notificacion = document.createElement('div');
        notificacion.className = `notificacion notificacion-${tipo}`;
        notificacion.textContent = mensaje;
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            ${tipo === 'success' ? 'background: #27ae60;' : 'background: #e74c3c;'}
        `;
        
        document.body.appendChild(notificacion);
        
        setTimeout(() => {
            notificacion.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            notificacion.style.transform = 'translateX(100%)';
            setTimeout(() => notificacion.remove(), 300);
        }, 3000);
    }
}

// Funciones globales para los botones
function verRestaurante(idRestaurante) {
    window.carrusel.verRestaurante(idRestaurante);
}

function agregarFavorito(idRestaurante) {
    window.carrusel.agregarFavorito(idRestaurante);
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.carrusel = new CarruselSaludJuarez();
});
