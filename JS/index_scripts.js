/**
 * Scripts específicos del index.html - Salud Juárez
 * Extraídos del index.html para optimización
 * Versión: 1.0.0
 * Fecha: 26 de Marzo de 2026
 */

// Inicialización del mapa en el index
let mapaIndex = null;
let capasActivas = {
    restaurantes: true,
    proveedores: false,
    premium: false
};

// Configuración del mapa principal
function inicializarMapaIndex() {
    if (typeof L !== 'undefined') {
        // Coordenadas centrales de Ciudad Juárez
        const centroJuarez = [31.6904, -106.4245];
        
        mapaIndex = L.map('mapaSalud').setView(centroJuarez, 12);
        
        // Capa base de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(mapaIndex);
        
        // Cargar restaurantes
        cargarRestaurantesMapa();
        
        // Agregar controles de capas
        agregarControlesCapas();
        
        // Agregar leyenda
        agregarLeyendaMapa();
    }
}

// Cargar restaurantes en el mapa
function cargarRestaurantesMapa() {
    // Simulación de datos de restaurantes
    const restaurantes = [
        {
            id: 1,
            nombre: "Restaurante Saludable 1",
            lat: 31.6904,
            lng: -106.4245,
            direccion: "Av. Principal 123",
            certificacion: "Oro",
            telefono: "+52 656 123 4567"
        },
        {
            id: 2,
            nombre: "Restaurante Saludable 2",
            lat: 31.7000,
            lng: -106.4300,
            direccion: "Calle Secundaria 456",
            certificacion: "Plata",
            telefono: "+52 656 987 6543"
        }
    ];
    
    restaurantes.forEach(restaurante => {
        const icono = crearIconoRestaurante(restaurante.certificacion);
        
        const marcador = L.marker([restaurante.lat, restaurante.lng], { icon: icono })
            .addTo(mapaIndex)
            .bindPopup(`
                <div class="popup-restaurante">
                    <h4>${restaurante.nombre}</h4>
                    <p><strong>Dirección:</strong> ${restaurante.direccion}</p>
                    <p><strong>Certificación:</strong> ${restaurante.certificacion}</p>
                    <p><strong>Teléfono:</strong> ${restaurante.telefono}</p>
                    <button class="btn-popup" onclick="verDetallesRestaurante(${restaurante.id})">
                        Ver Detalles
                    </button>
                </div>
            `);
    });
}

