// Solicitud de Pedido a Proveedor - Salud Juárez
class SolicitudPedidoProveedor {
    constructor() {
        this.proveedores = [];
        this.productos = [];
        this.productosSeleccionados = [];
        this.init();
    }

    init() {
        this.cargarProveedores();
        this.setupEventListeners();
    }

    async cargarProveedores() {
        const select = document.getElementById('proveedor');
        
        console.log('🔍 [DIAGNÓSTICO] === INICIO cargarProveedores() ===');
        console.log('🔍 Elemento select encontrado:', select ? '✅ SÍ' : '❌ NO');
        
        try {
            console.log('🔍 [DIAGNÓSTICO] Haciendo fetch a cargar_proveedores_disponibles.php...');
            
            const response = await fetch('../PHP/cargar_proveedores_disponibles.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            console.log('🔍 [DIAGNÓSTICO] Response status:', response.status, response.statusText);
            console.log('🔍 [DIAGNÓSTICO] Response headers:', [...response.headers.entries()]);
            
            const data = await response.json();
            
            console.log('🔍 [DIAGNÓSTICO] Respuesta JSON recibida:', data);
            console.log('🔍 [DIAGNÓSTICO] data.success:', data.success);
            console.log('🔍 [DIAGNÓSTICO] data.proveedores:', data.proveedores);
            console.log('🔍 [DIAGNÓSTICO] Cantidad de proveedores:', data.proveedores ? data.proveedores.length : 'N/A');
            
            // DEBUG INFO del servidor
            if (data.debug_info) {
                console.log('🔍 [DIAGNÓSTICO] Info de debug del servidor:');
                console.log('   - Total proveedores en BD:', data.debug_info.total_proveedores_bd);
                console.log('   - Proveedores activos:', data.debug_info.proveedores_activos);
                console.log('   - Productos disponibles:', data.debug_info.productos_disponibles);
                console.log('   - Encontrados con JOIN:', data.debug_info.encontrados_con_join);
            }
            
            if (data.success) {
                if (data.proveedores && data.proveedores.length > 0) {
                    console.log('✅ [DIAGNÓSTICO] Se encontraron', data.proveedores.length, 'proveedores');
                    this.proveedores = data.proveedores;
                    this.renderizarProveedores();
                } else {
                    // No hay proveedores con productos disponibles
                    console.warn('⚠️ [DIAGNÓSTICO] data.success=true pero NO hay proveedores en el array');
                    select.innerHTML = '<option value="">No hay proveedores con productos</option>';
                    this.mostrarNotificacion('⚠️ No hay proveedores con productos disponibles. Ejecuta el seeder primero.', 'warning', 8000);
                }
            } else {
                console.error('❌ [DIAGNÓSTICO] data.success = false');
                console.error('   Mensaje:', data.message);
                select.innerHTML = '<option value="">Error al cargar</option>';
                this.mostrarNotificacion('❌ ' + (data.message || 'Error al cargar proveedores'), 'error', 6000);
            }
            
            console.log('🔍 [DIAGNÓSTICO] === FIN cargarProveedores() ===');
            
        } catch (error) {
            console.error('❌ [DIAGNÓSTICO] EXCEPCIÓN en fetch:');
            console.error('   Error:', error.message);
            console.error('   Stack:', error.stack);
            select.innerHTML = '<option value="">Error de conexión</option>';
            this.mostrarNotificacion('❌ Error de conexión al servidor', 'error', 6000);
        }
    }

    renderizarProveedores() {
        console.log('🔍 [DIAGNÓSTICO] === INICIO renderizarProveedores() ===');
        
        const select = document.getElementById('proveedor');
        console.log('🔍 Select elemento:', select);
        console.log('🔍 this.proveedores:', this.proveedores);
        console.log('🔍 Cantidad a renderizar:', this.proveedores.length);
        
        select.innerHTML = '<option value="">Selecciona un proveedor</option>';
        
        this.proveedores.forEach((proveedor, index) => {
            console.log(`🔍 Renderizando proveedor #${index}:`, proveedor);
            
            const option = document.createElement('option');
            option.value = proveedor.id_proveedor;
            // CORREGIDO: Usar id_tipo_proveedor en lugar de tipo_proveedor
            const tipoProveedor = proveedor.id_tipo_proveedor ? `Tipo ${proveedor.id_tipo_proveedor}` : 'Sin tipo';
            option.textContent = `${proveedor.nombre_empresa} (${tipoProveedor})`;
            
            console.log(`🔍 Option creada: value=${option.value}, text=${option.textContent}`);
            
            select.appendChild(option);
        });
        
        console.log('🔍 [DIAGNÓSTICO] === FIN renderizarProveedores() ===');
        console.log('🔍 Total options en select:', select.options.length);
    }

    async cargarProductosProveedor(idProveedor) {
        if (!idProveedor) {
            document.getElementById('productosDisponibles').innerHTML = `
                <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                    Selecciona un proveedor para ver sus productos
                </div>
            `;
            return;
        }

        try {
            const response = await fetch(`../PHP/cargar_productos_proveedor_public.php?id_proveedor=${idProveedor}`, {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.productos = data.productos;
                this.renderizarProductos();
            } else {
                document.getElementById('productosDisponibles').innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                        Este proveedor no tiene productos disponibles
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('productosDisponibles').innerHTML = `
                <div style="text-align: center; padding: 20px; color: #e74c3c;">
                    Error al cargar los productos
                </div>
            `;
        }
    }

    renderizarProductos() {
        const container = document.getElementById('productosDisponibles');
        
        if (this.productos.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                    Este proveedor no tiene productos disponibles
                </div>
            `;
            return;
        }

        container.innerHTML = this.productos.map(producto => `
            <div class="sj-producto-item">
                <div class="sj-producto-info">
                    <div class="sj-producto-nombre">${producto.nombre_producto}</div>
                    <div style="color: #95a5a6; font-size: 0.9rem;">${producto.descripcion_producto}</div>
                    <div style="color: #f39c12; font-size: 1.1rem;">$${producto.precio_unitario} / ${producto.unidad_medida}</div>
                </div>
                <div class="sj-producto-cantidad">
                    <input type="number" 
                           class="sj-cantidad-input" 
                           type="number" 
                           min="0.1" 
                           step="0.1" 
                           placeholder="0"
                           data-id="${producto.id_producto}"
                           data-precio="${producto.precio_unitario}"
                           data-nombre="${producto.nombre_producto}"
                           onchange="solicitudPedido.actualizarCantidad(this)">
                    <span>${producto.unidad_medida}</span>
                </div>
            </div>
        `).join('');

        document.getElementById('productosSeleccionados').style.display = 'none';
        this.productosSeleccionados = [];
        this.actualizarTotal();
    }

    actualizarCantidad(input) {
        const idProducto = parseInt(input.dataset.id);
        const cantidad = parseFloat(input.value) || 0;
        const precio = parseFloat(input.dataset.precio);
        const nombre = input.dataset.nombre;

        if (cantidad <= 0) {
            // Eliminar de seleccionados
            this.productosSeleccionados = this.productosSeleccionados.filter(p => p.id_producto !== idProducto);
        } else {
            // Agregar o actualizar en seleccionados
            const existente = this.productosSeleccionados.find(p => p.id_producto === idProducto);
            if (existente) {
                existente.cantidad = cantidad;
                existente.subtotal = cantidad * precio;
            } else {
                this.productosSeleccionados.push({
                    id_producto: idProducto,
                    nombre_producto: nombre,
                    precio_unitario: precio,
                    cantidad: cantidad,
                    subtotal: cantidad * precio
                });
            }
        }

        this.renderizarSeleccionados();
        this.actualizarTotal();
    }

    renderizarSeleccionados() {
        const container = document.getElementById('listaProductosSeleccionados');
        const seleccionDiv = document.getElementById('productosSeleccionados');

        if (this.productosSeleccionados.length === 0) {
            seleccionDiv.style.display = 'none';
            return;
        }

        seleccionDiv.style.display = 'block';
        container.innerHTML = this.productosSeleccionados.map(producto => `
            <div class="sj-producto-item">
                <div class="sj-producto-info">
                    <div class="sj-producto-nombre">${producto.nombre_producto}</div>
                    <div class="sj-producto-precio">$${producto.precio_unitario} c/u</div>
                </div>
                <div class="sj-producto-cantidad">
                    <span>${producto.cantidad} unidades</span>
                    <button type="button" class="sj-eliminar-producto" onclick="solicitudPedido.eliminarProducto(${producto.id_producto})">
                        Eliminar
                    </button>
                </div>
            </div>
        `).join('');
    }

    eliminarProducto(idProducto) {
        this.productosSeleccionados = this.productosSeleccionados.filter(p => p.id_producto !== idProducto);
        
        // Limpiar input correspondiente
        const input = document.querySelector(`input[data-id="${idProducto}"]`);
        if (input) {
            input.value = '';
        }
        
        this.renderizarSeleccionados();
        this.actualizarTotal();
    }

    actualizarTotal() {
        const total = this.productosSeleccionados.reduce((sum, p) => sum + p.subtotal, 0);
        document.getElementById('totalPedido').textContent = `$${total.toFixed(2)}`;
    }

    setupEventListeners() {
        // Cambio de proveedor
        document.getElementById('proveedor').addEventListener('change', (e) => {
            this.cargarProductosProveedor(e.target.value);
        });

        // Envío del formulario
        document.getElementById('formSolicitudPedido').addEventListener('submit', (e) => {
            e.preventDefault();
            this.enviarPedido();
        });
    }

    async enviarPedido() {
        if (this.productosSeleccionados.length === 0) {
            this.mostrarNotificacion('Debes seleccionar al menos un producto', 'error');
            return;
        }

        const idProveedor = document.getElementById('proveedor').value;
        const direccionEntrega = document.getElementById('direccionEntrega').value;
        const metodoPago = document.getElementById('metodoPago').value;
        const notasPedido = document.getElementById('notasPedido').value;

        if (!idProveedor || !direccionEntrega || !metodoPago) {
            this.mostrarNotificacion('Completa todos los campos obligatorios', 'error');
            return;
        }

        try {
            // 1. ENVIAR PETICIÓN AL BACKEND (PHP) - CON TRANSACCIÓN
            console.log('📤 [DIAGNÓSTICO] Enviando pedido al servidor...');
            console.log('📤 Datos:', {
                id_proveedor: idProveedor,
                productos: this.productosSeleccionados,
                direccion_entrega: direccionEntrega,
                metodo_pago: metodoPago
            });
            
            const response = await fetch('../PHP/crear_solicitud_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_proveedor: idProveedor,
                    productos: this.productosSeleccionados,
                    direccion_entrega: direccionEntrega,
                    metodo_pago: metodoPago,
                    notas_pedido: notasPedido
                })
            });

            console.log('📥 [DIAGNÓSTICO] Response status:', response.status, response.statusText);
            
            // Capturar el texto crudo de la respuesta para debugging
            const responseText = await response.text();
            console.log('📥 [DIAGNÓSTICO] Response text (crudo):', responseText.substring(0, 500));
            
            // Intentar parsear como JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('❌ [DIAGNÓSTICO] ERROR PARSING JSON:', parseError);
                console.error('❌ Texto completo de respuesta:', responseText);
                throw new Error('El servidor devolvió una respuesta inválida. Ver consola para detalles.');
            }
            
            console.log('📥 [DIAGNÓSTICO] Datos JSON parseados:', data);

            // 2. VERIFICAR RESPUESTA DEL PHP (success: true/false)
            if (data.success === true) {
                // Pedido guardado exitosamente en BD
                
                // 3. DISPARAR EMAILJS CONDICIONALMENTE (solo si PHP retorna success)
                let emailEnviado = false;
                if (data.datos_email) {
                    emailEnviado = await this.enviarEmailJS(data.datos_email);
                }
                
                // 4. MOSTRAR NOTIFICACIÓN SEGÚN ESTADO
                if (emailEnviado) {
                    // ÉXITO TOTAL: Pedido guardado + Email enviado
                    this.mostrarNotificacion('✅ Pedido guardado y proveedor notificado correctamente', 'success', 5000);
                } else {
                    // ÉXITO PARCIAL: Pedido guardado pero email falló
                    this.mostrarNotificacion('⚠️ Pedido guardado, pero falló la notificación por correo', 'warning', 6000);
                }
                
                // Limpiar formulario
                document.getElementById('formSolicitudPedido').reset();
                this.productosSeleccionados = [];
                this.renderizarSeleccionados();
                this.actualizarTotal();
                
                // Mostrar número de pedido
                if (data.id_pedido) {
                    setTimeout(() => {
                        this.mostrarNotificacion(`Número de pedido: #${data.id_pedido}`, 'info');
                    }, 2000);
                }
            } else {
                // ERROR DEL PHP: Transacción falló
                this.mostrarNotificacion('❌ ' + (data.message || 'Error al procesar la solicitud'), 'error', 6000);
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificacion('❌ Error de conexión. Verifica tu conexión e intenta de nuevo.', 'error', 6000);
        }
    }
    
