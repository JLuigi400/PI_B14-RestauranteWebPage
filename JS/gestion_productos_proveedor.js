// Gestión de Productos para Proveedores - Salud Juárez
class GestionProductosProveedor {
    constructor() {
        this.productos = [];
        this.categoriaActual = 'todos';
        this.editandoProducto = null;
        
        this.init();
    }

    init() {
        this.cargarProductos();
        this.setupEventListeners();
        this.setupValidaciones();
    }

    setupEventListeners() {
        // Botones principales
        document.getElementById('btnAgregarProducto').addEventListener('click', () => this.abrirModal());
        document.getElementById('btnCerrarModal').addEventListener('click', () => this.cerrarModal());
        document.getElementById('btnCancelar').addEventListener('click', () => this.cerrarModal());
        
        // Formulario
        document.getElementById('formProducto').addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Filtros de categorías
        document.querySelectorAll('.sj-filtro-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.filtrarPorCategoria(e));
        });
        
        // Validaciones de precios
        document.getElementById('precioMayoreo').addEventListener('input', () => this.validarPrecios());
        document.getElementById('precioUnitario').addEventListener('input', () => this.validarPrecios());
        
        // Previsualización de imagen
        document.getElementById('imagenProducto').addEventListener('change', (e) => this.previsualizarImagen(e));
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalProducto').addEventListener('click', (e) => {
            if (e.target.id === 'modalProducto') {
                this.cerrarModal();
            }
        });
    }

    setupValidaciones() {
        // Validación de precios dinámicos
        this.setupValidacionPrecios();
    }

    setupValidacionPrecios() {
        const precioUnitario = document.getElementById('precioUnitario');
        const precioMayoreo = document.getElementById('precioMayoreo');
        const cantidadMinima = document.getElementById('cantidadMinima');
        const grupoCantidadMinima = document.getElementById('grupoCantidadMinima');

        // Función de validación
        const validar = () => {
            const precioUnit = parseFloat(precioUnitario.value) || 0;
            const precioMay = parseFloat(precioMayoreo.value) || 0;

            // Si hay precio de mayoreo, mostrar campo de cantidad mínima
            if (precioMayoreo.value && precioMay > 0) {
                grupoCantidadMinima.style.display = 'block';
                cantidadMinima.required = true;

                // Validar que precio de mayoreo sea menor al unitario
                if (precioMay >= precioUnit && precioUnit > 0) {
                    precioMayoreo.setCustomValidity('El precio de mayoreo debe ser menor al precio unitario');
                    this.mostrarError(precioMayoreo, 'El precio de mayoreo debe ser menor al precio unitario');
                } else {
                    precioMayoreo.setCustomValidity('');
                    this.limpiarError(precioMayoreo);
                }
            } else {
                grupoCantidadMinima.style.display = 'none';
                cantidadMinima.required = false;
                cantidadMinima.value = '';
                precioMayoreo.setCustomValidity('');
                this.limpiarError(precioMayoreo);
            }
        };

        // Agregar event listeners
        precioUnitario.addEventListener('input', validar);
        precioMayoreo.addEventListener('input', validar);
    }

    mostrarError(elemento, mensaje) {
        // Eliminar error anterior si existe
        this.limpiarError(elemento);
        
        // Crear mensaje de error
        const errorDiv = document.createElement('div');
        errorDiv.className = 'sj-error-mensaje';
        errorDiv.style.cssText = `
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        `;
        errorDiv.textContent = mensaje;
        
        // Insertar después del elemento
        elemento.parentNode.insertBefore(errorDiv, elemento.nextSibling);
        elemento.style.borderColor = '#e74c3c';
    }

    limpiarError(elemento) {
        const errorDiv = elemento.parentNode.querySelector('.sj-error-mensaje');
        if (errorDiv) {
            errorDiv.remove();
        }
        elemento.style.borderColor = '#34495e';
    }

    async cargarProductos() {
        try {
            const response = await fetch('../PHP/cargar_productos_proveedor.php');
            const data = await response.json();
            
            if (data.success) {
                this.productos = data.productos;
                this.renderizarProductos();
            } else {
                console.error('Error al cargar productos:', data.message);
                this.mostrarNotificacion('Error al cargar los productos', 'error');
            }
        } catch (error) {
            console.error('Error en la petición:', error);
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    renderizarProductos() {
        const grid = document.getElementById('productosGrid');
        const productosFiltrados = this.categoriaActual === 'todos' 
            ? this.productos 
            : this.productos.filter(p => p.categoria_producto === this.categoriaActual);

        if (productosFiltrados.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <div style="color: #7f8c8d; font-size: 1.2rem; margin-bottom: 10px;">
                        ${this.categoriaActual === 'todos' ? 'No tienes productos registrados' : 'No hay productos en esta categoría'}
                    </div>
                    <div style="color: #95a5a6;">
                        ${this.categoriaActual === 'todos' ? 'Haz clic en el botón + para agregar tu primer producto' : 'Intenta con otra categoría o agrega nuevos productos'}
                    </div>
                </div>
            `;
            return;
        }

        grid.innerHTML = productosFiltrados.map(producto => this.crearTarjetaProducto(producto)).join('');
        
        // Agregar event listeners a las tarjetas
        this.setupTarjetaEventListeners();
    }

    crearTarjetaProducto(producto) {
        const imagenUrl = producto.imagen_producto 
            ? `../IMG/UPLOADS/INSUMOS/${producto.imagen_producto}` 
            : '../IMG/placeholder-producto.jpg';

        const disponibilidadClass = producto.disponibilidad ? 'active' : '';
        const disponibilidadText = producto.disponibilidad ? 'Disponible' : 'Agotado';
        
        const precioMayoreoHtml = producto.precio_mayoreo && producto.cantidad_minima_mayoreo 
            ? `<div class="sj-precio-mayoreo">Mayoreo: $${producto.precio_mayoreo} (${producto.cantidad_minima_mayoreo}+)</div>`
            : '';

        return `
            <div class="sj-producto-card" data-id="${producto.id_producto}">
                <img src="${imagenUrl}" alt="${producto.nombre_producto}" class="sj-producto-imagen" onerror="this.src='../IMG/placeholder-producto.jpg'">
                <div class="sj-producto-info">
                    <h3 class="sj-producto-nombre">${producto.nombre_producto}</h3>
                    <div class="sj-producto-categoria">${producto.categoria_producto}</div>
                    <p class="sj-producto-descripcion">${producto.descripcion_producto}</p>
                    <div class="sj-producto-precios">
                        <div class="sj-precio-unitario">$${producto.precio_unitario} / ${producto.unidad_medida}</div>
                        ${precioMayoreoHtml}
                    </div>
                    <div class="sj-producto-acciones">
                        <div class="sj-disponibilidad-toggle ${disponibilidadClass}" 
                             data-id="${producto.id_producto}" 
                             data-disponibilidad="${producto.disponibilidad}"
                             title="${disponibilidadText}">
                        </div>
                        <button class="sj-acciones-btn editar" data-id="${producto.id_producto}">Editar</button>
                        <button class="sj-acciones-btn eliminar" data-id="${producto.id_producto}">Eliminar</button>
                    </div>
                </div>
            </div>
        `;
    }

    setupTarjetaEventListeners() {
        // Toggles de disponibilidad
        document.querySelectorAll('.sj-disponibilidad-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => this.toggleDisponibilidad(e));
        });

        // Botones de editar
        document.querySelectorAll('.sj-acciones-btn.editar').forEach(btn => {
            btn.addEventListener('click', (e) => this.editarProducto(e));
        });

        // Botones de eliminar
        document.querySelectorAll('.sj-acciones-btn.eliminar').forEach(btn => {
            btn.addEventListener('click', (e) => this.eliminarProducto(e));
        });
    }

    async toggleDisponibilidad(e) {
        const toggle = e.target;
        const idProducto = toggle.dataset.id;
        const disponibilidadActual = toggle.dataset.disponibilidad === '1';
        const nuevaDisponibilidad = disponibilidadActual ? 0 : 1;

        try {
            const response = await fetch('../PHP/toggle_disponibilidad_producto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_producto: idProducto,
                    disponibilidad: nuevaDisponibilidad
                })
            });

            const data = await response.json();

            if (data.success) {
                // Actualizar toggle
                toggle.classList.toggle('active');
                toggle.dataset.disponibilidad = nuevaDisponibilidad;
                toggle.title = nuevaDisponibilidad ? 'Disponible' : 'Agotado';

                // Actualizar producto en el array
                const producto = this.productos.find(p => p.id_producto == idProducto);
                if (producto) {
                    producto.disponibilidad = nuevaDisponibilidad;
                }

                this.mostrarNotificacion(
                    nuevaDisponibilidad ? 'Producto marcado como disponible' : 'Producto marcado como agotado',
                    'success'
                );
            } else {
                this.mostrarNotificacion('Error al actualizar disponibilidad', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    editarProducto(e) {
        const idProducto = e.target.dataset.id;
        const producto = this.productos.find(p => p.id_producto == idProducto);
        
        if (producto) {
            this.editandoProducto = producto;
            this.llenarFormulario(producto);
            this.abrirModal(true);
        }
    }

    async eliminarProducto(e) {
        const idProducto = e.target.dataset.id;
        const producto = this.productos.find(p => p.id_producto == idProducto);
        
        if (!producto) return;

        const confirmar = confirm(`¿Estás seguro de eliminar "${producto.nombre_producto}"? Esta acción no se puede deshacer.`);
        
        if (!confirmar) return;

        try {
            const response = await fetch('../PHP/eliminar_producto_proveedor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id_producto: idProducto })
            });

            const data = await response.json();

            if (data.success) {
                // Eliminar del array
                this.productos = this.productos.filter(p => p.id_producto != idProducto);
                
                // Re-renderizar
                this.renderizarProductos();
                
                this.mostrarNotificacion('Producto eliminado correctamente', 'success');
            } else {
                this.mostrarNotificacion('Error al eliminar producto', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    filtrarPorCategoria(e) {
        const btn = e.target;
        const categoria = btn.dataset.categoria;

        // Actualizar botón activo
        document.querySelectorAll('.sj-filtro-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Actualizar categoría y renderizar
        this.categoriaActual = categoria;
        this.renderizarProductos();
    }

    abrirModal(editando = false) {
        const modal = document.getElementById('modalProducto');
        const title = document.getElementById('modalTitle');
        
        title.textContent = editando ? 'Editar Producto' : 'Agregar Producto';
        modal.style.display = 'flex';
        
        if (!editando) {
            this.limpiarFormulario();
            this.editandoProducto = null;
        }
    }

    cerrarModal() {
        const modal = document.getElementById('modalProducto');
        modal.style.display = 'none';
        this.limpiarFormulario();
        this.editandoProducto = null;
    }

    llenarFormulario(producto) {
        document.getElementById('idProducto').value = producto.id_producto;
        document.getElementById('nombreProducto').value = producto.nombre_producto;
        document.getElementById('descripcionProducto').value = producto.descripcion_producto;
        document.getElementById('categoriaProducto').value = producto.categoria_producto;
        document.getElementById('unidadMedida').value = producto.unidad_medida;
        document.getElementById('precioUnitario').value = producto.precio_unitario;
        document.getElementById('precioMayoreo').value = producto.precio_mayoreo || '';
        document.getElementById('cantidadMinima').value = producto.cantidad_minima_mayoreo || '';

        // Mostrar imagen si existe
        if (producto.imagen_producto) {
            const imagenPreview = document.getElementById('imagenPreview');
            imagenPreview.innerHTML = `<img src="../IMG/UPLOADS/INSUMOS/${producto.imagen_producto}" alt="Previsualización">`;
        }

        // Disparar validación de precios
        this.validarPrecios();
    }

    limpiarFormulario() {
        document.getElementById('formProducto').reset();
        document.getElementById('idProducto').value = '';
        document.getElementById('imagenPreview').innerHTML = '<span>Previsualización de imagen</span>';
        document.getElementById('grupoCantidadMinima').style.display = 'none';
        
        // Limpiar errores
        document.querySelectorAll('.sj-error-mensaje').forEach(error => error.remove());
        document.querySelectorAll('.sj-form-input, .sj-form-select').forEach(input => {
            input.style.borderColor = '#34495e';
        });
    }

    previsualizarImagen(e) {
        const archivo = e.target.files[0];
        const preview = document.getElementById('imagenPreview');

        if (archivo) {
            // Validar que sea una imagen
            if (!archivo.type.startsWith('image/')) {
                this.mostrarNotificacion('Por favor, selecciona un archivo de imagen válido', 'error');
                e.target.value = '';
                preview.innerHTML = '<span>Previsualización de imagen</span>';
                return;
            }

            // Validar tamaño (máximo 5MB)
            if (archivo.size > 5 * 1024 * 1024) {
                this.mostrarNotificacion('La imagen no debe superar los 5MB', 'error');
                e.target.value = '';
                preview.innerHTML = '<span>Previsualización de imagen</span>';
                return;
            }

            // Crear previsualización
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `<img src="${e.target.result}" alt="Previsualización">`;
            };
            reader.readAsDataURL(archivo);
        } else {
            preview.innerHTML = '<span>Previsualización de imagen</span>';
        }
    }

    validarPrecios() {
        const precioUnitario = document.getElementById('precioUnitario');
        const precioMayoreo = document.getElementById('precioMayoreo');
        const cantidadMinima = document.getElementById('cantidadMinima');
        const grupoCantidadMinima = document.getElementById('grupoCantidadMinima');

        const precioUnit = parseFloat(precioUnitario.value) || 0;
        const precioMay = parseFloat(precioMayoreo.value) || 0;

        // Si hay precio de mayoreo, mostrar y validar cantidad mínima
        if (precioMayoreo.value && precioMay > 0) {
            grupoCantidadMinima.style.display = 'block';
            cantidadMinima.required = true;

            if (precioMay >= precioUnit && precioUnit > 0) {
                precioMayoreo.setCustomValidity('El precio de mayoreo debe ser menor al precio unitario');
                this.mostrarError(precioMayoreo, 'El precio de mayoreo debe ser menor al precio unitario');
                return false;
            } else {
                precioMayoreo.setCustomValidity('');
                this.limpiarError(precioMayoreo);
            }
        } else {
            grupoCantidadMinima.style.display = 'none';
            cantidadMinima.required = false;
            cantidadMinima.value = '';
            precioMayoreo.setCustomValidity('');
            this.limpiarError(precioMayoreo);
        }

        return true;
    }

    async handleSubmit(e) {
        e.preventDefault();

        // Validar precios antes de enviar
        if (!this.validarPrecios()) {
            this.mostrarNotificacion('Por favor, corrige los errores en los precios', 'error');
            return;
        }

        const formData = new FormData(e.target);
        const editando = this.editandoProducto !== null;

        try {
            const response = await fetch('../PHP/procesar_producto_proveedor.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarNotificacion(
                    editando ? 'Producto actualizado correctamente' : 'Producto agregado correctamente',
                    'success'
                );

                // Recargar productos
                await this.cargarProductos();
                
                // Cerrar modal
                this.cerrarModal();
            } else {
                this.mostrarNotificacion(data.message || 'Error al procesar el producto', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificacion('Error de conexión', 'error');
        }
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear notificación
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

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new GestionProductosProveedor();
});
