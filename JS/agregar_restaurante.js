// JavaScript para el Modal de Agregar Restaurante
class AgregarRestaurante {
    constructor() {
        this.mapa = null;
        this.marker = null;
        this.latitudActual = 31.7386; // Default: Ciudad Juárez
        this.longitudActual = -106.4844;
        this.init();
    }

    init() {
        console.log('Inicializando AgregarRestaurante...');
        
        // Inicializar eventos del formulario
        this.initFormEvents();
        
        // Dar tiempo al DOM para cargar el modal y mostrarlo
        setTimeout(() => {
            console.log('Iniciando mapa...');
            this.initMapa();
        }, 500);
    }

    initFormEvents() {
        const form = document.getElementById('formAgregarRestaurante');
        
        if (form) {
            form.addEventListener('submit', (e) => this.guardarRestaurante(e));
        }

        // Evento para select de colonias
        const coloniaSelect = document.getElementById('id_colonia');
        const nuevaColoniaGroup = document.getElementById('nueva_colonia_group');
        const nuevaColoniaDatosGroup = document.getElementById('nueva_colonia_datos_group');
        const nuevaColoniaGeoGroup = document.getElementById('nueva_colonia_geo_group');
        const nuevaColoniaCoordsGroup = document.getElementById('nueva_colonia_coords_group');
        
        console.log('Elementos de colonias:', {
            coloniaSelect: !!coloniaSelect,
            nuevaColoniaGroup: !!nuevaColoniaGroup,
            nuevaColoniaDatosGroup: !!nuevaColoniaDatosGroup,
            nuevaColoniaGeoGroup: !!nuevaColoniaGeoGroup,
            nuevaColoniaCoordsGroup: !!nuevaColoniaCoordsGroup
        });
        
        if (coloniaSelect && nuevaColoniaGroup && nuevaColoniaDatosGroup && nuevaColoniaGeoGroup && nuevaColoniaCoordsGroup) {
            coloniaSelect.addEventListener('change', (e) => {
                console.log('Cambio en select de colonia:', e.target.value);
                if (e.target.value === 'otro') {
                    console.log('Mostrando campos de nueva colonia');
                    nuevaColoniaGroup.style.display = 'block';
                    nuevaColoniaDatosGroup.style.display = 'flex';
                    nuevaColoniaGeoGroup.style.display = 'flex';
                    nuevaColoniaCoordsGroup.style.display = 'block';
                    
                    // Marcar campos requeridos
                    document.getElementById('nueva_colonia').required = true;
                    document.getElementById('nueva_colonia_ciudad').required = true;
                    document.getElementById('nueva_colonia_estado').required = true;
                    document.getElementById('nueva_colonia_pais').required = true;
                    
                    // Establecer valores por defecto
                    document.getElementById('nueva_colonia_pais').value = 'México';
                } else {
                    console.log('Ocultando campos de nueva colonia');
                    nuevaColoniaGroup.style.display = 'none';
                    nuevaColoniaDatosGroup.style.display = 'none';
                    nuevaColoniaGeoGroup.style.display = 'none';
                    nuevaColoniaCoordsGroup.style.display = 'none';
                    
                    // Desmarcar campos requeridos
                    document.getElementById('nueva_colonia').required = false;
                    document.getElementById('nueva_colonia_ciudad').required = false;
                    document.getElementById('nueva_colonia_estado').required = false;
                    document.getElementById('nueva_colonia_pais').required = false;
                    
                    // Limpiar valores
                    document.getElementById('nueva_colonia').value = '';
                    document.getElementById('nueva_colonia_ciudad').value = '';
                    document.getElementById('nueva_colonia_estado').value = '';
                    document.getElementById('nueva_colonia_pais').value = 'México';
                    document.getElementById('nueva_colonia_cp').value = '';
                    document.getElementById('nueva_colonia_lat').value = '';
                    document.getElementById('nueva_colonia_lng').value = '';
                }
            });
        } else {
            console.error('No se encontraron los elementos de colonia');
        }

        // Evento para geocodificación de dirección
        const direccionInput = document.getElementById('direccion_res');
        if (direccionInput) {
            direccionInput.addEventListener('blur', () => {
                const direccion = direccionInput.value.trim();
                if (direccion) {
                    this.geocodificarDireccion(direccion);
                }
            });
        }

        // Eventos para coordenadas manuales
        const latInput = document.getElementById('latitud');
        const lngInput = document.getElementById('longitud');
        
        if (latInput && lngInput) {
            const actualizarDesdeInputs = () => {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);
                
                if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                    this.latitudActual = lat;
                    this.longitudActual = lng;
                    
                    if (this.marker && this.mapa) {
                        this.marker.setLatLng([lat, lng]);
                        this.mapa.setView([lat, lng], 15);
                    }
                }
            };
            
