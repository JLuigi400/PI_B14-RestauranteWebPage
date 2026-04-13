/**
 * Mapa Interactivo para Dashboard de Usuario
 * Salud Juárez - Explorador de Restaurantes
 * Versión: 1.0.0
 */

class MapaUsuarioDashboard {
    constructor() {
        this.mapa = null;
        this.markers = [];
        this.markersFavoritos = [];
        this.usuarioUbicacion = null;
        this.restaurantes = [];
        this.favoritos = [];
        
        // Coordenadas de Ciudad Juárez
        this.coordenadasCdJuarez = [31.7200, -106.4600];
        
        // Configuración de tiles de OpenStreetMap
        this.tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors | Salud Juárez',
            maxZoom: 19
        });
        
        // Iconos personalizados
        this.iconos = {
            restaurante: this.crearIcono(''),
            restauranteFavorito: this.crearIconoFavorito(''),
            usuario: this.crearIconoUsuario(''),
            seleccionado: this.crearIconoSeleccionado('')
        };
    }
    
    crearIcono(emoji = ' ') {
        return L.divIcon({
            html: `<div style="background: linear-gradient(135deg, #27ae60, #2ecc71); 
                          color: white; 
                          border-radius: 50%; 
                          width: 35px; 
                          height: 35px; 
                          display: flex; 
                          align-items: center; 
                          justify-content: center; 
                          font-size: 18px;
                          border: 3px solid white;
                          box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                          font-weight: bold;">
                        ${emoji}
                    </div>`,
            className: 'marcador-restaurante',
            iconSize: [35, 35],
            iconAnchor: [17, 17],
            popupAnchor: [0, -20]
        });
    }
    
    crearIconoFavorito(emoji = ' ') {
        return L.divIcon({
            html: `<div style="background: linear-gradient(135deg, #e74c3c, #c0392b); 
                          color: white; 
                          border-radius: 50%; 
                          width: 40px; 
                          height: 40px; 
                          display: flex; 
                          align-items: center; 
                          justify-content: center; 
                          font-size: 20px;
                          border: 3px solid gold;
                          box-shadow: 0 2px 10px rgba(0,0,0,0.4);
                          font-weight: bold;">
                        ${emoji}
                    </div>`,
            className: 'marcador-favorito',
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -25]
        });
    }
    
    crearIconoUsuario(emoji = ' ') {
        return L.divIcon({
            html: `<div style="background: linear-gradient(135deg, #3498db, #2980b9); 
                          color: white; 
                          border-radius: 50%; 
                          width: 30px; 
                          height: 30px; 
                          display: flex; 
                          align-items: center; 
                          justify-content: center; 
                          font-size: 16px;
                          border: 2px solid white;
                          box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                          font-weight: bold;">
                        ${emoji}
                    </div>`,
            className: 'marcador-usuario',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -18]
        });
    }
    
    crearIconoSeleccionado(emoji = ' ') {
        return L.divIcon({
            html: `<div style="background: linear-gradient(135deg, #f39c12, #e67e22); 
                          color: white; 
                          border-radius: 50%; 
                          width: 45px; 
                          height: 45px; 
                          display: flex; 
                          align-items: center; 
                          justify-content: center; 
                          font-size: 22px;
                          border: 3px solid white;
                          box-shadow: 0 3px 12px rgba(0,0,0,0.5);
                          font-weight: bold;
                          animation: pulse 2s infinite;">
                        ${emoji}
                    </div>`,
            className: 'marcador-seleccionado',
            iconSize: [45, 45],
            iconAnchor: [22, 22],
            popupAnchor: [0, -30]
        });
    }
    
    async inicializar() {
        try {
            // Inicializar el mapa
            this.mapa = L.map('mapa-restaurantes').setView(this.coordenadasCdJuarez, 13);
            this.tileLayer.addTo(this.mapa);
            
            // Cargar datos
            await this.cargarRestaurantes();
            await this.cargarFavoritos();
            
            // Obtener ubicación del usuario
            this.obtenerUbicacionUsuario();
            
            // Agregar controles
            this.agregarControles();
            
            console.log('Mapa de usuario inicializado correctamente');
        } catch (error) {
            console.error('Error inicializando mapa:', error);
            this.mostrarError('No se pudo cargar el mapa. Por favor, recarga la página.');
        }
    }
    
    async cargarRestaurantes() {
        try {
            const response = await fetch('../API/obtener_restaurantes_mapa.php');
            const data = await response.json();
            
            if (data.success) {
                this.restaurantes = data.restaurantes;
                this.agregarMarcadoresRestaurantes();
            } else {
                console.error('Error cargando restaurantes:', data.message);
            }
        } catch (error) {
            console.error('Error en la petición:', error);
        }
    }
    
    async cargarFavoritos() {
        try {
            const response = await fetch('../API/obtener_favoritos_usuario.php');
            const data = await response.json();
            
            if (data.success) {
                this.favoritos = data.favoritos;
                this.actualizarMarcadoresFavoritos();
            }
        } catch (error) {
            console.error('Error cargando favoritos:', error);
        }
    }
    
    agregarMarcadoresRestaurantes() {
        this.restaurantes.forEach(restaurante => {
            if (restaurante.latitud && restaurante.longitud) {
                const esFavorito = this.favoritos.some(fav => fav.id_res === restaurante.id_res);
                const icono = esFavorito ? this.iconos.restauranteFavorito : this.iconos.restaurante;
                
                const marker = L.marker([restaurante.latitud, restaurante.longitud], { icono })
                    .addTo(this.mapa);
                
                // Crear popup con información
                const popupContent = this.crearPopupRestaurante(restaurante, esFavorito);
                marker.bindPopup(popupContent);
                
                // Evento click
                marker.on('click', () => {
                    this.seleccionarRestaurante(restaurante, marker);
                });
                
                this.markers.push(marker);
            }
        });
    }
    
    crearPopupRestaurante(restaurante, esFavorito) {
        const favoritoBtn = esFavorito 
            ? `<button class="btn-favorito activo" onclick="window.MapaUsuarioDashboard.toggleFavorito(${restaurante.id_res}, this)">`
            : `<button class="btn-favorito" onclick="window.MapaUsuarioDashboard.toggleFavorito(${restaurante.id_res}, this)">`;
        
        return `
            <div class="popup-restaurante">
                <h4>${restaurante.nombre_res}</h4>
                <p><strong>Dirección:</strong> ${restaurante.direccion_res}</p>
                <p><strong>Sector:</strong> ${restaurante.sector_res}</p>
                ${restaurante.telefono_res ? `<p><strong>Teléfono:</strong> ${restaurante.telefono_res}</p>` : ''}
                <div class="popup-actions">
                    ${favoritoBtn}
                        <span class="btn-icon"> ${esFavorito ? '' : ''}</span>
                        <span class="btn-text">${esFavorito ? 'Quitar de Favoritos' : 'Agregar a Favoritos'}</span>
                    </button>
                    <a href="buscar_restaurantes.php?restaurante=${restaurante.id_res}" class="btn-ver">
                        Ver Detalles
                    </a>
                </div>
            </div>
        `;
    }
    
    actualizarMarcadoresFavoritos() {
        // Actualizar iconos de restaurantes que son favoritos
        this.markers.forEach((marker, index) => {
            const restaurante = this.restaurantes[index];
            const esFavorito = this.favoritos.some(fav => fav.id_res === restaurante.id_res);
            
            if (esFavorito) {
                marker.setIcon(this.iconos.restauranteFavorito);
            } else {
                marker.setIcon(this.iconos.restaurante);
            }
        });
    }
    
    seleccionarRestaurante(restaurante, marker) {
        // Quitar selección anterior
        this.markers.forEach(m => m.setIcon(this.iconos.restaurante));
        this.markersFavoritos.forEach(m => m.setIcon(this.iconos.restauranteFavorito));
        
        // Seleccionar nuevo
        marker.setIcon(this.iconos.seleccionado);
        
        // Centrar mapa en el restaurante
        this.mapa.setView([restaurante.latitud, restaurante.longitud], 16);
        
        // Abrir popup
        marker.openPopup();
    }
    
    async toggleFavorito(id_res, button) {
        try {
            const formData = new FormData();
            formData.append('id_res', id_res);
            
            const response = await fetch('../API/toggle_favorito.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Actualizar estado del botón
                button.classList.toggle('activo');
                const esActivo = button.classList.contains('activo');
                button.innerHTML = `
                    <span class="btn-icon">${esActivo ? '' : ''}</span>
                    <span class="btn-text">${esActivo ? 'Quitar de Favoritos' : 'Agregar a Favoritos'}</span>
                `;
                
                // Actualizar lista de favoritos
                if (esActivo) {
                    const restaurante = this.restaurantes.find(r => r.id_res === id_res);
                    if (restaurante) {
                        this.favoritos.push({ id_res: restaurante.id_res, nombre_res: restaurante.nombre_res });
                    }
                } else {
                    this.favoritos = this.favoritos.filter(fav => fav.id_res !== id_res);
                }
                
                // Actualizar marcadores
                this.actualizarMarcadoresFavoritos();
                
                // Actualizar sección de favoritos
                if (window.FavoritosUsuario) {
                    window.FavoritosUsuario.cargarFavoritos();
                }
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error toggle favorito:', error);
            alert('Error al actualizar favoritos. Por favor, intenta de nuevo.');
        }
    }
    
    obtenerUbicacionUsuario() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.usuarioUbicacion = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // Agregar marcador de usuario
                    const markerUsuario = L.marker([this.usuarioUbicacion.lat, this.usuarioUbicacion.lng], {
                        icon: this.iconos.usuario
                    }).addTo(this.mapa);
                    
                    markerUsuario.bindPopup('Tu ubicación actual');
                    
                    // Centrar mapa en la ubicación del usuario
                    this.mapa.setView([this.usuarioUbicacion.lat, this.usuarioUbicacion.lng], 14);
                    
                    // Calcular distancias
                    this.calcularDistancias();
                },
                (error) => {
                    console.warn('No se pudo obtener la ubicación:', error);
                    // Mantener vista en Ciudad Juárez
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
    }
    
    calcularDistancias() {
        if (!this.usuarioUbicacion) return;
        
        this.restaurantes.forEach(restaurante => {
            if (restaurante.latitud && restaurante.longitud) {
                const distancia = this.calcularDistancia(
                    this.usuarioUbicacion.lat,
                    this.usuarioUbicacion.lng,
                    restaurante.latitud,
                    restaurante.longitud
                );
                restaurante.distancia = distancia;
            }
        });
        
        // Ordenar por distancia
        this.restaurantes.sort((a, b) => (a.distancia || Infinity) - (b.distancia || Infinity));
    }
    
    calcularDistancia(lat1, lon1, lat2, lon2) {
        const R = 6371; // Radio de la Tierra en km
        const dLat = this.toRad(lat2 - lat1);
        const dLon = this.toRad(lon2 - lon1);
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c; // Distancia en km
    }
    
    toRad(deg) {
        return deg * (Math.PI/180);
    }
    
    agregarControles() {
        // Control de zoom
        L.control.zoom({
            position: 'topright'
        }).addTo(this.mapa);
        
        // Control de escala
        L.control.scale({
            position: 'bottomleft'
        }).addTo(this.mapa);
        
        // Control personalizado para restaurantes cercanos
        const controlCercanos = L.control({ position: 'topleft' });
        controlCercanos.onAdd = (map) => {
            const div = L.DomUtil.create('div', 'control-cercanos');
            div.innerHTML = `
                <button onclick="window.MapaUsuarioDashboard.mostrarCercanos()" class="btn-cercanos">
                    Restaurantes Cercanos
                </button>
            `;
            return div;
        };
        controlCercanos.addTo(this.mapa);
    }
    
    mostrarCercanos() {
        if (!this.usuarioUbicacion) {
            alert('No se pudo obtener tu ubicación. Activa el GPS y recarga la página.');
            return;
        }
        
        // Filtrar restaurantes cercanos (dentro de 5 km)
        const cercanos = this.restaurantes.filter(r => r.distancia && r.distancia <= 5);
        
        if (cercanos.length === 0) {
            alert('No hay restaurantes saludables cercanos (dentro de 5 km).');
            return;
        }
        
        // Crear bounds para mostrar todos los cercanos
        const bounds = L.latLngBounds();
        bounds.extend([this.usuarioUbicacion.lat, this.usuarioUbicacion.lng]);
        
        cercanos.forEach(restaurante => {
            bounds.extend([restaurante.latitud, restaurante.longitud]);
        });
        
        this.mapa.fitBounds(bounds, { padding: [50, 50] });
        
        // Mostrar información
        const mensaje = `Se encontraron ${cercanos.length} restaurantes saludables cercanos:`;
        const lista = cercanos.map(r => `· ${r.nombre_res} (${r.distancia.toFixed(1)} km)`).join('\n');
        
        // Crear popup informativo
        L.popup()
            .setLatLng([this.usuarioUbicacion.lat, this.usuarioUbicacion.lng])
            .setContent(`<div class="popup-cercanos"><h4>Restaurantes Cercanos</h4><p>${mensaje}</p><pre>${lista}</pre></div>`)
            .openOn(this.mapa);
    }
    
    mostrarError(mensaje) {
        const mapaContainer = document.getElementById('mapa-restaurantes');
        if (mapaContainer) {
            mapaContainer.innerHTML = `
                <div class="mapa-error">
                    <div class="error-icon">!</div>
                    <h3>Error cargando el mapa</h3>
                    <p>${mensaje}</p>
                    <button onclick="location.reload()" class="btn-reintentar">Reintentar</button>
                </div>
            `;
        }
    }
}

// Hacer la clase disponible globalmente
window.MapaUsuarioDashboard = new MapaUsuarioDashboard();
