/**
 * Carrusel Unificado - Salud Juárez
 * Sistema de clonación desde plantilla con ciclo infinito
 * Versión: 3.0.0
 * Fecha: 27 de Marzo de 2026
 */

class CarruselUnificado {
    constructor() {
        this.container = document.querySelector('.sj-restaurantes');
        if (!this.container) {
            console.error('Contenedor del carrusel no encontrado');
            return;
        }

        // Elementos del DOM
        this.track = document.getElementById('carrusel-track');
        this.plantilla = document.querySelector('.slide-plantilla');
        this.controles = {
            prev: this.container.querySelector('.carrusel-control.prev'),
            next: this.container.querySelector('.carrusel-control.next')
        };
        this.indicadoresContainer = document.getElementById('carrusel-indicadores');

        // Estado del carrusel
        this.currentIndex = 0;
        this.slides = [];
        this.totalSlides = 0;
        this.slidesPorVista = this.getSlidesPorVista();
        this.autoplayInterval = null;
        this.isAnimating = false;

        // Configuración
        this.config = {
            espacio: 20,
            autoplay: true,
            velocidadAutoplay: 5000,
            velocidadTransicion: 500,
            infinito: true,
            pausarAlHover: true
        };

        // Datos de restaurantes (simulados - en producción vendrían de API)
        this.restaurantesData = [
            {
                id: 1,
                nombre: 'Restaurante Verde',
                descripcion: 'Cocina orgánica y fresca con ingredientes locales',
                banner: 'IMG/UPLOADS/RESTAURANTES/Restaurante_Verde_01.jpeg',
                logo: 'IMG/LOGOTIPOS/RESTAURANTE/logo_restaurante_verde.png',
                certificacion: 'Oro',
                rating: 4.8,
                platillos: 12,
                precio: 120.00,
                enlace: '#restaurante1'
            },
            {
                id: 2,
                nombre: 'Sabor Saludable',
                descripcion: 'Platillos nutritivos deliciosos, balanceados por expertos',
                banner: 'IMG/UPLOADS/RESTAURANTES/Sabor_Saludable_02.png',
                logo: 'IMG/LOGOTIPOS/RESTAURANTE/logo_sabor_saludable.jpeg',
                certificacion: 'Plata',
                rating: 4.6,
                platillos: 15,
                precio: 110.00,
                enlace: '#restaurante2'
            },
            {
                id: 3,
                nombre: 'Nutri Kitchen',
                descripcion: 'Especialistas en dietas balanceadas y planes personalizados',
                banner: 'IMG/UPLOADS/RESTAURANTES/Nutri_Kitchen_02.jpeg',
                logo: 'IMG/LOGOTIPOS/RESTAURANTE/logo_nutri_kitchen.png',
                certificacion: 'Oro',
                rating: 4.9,
                platillos: 18,
                precio: 130.00,
                enlace: '#restaurante3'
            },
            {
                id: 4,
                nombre: 'Vida Verde',
                descripcion: 'Comida vegetariana y vegana creativa con productos orgánicos',
                banner: 'IMG/UPLOADS/RESTAURANTES/Vida_Verde_01.png',
                logo: 'IMG/LOGOTIPOS/RESTAURANTE/logo_vida_verde.jpeg',
                certificacion: 'Bronce',
                rating: 4.5,
                platillos: 10,
                precio: 95.00,
                enlace: '#restaurante4'
            },
            {
                id: 5,
                nombre: 'El Rincón Saludable',
                descripcion: 'Opciones bajas en calorías y grasas para estilo de vida consciente',
                banner: 'IMG/UPLOADS/RESTAURANTES/El_Rincón_Saludable_02.png',
                logo: 'IMG/LOGOTIPOS/RESTAURANTE/logo_rincon_saludable.png',
                certificacion: 'Plata',
                rating: 4.7,
                platillos: 14,
                precio: 105.00,
                enlace: '#restaurante5'
            }
        ];

        this.init();
    }

    init() {
        this.crearSlidesDesdePlantilla();
        this.configurarEventos();
        this.actualizarCarrusel();
        this.iniciarAutoplay();
    }