            latInput.addEventListener('input', actualizarDesdeInputs);
            lngInput.addEventListener('input', actualizarDesdeInputs);
        }
    }

    initMapa() {
        console.log('initMapa() llamado');
        
        // Inicializar el mapa
        const mapContainer = document.getElementById('mapaRestaurante');
        
        if (!mapContainer) {
            console.error('No se encontró el contenedor del mapa');
            return;
        }

        // Verificar que Leaflet esté disponible
        if (typeof L === 'undefined') {
            console.error('Leaflet no está disponible');
            return;
        }
        
        // Forzar redimensionamiento del contenedor
        mapContainer.style.height = '300px';
        mapContainer.style.width = '100%';
        console.log('Contenedor del mapa configurado');
        
        // Destruir mapa anterior si existe
        if (this.mapa) {
            this.mapa.remove();
            this.mapa = null;
        }
        
        try {
            // Inicializar el mapa
            this.mapa = L.map('mapaRestaurante').setView([this.latitudActual, this.longitudActual], 13);
            console.log('Mapa inicializado');

            // Agregar capa de OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(this.mapa);
            console.log('Capa de tiles agregada');

            // Agregar marcador inicial
            this.marker = L.marker([this.latitudActual, this.longitudActual], {
                draggable: true
            }).addTo(this.mapa);
            this.marker.bindPopup('Arrastra el marcador o haz clic en el mapa para ubicar tu restaurante').openPopup();
            console.log('Marcador agregado');

            // Evento clic en el mapa
            this.mapa.on('click', (e) => {
                this.actualizarUbicacion(e.latlng.lat, e.latlng.lng);
            });

            // Evento arrastrar marcador
            this.marker.on('dragend', (e) => {
                const position = e.target.getLatLng();
                this.actualizarUbicacion(position.lat, position.lng);
            });

            // Actualizar inputs iniciales
            this.actualizarInputsCoordenadas();
            
            // Forzar redibujado del mapa después de un tiempo
            setTimeout(() => {
                if (this.mapa) {
                    this.mapa.invalidateSize();
                    console.log('Mapa redibujado');
                }
            }, 200);
            
        } catch (error) {
            console.error('Error al inicializar el mapa:', error);
        }
    }

    actualizarUbicacion(lat, lng) {
        this.latitudActual = lat;
        this.longitudActual = lng;
        
        if (this.marker) {
            this.marker.setLatLng([lat, lng]);
        }
        
        this.actualizarInputsCoordenadas();
    }

    actualizarInputsCoordenadas() {
        const latInput = document.getElementById('latitud');
        const lngInput = document.getElementById('longitud');
        
        if (latInput) latInput.value = this.latitudActual;
        if (lngInput) lngInput.value = this.longitudActual;
    }

    async geocodificarDireccion(direccion) {
        try {
            // Usar Nominatim de OpenStreetMap para geocodificación
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(direccion)}&limit=1`);
            const data = await response.json();

            if (data && data.length > 0) {
                const { lat, lon } = data[0];
                this.actualizarUbicacion(parseFloat(lat), parseFloat(lon));
                
                // Centrar el mapa en la nueva ubicación
                if (this.mapa) {
                    this.mapa.setView([parseFloat(lat), parseFloat(lon)], 15);
                }
            }
        } catch (error) {
            // Silenciar error de geocodificación
        }
    }

    async guardarRestaurante(event) {
        event.preventDefault();
        console.log('Iniciando guardado de restaurante...');

        const form = event.target;
        const submitBtn = form.querySelector('.sj-btn-primary');
        const btnText = submitBtn?.querySelector('.sj-btn-text');
        const btnLoading = submitBtn?.querySelector('.sj-btn-loading');

        if (!submitBtn) {
            console.error('No se encontró el botón de submit');
            return;
        }

        // Mostrar estado de carga
        submitBtn.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnLoading) btnLoading.style.display = 'flex';

        try {
            // Crear FormData
            const formData = new FormData(form);
            console.log('FormData creado, tamaño:', formData.entries().length);
            
            // Log de datos del formulario
            for (let [key, value] of formData.entries()) {
                console.log(`${key}:`, value);
            }

            // Validar campos requeridos
            if (!this.validarFormulario(form)) {
                throw new Error('Por favor completa todos los campos requeridos');
            }

            console.log('Enviando datos al backend...');
            // Enviar datos al backend
            const response = await fetch('../PHP/crear_restaurante.php', {
                method: 'POST',
                body: formData
            });

            console.log('Respuesta del backend:', response.status, response.statusText);
            const result = await response.json();
            console.log('Resultado parseado:', result);

            if (result.success) {
                this.mostrarNotificacion('Restaurante creado correctamente', 'success');
                
                // Cerrar modal después de 2 segundos
                setTimeout(() => {
                    this.cerrarModal();
                    // Recargar la página para mostrar el nuevo restaurante
                    location.reload();
                }, 2000);
            } else {
                throw new Error(result.message || 'Error al crear el restaurante');
            }

        } catch (error) {
            console.error('Error en guardarRestaurante:', error);
            this.mostrarNotificacion(error.message, 'error');
        } finally {
            // Restaurar botón
            submitBtn.disabled = false;
            btnText.style.display = 'flex';
            btnLoading.style.display = 'none';
        }
    }

    validarFormulario(form) {
        const nombre = form.nombre_res?.value?.trim();
        const direccion = form.direccion_res?.value?.trim();

        if (!nombre) {
            this.mostrarNotificacion('El nombre del restaurante es requerido', 'error');
            return false;
        }

        if (!direccion) {
            this.mostrarNotificacion('La dirección es requerida', 'error');
            return false;
        }

        // Validar coordenadas
        const lat = parseFloat(form.latitud?.value);
        const lng = parseFloat(form.longitud?.value);

        if (!lat || !lng || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
            this.mostrarNotificacion('Por favor selecciona una ubicación válida en el mapa', 'error');
            return false;
        }

        // Validar colonia
        const idColonia = form.id_colonia?.value;
        if (!idColonia) {
            this.mostrarNotificacion('Por favor selecciona una colonia', 'error');
            return false;
        }

        // Si seleccionó "Otro", validar que haya ingresado el nombre
        if (idColonia === 'otro') {
            const nuevaColonia = form.nueva_colonia?.value?.trim();
            const nuevaColoniaCiudad = form.nueva_colonia_ciudad?.value?.trim();
            
            if (!nuevaColonia) {
                this.mostrarNotificacion('El nombre de la nueva colonia es requerido', 'error');
                return false;
            }
            
            if (!nuevaColoniaCiudad) {
                this.mostrarNotificacion('La ciudad de la nueva colonia es requerida', 'error');
                return false;
            }
        }

        return true;
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear elemento de notificación
        const notificacion = document.createElement('div');
        notificacion.className = `sj-notificacion sj-notificacion-${tipo}`;
        notificacion.textContent = mensaje;

        // Estilos para la notificación
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10001;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 400px;
        `;

        // Colores según tipo
        switch (tipo) {
            case 'success':
                notificacion.style.background = '#27ae60';
                break;
            case 'error':
                notificacion.style.background = '#e74c3c';
                break;
            case 'warning':
                notificacion.style.background = '#f39c12';
                break;
            default:
                notificacion.style.background = '#3498db';
        }

        document.body.appendChild(notificacion);

        // Animar entrada
        setTimeout(() => {
            notificacion.style.transform = 'translateX(0)';
        }, 100);

        // Remover después de 4 segundos
        setTimeout(() => {
            notificacion.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notificacion.parentNode) {
                    notificacion.parentNode.removeChild(notificacion);
                }
            }, 300);
        }, 4000);
    }

    cerrarModal() {
        const modal = document.getElementById('modalAgregarRestaurante');
        if (modal) {
            // Buscar el contenedor padre y eliminarlo
            const modalContainer = modal.closest('.sj-modal-overlay');
            if (modalContainer) {
                modalContainer.remove();
            } else {
                modal.remove();
            }
        }
        
        // Limpiar la instancia global
        if (window.agregarRestauranteInstance === this) {
            window.agregarRestauranteInstance = null;
        }
    }

    destroy() {
        // Limpiar el mapa si existe
        if (this.mapa) {
            this.mapa.remove();
            this.mapa = null;
        }
        this.marker = null;
        
        // Cerrar y remover el modal
        this.cerrarModal();
    }
}

// Función global para cerrar el modal
function cerrarModalAgregarRestaurante() {
    if (window.agregarRestauranteInstance) {
        window.agregarRestauranteInstance.destroy();
        window.agregarRestauranteInstance = null;
    }
}
