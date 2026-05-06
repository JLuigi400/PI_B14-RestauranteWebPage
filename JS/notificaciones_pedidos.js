/**
 * =========================================================
 * MÓDULO DE NOTIFICACIONES Y EMAILJS - SALUD JUÁREZ B2B
 * =========================================================
 * Gestiona alertas en pantalla y envíos de correo automático
 * Requiere: <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
 */

class NotificadorB2B {
    constructor() {
        // IDs de EmailJS (Ajustar con las credenciales reales de tu cuenta EmailJS)
        this.serviceID = 'servicio_salud_juarez'; 
        this.templateNuevoPedido = 'template_nuevo_pedido';
        this.templateCopiaAdmin = 'template_copia_admin';
        
        // Inicializar EmailJS si está cargado en el DOM
        if (typeof emailjs !== 'undefined') {
            emailjs.init("TU_PUBLIC_KEY_DE_EMAILJS"); // Reemplazar con tu clave real
        } else {
            console.warn("EmailJS no está cargado en el documento.");
        }

        this.initToastContainer();
    }

    /**
     * 1. CONTENEDOR VISUAL (Zero Bootstrap)
     * Crea un contenedor flotante para las alertas nativas (Toasts)
     */
    initToastContainer() {
        if (!document.getElementById('sj-toast-container')) {
            const container = document.createElement('div');
            container.id = 'sj-toast-container';
            container.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 12px;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }
    }

    /**
     * 2. MOSTRAR ALERTA EN PANTALLA
     * @param {string} mensaje - Texto de la alerta
     * @param {string} tipo - 'success', 'warning', 'error', 'info'
     */
    mostrarAlerta(mensaje, tipo = 'info') {
        const colores = {
            success: '#27ae60', // Verde Salud
            warning: '#f39c12', // Alerta Naranja
            error: '#e74c3c',   // Error Rojo
            info: '#3498db'     // Azul Nutrición
        };

        const toast = document.createElement('div');
        toast.className = `sj-toast sj-toast-${tipo}`;
        toast.style.cssText = `
            background-color: #1a1a1a;
            border-left: 6px solid ${colores[tipo]};
            color: #ffffff;
            padding: 16px 24px;
            border-radius: 6px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.4);
            font-family: inherit;
            min-width: 280px;
            pointer-events: auto;
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 12px;
        `;
        
        toast.innerHTML = `<span>${mensaje}</span>`;
        document.getElementById('sj-toast-container').appendChild(toast);

        // Animación de entrada
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(0)';
        });

        // Animación de salida tras 4.5 segundos
        setTimeout(() => {
            toast.style.transform = 'translateX(120%)';
            setTimeout(() => toast.remove(), 300);
        }, 4500);
    }

    /**
     * 3. INTEGRACIÓN CON EMAILJS (Envío a Proveedor)
     * Basado en las especificaciones del template 
     * @param {Object} datosPedido - Objeto con la información de la transacción
     */
    notificarNuevoPedido(datosPedido) {
        if (typeof emailjs === 'undefined') return;

        const parametrosTemplate = {
            pedido_num: datosPedido.numero_pedido,
            nombre_proveedor: datosPedido.nombre_proveedor,
            email_proveedor: datosPedido.email_proveedor,
            restaurante_nombre: datosPedido.nombre_restaurante,
            fecha_pedido: new Date().toLocaleDateString(),
            nivel_urgencia: datosPedido.urgencia,
            lista_productos: datosPedido.resumen_productos,
            total_pedido: datosPedido.total_pedido,
            direccion_entrega: datosPedido.direccion_entrega,
            nombre_contacto: datosPedido.contacto_entrega,
            nombre_dueno: datosPedido.nombre_dueno
        };

        emailjs.send(this.serviceID, this.templateNuevoPedido, parametrosTemplate)
            .then((response) => {
                console.log('Correo B2B enviado con éxito', response.status);
                this.mostrarAlerta('Proveedor notificado por correo', 'success');
            })
            .catch((error) => {
                console.error('Fallo al enviar notificación EmailJS', error);
                this.mostrarAlerta('El pedido se guardó, pero hubo un error al enviar el correo', 'warning');
            });
    }

    /**
     * 4. POLLING DE PEDIDOS (Escucha en tiempo real para el Proveedor)
     * @param {number} idProveedor - ID del usuario proveedor autenticado
     */
    iniciarEscuchaPedidos(idProveedor) {
        // Consultar el backend cada 30 segundos buscando el estatus "Pendiente"
        setInterval(() => {
            fetch(`../PHP/api_checar_pedidos.php?id_prov=${idProveedor}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.nuevos_pedidos > 0) {
                        this.mostrarAlerta(`🔔 Tienes ${data.nuevos_pedidos} pedido(s) nuevo(s) por revisar.`, 'warning');
                        // Opcional: recargar el grid de pedidos si estamos en esa vista
                        if (typeof actualizarGridPedidos === 'function') {
                            actualizarGridPedidos();
                        }
                    }
                })
                .catch(err => console.error("Error en polling de pedidos:", err));
        }, 30000);
    }
}

// Instanciar globalmente para su uso en toda la plataforma
const NotificacionesSJ = new NotificadorB2B();