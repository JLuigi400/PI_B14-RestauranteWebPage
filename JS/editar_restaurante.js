// JavaScript para el Modal de Edición de Restaurante
class EditarRestaurante {
    constructor() {
        this.mapa = null;
        this.marker = null;
        this.latitudActual = null;
        this.longitudActual = null;
        this.init();
    }

    init() {
        // Inicializar eventos del formulario
        this.initFormEvents();
        
        // Inicializar mapa después de que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initMapa());
        } else {
            this.initMapa();
        }
    }

    initFormEvents() {
        const form = document.getElementById('formEditarRestaurante');
        if (form) {
            form.addEventListener('submit', (e) => this.guardarRestaurante(e));
        }

        // Evento para validación admin
        const validadoSelect = document.getElementById('validado_admin');
        const motivoRechazo = document.getElementById('motivo_rechazo');
        
        if (validadoSelect && motivoRechazo) {
            validadoSelect.addEventListener('change', (e) => {
                if (e.target.value === '2') { // Rechazado
                    motivoRechazo.required = true;
                    motivoRechazo.style.display = 'block';
                } else {
                    motivoRechazo.required = false;
                    motivoRechazo.style.display = 'none';
                }
            });
        }
    }

    initMapa() {
        // Obtener coordenadas iniciales
        const latInput = document.getElementById('latitud');
        const lngInput = document.getElementById('longitud');
        
        this.latitudActual = parseFloat(latInput.value) || 31.7386; // Default: Ciudad Juárez
        this.longitudActual = parseFloat(lngInput.value) || -106.4844;

        // Inicializar el mapa
        this.mapa = L.map('mapaRestaurante').setView([this.latitudActual, this.longitudActual], 13);

        // Agregar capa de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(this.mapa);

        // Agregar marcador inicial
        if (this.latitudActual && this.longitudActual) {
            this.marker = L.marker([this.latitudActual, this.longitudActual]).addTo(this.mapa);
            this.marker.bindPopup('Ubicación del restaurante').openPopup();
        }

        // Evento de clic en el mapa
        this.mapa.on('click', (e) => {
            this.actualizarUbicacion(e.latlng.lat, e.latlng.lng);
        });

        // Evento para autocompletar dirección
        const direccionInput = document.getElementById('direccion_res');
        if (direccionInput) {
            direccionInput.addEventListener('blur', () => this.geocodificarDireccion());
        }
    }

    actualizarUbicacion(lat, lng) {
        // Actualizar coordenadas
        this.latitudActual = lat;
        this.longitudActual = lng;

        // Actualizar inputs ocultos
        document.getElementById('latitud').value = lat;
        document.getElementById('longitud').value = lng;

        // Actualizar o crear marcador
        if (this.marker) {
            this.marker.setLatLng([lat, lng]);
        } else {
            this.marker = L.marker([lat, lng]).addTo(this.mapa);
            this.marker.bindPopup('Ubicación del restaurante').openPopup();
        }

        // Centrar mapa en nueva ubicación
        this.mapa.setView([lat, lng], 15);

        // Mostrar notificación
        this.mostrarNotificacion('Ubicación actualizada en el mapa', 'success');
    }

    async geocodificarDireccion() {
        const direccion = document.getElementById('direccion_res').value;
        const coloniaSelect = document.getElementById('id_colonia');
        
        if (!direccion || !coloniaSelect.value) return;

        // Construir dirección completa
        const coloniaText = coloniaSelect.options[coloniaSelect.selectedIndex]?.text || '';
        const direccionCompleta = `${direccion}, ${coloniaText}, Ciudad Juárez, Chihuahua, México`;

        try {
            // Usar Nominatim de OpenStreetMap para geocodificación
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccionCompleta)}&limit=1`);
            const data = await response.json();

            if (data && data.length > 0) {
                const { lat, lon } = data[0];
                this.actualizarUbicacion(parseFloat(lat), parseFloat(lon));
            }
        } catch (error) {
            console.warn('Error en geocodificación:', error);
            // No mostrar error al usuario, solo log
        }
    }

    async guardarRestaurante(event) {
        event.preventDefault();

        const form = event.target;
        const submitBtn = form.querySelector('.sj-btn-primary');
        const btnText = submitBtn.querySelector('.sj-btn-text');
        const btnLoading = submitBtn.querySelector('.sj-btn-loading');

        // Mostrar estado de carga
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'flex';

        try {
            // Crear FormData
            const formData = new FormData(form);

            // Validar campos requeridos
            if (!this.validarFormulario(form)) {
                throw new Error('Por favor completa todos los campos requeridos');
            }

            // Enviar datos al backend
            const response = await fetch('../PHP/actualizar_restaurante.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.mostrarNotificacion('Restaurante actualizado correctamente', 'success');
                
                // Enviar notificación por EmailJS
                await this.enviarNotificacionEmail();
                
                // Cerrar modal después de 2 segundos
                setTimeout(() => {
                    this.cerrarModal();
                    // Recargar página para mostrar cambios
                    window.location.reload();
                }, 2000);
            } else {
                throw new Error(result.message || 'Error al actualizar el restaurante');
            }

        } catch (error) {
            this.mostrarNotificacion(error.message, 'error');
        } finally {
            // Restaurar botón
            submitBtn.disabled = false;
            btnText.style.display = 'flex';
            btnLoading.style.display = 'none';
        }
    }

    validarFormulario(form) {
        const nombre = form.nombre_res.value.trim();
        const direccion = form.direccion_res.value.trim();

        if (!nombre) {
            this.mostrarNotificacion('El nombre del restaurante es requerido', 'error');
            return false;
        }

        if (!direccion) {
            this.mostrarNotificacion('La dirección es requerida', 'error');
            return false;
        }

        // Validar coordenadas
        const lat = parseFloat(form.latitud.value);
        const lng = parseFloat(form.longitud.value);

        if (!lat || !lng || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            this.mostrarNotificacion('Por favor selecciona una ubicación válida en el mapa', 'error');
            return false;
        }

        // Validar validación de admin
        const validadoSelect = form.validado_admin;
        const motivoRechazo = form.motivo_rechazo;
        
        if (validadoSelect && validadoSelect.value === '2' && !motivoRechazo.value.trim()) {
            this.mostrarNotificacion('El motivo de rechazo es requerido cuando se rechaza un restaurante', 'error');
            return false;
        }

        return true;
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear elemento de notificación
        const notificacion = document.createElement('div');
        notificacion.className = `sj-notificacion sj-notificacion-${tipo}`;
        notificacion.textContent = mensaje;

        // Estilos para la notificación
        Object.assign(notificacion.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 20px',
            borderRadius: '8px',
            color: 'white',
            fontWeight: '500',
            zIndex: '2000',
            maxWidth: '300px',
            wordWrap: 'break-word',
            opacity: '0',
            transform: 'translateX(100%)',
            transition: 'all 0.3s ease'
        });

        // Color según tipo
        switch (tipo) {
            case 'success':
                notificacion.style.background = '#27ae60';
                break;
            case 'error':
                notificacion.style.background = '#e74c3c';
                break;
            default:
                notificacion.style.background = '#3498db';
        }

        document.body.appendChild(notificacion);

        // Animar entrada
        setTimeout(() => {
            notificacion.style.opacity = '1';
            notificacion.style.transform = 'translateX(0)';
        }, 100);

        // Remover después de 4 segundos
        setTimeout(() => {
            notificacion.style.opacity = '0';
            notificacion.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 300);
        }, 4000);
    }

    cerrarModal() {
        const modal = document.getElementById('modalEditarRestaurante');
        if (modal) {
            modal.remove();
        }
    }

    async enviarNotificacionEmail() {
        try {
            // Obtener datos del formulario para el correo
            const form = document.getElementById('formEditarRestaurante');
            const nombreRestaurante = form.nombre_res.value;
            const idRestaurante = form.id_res.value;
            
            // Obtener información del usuario actual
            const response = await fetch('../PHP/obtener_usuario_actual.php');
            const userData = await response.json();
            
            if (!userData.success) {
                console.warn('No se pudo obtener información del usuario para el correo');
                return;
            }

            // Determinar destinatario y tipo de notificación
            const esAdmin = userData.id_rol === 1;
            const emailDestinatario = esAdmin ? userData.correo_usu : userData.correo_per || userData.correo_usu;
            const nombreDestinatario = userData.nombre_per || userData.username_usu;

            // Preparar datos para EmailJS
            const emailData = {
                nombre_destinatario: nombreDestinatario,
                email_destinatario: emailDestinatario,
                nombre_restaurante: nombreRestaurante,
                id_restaurante: idRestaurante,
                actualizado_por: userData.username_usu,
                campos_actualizados: this.obtenerCamposActualizados(form),
                url_accion: `${window.location.origin}/restaurantes.php?id=${idRestaurante}`
            };

            // Enviar correo usando EmailJS
            const resultado = await window.emailjsConfig.enviarNotificacionActualizacion(emailData);
            
            if (resultado.success) {
                console.log('Notificación por correo enviada correctamente');
                this.mostrarNotificacion('Notificación por correo enviada', 'info');
            } else {
                console.error('Error al enviar notificación por correo:', resultado.message);
                // No mostrar error al usuario, solo log
            }

        } catch (error) {
            console.error('Error en enviarNotificacionEmail:', error);
            // No interrumpir el flujo principal si falla el correo
        }
    }

    obtenerCamposActualizados(form) {
        const campos = [];
        
        // Lista de campos importantes para notificar
        const camposImportantes = {
            'nombre_res': 'Nombre del restaurante',
            'descripcion_res': 'Descripción',
            'telefono_res': 'Teléfono',
            'url_web': 'Sitio web',
            'direccion_res': 'Dirección',
            'id_colonia': 'Colonia',
            'logo_res': 'Logo',
            'banner_res': 'Banner',
            'estatus_res': 'Estatus',
            'validado_admin': 'Validación',
            'motivo_rechazo': 'Motivo de rechazo'
        };

        // Detectar cambios (esto es una simplificación, en producción deberías comparar con valores originales)
        for (const [campoId, campoNombre] of Object.entries(camposImportantes)) {
            const campo = form.elements[campoId];
            if (campo && campo.value && campo.value.trim() !== '') {
                // Para archivos, verificar si se seleccionó uno nuevo
                if (campo.type === 'file' && campo.files.length > 0) {
                    campos.push(campoNombre);
                } else if (campo.type !== 'file') {
                    campos.push(campoNombre);
                }
            }
        }

        return campos.length > 0 ? campos.join(', ') : 'Información general del restaurante';
    }
}

// Funciones globales para compatibilidad
function cerrarModalEditarRestaurante() {
    if (window.editarRestauranteInstance) {
        window.editarRestauranteInstance.cerrarModal();
    }
}

// Inicializar cuando se carga el modal
document.addEventListener('DOMContentLoaded', () => {
    // Verificar si existe el modal en la página
    if (document.getElementById('modalEditarRestaurante')) {
        window.editarRestauranteInstance = new EditarRestaurante();
    }
});

// Función para abrir el modal (si se necesita desde otros archivos)
function abrirModalEditarRestaurante(idRes) {
    fetch(`../DIRECCIONES/componentes/modal_editar_restaurante.php?id_res=${idRes}`)
        .then(response => response.text())
        .then(html => {
            // Crear contenedor para el modal
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = html;
            document.body.appendChild(modalContainer);

            // Inicializar la clase
            window.editarRestauranteInstance = new EditarRestaurante();
        })
        .catch(error => {
            console.error('Error al cargar el modal:', error);
            alert('Error al cargar el formulario de edición');
        });
}