    /**
     * Crear slides clonando la plantilla
     */
    crearSlidesDesdePlantilla() {
        if (!this.plantilla) {
            console.error('Plantilla no encontrada');
            return;
        }

        // Limpiar slides existentes (excepto la plantilla)
        const slidesExistentes = this.track.querySelectorAll('.carrusel-slide:not(.slide-plantilla)');
        slidesExistentes.forEach(slide => slide.remove());

        // Crear clones para cada restaurante
        this.restaurantesData.forEach((restaurante, index) => {
            const clone = this.clonarSlide(restaurante, index);
            this.track.appendChild(clone);
            this.slides.push(clone);
        });

        // Para ciclo infinito, duplicar los primeros slides
        if (this.config.infinito) {
            const slidesADuplicar = Math.min(this.slidesPorVista, this.slides.length);
            for (let i = 0; i < slidesADuplicar; i++) {
                const cloneDuplicado = this.clonarSlide(this.restaurantesData[i], this.slides.length + i);
                this.track.appendChild(cloneDuplicado);
                this.slides.push(cloneDuplicado);
            }
        }

        this.totalSlides = this.slides.length;
        this.crearIndicadores();
    }

    /**
     * Clonar un slide desde la plantilla con datos específicos
     */
    clonarSlide(restaurante, index) {
        const clone = this.plantilla.cloneNode(true);
        
        // Remover clase de plantilla y mostrar
        clone.classList.remove('slide-plantilla');
        clone.style.display = '';
        clone.setAttribute('data-index', index);
        clone.setAttribute('data-restaurante-id', restaurante.id);

        // Usar querySelector para encontrar elementos dentro del clone
        const img = clone.querySelector('.slide-img');
        const logo = clone.querySelector('.slide-logo');
        const titulo = clone.querySelector('.slide-titulo');
        const descripcion = clone.querySelector('.slide-descripcion');
        const certificacion = clone.querySelector('.certificacion-texto');
        const estrellas = clone.querySelector('.rating-estrellas');
        const rating = clone.querySelector('.rating-numero');
        const platillos = clone.querySelector('.slide-platillos');
        const precio = clone.querySelector('.slide-precio');
        const boton = clone.querySelector('.slide-boton.primary');
        const botonFavorito = clone.querySelector('.slide-boton.secondary');

        // Asignar datos con fallback
        if (img) {
            img.src = this.getImagenConFallback(restaurante.banner, 'IMG/UPLOADS/RESTAURANTES/default_banner.png');
            img.alt = restaurante.nombre;
            img.onerror = () => { img.src = 'IMG/UPLOADS/RESTAURANTES/default_banner.png'; };
        }

        if (logo) {
            logo.src = this.getImagenConFallback(restaurante.logo, 'IMG/UPLOADS/RESTAURANTES/default_logo.jpeg');
            logo.alt = `Logo ${restaurante.nombre}`;
            logo.onerror = () => { logo.src = 'IMG/UPLOADS/RESTAURANTES/default_logo.jpeg'; };
        }

        if (titulo) titulo.textContent = restaurante.nombre;
        if (descripcion) descripcion.textContent = restaurante.descripcion;
        if (certificacion) certificacion.textContent = restaurante.certificacion;
        if (estrellas) estrellas.textContent = this.generarEstrellas(restaurante.rating);
        if (rating) rating.textContent = restaurante.rating.toFixed(1);
        if (platillos) platillos.textContent = `${restaurante.platillos} platillos`;
        if (precio) precio.textContent = `Desde $${restaurante.precio.toFixed(2)}`;
        if (boton) boton.href = restaurante.enlace;

        if (botonFavorito) {
            botonFavorito.onclick = () => this.agregarFavorito(restaurante.id, restaurante.nombre);
        }

        // Aplicar color de certificación
        const certificacionElement = clone.querySelector('.slide-certificacion');
        if (certificacionElement) {
            const color = this.getColorCertificacion(restaurante.certificacion);
            certificacionElement.style.background = color;
        }

        return clone;
    }

    /**
     * Obtener imagen con fallback
     */
    getImagenConFallback(imagen, defaultImage) {
        // Verificar si la imagen es válida
        if (!imagen || imagen === 'default_logo.png' || imagen === 'default_banner.png') {
            return defaultImage;
        }
        return imagen;
    }

