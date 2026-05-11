// Proveedores Cercanos - Salud Juárez
class ProveedoresCercanos {
    constructor() {
        this.mapa = null;
        this.restauranteActual = null;
        
        // Usar datos globales desde PHP (restaurantesData y proveedoresData)
        this.restaurantesData = typeof window.restaurantesData !== 'undefined' ? window.restaurantesData : [];
        this.proveedoresData = typeof window.proveedoresData !== 'undefined' ? window.proveedoresData : [];
        this.categoriaActual = 'todos';
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.inicializarMapa();
    }

    async cargarDatos() {
        try {
            // Cargar proveedores
            const responseProveedores = await fetch('../PHP/cargar_proveedores_cercanos.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const dataProveedores = await responseProveedores.json();
            if (dataProveedores.success) {
                this.proveedoresData = dataProveedores.proveedores;
            }

        } catch (error) {
            console.error('Error cargando datos:', error);
            this.mostrarNotificacion('Error al cargar datos', 'error');
        }
    }

    renderizarSelectRestaurantes() {
        const select = document.getElementById('restauranteSelect');
        if (!select) return;

        select.innerHTML = '<option value="">Selecciona un restaurante</option>';
        
        this.restaurantesData.forEach(restaurante => {
            const option = document.createElement('option');
            option.value = restaurante.id_res;
            option.textContent = restaurante.nombre_res;
            option.dataset.lat = restaurante.latitud;
            option.dataset.lng = restaurante.longitud;
            select.appendChild(option);
        });
    }

    inicializarMapa() {
        // Verificar si el contenedor del mapa existe
        const mapaContainer = document.getElementById('mapa');
        if (!mapaContainer) {
            console.error('No se encontró el contenedor del mapa');
            return;
        }
        
        // Crear mapa centrado en Cd. Juárez
        this.mapa = L.map('mapa').setView([31.690363, -106.424548], 11);
        
        // Agregar capa de tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(this.mapa);
        
        // Definir iconos personalizados
        const iconoRestaurante = L.divIcon({
            html: '<div style="background: #27ae60; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-weight: bold;">🍽️</div>',
            iconSize: [20, 20],
            className: 'restaurante-marker'
        });

        const iconoProveedor = L.divIcon({
            html: '<div style="background: #e74c3c; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-weight: bold;">📦</div>',
            iconSize: [20, 20],
            className: 'proveedor-marker'
        });
        
        // Agregar marcadores de restaurantes
        this.restaurantesData.forEach(restaurante => {
            if (restaurante.latitud && restaurante.longitud) {
                const marcador = L.marker([restaurante.latitud, restaurante.longitud], { icon: iconoRestaurante })
                    .bindPopup(`
                        <div style="padding: 10px;">
                            <strong>${restaurante.nombre_res}</strong><br>
                            <a href="../restaurantes.php?id=${restaurante.id_res}" style="color: #27ae60;">Ver detalles</a>
                        </div>
                    `)
                    .addTo(this.mapa);
            }
        });

        // Agregar marcadores de proveedores
        this.proveedoresData.forEach(proveedor => {
            if (proveedor.latitud_proveedor && proveedor.longitud_proveedor) {
                const marcador = L.marker([proveedor.latitud_proveedor, proveedor.longitud_proveedor], { icon: iconoProveedor })
                    .bindPopup(`
                        <div style="padding: 10px;">
                            <strong>${proveedor.nombre_empresa}</strong><br>
                            <small>Tipo: ${proveedor.id_tipo_proveedor || 'N/A'}</small><br>
                            <a href="../proveedores.php?id=${proveedor.id_proveedor}" style="color: #e74c3c;">Ver detalles</a>
                        </div>
                    `)
                    .addTo(this.mapa);
            }
        });
    }

    setupEventListeners() {
        // Cambio de restaurante
        const restauranteSelect = document.getElementById('restauranteSelect');
        if (restauranteSelect) {
            restauranteSelect.addEventListener('change', (e) => {
                this.actualizarVista(e.target.value);
            });
        }

        // Cambio de categoría
        const categoriaSelect = document.getElementById('categoriaSelect');
        if (categoriaSelect) {
            categoriaSelect.addEventListener('change', (e) => {
                this.actualizarCategoria(e.target.value);
            });
        }
    }

    actualizarVista(idRestaurante) {
        const option = document.querySelector(`#restauranteSelect option[value="${idRestaurante}"]`);
        
        if (idRestaurante && option) {
            this.restauranteActual = {
                id: parseInt(idRestaurante),
                nombre: option.textContent,
                latitud: parseFloat(option.dataset.lat),
                longitud: parseFloat(option.dataset.lng)
            };
            
            if (this.restauranteActual.latitud && this.restauranteActual.longitud) {
                // Centrar mapa en el restaurante
                this.mapa.setView([this.restauranteActual.latitud, this.restauranteActual.longitud], 13);
                
                // Mostrar proveedores cercanos
                this.mostrarProveedoresCercanos();
            } else {
                this.mostrarNotificacion('Este restaurante no tiene ubicación configurada', 'warning');
            }
        } else {
            this.restauranteActual = null;
            this.limpiarVista();
        }
    }

    actualizarCategoria(categoria) {
        this.categoriaActual = categoria;
        
        // Actualizar vista si hay restaurante seleccionado
        if (this.restauranteActual) {
            this.mostrarProveedoresCercanos();
        }
    }

    mostrarProveedoresCercanos() {
        if (!this.restauranteActual || !this.restauranteActual.latitud || !this.restauranteActual.longitud) {
            return;
        }
        
        const coordenadasRestaurante = [this.restauranteActual.latitud, this.restauranteActual.longitud];
        
        // Filtrar proveedores por categoría
        // CORREGIDO: Usar id_tipo_proveedor en lugar de tipo_proveedor
        let proveedoresFiltrados = this.proveedoresData.filter(p => {
            return this.categoriaActual === 'todos' || p.id_tipo_proveedor == this.categoriaActual;
        });
        
        // Calcular distancia y ordenar (todos, no solo los cercanos)
        proveedoresFiltrados = proveedoresFiltrados.map(p => ({
            ...p,
            distancia: this.calcularDistancia(coordenadasRestaurante, [p.latitud_proveedor, p.longitud_proveedor])
        })).sort((a, b) => a.distancia - b.distancia);
        
        // NO filtrar por radio - mostrar TODOS los proveedores
        // pero marcar cuáles están cerca (< 50km) vs lejanos
        
        // Actualizar lista con todos los proveedores
        this.actualizarListaProveedores(proveedoresFiltrados, true); // true = mostrar todos
    }

    calcularDistancia(coord1, coord2) {
        // Fórmula de Haversine para calcular distancia en metros
        const R = 6371000; // Radio de la Tierra en metros
        const lat1 = coord1[0] * Math.PI / 180;
        const lat2 = coord2[0] * Math.PI / 180;
        const deltaLat = (coord2[0] - coord1[0]) * Math.PI / 180;
        const deltaLon = (coord2[1] - coord1[1]) * Math.PI / 180;

        const a = Math.sin(deltaLat/2) * Math.sin(deltaLat/2) +
                  Math.cos(lat1) * Math.cos(lat2) *
                  Math.sin(deltaLon/2) * Math.sin(deltaLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

        return R * c;
    }

    actualizarListaProveedores(proveedores, mostrarTodos = false) {
        const lista = document.getElementById('proveedores_list');
        if (!lista) return;
        
        if (proveedores.length === 0) {
            lista.innerHTML = `
                <div class="empty-state">
                    <h4>🔍 No hay proveedores</h4>
                    <p>No se encontraron proveedores de esta categoría</p>
                </div>
            `;
            return;
        }
        
        // Separar proveedores en cercanos (< 50km) y lejanos (> 50km)
        const cercanos = proveedores.filter(p => p.distancia <= 50000);
        const lejanos = proveedores.filter(p => p.distancia > 50000);
        
        let html = '';
        
        // Sección de proveedores cercanos
        if (cercanos.length > 0) {
            html += `
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #27ae60; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        🟢 Proveedores Cercanos (< 50km)
                    </h4>
                    ${cercanos.map(proveedor => this.renderProveedorCard(proveedor, true)).join('')}
                </div>
            `;
        }
        
        // Sección de proveedores lejanos (solo si mostrarTodos es true)
        if (mostrarTodos && lejanos.length > 0) {
            html += `
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #e74c3c; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                        🔴 Proveedores Lejanos (> 50km)
                    </h4>
                    ${lejanos.map(proveedor => this.renderProveedorCard(proveedor, false)).join('')}
                </div>
            `;
        }
        
        lista.innerHTML = html;
    }
    
    renderProveedorCard(proveedor, esCercano) {
        const distanciaKm = (proveedor.distancia / 1000).toFixed(1);
        const colorDistancia = esCercano ? '#27ae60' : '#e74c3c';
        const bgStyle = esCercano ? '' : 'opacity: 0.8; background: #f8f9fa;';
        
        return `
            <div class="proveedor-item" style="${bgStyle}">
                <div class="proveedor-nombre">${proveedor.nombre_empresa}</div>
                <div class="proveedor-categoria">Tipo ${proveedor.id_tipo_proveedor || 'N/A'}</div>
                <div class="proveedor-info">
                    📍 ${proveedor.nombre_empresa}<br>
                    📞 ${proveedor.telefono || 'No disponible'}<br>
                    ⏰ Disponible 24/7
                </div>
                <div class="proveedor-distancia" style="color: ${colorDistancia};">
                    � ${distanciaKm} km de distancia ${esCercano ? '(Cercano)' : '(Lejano)'}
                </div>
                <button class="btn-solicitud" onclick="window.proveedoresCercanos.crearSolicitud(${proveedor.id_proveedor})">
                    Solicitar Re-stock
                </button>
            </div>
        `;
    }
    
    // ==========================================
    // INTEGRACIÓN CON EMAILJS
    // ==========================================
    
    async enviarNotificacionEmail(proveedor, insumo, cantidad) {
        // Configuración de EmailJS - REEMPLAZAR CON TUS CREDENCIALES
        const EMAILJS_CONFIG = {
            serviceID: 'TU_SERVICE_ID',      // Ej: 'gmail', 'outlook', etc.
            templateID: 'TU_TEMPLATE_ID',    // ID de la plantilla creada en EmailJS
            publicKey: 'TU_PUBLIC_KEY'       // Tu clave pública de EmailJS
        };
        
        // Datos para el template de EmailJS
        const templateParams = {
            to_email: proveedor.email || 'proveedor@ejemplo.com',
            to_name: proveedor.nombre_empresa,
            from_name: 'Salud Juárez - Sistema de Restaurantes',
            subject: `Solicitud de Re-stock: ${insumo}`,
            message: `
                Estimado ${proveedor.nombre_empresa},
                
                Se ha generado una nueva solicitud de re-stock:
                
                📦 Producto: ${insumo}
                📊 Cantidad: ${cantidad}
                🏪 Restaurante: ${this.restauranteActual ? this.restauranteActual.nombre : 'No especificado'}
                📅 Fecha: ${new Date().toLocaleDateString()}
                
                Por favor, confirmar disponibilidad y tiempo de entrega.
                
                Gracias,
                Equipo Salud Juárez
            `,
            insumo: insumo,
            cantidad: cantidad.toString(),
            restaurante: this.restauranteActual ? this.restauranteActual.nombre : 'No especificado',
            fecha: new Date().toLocaleDateString()
        };
        
        try {
            // Verificar si EmailJS está disponible
            if (typeof emailjs === 'undefined') {
                console.error('EmailJS no está cargado');
                this.mostrarNotificacion('Error: EmailJS no está configurado', 'error');
                return false;
            }
            
            // Enviar email
            const response = await emailjs.send(
                EMAILJS_CONFIG.serviceID,
                EMAILJS_CONFIG.templateID,
                templateParams,
                EMAILJS_CONFIG.publicKey
            );
            
            console.log('Email enviado:', response);
            this.mostrarNotificacion('Notificación enviada al proveedor', 'success');
            return true;
            
        } catch (error) {
            console.error('Error enviando email:', error);
            this.mostrarNotificacion('Error al enviar notificación', 'error');
            return false;
        }
    }
    
    // Función para configurar EmailJS (llamar al inicio)
    initEmailJS() {
        // Cargar script de EmailJS si no está cargado
        if (typeof emailjs === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js';
            script.onload = () => {
                // Inicializar EmailJS con tu Public Key
                // emailjs.init('TU_PUBLIC_KEY');
                console.log('EmailJS cargado correctamente');
            };
            document.head.appendChild(script);
        }
    }

    limpiarVista() {
        const lista = document.getElementById('proveedores_list');
        if (lista) {
            lista.innerHTML = `
                <div class="empty-state">
                    <h4>📍 Selecciona un restaurante</h4>
                    <p>Para ver los proveedores cercanos</p>
                </div>
            `;
        }
    }

    crearSolicitud(idProveedor) {
        if (!this.restauranteActual) {
            this.mostrarNotificacion('Debes seleccionar un restaurante primero', 'warning');
            return;
        }
        
        // Redirigir a página de creación de solicitud
        window.location.href = `solicitar_pedido_proveedor.php?id_proveedor=${idProveedor}&id_restaurante=${this.restauranteActual.id}`;
    }

    crearSolicitudRapida(idInv) {
        if (!this.restauranteActual) {
            this.mostrarNotificacion('Debes seleccionar un restaurante primero', 'warning');
            return;
        }
        
        // Abrir modal para solicitud rápida
        this.abrirModalSolicitudRapida(idInv);
    }

    abrirModalSolicitudRapida(idInv) {
        // Crear modal simple para solicitud rápida
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            display: flex;
            justify-content: center;
            align-items: center;
        `;
        
        modal.innerHTML = `
            <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%;">
                <h3 style="color: #2c3e50; margin-bottom: 20px;">Solicitud Rápida de Re-stock</h3>
                <p style="color: #666; margin-bottom: 20px;">Selecciona un proveedor para solicitar el insumo.</p>
                <select id="proveedorRapidoSelect" style="width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">Selecciona un proveedor</option>
                    ${this.proveedoresData.map(p => `
                        <option value="${p.id_proveedor}">${p.nombre_empresa} (Tipo ${p.id_tipo_proveedor || 'N/A'})</option>
                    `).join('')}
                </select>
                <div style="display: flex; gap: 10px;">
                    <button onclick="window.proveedoresCercanos.enviarSolicitudRapida(${idInv})" style="flex: 1; background: #27ae60; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer;">Enviar Solicitud</button>
                    <button onclick="window.proveedoresCercanos.cerrarModal()" style="flex: 1; background: #e74c3c; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer;">Cancelar</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        this.modalActual = modal;
    }

    enviarSolicitudRapida(idInv) {
        const proveedorSelect = document.getElementById('proveedorRapidoSelect');
        const idProveedor = proveedorSelect.value;
        
        if (!idProveedor) {
            this.mostrarNotificacion('Selecciona un proveedor', 'warning');
            return;
        }
        
        // Aquí iría la lógica para enviar la solicitud
        this.mostrarNotificacion('Solicitud enviada correctamente', 'success');
        this.cerrarModal();
    }

    cerrarModal() {
        if (this.modalActual) {
            document.body.removeChild(this.modalActual);
            this.modalActual = null;
        }
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear notificación flotante
        const notificacion = document.createElement('div');
        notificacion.className = `sj-notificacion sj-notificacion-${tipo}`;
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
window.actualizarVista = function(idRestaurante) {
    if (window.proveedoresCercanos) {
        window.proveedoresCercanos.actualizarVista(idRestaurante);
    }
};

window.crearSolicitudRapida = function(idInv) {
    if (window.proveedoresCercanos) {
        window.proveedoresCercanos.crearSolicitudRapida(idInv);
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.proveedoresCercanos = new ProveedoresCercanos();
});