// Crear iconos para restaurantes según certificación
function crearIconoRestaurante(certificacion) {
    let color = '#3498db'; // Azul por defecto
    
    switch(certificacion) {
        case 'Oro':
            color = '#f39c12'; // Dorado
            break;
        case 'Plata':
            color = '#95a5a6'; // Plateado
            break;
        case 'Bronce':
            color = '#cd7f32'; // Bronce
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

// Agregar controles de capas
function agregarControlesCapas() {
    const controlesHTML = `
        <div class="mapa-controls">
            <button class="mapa-btn active" onclick="toggleCapa('restaurantes')">
                🍽️ Restaurantes
            </button>
            <button class="mapa-btn" onclick="toggleCapa('proveedores')">
                📦 Proveedores
            </button>
            <button class="mapa-btn" onclick="toggleCapa('premium')">
                ⭐ Solo Premium
            </button>
        </div>
    `;
    
    // Insertar controles antes del mapa
    const mapaContainer = document.getElementById('mapaSalud');
    mapaContainer.insertAdjacentHTML('beforebegin', controlesHTML);
}

// Toggle de capas del mapa
function toggleCapa(capa) {
    capasActivas[capa] = !capasActivas[capa];
    
    // Actualizar botones
    const botones = document.querySelectorAll('.mapa-btn');
    botones.forEach(btn => {
        if (btn.textContent.toLowerCase().includes(capa)) {
            btn.classList.toggle('active');
        }
    });
    
    // Aquí iría la lógica para mostrar/ocultar capas
    console.log(`Capa ${capa}: ${capasActivas[capa] ? 'activada' : 'desactivada'}`);
}

// Agregar leyenda al mapa
function agregarLeyendaMapa() {
    const leyendaHTML = `
        <div class="mapa-leyenda">
            <h4>Leyenda</h4>
            <div class="leyenda-item">
                <div class="leyenda-icono restaurante"></div>
                <span>Restaurantes</span>
            </div>
            <div class="leyenda-item">
                <div class="leyenda-icono proveedor"></div>
                <span>Proveedores</span>
            </div>
            <div class="leyenda-item">
                <div class="leyenda-icono premium"></div>
                <span>Certificación Oro</span>
            </div>
        </div>
    `;
    
    // Insertar leyenda después del mapa
    const mapaContainer = document.getElementById('mapaSalud');
    mapaContainer.insertAdjacentHTML('afterend', leyendaHTML);
}

// Ver detalles de restaurante
function verDetallesRestaurante(idRestaurante) {
    // Redirigir a la página de detalles o mostrar modal
    console.log(`Ver detalles del restaurante ${idRestaurante}`);
    // window.location.href = `DIRECCIONES/ver_menu.php?id=${idRestaurante}`;
}

// Sistema de lazy loading para imágenes
function inicializarLazyLoading() {
    const imagenes = document.querySelectorAll('img[data-src]');
    
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
    
    imagenes.forEach(img => imageObserver.observe(img));
}

// Sistema de animaciones al hacer scroll
function inicializarAnimacionesScroll() {
    const elementosAnimados = document.querySelectorAll('.info-card, .desarrollador-card');
    
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
    
    elementosAnimados.forEach(elemento => {
        elemento.style.opacity = '0';
        elemento.style.transform = 'translateY(30px)';
        elemento.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(elemento);
    });
}

// Sistema de navegación suave
function inicializarNavegacionSuave() {
    const enlaces = document.querySelectorAll('a[href^="#"]');
    
    enlaces.forEach(enlace => {
        enlace.addEventListener('click', function(e) {
            e.preventDefault();
            
            const destino = document.querySelector(this.getAttribute('href'));
            if (destino) {
                destino.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Sistema de prefetch de recursos críticos
function prefetchRecursos() {
    const recursosCriticos = [
        'JS/carrusel.js',
        'JS/qr_portfolio.js',
        'JS/mapa_salud_juarez.js',
        'JS/modal_desarrollador.js'
    ];
    
    recursosCriticos.forEach(recurso => {
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = recurso;
        if (recurso.endsWith('.js')) {
            link.as = 'script';
        }
        document.head.appendChild(link);
    });
}

// Sistema de detección de conexión
function verificarConexion() {
    const conexionIndicator = document.createElement('div');
    conexionIndicator.id = 'conexion-indicator';
    conexionIndicator.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 10px 15px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        display: none;
    `;
    document.body.appendChild(conexionIndicator);
    
    function actualizarEstadoConexion() {
        const online = navigator.onLine;
        conexionIndicator.textContent = online ? '🟢 Conectado' : '🔴 Sin conexión';
        conexionIndicator.style.background = online ? '#27ae60' : '#e74c3c';
        conexionIndicator.style.display = 'block';
        
        setTimeout(() => {
            conexionIndicator.style.display = 'none';
        }, 3000);
    }
    
    window.addEventListener('online', actualizarEstadoConexion);
    window.addEventListener('offline', actualizarEstadoConexion);
}

// Sistema de análisis de rendimiento
function inicializarAnalisisRendimiento() {
    // Medir tiempo de carga
    window.addEventListener('load', () => {
        const tiempoCarga = performance.now();
        console.log(`Tiempo de carga: ${tiempoCarga.toFixed(2)}ms`);
        
        // Enviar datos de análisis si es necesario
        if (typeof gtag !== 'undefined') {
            gtag('event', 'page_load_time', {
                custom_parameter: tiempoCarga
            });
        }
    });
}

// Inicialización principal cuando el DOM está listo
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar componentes
    inicializarLazyLoading();
    inicializarAnimacionesScroll();
    inicializarNavegacionSuave();
    prefetchRecursos();
    verificarConexion();
    inicializarAnalisisRendimiento();
    
    // Inicializar mapa si existe el contenedor
    if (document.getElementById('mapaSalud')) {
        // Esperar a que se cargue Leaflet
        if (typeof L !== 'undefined') {
            inicializarMapaIndex();
        } else {
            // Cargar Leaflet dinámicamente
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = inicializarMapaIndex;
            document.head.appendChild(script);
            
            // Cargar CSS de Leaflet
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);
        }
    }
    
    console.log('Salud Juárez - Sistema cargado exitosamente');
});

// Manejo de errores globales
window.addEventListener('error', (e) => {
    console.error('Error en la aplicación:', e.error);
    
    // Enviar error a servicio de análisis si está disponible
    if (typeof gtag !== 'undefined') {
        gtag('event', 'exception', {
            description: e.error.message,
            fatal: false
        });
    }
});

// Exportar funciones para uso global
window.toggleCapa = toggleCapa;
window.verDetallesRestaurante = verDetallesRestaurante;
window.inicializarMapaIndex = inicializarMapaIndex;