    /**
     * Generar estrellas de rating
     */
    generarEstrellas(rating) {
        const entero = Math.floor(rating);
        const mitad = (rating - entero) >= 0.5;
        let estrellas = '';

        for (let i = 0; i < 5; i++) {
            if (i < entero) {
                estrellas += '⭐';
            } else if (i === entero && mitad) {
                estrellas += '✨';
            } else {
                estrellas += '☆';
            }
        }
        return estrellas;
    }

    /**
     * Obtener color de certificación
     */
    getColorCertificacion(certificacion) {
        const colores = {
            'Oro': '#ffd700',
            'Plata': '#c0c0c0',
            'Bronce': '#cd7f32'
        };
        return colores[certificacion] || '#95a5a6';
    }

    /**
     * Crear indicadores
     */
    crearIndicadores() {
        if (!this.indicadoresContainer) return;

        this.indicadoresContainer.innerHTML = '';
        
        // Solo crear indicadores para los restaurantes originales
        const restaurantesOriginales = this.restaurantesData.length;
        
        for (let i = 0; i < restaurantesOriginales; i++) {
            const indicador = document.createElement('button');
            indicador.className = `indicador ${i === 0 ? 'activo' : ''}`;
            indicador.setAttribute('data-index', i);
            indicador.setAttribute('aria-label', `Ir al restaurante ${i + 1}`);
            
            const dot = document.createElement('span');
            dot.className = 'indicador-dot';
            
            indicador.appendChild(dot);
            this.indicadoresContainer.appendChild(indicador);
        }
    }

