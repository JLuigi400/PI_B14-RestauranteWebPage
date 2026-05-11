// Gestión de Pedidos para Proveedores - Salud Juárez
class GestionPedidosProveedor {
    constructor() {
        this.pedidos = [];
        this.filtroEstado = '';
        this.filtroFecha = '';
        this.init();
    }

    init() {
        this.cargarPedidos();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Filtros
        document.getElementById('filtroEstado').addEventListener('change', () => this.filtrarPedidos());
        document.getElementById('filtroFecha').addEventListener('change', () => this.filtrarPedidos());
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalDetalles').addEventListener('click', (e) => {
            if (e.target.id === 'modalDetalles') {
                this.cerrarModal();
            }
        });
    }

    async cargarPedidos() {
        // Mostrar notificación de carga
        this.mostrarNotificacion('Actualizando pedidos...', 'info');
        
        try {
            const response = await fetch('../PHP/cargar_pedidos_proveedor.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Comparar con datos anteriores para detectar cambios
                const pedidosAnteriores = this.pedidos.length;
                const nuevosPedidos = data.pedidos.length;
                
                this.pedidos = data.pedidos;
                this.renderizarPedidos();
                
                // Notificar cambios si hay nuevos pedidos
                if (nuevosPedidos > pedidosAnteriores) {
                    const nuevos = nuevosPedidos - pedidosAnteriores;
                    this.mostrarNotificacion(`Tienes ${nuevos} nuevo(s) pedido(s)`, 'success');
                } else {
                    this.mostrarNotificacion('Pedidos actualizados correctamente', 'success');
                }
                
                // Actualizar timestamp de última actualización
                this.ultimaActualizacion = new Date();
                
            } else {
                this.mostrarNotificacion('Error al cargar los pedidos', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    renderizarPedidos() {
        const grid = document.getElementById('pedidosGrid');
        const pedidosFiltrados = this.filtrarPedidosArray();
        
        if (pedidosFiltrados.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <div style="color: #7f8c8d; font-size: 1.2rem; margin-bottom: 10px;">
                        No tienes pedidos ${this.filtroEstado || this.filtroFecha ? 'con estos filtros' : 'recibidos'}
                    </div>
                    <div style="color: #95a5a6;">
                        ${this.filtroEstado || this.filtroFecha ? 'Intenta con otros filtros' : 'Los pedidos aparecerán aquí cuando los restaurantes te contacten'}
                    </div>
                </div>
            `;
            return;
        }

        grid.innerHTML = pedidosFiltrados.map(pedido => this.crearTarjetaPedido(pedido)).join('');
        this.setupTarjetaEventListeners();
    }

    crearTarjetaPedido(pedido) {
        const estadoClass = this.getEstadoClass(pedido.estado_pedido);
        const estadoTexto = pedido.estado_pedido;
        
        return `
            <div class="sj-pedido-card" data-id="${pedido.id_pedido}">
                <div class="sj-pedido-header">
                    <span class="sj-pedido-estado ${estadoClass}">${estadoTexto}</span>
                    <span class="sj-pedido-id">#${pedido.id_pedido}</span>
                </div>
                <div class="sj-pedido-body">
                    <div class="sj-pedido-info">
                        <span class="sj-pedido-info-label">Restaurante:</span>
                        <span class="sj-pedido-restaurante">${pedido.nombre_restaurante || 'Cargando...'}</span>
                    </div>
                    <div class="sj-pedido-info">
                        <span class="sj-pedido-info-label">Fecha:</span>
                        <span class="sj-pedido-info-value">${this.formatearFecha(pedido.fecha_solicitud)}</span>
                    </div>
                    <div class="sj-pedido-info">
                        <span class="sj-pedido-info-label">Total:</span>
                        <span class="sj-pedido-info-value">$${pedido.total_pedido}</span>
                    </div>
                    <div class="sj-pedido-total">
                        $${pedido.total_pedido}
                    </div>
                </div>
                <div class="sj-pedido-acciones">
                    <button class="sj-accion-btn" onclick="gestionPedidos.verDetalles(${pedido.id_pedido})">Ver Detalles</button>
                    ${this.getBotonesAccion(pedido)}
                </div>
            </div>
        `;
    }

    getEstadoClass(estado) {
        const clases = {
            'Pendiente': 'sj-estado-pendiente',
            'Confirmado': 'sj-estado-confirmado',
            'Enviado': 'sj-estado-enviado',
            'Entregado': 'sj-estado-entregado',
            'Cancelado': 'sj-estado-cancelado'
        };
        return clases[estado] || 'sj-estado-pendiente';
    }

    getBotonesAccion(pedido) {
        switch(pedido.estado_pedido) {
            case 'Pendiente':
                return `
                    <button class="sj-accion-btn confirmar" onclick="gestionPedidos.confirmarPedido(${pedido.id_pedido})">Confirmar</button>
                    <button class="sj-accion-btn cancelar" onclick="gestionPedidos.cancelarPedido(${pedido.id_pedido})">Cancelar</button>
                `;
            case 'Confirmado':
                return `
                    <button class="sj-accion-btn enviar" onclick="gestionPedidos.enviarPedido(${pedido.id_pedido})">Enviar</button>
                `;
            case 'Enviado':
                return `
                    <button class="sj-accion-btn entregado" onclick="gestionPedidos.entregarPedido(${pedido.id_pedido})">Marcar Entregado</button>
                `;
            default:
                return '';
        }
    }

    filtrarPedidosArray() {
        return this.pedidos.filter(pedido => {
            let cumpleEstado = !this.filtroEstado || pedido.estado_pedido === this.filtroEstado;
            let cumpleFecha = !this.filtroFecha || this.cumpleFiltro(pedido.fecha_solicitud);
            return cumpleEstado && cumpleFecha;
        });
    }

    cumpleFiltro(fecha) {
        const fechaPedido = new Date(fecha);
        const hoy = new Date();
        
        switch(this.filtroFecha) {
            case 'hoy':
                return fechaPedido.toDateString() === hoy.toDateString();
            case 'semana':
                const semanaAtras = new Date(hoy.getTime() - 7 * 24 * 60 * 60 * 1000);
                return fechaPedido >= semanaAtras;
            case 'mes':
                const mesAtras = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
                return fechaPedido >= mesAtras;
            default:
                return true;
        }
    }

    async verDetalles(idPedido) {
        try {
            const response = await fetch(`../PHP/cargar_detalles_pedido.php?id_pedido=${idPedido}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarModalDetalles(data.pedido);
            } else {
                this.mostrarNotificacion('Error al cargar los detalles', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    mostrarModalDetalles(pedido) {
        const modal = document.getElementById('modalDetalles');
        const contenido = document.getElementById('detallesContenido');
        
        contenido.innerHTML = `
            <div style="margin-bottom: 20px;">
                <h3 style="color: #27ae60; margin-bottom: 15px;">Información del Pedido</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <strong style="color: #ecf0f1;">ID Pedido:</strong> #${pedido.id_pedido}<br>
                        <strong style="color: #ecf0f1;">Estado:</strong> <span class="sj-pedido-estado ${this.getEstadoClass(pedido.estado_pedido)}">${pedido.estado_pedido}</span><br>
                        <strong style="color: #ecf0f1;">Fecha Solicitud:</strong> ${this.formatearFecha(pedido.fecha_solicitud)}
                    </div>
                    <div>
                        <strong style="color: #ecf0f1;">Restaurante:</strong> ${pedido.nombre_restaurante}<br>
                        <strong style="color: #ecf0f1;">Contacto:</strong> ${pedido.contacto_restaurante}<br>
                        <strong style="color: #ecf0f1;">Teléfono:</strong> ${pedido.telefono_restaurante}
                    </div>
                </div>
            </div>
            
            <h3 style="color: #27ae60; margin-bottom: 15px;">Productos Solicitados</h3>
            <div class="sj-productos-lista">
                ${pedido.detalles.map(detalle => `
                    <div class="sj-producto-item">
                        <span class="sj-producto-nombre">${detalle.nombre_producto}</span>
                        <span class="sj-producto-cantidad">${detalle.cantidad_solicitada} ${detalle.unidad_medida}</span>
                        <span class="sj-producto-precio">$${detalle.precio_unitario}</span>
                    </div>
                `).join('')}
            </div>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #34495e;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: #ecf0f1;">Subtotal:</strong> $${pedido.subtotal_productos}<br>
                        <strong style="color: #ecf0f1;">Envío:</strong> $${pedido.costo_envio}<br>
                        <strong style="color: #f39c12; font-size: 1.2rem;">Total:</strong> $${pedido.total_pedido}
                    </div>
                    <div>
                        <strong style="color: #ecf0f1;">Dirección Entrega:</strong><br>
                        ${pedido.direccion_entrega || 'No especificada'}<br>
                        <strong style="color: #ecf0f1;">Método Pago:</strong> ${pedido.metodo_pago || 'No especificado'}
                    </div>
                </div>
            </div>
        `;
        
        modal.style.display = 'flex';
    }

    cerrarModal() {
        document.getElementById('modalDetalles').style.display = 'none';
    }

    async confirmarPedido(idPedido) {
        if (!confirm('¿Estás seguro de confirmar este pedido?')) return;
        
        await this.ejecutarAccion(idPedido, 'confirmar');
    }

    async enviarPedido(idPedido) {
        if (!confirm('¿Estás seguro de marcar este pedido como enviado?')) return;
        
        await this.ejecutarAccion(idPedido, 'enviar');
    }

    async entregarPedido(idPedido) {
        if (!confirm('¿Estás seguro de marcar este pedido como entregado?')) return;
        
        await this.ejecutarAccion(idPedido, 'entregar');
    }

    async cancelarPedido(idPedido) {
        if (!confirm('¿Estás seguro de cancelar este pedido? Esta acción no se puede deshacer.')) return;
        
        await this.ejecutarAccion(idPedido, 'cancelar');
    }

    async ejecutarAccion(idPedido, accion) {
        try {
            const response = await fetch('../PHP/procesar_accion_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_pedido: idPedido,
                    accion: accion
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.mostrarNotificacion(data.message, 'success');
                await this.cargarPedidos(); // Recargar lista
            } else {
                this.mostrarNotificacion(data.message || 'Error al procesar la acción', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    setupTarjetaEventListeners() {
        // Los event listeners ya se agregan en el HTML con onclick
    }

    formatearFecha(fecha) {
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    filtrarPedidos() {
        this.filtroEstado = document.getElementById('filtroEstado').value;
        this.filtroFecha = document.getElementById('filtroFecha').value;
        this.renderizarPedidos();
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
window.cargarPedidos = function() {
    if (window.gestionPedidos) {
        window.gestionPedidos.cargarPedidos();
    }
};

window.verDetalles = function(idPedido) {
    if (window.gestionPedidos) {
        window.gestionPedidos.verDetalles(idPedido);
    }
};

window.confirmarPedido = function(idPedido) {
    if (window.gestionPedidos) {
        window.gestionPedidos.confirmarPedido(idPedido);
    }
};

window.enviarPedido = function(idPedido) {
    if (window.gestionPedidos) {
        window.gestionPedidos.enviarPedido(idPedido);
    }
};

window.entregarPedido = function(idPedido) {
    if (window.gestionPedidos) {
        window.gestionPedidos.entregarPedido(idPedido);
    }
};

window.cancelarPedido = function(idPedido) {
    if (window.gestionPedidos) {
        window.gestionPedidos.cancelarPedido(idPedido);
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.gestionPedidos = new GestionPedidosProveedor();
});