    /**
     * Enviar correo vía EmailJS - Template: Alerta de Solicitud de Re-stock B2B
     * Template ID: template_p8hu9qn
     * @param {Object} params - Datos del pedido formateados
     * @returns {Promise<boolean>} - true si se envió correctamente
     */
    async enviarEmailJS(params) {
        // 1. Forzamos la fecha y hora exacta de la solicitud en los parámetros
        params.fecha_pedido = new Date().toLocaleString('es-MX', { timeZone: 'America/Ciudad_Juarez' });

        try {
            // 2. IMPORTANTE: Usa la llave directamente aquí para evitar el 404
            const PUBLIC_KEY = "bJjfLm9SYVJvjQSNk";
            const CORRECT_SERVICE_ID = "service_t8yl29t";
            
            console.log("📤 Iniciando transferencia a EmailJS para el Pedido:", params.id_pedido);
            console.log("🔧 Validación: Service ID =", CORRECT_SERVICE_ID);
            console.log("🔧 Validación: Template ID =", "template_p8hu9qn");

            const response = await emailjs.send(
                CORRECT_SERVICE_ID, 
                "template_p8hu9qn", 
                params, 
                PUBLIC_KEY // Pasamos la llave aquí para matar el error 404
            );

            return true;
        } catch (error) {
            console.error("❌ Fallo en la transferencia de datos:", error);
            return false;
        }
    }

