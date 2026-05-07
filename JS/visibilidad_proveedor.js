// Visibilidad de Proveedor - Salud Juárez
class VisibilidadProveedor {
    constructor() {
        this.mapa = null;
        this.marker = null;
        this.latitud = null;
        this.longitud = null;
        this.init();
    }

    init() {
        this.cargarDatosProveedor();
        this.inicializarMapa();
        this.cargarEstadisticas();
        this.setupEventListeners();
    }

    async cargarDatosProveedor() {
        try {
            const response = await fetch('../PHP/cargar_datos_visibilidad.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.latitud = data.proveedor.latitud_proveedor;
                this.longitud = data.proveedor.longitud_proveedor;
                this.actualizarUI(data.proveedor);
            } else {
                this.mostrarNotificacion('Error al cargar datos del proveedor', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    inicializarMapa() {
        // Inicializar el mapa centrado en la ubicación del proveedor
        if (!this.latitud || !this.longitud) {
            document.getElementById('mapaProveedor').innerHTML = `
                <div style="text-align: center; padding: 50px; color: #7f8c8d;">
                    <h3>📍 Ubicación no configurada</h3>
                    <p>Por favor, configura tu ubicación en tu perfil para mostrarla en el mapa.</p>
                </div>
            `;
            return;
        }

        this.mapa = L.map('mapaProveedor').setView([this.latitud, this.longitud], 13);

        // Agregar capa de tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.mapa);

        // Agregar marcador del proveedor
        const iconoProveedor = L.divIcon({
            html: '<div style="background: #27ae60; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold;">📦</div>',
            iconSize: [30, 30],
            className: 'sj-marcador-proveedor'
        });

        this.marker = L.marker([this.latitud, this.longitud], {
            icon: iconoProveedor,
            title: 'Tu Negocio'
        }).addTo(this.mapa);

        // Popup con información del proveedor
        this.marker.bindPopup(`
            <div style="text-align: center; padding: 10px;">
                <strong style="color: #27ae60;">🏢 Tu Negocio</strong><br>
                <span style="color: #ecf0f1;">Aquí te encuentran los restaurantes</span>
            </div>
        `);

        // Cargar otros proveedores cercanos
        this.cargarProveedoresCercanos();
    }

    async cargarProveedoresCercanos() {
        try {
            const response = await fetch('../PHP/cargar_proveedores_cercanos.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success && data.proveedores) {
                data.proveedores.forEach(proveedor => {
                    if (proveedor.latitud_proveedor && proveedor.longitud_proveedor) {
                        const iconoOtro = L.divIcon({
                            html: '<div style="background: #3498db; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; font-size: 12px;">📦</div>',
                            iconSize: [25, 25],
                            className: 'sj-marcador-otro'
                        });

                        L.marker([proveedor.latitud_proveedor, proveedor.longitud_proveedor], {
                            icon: iconoOtro,
                            title: proveedor.nombre_empresa
                        }).addTo(this.mapa).bindPopup(`
                            <div style="text-align: center; padding: 10px;">
                                <strong>${proveedor.nombre_empresa}</strong><br>
                                <span style="color: #95a5a6;">${proveedor.tipo_proveedor}</span>
                            </div>
                        `);
                    }
                });
            }
        } catch (error) {
            console.error('Error cargando proveedores cercanos:', error);
        }
    }

    async cargarEstadisticas() {
        try {
            const response = await fetch('../PHP/cargar_estadisticas_visibilidad.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('vistasSemana').textContent = data.estadisticas.vistas_semana || 0;
                document.getElementById('contactosSemana').textContent = data.estadisticas.contactos_semana || 0;
                document.getElementById('vistasTotales').textContent = data.estadisticas.vistas_totales || 0;
                document.getElementById('productosActivos').textContent = data.estadisticas.productos_activos || 0;
            }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
        }
    }

    actualizarUI(proveedor) {
        // Actualizar título con nombre del negocio
        const title = document.querySelector('.sj-visibilidad-title');
        if (title && proveedor.nombre_empresa) {
            title.textContent = `Visibilidad - ${proveedor.nombre_empresa}`;
        }

        // Cargar opciones guardadas
        if (proveedor.estado_visibilidad) {
            document.getElementById('estadoVisibilidad').value = proveedor.estado_visibilidad;
        }
        if (proveedor.radio_busqueda) {
            document.getElementById('radioBusqueda').value = proveedor.radio_busqueda;
        }
        if (proveedor.descripcion_destacada) {
            document.getElementById('descripcionDestacada').value = proveedor.descripcion_destacada;
        }
    }

    setupEventListeners() {
        // Los event listeners ya se agregan en el HTML con onclick
    }

    actualizarPosicion() {
        if (!navigator.geolocation) {
            this.mostrarNotificacion('Tu navegador no soporta geolocalización', 'error');
            return;
        }

        this.mostrarNotificacion('Obteniendo tu ubicación actual...', 'info');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords;
                
                // Actualizar mapa
                this.mapa.setView([latitude, longitude], 15);
                
                // Mover marcador
                if (this.marker) {
                    this.marker.setLatLng([latitude, longitude]);
                }

                this.mostrarNotificación('Ubicación actualizada correctamente', 'success');
            },
            (error) => {
                console.error('Error de geolocalización:', error);
                this.mostrarNotificacion('No se pudo obtener tu ubicación', 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    centrarMapa() {
        if (this.latitud && this.longitud && this.mapa) {
            this.mapa.setView([this.latitud, this.longitud], 13);
            this.mostrarNotificación('Mapa centrado en tu ubicación', 'success');
        }
    }

    async guardarOpcionesVisibilidad() {
        const estadoVisibilidad = document.getElementById('estadoVisibilidad').value;
        const radioBusqueda = document.getElementById('radioBusqueda').value;
        const descripcionDestacada = document.getElementById('descripcionDestacada').value;

        try {
            const response = await fetch('../PHP/guardar_opciones_visibilidad.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    estado_visibilidad: estadoVisibilidad,
                    radio_busqueda: radioBusqueda,
                    descripcion_destacada: descripcionDestacada
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarNotificación('Opciones de visibilidad guardadas', 'success');
            } else {
                this.mostrarNotificación('Error al guardar las opciones', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificación('Error de conexión', 'error');
        }
    }

    mostrarNotificación(mensaje, tipo = 'info') {
        // Crear notificación flotante
        const notificacion = document.createElement('div');
        notificacion.className = `sj-notificación sj-notificación-${tipo}`;
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 3000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 300px;
        `;

        // Colores según tipo
        const colores = {
            success: '#27ae60',
            error: '#e74c3c',
            info: '#3498db',
            warning: '#f39c12'
        };

        notificacion.style.background = colores[tipo] || colores.info;
        notificacion.textContent = mensaje;

        // Agregar al DOM
        document.body.appendChild(notificacion);

        // Animar entrada
        setTimeout(() => {
            notificacion.style.transform = 'translateX(0)';
        }, 100);

        // Remover después de 3 segundos
        setTimeout(() => {
            notificacion.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 300);
        }, 3000);
    }
}

// Hacer funciones globales para onclick
window.actualizarPosicion = function() {
    if (window.visibilidadProveedor) {
        window.visibilidadProveedor.actualizarPosicion();
    }
};

window.centrarMapa = function() {
    if (window.visibilidadProveedor) {
        window.visibilidadProveedor.centrarMapa();
    }
};

window.guardarOpcionesVisibilidad = function() {
    if (window.visibilidadProveedor) {
        window.visibilidadProveedor.guardarOpcionesVisibilidad();
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.visibilidadProveedor = new VisibilidadProveedor();
});
