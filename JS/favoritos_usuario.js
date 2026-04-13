/**
 * Gestión de Favoritos para Dashboard de Usuario
 * Salud Juárez - Sistema de Restaurantes
 * Versión: 1.0.0
 */

class FavoritosUsuario {
    constructor() {
        this.favoritos = [];
        this.container = null;
    }
    
    async cargarFavoritos() {
        try {
            this.container = document.getElementById('favoritos-container');
            
            if (!this.container) {
                console.error('Contenedor de favoritos no encontrado');
                return;
            }
            
            // Mostrar loading
            this.mostrarLoading();
            
            // Obtener favoritos del servidor
            const response = await fetch('../API/obtener_favoritos_usuario.php');
            const data = await response.json();
            
            if (data.success) {
                this.favoritos = data.favoritos;
                this.renderizarFavoritos();
            } else {
                this.mostrarError('No se pudieron cargar tus favoritos');
            }
        } catch (error) {
            console.error('Error cargando favoritos:', error);
            this.mostrarError('Error de conexión. Por favor, intenta de nuevo.');
        }
    }
    
    mostrarLoading() {
        if (this.container) {
            this.container.innerHTML = `
                <div class="loading-placeholder">
                    <div class="spinner"></div>
                    <p>Cargando tus favoritos...</p>
                </div>
            `;
        }
    }
    
    mostrarError(mensaje) {
        if (this.container) {
            this.container.innerHTML = `
                <div class="error-placeholder">
                    <div class="error-icon">!</div>
                    <h3>Error</h3>
                    <p>${mensaje}</p>
                    <button onclick="window.FavoritosUsuario.cargarFavoritos()" class="btn-reintentar">
                        Reintentar
                    </button>
                </div>
            `;
        }
    }
    
    renderizarFavoritos() {
        if (!this.container) return;
        
        if (this.favoritos.length === 0) {
            this.container.innerHTML = `
                <div class="empty-favoritos">
                    <div class="empty-icon"></div>
                    <h3>Aún no tienes favoritos</h3>
                    <p>Explora restaurantes y agrégalos a tus favoritos para verlos aquí.</p>
                    <a href="buscar_restaurantes.php" class="btn-explorar">
                        Explorar Restaurantes
                    </a>
                </div>
            `;
            return;
        }
        
        const favoritosHTML = this.favoritos.map(favorito => this.crearTarjetaFavorito(favorito)).join('');
        
        this.container.innerHTML = `
            <div class="favoritos-grid">
                ${favoritosHTML}
            </div>
        `;
    }
    
    crearTarjetaFavorito(favorito) {
        const imagenRestaurante = favorito.logo_res 
            ? `../${favorito.logo_res}` 
            : '../IMG/UPLOADS/RESTAURANTES/default_logo.png';
        
        const ratingHTML = this.generarRating(favorito.rating_promedio || 0);
        
        return `
            <div class="favorito-card" data-id-res="${favorito.id_res}">
                <div class="favorito-imagen">
                    <img src="${imagenRestaurante}" alt="${favorito.nombre_res}" 
                         onerror="this.src='../IMG/UPLOADS/RESTAURANTES/default_logo.png'">
                    <div class="favorito-badge"></div>
                </div>
                <div class="favorito-info">
                    <h4>${favorito.nombre_res}</h4>
                    <p class="favorito-direccion">${favorito.direccion_res}</p>
                    <p class="favorito-sector">${favorito.sector_res}</p>
                    ${ratingHTML}
                    ${favorito.telefono_res ? `<p class="favorito-telefono">${favorito.telefono_res}</p>` : ''}
                </div>
                <div class="favorito-actions">
                    <button class="btn-accion btn-ver" onclick="window.FavoritosUsuario.verRestaurante(${favorito.id_res})">
                        Ver Detalles
                    </button>
                    <button class="btn-accion btn-quitar" onclick="window.FavoritosUsuario.quitarFavorito(${favorito.id_res}, this)">
                        Quitar
                    </button>
                </div>
            </div>
        `;
    }
    
    generarRating(rating) {
        const estrellas = Math.round(rating);
        const estrellasHTML = Array.from({length: 5}, (_, i) => {
            const clase = i < estrellas ? 'estrella-llena' : 'estrella-vacia';
            return `<span class="estrella ${clase}"></span>`;
        }).join('');
        
        return `
            <div class="favorito-rating">
                <div class="estrellas">${estrellasHTML}</div>
                <span class="rating-numero">${rating.toFixed(1)}</span>
            </div>
        `;
    }
    
    verRestaurante(id_res) {
        window.location.href = `buscar_restaurantes.php?restaurante=${id_res}`;
    }
    
    async quitarFavorito(id_res, button) {
        try {
            // Deshabilitar botón
            button.disabled = true;
            button.textContent = 'Quitando...';
            
            const formData = new FormData();
            formData.append('id_res', id_res);
            
            const response = await fetch('../API/toggle_favorito.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remover de la lista local
                this.favoritos = this.favoritos.filter(fav => fav.id_res !== id_res);
                
                // Actualizar vista
                this.renderizarFavoritos();
                
                // Actualizar mapa si existe
                if (window.MapaUsuarioDashboard) {
                    window.MapaUsuarioDashboard.favoritos = this.favoritos;
                    window.MapaUsuarioDashboard.actualizarMarcadoresFavoritos();
                }
                
                // Mostrar notificación
                this.mostrarNotificacion('Restaurante quitado de favoritos', 'success');
            } else {
                throw new Error(data.message || 'Error al quitar favorito');
            }
        } catch (error) {
            console.error('Error quitando favorito:', error);
            
            // Restaurar botón
            button.disabled = false;
            button.textContent = 'Quitar';
            
            this.mostrarNotificacion('Error al quitar favorito. Intenta de nuevo.', 'error');
        }
    }
    
    async agregarFavorito(id_res) {
        try {
            const formData = new FormData();
            formData.append('id_res', id_res);
            
            const response = await fetch('../API/toggle_favorito.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Recargar favoritos
                await this.cargarFavoritos();
                
                // Actualizar mapa si existe
                if (window.MapaUsuarioDashboard) {
                    window.MapaUsuarioDashboard.cargarFavoritos();
                }
                
                this.mostrarNotificacion('Restaurante agregado a favoritos', 'success');
            } else {
                throw new Error(data.message || 'Error al agregar favorito');
            }
        } catch (error) {
            console.error('Error agregando favorito:', error);
            this.mostrarNotificacion('Error al agregar favorito. Intenta de nuevo.', 'error');
        }
    }
    
    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear elemento de notificación
        const notificacion = document.createElement('div');
        notificacion.className = `notificacion-flotante notificacion-${tipo}`;
        notificacion.innerHTML = `
            <span class="notificacion-icono">${tipo === 'success' ? '' : '!'}</span>
            <span class="notificacion-mensaje">${mensaje}</span>
            <button class="notificacion-cerrar" onclick="this.parentElement.remove()">×</button>
        `;
        
        // Agregar al DOM
        document.body.appendChild(notificacion);
        
        // Animación de entrada
        setTimeout(() => {
            notificacion.classList.add('visible');
        }, 100);
        
        // Auto-eliminar después de 3 segundos
        setTimeout(() => {
            if (notificacion.parentElement) {
                notificacion.classList.remove('visible');
                setTimeout(() => {
                    notificacion.remove();
                }, 300);
            }
        }, 3000);
    }
    
    // Método para actualizar favoritos desde otras partes del sistema
    actualizarFavoritos() {
        this.cargarFavoritos();
    }
}

// Hacer la clase disponible globalmente
window.FavoritosUsuario = new FavoritosUsuario();
