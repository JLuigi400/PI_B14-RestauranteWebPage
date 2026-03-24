/**
 * Sistema de Mapas - Salud Juárez
 * Implementación con OpenStreetMap + Leaflet.js
 * Versión: 1.0.0
 * Fecha: 2026-03-23
 */

class MapaSaludJuarez {
    constructor() {
        this.mapa = null;
        this.markers = [];
        this.markerCluster = null;
        this.usuarioUbicacion = null;
        this.rolUsuario = null;
        this.coordenadasCdJuarez = [31.7200, -106.4600]; // Centro de Ciudad Juárez
        
        // Configuración de tiles de OpenStreetMap
        this.tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors | Salud Juárez',
            maxZoom: 19
        });
        
        // Iconos personalizados según rol y tipo
        this.iconos = {
            restaurante: this.crearIcono('🍽️', '#27ae60'),
            restaurantePremium: this.crearIcono('🏆', '#f39c12'),
            proveedor: this.crearIcono('📦', '#3498db'),
            usuario: this.crearIcono('📍', '#e74c3c'),
            seleccionado: this.crearIcono('⭐', '#e67e22')
        };
    }
    
    /**
     * Crear icono personalizado para marcadores
     */
    crearIcono(emoji, color) {
        return L.divIcon({
            html: `<div style="
                background: ${color};
                color: white;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
                border: 2px solid white;
                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                font-weight: bold;
            ">${emoji}</div>`,
            className: 'marcador-personalizado',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15]
        });
    }
    
    /**
     * Inicializar el mapa en el contenedor especificado
     */
    inicializarMapa(contenedorId, opciones = {}) {
        const opcionesPorDefecto = {
            center: this.coordenadasCdJuarez,
            zoom: 12,
            layers: [this.tileLayer]
        };
        
        this.mapa = L.map(contenedorId, { ...opcionesPorDefecto, ...opciones });
        
        // Agregar control de escala
        L.control.scale().addTo(this.mapa);
        
        // Agregar control de capas
        const capasBase = {
            "OpenStreetMap": this.tileLayer,
            "Satélite": L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{d}', {
                attribution: '© Esri | Salud Juárez'
            })
        };
        
        L.control.layers(capasBase).addTo(this.mapa);
        
        return this.mapa;
    }
    
    /**
     * Obtener ubicación actual del usuario
     */
    obtenerUbicacionUsuario() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocalización no soportada'));
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.usuarioUbicacion = [
                        position.coords.latitude,
                        position.coords.longitude
                    ];
                    resolve(this.usuarioUbicacion);
                },
                (error) => {
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000 // 5 minutos
                }
            );
        });
    }
    
    /**
     * Centrar mapa en ubicación del usuario
     */
    centrarEnUsuario() {
        if (this.usuarioUbicacion) {
            this.mapa.setView(this.usuarioUbicacion, 14);
            
            // Agregar marcador del usuario
            this.agregarMarcador(
                this.usuarioUbicacion,
                'Tu ubicación',
                this.iconos.usuario,
                'Tu ubicación actual'
            );
        }
    }
    
    /**
     * Agregar marcador al mapa
     */
    agregarMarcador(coordenadas, titulo, icono = null, popupContent = '') {
        const marcador = L.marker(coordenadas, { icon: icono || this.iconos.restaurante });
        
        if (popupContent) {
            marcador.bindPopup(popupContent);
        }
        
        marcador.bindTooltip(titulo, {
            permanent: false,
            direction: 'top'
        });
        
        marcador.addTo(this.mapa);
        this.markers.push(marcador);
        
        return marcador;
    }
    
    /**
     * Agregar múltiples restaurantes al mapa
     */
    agregarRestaurantes(restaurantes) {
        const bounds = [];
        
        restaurantes.forEach(restaurante => {
            if (restaurante.latitud && restaurante.longitud) {
                const coordenadas = [restaurante.latitud, restaurante.longitud];
                bounds.push(coordenadas);
                
                // Determinar icono según certificación
                let icono = this.iconos.restaurante;
                if (restaurante.certificacion === 'oro') {
                    icono = this.iconos.restaurantePremium;
                }
                
                // Crear popup con información del restaurante
                const popupContent = this.crearPopupRestaurante(restaurante);
                
                this.agregarMarcador(coordenadas, restaurante.nombre_res, icono, popupContent);
            }
        });
        
        // Ajustar vista para mostrar todos los marcadores
        if (bounds.length > 0) {
            this.mapa.fitBounds(bounds, { padding: [50, 50] });
        }
    }
    
    /**
     * Crear popup para restaurante
     */
    crearPopupRestaurante(restaurante) {
        const certificacion = restaurante.certificacion || 'bronce';
        const badgeCertificacion = this.obtenerBadgeCertificacion(certificacion);
        
        return `
            <div style="min-width: 200px; font-family: Arial, sans-serif;">
                <h4 style="margin: 0 0 8px 0; color: #2c3e50;">
                    ${restaurante.nombre_res}
                </h4>
                <div style="margin-bottom: 8px;">
                    ${badgeCertificacion}
                </div>
                <p style="margin: 4px 0; color: #666; font-size: 14px;">
                    📍 ${restaurante.direccion_res || 'Dirección no disponible'}
                </p>
                <p style="margin: 4px 0; color: #666; font-size: 14px;">
                    📞 ${restaurante.telefono_res || 'Teléfono no disponible'}
                </p>
                <p style="margin: 4px 0; color: #666; font-size: 14px;">
                    🍽️ ${restaurante.total_platillos || 0} platillos disponibles
                </p>
                <button onclick="window.location.href='ver_menu.php?id=${restaurante.id_res}'" 
                        style="
                            background: #27ae60;
                            color: white;
                            border: none;
                            padding: 8px 16px;
                            border-radius: 4px;
                            cursor: pointer;
                            margin-top: 8px;
                            width: 100%;
                            font-weight: bold;
                        " onmouseover="this.style.background='#219a52'" 
                           onmouseout="this.style.background='#27ae60'">
                    Ver Menú
                </button>
            </div>
        `;
    }
    
    /**
     * Obtener badge de certificación
     */
    obtenerBadgeCertificacion(certificacion) {
        const badges = {
            oro: '<span style="background: #ffd700; color: #333; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">🏆 Premium Salud</span>',
            plata: '<span style="background: #c0c0c0; color: #333; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">🥈 Restaurante Saludable</span>',
            bronce: '<span style="background: #cd7f32; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">🥉 Consciente</span>'
        };
        
        return badges[certificacion] || badges.bronce;
    }
    
    /**
     * Agregar proveedores al mapa (para dueños de restaurantes)
     */
    agregarProveedores(proveedores, categoriaFiltro = null) {
        // Limpiar marcadores anteriores de proveedores
        this.limpiarMarcadoresPorTipo('proveedor');
        
        proveedores.forEach(proveedor => {
            if (!categoriaFiltro || proveedor.categoria_insumo === categoriaFiltro) {
                const coordenadas = [proveedor.latitud, proveedor.longitud];
                
                const popupContent = `
                    <div style="min-width: 200px; font-family: Arial, sans-serif;">
                        <h4 style="margin: 0 0 8px 0; color: #2c3e50;">
                            ${proveedor.nombre_tienda}
                        </h4>
                        <p style="margin: 4px 0; color: #666; font-size: 14px;">
                            📦 ${proveedor.categoria_insumo}
                        </p>
                        <p style="margin: 4px 0; color: #666; font-size: 14px;">
                            📍 ${proveedor.direccion_texto}
                        </p>
                        <p style="margin: 4px 0; color: #666; font-size: 14px;">
                            📞 ${proveedor.telefono}
                        </p>
                        <p style="margin: 4px 0; color: #666; font-size: 14px;">
                            ⏰ ${proveedor.horario_atencion}
                        </p>
                        <button onclick="crearSolicitudRestock(${proveedor.id_proveedor})" 
                                style="
                                    background: #3498db;
                                    color: white;
                                    border: none;
                                    padding: 8px 16px;
                                    border-radius: 4px;
                                    cursor: pointer;
                                    margin-top: 8px;
                                    width: 100%;
                                    font-weight: bold;
                                " onmouseover="this.style.background='#2980b9'" 
                                   onmouseout="this.style.background='#3498db'">
                            Solicitar Re-stock
                        </button>
                    </div>
                `;
                
                this.agregarMarcador(coordenadas, proveedor.nombre_tienda, this.iconos.proveedor, popupContent);
            }
        });
    }
    
    /**
     * Calcular distancia entre dos puntos
     */
    calcularDistancia(punto1, punto2) {
        return L.latLng(punto1[0], punto1[1]).distanceTo(L.latLng(punto2[0], punto2[1]));
    }
    
    /**
     * Encontrar proveedores cercanos a un punto
     */
    encontrarProveedoresCercanos(proveedores, puntoCentral, radioKm = 10) {
        return proveedores.filter(proveedor => {
            const distancia = this.calcularDistancia(
                puntoCentral,
                [proveedor.latitud, proveedor.longitud]
            );
            return distancia <= (radioKm * 1000); // Convertir km a metros
        }).map(proveedor => ({
            ...proveedor,
            distancia: this.calcularDistancia(puntoCentral, [proveedor.latitud, proveedor.longitud])
        })).sort((a, b) => a.distancia - b.distancia);
    }
    
    /**
     * Dibujar ruta entre dos puntos
     */
    dibujarRuta(puntoInicio, puntoFin, opciones = {}) {
        const opcionesPorDefecto = {
            color: '#3498db',
            weight: 4,
            opacity: 0.7,
            dashArray: '10, 10'
        };
        
        const ruta = L.polyline([puntoInicio, puntoFin], { ...opcionesPorDefecto, ...opciones });
        ruta.addTo(this.mapa);
        
        return ruta;
    }
    
    /**
     * Limpiar marcadores por tipo
     */
    limpiarMarcadoresPorTipo(tipo) {
        this.markers = this.markers.filter(marcador => {
            if (marcador.tipo === tipo) {
                this.mapa.removeLayer(marcador);
                return false;
            }
            return true;
        });
    }
    
    /**
     * Limpiar todos los marcadores
     */
    limpiarTodosLosMarcadores() {
        this.markers.forEach(marcador => {
            this.mapa.removeLayer(marcador);
        });
        this.markers = [];
    }
    
    /**
     * Buscar dirección usando Nominatim API (OpenStreetMap)
     */
    async buscarDireccion(direccion) {
        try {
            const respuesta = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}&limit=1`
            );
            
            const resultados = await respuesta.json();
            
            if (resultados && resultados.length > 0) {
                const resultado = resultados[0];
                return {
                    latitud: parseFloat(resultado.lat),
                    longitud: parseFloat(resultado.lon),
                    direccion: resultado.display_name
                };
            }
            
            return null;
        } catch (error) {
            console.error('Error buscando dirección:', error);
            return null;
        }
    }
    
    /**
     * Geocodificación inversa (coordenadas a dirección)
     */
    async geocodificacionInversa(latitud, longitud) {
        try {
            const respuesta = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitud}&lon=${longitud}`
            );
            
            const resultado = await respuesta.json();
            
            if (resultado && resultado.display_name) {
                return resultado.display_name;
            }
            
            return null;
        } catch (error) {
            console.error('Error en geocodificación inversa:', error);
            return null;
        }
    }
    
    /**
     * Manejar clic en el mapa
     */
    alClicMapa(callback) {
        this.mapa.on('click', (evento) => {
            const coordenadas = [evento.latlng.lat, evento.latlng.lng];
            callback(coordenadas, evento);
        });
    }
    
    /**
     * Obtener coordenadas del centro actual del mapa
     */
    obtenerCentroMapa() {
        const centro = this.mapa.getCenter();
        return [centro.lat, centro.lng];
    }
    
    /**
     * Establecer vista del mapa
     */
    establecerVista(coordenadas, zoom) {
        this.mapa.setView(coordenadas, zoom);
    }
    
    /**
     * Exportar mapa a imagen (opcional)
     */
    exportarMapa() {
        // Implementación futura para exportar el mapa como imagen
        console.log('Función de exportación pendiente de implementar');
    }
}

// Funciones globales para compatibilidad
let mapaSaludJuarez = null;

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    mapaSaludJuarez = new MapaSaludJuarez();
});

// Función helper para crear solicitudes de re-stock
function crearSolicitudRestock(idProveedor) {
    if (typeof window.crearSolicitudRestockGlobal === 'function') {
        window.crearSolicitudRestockGlobal(idProveedor);
    } else {
        console.log('Función crearSolicitudRestockGlobal no definida');
    }
}
