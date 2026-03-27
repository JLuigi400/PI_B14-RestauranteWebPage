/**
 * Scripts para páginas PHP - Salud Juárez
 * Extraídos de archivos PHP para optimización
 * Versión: 1.0.0
 * Fecha: 26 de Marzo de 2026
 */

// Scripts de admin_usuarios.php
function filtrarUsuarios() {
    const busqueda = document.getElementById('busquedaUsuario')?.value.toLowerCase() || '';
    const filtroRol = document.getElementById('filtroRol')?.value || '';
    const filtroEstado = document.getElementById('filtroEstado')?.value || '';
    
    const filas = document.querySelectorAll('.users-table tbody tr');
    
    filas.forEach(fila => {
        const textoFila = fila.textContent.toLowerCase();
        const rol = fila.dataset.rol || '';
        const estado = fila.dataset.estado || '';
        
        const coincideBusqueda = !busqueda || textoFila.includes(busqueda);
        const coincideRol = !filtroRol || rol === filtroRol;
        const coincideEstado = !filtroEstado || estado === filtroEstado;
        
        if (coincideBusqueda && coincideRol && coincideEstado) {
            fila.style.display = '';
            // Animación de entrada
            fila.style.opacity = '0';
            fila.style.transform = 'translateY(10px)';
            setTimeout(() => {
                fila.style.transition = 'all 0.3s ease';
                fila.style.opacity = '1';
                fila.style.transform = 'translateY(0)';
            }, 50);
        } else {
            fila.style.display = 'none';
        }
    });
    
    actualizarContadorUsuarios();
}

function actualizarContadorUsuarios() {
    const filasVisibles = document.querySelectorAll('.users-table tbody tr:not([style*="display: none"])');
    const contador = document.getElementById('contadorUsuarios');
    if (contador) {
        contador.textContent = `${filasVisibles.length} usuarios encontrados`;
    }
}

function editarUsuario(idUsuario) {
    // Abrir modal de edición o redirigir
    console.log(`Editar usuario ${idUsuario}`);
    window.location.href = `editar_usuario.php?id=${idUsuario}`;
}

function eliminarUsuario(idUsuario, nombreUsuario) {
    if (confirm(`¿Estás seguro de que deseas eliminar al usuario "${nombreUsuario}"?\n\nEsta acción no se puede deshacer.`)) {
        // Enviar solicitud de eliminación
        fetch('PHP/procesar_admin_usuarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=eliminar&id_usuario=${idUsuario}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('Usuario eliminado exitosamente', 'success');
                // Remover la fila de la tabla
                const fila = document.querySelector(`tr[data-usuario-id="${idUsuario}"]`);
                if (fila) {
                    fila.style.transition = 'all 0.3s ease';
                    fila.style.opacity = '0';
                    fila.style.transform = 'translateX(-20px)';
                    setTimeout(() => fila.remove(), 300);
                }
                actualizarContadorUsuarios();
            } else {
                mostrarNotificacion(data.message || 'Error al eliminar usuario', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexión. Por favor intenta nuevamente.', 'error');
        });
    }
}

function cambiarEstadoUsuario(idUsuario, estadoActual) {
    const nuevoEstado = estadoActual === '1' ? '0' : '1';
    const accion = nuevoEstado === '1' ? 'activar' : 'desactivar';
    
    if (confirm(`¿Estás seguro de que deseas ${accion} este usuario?`)) {
        fetch('PHP/procesar_admin_usuarios.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=cambiar_estado&id_usuario=${idUsuario}&estado=${nuevoEstado}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion(`Usuario ${accion}do exitosamente`, 'success');
                // Actualizar el estado en la tabla
                const fila = document.querySelector(`tr[data-usuario-id="${idUsuario}"]`);
                if (fila) {
                    fila.dataset.estado = nuevoEstado;
                    const statusBadge = fila.querySelector('.user-status');
                    if (statusBadge) {
                        statusBadge.className = `user-status status-${nuevoEstado === '1' ? 'active' : 'inactive'}`;
                        statusBadge.textContent = nuevoEstado === '1' ? 'Activo' : 'Inactivo';
                    }
                }
            } else {
                mostrarNotificacion(data.message || 'Error al cambiar estado', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexión. Por favor intenta nuevamente.', 'error');
        });
    }
}

// Scripts de buscar_restaurantes.php
let mapa = null;
let restaurantesData = [];
let marcadoresRestaurantes = [];

