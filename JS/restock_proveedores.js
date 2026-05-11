// Re-stock de Proveedores - Salud Juárez
class RestockProveedores {
    constructor() {
        this.solicitudes = [];
        this.init();
    }

    init() {
        this.cargarSolicitudes();
        this.setupEventListeners();
    }

    async cargarSolicitudes() {
        try {
            const response = await fetch('../PHP/cargar_solicitudes_restock.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.solicitudes = data.solicitudes;
                this.renderizarSolicitudes();
            } else {
                this.mostrarNotificacion('Error al cargar solicitudes', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    renderizarSolicitudes() {
        const grid = document.getElementById('solicitudesGrid');
        
        if (this.solicitudes.length === 0) {
            grid.innerHTML = `
                <div class="sj-empty-state">
                    <div class="sj-empty-icon">📦</div>
                    <div class="sj-empty-title">Sin solicitudes activas</div>
                    <div class="sj-empty-description">Tus solicitudes de re-stock aparecerán aquí</div>
                </div>
            `;
            return;
        }

        grid.innerHTML = this.solicitudes.map(solicitud => `
            <div class="sj-solicitud-item">
                <div class="sj-solicitud-info">
                    <div class="sj-solicitud-producto">${solicitud.nombre_producto}</div>
                    <div class="sj-solicitud-proveedor">${solicitud.nombre_proveedor}</div>
                    <div class="sj-solicitud-fecha">Solicitado: ${this.formatearFecha(solicitud.fecha_solicitud)}</div>
                </div>
                <div class="sj-solicitud-estado ${this.getEstadoClass(solicitud.estado_pedido)}">
                    ${solicitud.estado_pedido}
                </div>
            </div>
        `).join('');
    }

    getEstadoClass(estado) {
        const clases = {
            'Pendiente': 'sj-estado-pendiente',
            'Confirmado': 'sj-estado-confirmado',
            'Enviado': 'sj-estado-enviado',
            'Entregado': 'sj-estado-entregado'
        };
        return clases[estado] || 'sj-estado-pendiente';
    }

    formatearFecha(fecha) {
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    setupEventListeners() {
        // Los event listeners ya se agregan en el HTML con onclick
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

// Hacer función global para onclick
window.configurarAutomatico = function() {
    alert('Función de configuración automática en desarrollo. Próximamente podrás configurar umbrales mínimos para re-stock automático.');
};

window.cargarSolicitudes = function() {
    if (window.restockProveedores) {
        window.restockProveedores.cargarSolicitudes();
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.restockProveedores = new RestockProveedores();
});
