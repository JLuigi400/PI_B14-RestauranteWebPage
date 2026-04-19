// JavaScript para el Modal de Edición de Usuario
class EditarUsuario {
    constructor() {
        this.init();
    }

    init() {
        // Inicializar eventos del formulario
        this.initFormEvents();
    }

    initFormEvents() {
        const form = document.getElementById('formEditarUsuario');
        if (form) {
            form.addEventListener('submit', (e) => this.guardarUsuario(e));
        }

        // Evento para rol de usuario
        const rolSelect = document.getElementById('id_rol');
        if (rolSelect) {
            rolSelect.addEventListener('change', (e) => {
                this.mostrarOcultarCamposPorRol(e.target.value);
            });
            
            // Mostrar/ocultar campos según rol actual
            this.mostrarOcultarCamposPorRol(rolSelect.value);
        }
    }

    mostrarOcultarCamposPorRol(rol) {
        const camposPersonal = document.querySelectorAll('.seccion-personal input, .seccion-personal label');
        const camposSistema = document.querySelectorAll('.seccion-sistema input, .seccion-sistema label');

        if (rol == 1) {
            // Admin: mostrar todos los campos
            camposPersonal.forEach(campo => campo.style.display = 'block');
            camposSistema.forEach(campo => campo.style.display = 'block');
        } else if (rol == 2) {
            // Dueño: mostrar información personal, ocultar algunos campos de sistema
            camposPersonal.forEach(campo => campo.style.display = 'block');
            camposSistema.forEach(campo => campo.style.display = 'block');
        } else {
            // Cliente: mostrar solo información personal básica
            camposPersonal.forEach(campo => campo.style.display = 'block');
            // Ocultar campos sensibles para clientes
            const tokenField = document.getElementById('token_verificacion');
            if (tokenField) {
                tokenField.parentElement.style.display = 'none';
            }
        }
    }

    async guardarUsuario(event) {
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
            const response = await fetch('../PHP/actualizar_usuario.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.mostrarNotificacion('Usuario actualizado correctamente', 'success');
                
                // Enviar notificación por EmailJS
                await this.enviarNotificacionEmail();
                
                // Cerrar modal después de 2 segundos
                setTimeout(() => {
                    this.cerrarModal();
                    // Recargar página para mostrar cambios
                    window.location.reload();
                }, 2000);
            } else {
                throw new Error(result.message || 'Error al actualizar el usuario');
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
        const correo = form.correo_usu.value.trim();
        const nombre = form.nombre_per.value.trim();
        const apellidos = form.apellidos_per.value.trim();

        // Validar correo
        if (!correo) {
            this.mostrarNotificacion('El correo electrónico es requerido', 'error');
            return false;
        }

        if (!this.validarEmail(correo)) {
            this.mostrarNotificacion('El correo electrónico no es válido', 'error');
            return false;
        }

        // Validar nombre y apellidos
        if (!nombre || !apellidos) {
            this.mostrarNotificacion('El nombre y apellidos son requeridos', 'error');
            return false;
        }

        // Validar que el correo no exista (excepto el actual)
        const idUsuarioActual = form.id_usu.value;
        if (!this.validarCorreoUnico(correo, idUsuarioActual)) {
            return false;
        }

        return true;
    }

    validarEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    async validarCorreoUnico(correo, idActual) {
        try {
            const response = await fetch('../PHP/verificar_correo_unico.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `correo_usu=${encodeURIComponent(correo)}&id_usu=${idActual}`
            });

            const result = await response.json();

            if (!result.success && result.existe) {
                this.mostrarNotificacion('El correo electrónico ya está en uso por otro usuario', 'error');
                return false;
            }

            return true;
        } catch (error) {
            console.error('Error al validar correo único:', error);
            return true; // Permitir continuar si hay error en la validación
        }
    }

    async enviarNotificacionEmail() {
        try {
            // Obtener datos del formulario para el correo
            const form = document.getElementById('formEditarUsuario');
            const idUsuario = form.id_usu.value;
            const nombreUsuario = form.nombre_per.value + ' ' + form.apellidos_per.value;
            const rolUsuario = form.id_rol.options[form.id_rol.selectedIndex].text;
            
            // Obtener información del administrador actual
            const response = await fetch('../PHP/obtener_admin_actual.php');
            const adminData = await response.json();
            
            if (!adminData.success) {
                console.warn('No se pudo obtener información del administrador para el correo');
                return;
            }

            // Preparar datos para EmailJS
            const emailData = {
                nombre_destinatario: nombreUsuario,
                email_destinatario: form.correo_usu.value,
                usuario_id: idUsuario,
                rol_usuario: rolUsuario,
                actualizado_por: adminData.username_usu,
                campos_actualizados: this.obtenerCamposActualizados(form),
                actualizacion_fecha: new Date().toLocaleString('es-MX', {
                    timeZone: 'America/Mexico_City',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }),
                url_accion: `${window.location.origin}/admin_usuarios.php`
            };

            // Enviar correo usando EmailJS
            const resultado = await window.emailjsConfig.enviarAlertaSistema({
                ...emailData,
                tipo_alerta: 'Actualización de Usuario',
                mensaje_alerta: `El usuario ${nombreUsuario} ha sido actualizado en el sistema.`
            });
            
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
            'correo_usu': 'Correo electrónico',
            'id_rol': 'Rol del usuario',
            'nombre_per': 'Nombre',
            'apellidos_per': 'Apellidos',
            'correo_per': 'Correo personal',
            'cel_per': 'Celular',
            'estatus_usu': 'Estatus del usuario'
        };

        // Detectar cambios (esto es una simplificación, en producción deberías comparar con valores originales)
        for (const [campoId, campoNombre] of Object.entries(camposImportantes)) {
            const campo = form.elements[campoId];
            if (campo && campo.value && campo.value.trim() !== '') {
                // Para checkboxes, verificar el estado
                if (campo.type === 'checkbox') {
                    if (campo.checked) {
                        campos.push(campoNombre);
                    }
                } else if (campo.type !== 'file') {
                    campos.push(campoNombre);
                }
            }
        }

        return campos.length > 0 ? campos.join(', ') : 'Información general del usuario';
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
        const modal = document.getElementById('modalEditarUsuario');
        if (modal) {
            modal.remove();
        }
    }

    destroy() {
        // Limpiar eventos y referencias
        const form = document.getElementById('formEditarUsuario');
        if (form) {
            form.removeEventListener('submit', this.guardarUsuario);
        }
    }
}

// Funciones globales para compatibilidad
function cerrarModalEditarUsuario() {
    if (window.editarUsuarioInstance) {
        window.editarUsuarioInstance.cerrarModal();
    }
}

// Inicializar cuando se carga el modal
document.addEventListener('DOMContentLoaded', () => {
    // Verificar si existe el modal en la página
    if (document.getElementById('modalEditarUsuario')) {
        window.editarUsuarioInstance = new EditarUsuario();
    }
});