function inicializarMapaBusqueda() {
    if (typeof L !== 'undefined' && document.getElementById('mapaBusqueda')) {
        // Coordenadas centrales de Ciudad Juárez
        mapa = L.map('mapaBusqueda').setView([31.6904, -106.4245], 12);
        
        // Capa base de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(mapa);
        
        cargarRestaurantesMapa();
    }
}

function cargarRestaurantesMapa() {
    // Simulación de carga de restaurantes
    fetch('PHP/procesar_geolocalizacion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'accion=cargar_restaurantes'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            restaurantesData = data.restaurantes || [];
            mostrarRestaurantesEnMapa();
            mostrarRestaurantesEnTarjetas();
        } else {
            console.error('Error al cargar restaurantes:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Cargar datos de ejemplo
        cargarDatosEjemplo();
    });
}

function cargarDatosEjemplo() {
    restaurantesData = [
        {
            id: 1,
            nombre: "Restaurante Saludable 1",
            direccion: "Av. Principal 123",
            lat: 31.6904,
            lng: -106.4245,
            certificacion: "Oro",
            telefono: "+52 656 123 4567"
        },
        {
            id: 2,
            nombre: "Restaurante Saludable 2",
            direccion: "Calle Secundaria 456",
            lat: 31.7000,
            lng: -106.4300,
            certificacion: "Plata",
            telefono: "+52 656 987 6543"
        }
    ];
    mostrarRestaurantesEnMapa();
    mostrarRestaurantesEnTarjetas();
}

function mostrarRestaurantesEnMapa() {
    // Limpiar marcadores existentes
    marcadoresRestaurantes.forEach(marcador => mapa.removeLayer(marcador));
    marcadoresRestaurantes = [];
    
    restaurantesData.forEach(restaurante => {
        const icono = crearIconoRestaurante(restaurante.certificacion);
        
        const marcador = L.marker([restaurante.lat, restaurante.lng], { icon: icono })
            .addTo(mapa)
            .bindPopup(`
                <div class="popup-restaurante">
                    <h4>${restaurante.nombre}</h4>
                    <p><strong>Dirección:</strong> ${restaurante.direccion}</p>
                    <p><strong>Certificación:</strong> ${restaurante.certificacion}</p>
                    <p><strong>Teléfono:</strong> ${restaurante.telefono}</p>
                    <button class="btn-popup" onclick="verMenuRestaurante(${restaurante.id})">
                        Ver Menú
                    </button>
                    <button class="btn-popup" onclick="agregarFavorito(${restaurante.id})">
                        ⭐ Favorito
                    </button>
                </div>
            `);
        
        marcadoresRestaurantes.push(marcador);
    });
    
    // Ajustar vista del mapa
    if (marcadoresRestaurantes.length > 0) {
        const group = new L.featureGroup(marcadoresRestaurantes);
        mapa.fitBounds(group.getBounds().pad(0.1));
    }
}

