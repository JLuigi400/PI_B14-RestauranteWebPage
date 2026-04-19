// Configuración de EmailJS para el sistema Salud Juárez
class EmailJSConfig {
    constructor() {
        this.serviceID = 'service_kchdp9f';
        this.publicKey = 'VkhEAneBLv5m5rOgO';
        this.templateID = 'template_tnrferf';
        this.init();
    }

    init() {
        // Inicializar EmailJS con las credenciales
        emailjs.init(this.publicKey);
    }

    // Enviar correo de notificación de restaurante actualizado
    async enviarNotificacionActualizacion(datos) {
        const templateParams = {
            to_name: datos.nombre_destinatario,
            to_email: datos.email_destinatario,
            restaurant_name: datos.nombre_restaurante,
            restaurant_id: datos.id_restaurante,
            updated_by: datos.actualizado_por,
            updated_fields: datos.campos_actualizados,
            update_date: new Date().toLocaleString('es-MX', {
                timeZone: 'America/Mexico_City',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }),
            action_url: datos.url_accion || `${window.location.origin}/restaurantes.php?id=${datos.id_restaurante}`
        };

        try {
            const response = await emailjs.send(
                this.serviceID,
                this.templateID,
                templateParams
            );
            
            return {
                success: true,
                message: 'Correo enviado correctamente',
                response: response
            };
        } catch (error) {
            console.error('Error al enviar correo:', error);
            return {
                success: false,
                message: 'Error al enviar el correo: ' + error.text,
                error: error
            };
        }
    }

    // Enviar correo de validación de restaurante
    async enviarNotificacionValidacion(datos) {
        const templateParams = {
            to_name: datos.nombre_destinatario,
            to_email: datos.email_destinatario,
            restaurant_name: datos.nombre_restaurante,
            restaurant_id: datos.id_restaurante,
            validation_status: datos.estatus_validacion,
            validation_date: new Date().toLocaleString('es-MX', {
                timeZone: 'America/Mexico_City',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }),
            rejection_reason: datos.motivo_rechazo || 'No especificado',
            validated_by: datos.validado_por,
            action_url: datos.url_accion || `${window.location.origin}/restaurantes.php?id=${datos.id_restaurante}`
        };

        try {
            const response = await emailjs.send(
                this.serviceID,
                this.templateID,
                templateParams
            );
            
            return {
                success: true,
                message: 'Correo de validación enviado correctamente',
                response: response
            };
        } catch (error) {
            console.error('Error al enviar correo de validación:', error);
            return {
                success: false,
                message: 'Error al enviar el correo: ' + error.text,
                error: error
            };
        }
    }

    // Enviar correo de bienvenida para nuevos restaurantes
    async enviarBienvenidaRestaurante(datos) {
        const templateParams = {
            to_name: datos.nombre_destinatario,
            to_email: datos.email_destinatario,
            restaurant_name: datos.nombre_restaurante,
            restaurant_id: datos.id_restaurante,
            registration_date: new Date().toLocaleString('es-MX', {
                timeZone: 'America/Mexico_City',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }),
            setup_url: datos.url_configuracion || `${window.location.origin}/editar_restaurante.php?id=${datos.id_restaurante}`,
            support_email: 'soporte@saludjuarez.com'
        };

        try {
            const response = await emailjs.send(
                this.serviceID,
                this.templateID,
                templateParams
            );
            
            return {
                success: true,
                message: 'Correo de bienvenida enviado correctamente',
                response: response
            };
        } catch (error) {
            console.error('Error al enviar correo de bienvenida:', error);
            return {
                success: false,
                message: 'Error al enviar el correo: ' + error.text,
                error: error
            };
        }
    }

    // Enviar correo de alerta de sistema
    async enviarAlertaSistema(datos) {
        const templateParams = {
            to_name: datos.nombre_destinatario || 'Administrador',
            to_email: datos.email_destinatario || 'admin@saludjuarez.com',
            alert_type: datos.tipo_alerta,
            alert_message: datos.mensaje_alerta,
            restaurant_name: datos.nombre_restaurante || 'No especificado',
            restaurant_id: datos.id_restaurante || 'No especificado',
            alert_date: new Date().toLocaleString('es-MX', {
                timeZone: 'America/Mexico_City',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }),
            action_url: datos.url_accion || `${window.location.origin}/admin/dashboard.php`
        };

        try {
            const response = await emailjs.send(
                this.serviceID,
                this.templateID,
                templateParams
            );
            
            return {
                success: true,
                message: 'Alerta de sistema enviada correctamente',
                response: response
            };
        } catch (error) {
            console.error('Error al enviar alerta de sistema:', error);
            return {
                success: false,
                message: 'Error al enviar la alerta: ' + error.text,
                error: error
            };
        }
    }

    // Validar configuración de EmailJS
    validarConfiguracion() {
        if (!this.serviceID || !this.publicKey || !this.templateID) {
            throw new Error('Faltan credenciales de EmailJS');
        }

        if (typeof emailjs === 'undefined') {
            throw new Error('EmailJS no está cargado');
        }

        return true;
    }
}

// Crear instancia global
window.emailjsConfig = new EmailJSConfig();

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EmailJSConfig;
}