    /**
     * Configurar eventos
     */
    configurarEventos() {
        // Controles de navegación
        if (this.controles.prev) {
            this.controles.prev.addEventListener('click', () => this.mover('prev'));
        }
        if (this.controles.next) {
            this.controles.next.addEventListener('click', () => this.mover('next'));
        }

        // Indicadores
        const indicadores = this.indicadoresContainer?.querySelectorAll('.indicador');
        indicadores?.forEach(indicador => {
            indicador.addEventListener('click', () => {
                const index = parseInt(indicador.dataset.index);
                this.irASlide(index);
            });
        });

        // Touch events
        this.configurarTouchEvents();

        // Keyboard navigation
        this.container.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.mover('prev');
            if (e.key === 'ArrowRight') this.mover('next');
        });

        // Hover pause
        if (this.config.pausarAlHover && this.config.autoplay) {
            const carruselContenedor = this.container.querySelector('.carrusel-contenedor');
            carruselContenedor.addEventListener('mouseenter', () => this.pausarAutoplay());
            carruselContenedor.addEventListener('mouseleave', () => this.iniciarAutoplay());
        }

        // Responsive
        window.addEventListener('resize', () => {
            const nuevoSlidesPorVista = this.getSlidesPorVista();
            if (nuevoSlidesPorVista !== this.slidesPorVista) {
                this.slidesPorVista = nuevoSlidesPorVista;
                this.actualizarCarrusel();
            }
        });
    }

    /**
     * Configurar touch events
     */
    configurarTouchEvents() {
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        
        const carruselContenedor = this.container.querySelector('.carrusel-contenedor');
        
        carruselContenedor.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isDragging = true;
        });
        
        carruselContenedor.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
        });
        
        carruselContenedor.addEventListener('touchend', () => {
            if (!isDragging) return;
            
            const diff = startX - currentX;
            const threshold = 50;
            
            if (diff > threshold) {
                this.mover('next');
            } else if (diff < -threshold) {
                this.mover('prev');
            }
            
            isDragging = false;
        });
    }

    /**
     * Mover carrusel
     */
    mover(direccion) {
        if (this.isAnimating) return;

        if (direccion === 'next') {
            this.currentIndex++;
        } else {
            this.currentIndex--;
        }

        this.actualizarCarrusel();
    }

    /**
     * Ir a slide específico
     */
    irASlide(index) {
        if (this.isAnimating || index === this.currentIndex) return;
        
        this.currentIndex = index;
        this.actualizarCarrusel();
    }

    /**
     * Actualizar carrusel
     */
    actualizarCarrusel() {
        this.isAnimating = true;

        const slideWidth = this.slides[0]?.offsetWidth || 0;
        const espacio = this.config.espacio;
        const offset = (slideWidth + espacio) * this.currentIndex;

        // Aplicar transformación
        this.track.style.transition = `transform ${this.config.velocidadTransicion}ms ease`;
        this.track.style.transform = `translateX(-${offset}px)`;

        // Manejar ciclo infinito
        if (this.config.infinito) {
            setTimeout(() => {
                this.manejarBucleInfinito();
            }, this.config.velocidadTransicion);
        }

        // Actualizar indicadores
        this.actualizarIndicadores();

        // Resetear animación
        setTimeout(() => {
            this.isAnimating = false;
        }, this.config.velocidadTransicion);
    }

    /**
     * Manejar bucle infinito
     */
    manejarBucleInfinito() {
        const restaurantesOriginales = this.restaurantesData.length;

        // Si llegamos al final duplicado, volver al inicio
        if (this.currentIndex >= restaurantesOriginales) {
            this.currentIndex = this.currentIndex % restaurantesOriginales;
            this.track.style.transition = 'none';
            const slideWidth = this.slides[0]?.offsetWidth || 0;
            const espacio = this.config.espacio;
            const offset = (slideWidth + espacio) * this.currentIndex;
            this.track.style.transform = `translateX(-${offset}px)`;
            
            setTimeout(() => {
                this.track.style.transition = '';
            }, 50);
        }

        // Si vamos antes del inicio, ir al final duplicado
        if (this.currentIndex < 0) {
            this.currentIndex = restaurantesOriginales + this.currentIndex;
            this.track.style.transition = 'none';
            const slideWidth = this.slides[0]?.offsetWidth || 0;
            const espacio = this.config.espacio;
            const offset = (slideWidth + espacio) * this.currentIndex;
            this.track.style.transform = `translateX(-${offset}px)`;
            
            setTimeout(() => {
                this.track.style.transition = '';
            }, 50);
        }
    }

    /**
     * Actualizar indicadores
     */
    actualizarIndicadores() {
        const indicadores = this.indicadoresContainer?.querySelectorAll('.indicador');
        if (!indicadores) return;

        const restaurantesOriginales = this.restaurantesData.length;
        const activeIndex = this.currentIndex % restaurantesOriginales;

        indicadores.forEach((indicador, index) => {
            if (index === activeIndex) {
                indicador.classList.add('activo');
            } else {
                indicador.classList.remove('activo');
            }
        });
    }

    /**
     * Obtener slides por vista
     */
    getSlidesPorVista() {
        const containerWidth = this.container.querySelector('.carrusel-contenedor')?.offsetWidth || 0;
        const slideWidth = 350; // Ancho estimado de slide
        
        if (window.innerWidth <= 768) return 1;
        if (window.innerWidth <= 1024) return 2;
        return Math.min(3, Math.floor(containerWidth / (slideWidth + 20)));
    }

    /**
     * Iniciar autoplay
     */
    iniciarAutoplay() {
        if (!this.config.autoplay) return;

        this.pausarAutoplay();
        this.autoplayInterval = setInterval(() => {
            this.mover('next');
        }, this.config.velocidadAutoplay);
    }

    /**
     * Pausar autoplay
     */
    pausarAutoplay() {
        if (this.autoplayInterval) {
            clearInterval(this.autoplayInterval);
            this.autoplayInterval = null;
        }
    }

    /**
     * Agregar a favoritos
     */
    agregarFavorito(restauranteId, restauranteNombre) {
        console.log(`Agregando a favoritos: ${restauranteNombre} (ID: ${restauranteId})`);
        
        // Mostrar notificación temporal
        this.mostrarNotificacion(`⭐ ${restauranteNombre} agregado a favoritos`);
    }

    /**
     * Mostrar notificación
     */
    mostrarNotificacion(mensaje) {
        const notificacion = document.createElement('div');
        notificacion.className = 'notificacion-temporal';
        notificacion.textContent = mensaje;
        notificacion.style.cssText = `
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
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        `;
        
        document.body.appendChild(notificacion);
        
        setTimeout(() => {
            notificacion.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Destruir carrusel
     */
    destroy() {
        this.pausarAutoplay();
        this.slides = [];
        this.totalSlides = 0;
    }
}

// Inicialización automática
document.addEventListener('DOMContentLoaded', () => {
    const carrusel = new CarruselUnificado();
    
    // Exponer globalmente para debugging
    window.carruselUnificado = carrusel;
});

// Exponer la clase para uso manual
window.CarruselUnificado = CarruselUnificado;