    mostrarNotificacion(mensaje, tipo = 'info', duracion = 4000) {
        // Crear notificación flotante
        const notificacion = document.createElement('div');
        notificacion.className = `sj-notificacion sj-notificacion-${tipo}`;
        
        // Colores según tipo
        const colores = {
            success: { bg: '#27ae60', icon: '✅' },
            error: { bg: '#e74c3c', icon: '❌' },
            info: { bg: '#3498db', icon: 'ℹ️' },
            warning: { bg: '#f39c12', icon: '⚠️' }
        };
        
        const estilo = colores[tipo] || colores.info;
        
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 20px;
            border-radius: 8px;
            background: ${estilo.bg};
            color: white;
            font-weight: 500;
            z-index: 10000;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            max-width: 350px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        `;

        notificacion.innerHTML = `
            <span style="font-size: 20px;">${estilo.icon}</span>
            <span>${mensaje}</span>
        `;

        // Agregar al DOM
        document.body.appendChild(notificacion);

        // Animar entrada
        requestAnimationFrame(() => {
            notificacion.style.transform = 'translateX(0)';
        });

        // Remover después de la duración especificada
        setTimeout(() => {
            notificacion.style.transform = 'translateX(120%)';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 400);
        }, duracion);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.solicitudPedido = new SolicitudPedidoProveedor();
});