function mostrarRestaurantesEnTarjetas() {
    const contenedor = document.getElementById('gridRestaurantes');
    if (!contenedor) return;
    
    contenedor.innerHTML = '';
    
    restaurantesData.forEach((restaurante, index) => {
        const tarjeta = crearTarjetaRestaurante(restaurante);
        contenedor.appendChild(tarjeta);
        
        // Animación de entrada
        setTimeout(() => {
            tarjeta.style.opacity = '1';
            tarjeta.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

function crearTarjetaRestaurante(restaurante) {
    const tarjeta = document.createElement('div');
    tarjeta.className = 'restaurante-card';
    tarjeta.style.opacity = '0';
    tarjeta.style.transform = 'translateY(20px)';
    tarjeta.style.transition = 'all 0.5s ease';
    
    tarjeta.innerHTML = `
        <div class="restaurante-imagen">
            <img src="IMG/UPLOADS/RESTAURANTES/restaurante_${restaurante.id}.jpg" 
                 alt="${restaurante.nombre}"
                 onerror="this.src='IMG/default-restaurante.jpg'">
        </div>
        <div class="restaurante-info">
            <h3 class="restaurante-nombre">${restaurante.nombre}</h3>
            <p class="restaurante-direccion">${restaurante.direccion}</p>
            <span class="restaurante-certificacion cert-${restaurante.certificacion.toLowerCase()}">
                ${restaurante.certificacion}
            </span>
            <div class="restaurante-actions">
                <a href="DIRECCIONES/ver_menu.php?id=${restaurante.id}" class="restaurante-btn btn-ver-menu">
                    Ver Menú
                </a>
                <button class="restaurante-btn btn-favorito" onclick="agregarFavorito(${restaurante.id})">
                    ⭐ Favorito
                </button>
            </div>
        </div>
    `;
    
    return tarjeta;
}

function crearIconoRestaurante(certificacion) {
    let color = '#3498db'; // Azul por defecto
    
    switch(certificacion) {
        case 'Oro':
            color = '#f39c12'; // Dorado
            break;
        case 'Plata':
            color = '#95a5a6'; // Plateado
            break;
        case 'Bronce':
            color = '#cd7f32'; // Bronce
            break;
    }
    
    return L.divIcon({
        html: `<div style="
            background-color: ${color};
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
        ">🍽️</div>`,
        className: 'marcador-restaurante',
        iconSize: [30, 30],
        iconAnchor: [15, 15],
        popupAnchor: [0, -15]
    });
}

function verMenuRestaurante(idRestaurante) {
    window.location.href = `DIRECCIONES/ver_menu.php?id=${idRestaurante}`;
}

function agregarFavorito(idRestaurante) {
    fetch('PHP/procesar_favoritos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `accion=agregar&id_restaurante=${idRestaurante}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('Restaurante agregado a favoritos', 'success');
        } else {
            mostrarNotificacion(data.message || 'Error al agregar a favoritos', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión. Por favor intenta nuevamente.', 'error');
    });
}

function filtrarRestaurantes(tipo) {
    const botones = document.querySelectorAll('.toggle-btn');
    botones.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase().includes(tipo)) {
            btn.classList.add('active');
        }
    });
    
    // Filtrar restaurantes según el tipo
    let restaurantesFiltrados = restaurantesData;
    
    switch(tipo) {
        case 'todos':
            // Mostrar todos
            break;
        case 'saludables':
            restaurantesFiltrados = restaurantesData.filter(r => 
                ['Oro', 'Plata'].includes(r.certificacion)
            );
            break;
        case 'certificados':
            restaurantesFiltrados = restaurantesData.filter(r => 
                r.certificacion === 'Oro'
            );
            break;
        case 'cercanos':
            // Aquí iría lógica de geolocalización
            break;
    }
    
    mostrarRestaurantesEnTarjetasFiltrados(restaurantesFiltrados);
}

function mostrarRestaurantesEnTarjetasFiltrados(restaurantes) {
    const contenedor = document.getElementById('gridRestaurantes');
    if (!contenedor) return;
    
    contenedor.innerHTML = '';
    
    restaurantes.forEach((restaurante, index) => {
        const tarjeta = crearTarjetaRestaurante(restaurante);
        contenedor.appendChild(tarjeta);
        
        setTimeout(() => {
            tarjeta.style.opacity = '1';
            tarjeta.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

// Scripts de mis_favoritos.php
let mapaModal = null;

function agregarFavorito(idRestaurante) {
    fetch('PHP/procesar_favoritos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `accion=agregar&id_restaurante=${idRestaurante}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('Restaurante agregado a favoritos', 'success');
        } else {
            mostrarNotificacion(data.message || 'Error al agregar a favoritos', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión. Por favor intenta nuevamente.', 'error');
    });
}

function eliminarFavorito(idFavorito) {
    if (confirm('¿Estás seguro de que deseas eliminar este restaurante de tus favoritos?')) {
        fetch('PHP/procesar_favoritos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=eliminar&id_favorito=${idFavorito}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('Restaurante eliminado de favoritos', 'success');
                // Recargar la página o actualizar la lista
                location.reload();
            } else {
                mostrarNotificacion(data.message || 'Error al eliminar de favoritos', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexión. Por favor intenta nuevamente.', 'error');
        });
    }
}

function mostrarMapaModal(idRestaurante, nombre, direccion, lat, lng) {
    const modal = document.getElementById('modalMapa');
    if (!modal) return;
    
    modal.style.display = 'block';
    
    // Inicializar mapa modal si no existe
    if (!mapaModal && typeof L !== 'undefined') {
        setTimeout(() => {
            mapaModal = L.map('mapaModalMap').setView([lat, lng], 15);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(mapaModal);
            
            const icono = crearIconoRestaurante('Oro');
            
            L.marker([lat, lng], { icon: icono })
                .addTo(mapaModal)
                .bindPopup(`
                    <div class="popup-restaurante">
                        <h4>${nombre}</h4>
                        <p><strong>Dirección:</strong> ${direccion}</p>
                        <button class="btn-popup" onclick="verMenuRestaurante(${idRestaurante})">
                            Ver Menú
                        </button>
                    </div>
                `)
                .openPopup();
        }, 100);
    }
}

function cerrarModalMapa() {
    const modal = document.getElementById('modalMapa');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Scripts de gestión de platillos
let ingredientesSeleccionados = [];
let ingredienteIdCounter = 0;

function agregarIngrediente() {
    const container = document.getElementById('ingredientesContainer');
    if (!container) return;
    
    const ingredienteId = ++ingredienteIdCounter;
    
    const ingredienteDiv = document.createElement('div');
    ingredienteDiv.className = 'ingrediente-item';
    ingredienteDiv.dataset.id = ingredienteId;
    ingredienteDiv.innerHTML = `
        <div class="ingrediente-header">
            <h4>Ingrediente ${ingredienteId}</h4>
            <button type="button" class="btn-eliminar-ingrediente" onclick="eliminarIngrediente(${ingredienteId})">
                ❌ Eliminar
            </button>
        </div>
        <div class="ingrediente-content">
            <div class="form-group">
                <label>Nombre del ingrediente:</label>
                <input type="text" name="ingrediente_nombre_${ingredienteId}" required>
            </div>
            <div class="form-group">
                <label>Cantidad:</label>
                <input type="number" name="ingrediente_cantidad_${ingredienteId}" step="0.01" required>
            </div>
            <div class="form-group">
                <label>Unidad:</label>
                <select name="ingrediente_unidad_${ingredienteId}">
                    <option value="kg">Kilogramos</option>
                    <option value="g">Gramos</option>
                    <option value="l">Litros</option>
                    <option value="ml">Mililitros</option>
                    <option value="unidad">Unidades</option>
                </select>
            </div>
            <div class="form-group">
                <label>Calorías (por unidad):</label>
                <input type="number" name="ingrediente_calorias_${ingredienteId}" step="0.01">
            </div>
            <div class="form-group">
                <label>Alergenos (separados por comas):</label>
                <input type="text" name="ingrediente_alergenos_${ingredienteId}" 
                       placeholder="ej: gluten, lactosa, nueces">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="ingrediente_secreto_${ingredienteId}">
                    Ingrediente secreto (no visible para clientes)
                </label>
            </div>
        </div>
    `;
    
    container.appendChild(ingredienteDiv);
    
    // Animación de entrada
    ingredienteDiv.style.opacity = '0';
    ingredienteDiv.style.transform = 'translateY(20px)';
    setTimeout(() => {
        ingredienteDiv.style.transition = 'all 0.3s ease';
        ingredienteDiv.style.opacity = '1';
        ingredienteDiv.style.transform = 'translateY(0)';
    }, 50);
}

function eliminarIngrediente(ingredienteId) {
    const ingredienteDiv = document.querySelector(`[data-id="${ingredienteId}"]`);
    if (ingredienteDiv) {
        ingredienteDiv.style.transition = 'all 0.3s ease';
        ingredienteDiv.style.opacity = '0';
        ingredienteDiv.style.transform = 'translateX(-20px)';
        setTimeout(() => ingredienteDiv.remove(), 300);
    }
}

function calcularCaloriasTotales() {
    const ingredientes = document.querySelectorAll('.ingrediente-item');
    let caloriasTotales = 0;
    
    ingredientes.forEach(ingrediente => {
        const calorias = ingrediente.querySelector('input[name^="ingrediente_calorias_"]')?.value || 0;
        const cantidad = ingrediente.querySelector('input[name^="ingrediente_cantidad_"]')?.value || 1;
        
        caloriasTotales += parseFloat(calorias) * parseFloat(cantidad);
    });
    
    const caloriasDisplay = document.getElementById('caloriasTotales');
    if (caloriasDisplay) {
        caloriasDisplay.textContent = caloriasTotales.toFixed(2);
    }
}

// Sistema de notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion notificacion-${tipo}`;
    notificacion.textContent = mensaje;
    
    // Estilos
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 10000;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        max-width: 300px;
        word-wrap: break-word;
    `;
    
    // Colores según tipo
    switch(tipo) {
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
    
    // Animación de entrada
    setTimeout(() => {
        notificacion.style.opacity = '1';
        notificacion.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-eliminación
    setTimeout(() => {
        notificacion.style.opacity = '0';
        notificacion.style.transform = 'translateX(100%)';
        setTimeout(() => notificacion.remove(), 300);
    }, 5000);
}

// Sistema de autocompletado
function inicializarAutocompletado() {
    const alergenosComunes = [
        'gluten', 'lactosa', 'nueces', 'soja', 'huevo', 'pescado', 'mariscos', 
        'cacahuates', 'sésamo', 'mostaza', 'apio', 'lupinos', 'moluscos'
    ];
    
    document.querySelectorAll('input[placeholder*="alergenos"]').forEach(input => {
        input.addEventListener('input', function() {
            const valor = this.value.toLowerCase();
            const palabras = valor.split(',').map(p => p.trim());
            const ultimaPalabra = palabras[palabras.length - 1];
            
            if (ultimaPalabra.length > 2) {
                const sugerencias = alergenosComunes.filter(alergeno => 
                    alergeno.toLowerCase().includes(ultimaPalabra) &&
                    !palabras.includes(alergeno)
                );
                
                mostrarSugerencias(this, sugerencias);
            }
        });
    });
}

function mostrarSugerencias(input, sugerencias) {
    // Eliminar sugerencias anteriores
    const sugerenciasExistente = document.getElementById('sugerencias-alergenos');
    if (sugerenciasExistente) {
        sugerenciasExistente.remove();
    }
    
    if (sugerencias.length === 0) return;
    
    const contenedor = document.createElement('div');
    contenedor.id = 'sugerencias-alergenos';
    contenedor.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
    `;
    
    sugerencias.forEach(sugerencia => {
        const item = document.createElement('div');
        item.textContent = sugerencia;
        item.style.cssText = `
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        `;
        
        item.addEventListener('click', () => {
            const valorActual = input.value;
            const palabras = valorActual.split(',').map(p => p.trim());
            palabras[palabras.length - 1] = sugerencia;
            input.value = palabras.join(', ');
            contenedor.remove();
        });
        
        item.addEventListener('mouseenter', () => {
            item.style.background = '#f0f0f0';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.background = 'white';
        });
        
        contenedor.appendChild(item);
    });
    
    // Posicionar el contenedor
    const rect = input.getBoundingClientRect();
    contenedor.style.position = 'fixed';
    contenedor.style.top = rect.bottom + 'px';
    contenedor.style.left = rect.left + 'px';
    contenedor.style.width = rect.width + 'px';
    
    document.body.appendChild(contenedor);
}

// Cerrar sugerencias al hacer clic fuera
document.addEventListener('click', (e) => {
    if (!e.target.matches('input[placeholder*="alergenos"]')) {
        const sugerencias = document.getElementById('sugerencias-alergenos');
        if (sugerencias) {
            sugerencias.remove();
        }
    }
});

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar según la página actual
    const paginaActual = window.location.pathname;
    
    if (paginaActual.includes('admin_usuarios.php')) {
        // Event listeners para admin_usuarios.php
        document.getElementById('busquedaUsuario')?.addEventListener('input', filtrarUsuarios);
        document.getElementById('filtroRol')?.addEventListener('change', filtrarUsuarios);
        document.getElementById('filtroEstado')?.addEventListener('change', filtrarUsuarios);
        
        // Contador inicial
        actualizarContadorUsuarios();
    }
    
    if (paginaActual.includes('buscar_restaurantes.php')) {
        inicializarMapaBusqueda();
    }
    
    if (paginaActual.includes('gestion_platillos.php')) {
        inicializarAutocompletado();
        
        // Event listener para cálculo de calorías
        document.addEventListener('input', (e) => {
            if (e.target.name && e.target.name.includes('calorias')) {
                calcularCaloriasTotales();
            }
        });
    }
    
    // Cerrar modales al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
});

// Exportar funciones para uso global
window.filtrarUsuarios = filtrarUsuarios;
window.editarUsuario = editarUsuario;
window.eliminarUsuario = eliminarUsuario;
window.cambiarEstadoUsuario = cambiarEstadoUsuario;
window.verMenuRestaurante = verMenuRestaurante;
window.agregarFavorito = agregarFavorito;
window.eliminarFavorito = eliminarFavorito;
window.mostrarMapaModal = mostrarMapaModal;
window.cerrarModalMapa = cerrarModalMapa;
window.agregarIngrediente = agregarIngrediente;
window.eliminarIngrediente = eliminarIngrediente;
window.calcularCaloriasTotales = calcularCaloriasTotales;
window.filtrarRestaurantes = filtrarRestaurantes;
