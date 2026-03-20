// ==========================================
// ARCHIVO: JS/session_check.js
// Verificación automática de sesión activa
// ==========================================

document.addEventListener("DOMContentLoaded", function() {
    console.log("🔐 Verificador de sesión iniciado");
    
    // URLs importantes
    const LOGIN_URL = 'login.php';
    const DASHBOARD_URL = 'DIRECCIONES/dashboard.php';
    const INDEX_URL = 'index.html';
    
    // Páginas que requieren sesión
    const PROTECTED_PAGES = [
        'DIRECCIONES/dashboard.php',
        'DIRECCIONES/inventario/',
        'DIRECCIONES/gestion_platillos.php',
        'DIRECCIONES/buscar_restaurantes.php',
        'DIRECCIONES/mis_favoritos.php'
    ];
    
    // Páginas públicas
    const PUBLIC_PAGES = [
        'index.html',
        'login.php',
        'signup.php',
        'diagnosticar.php'
    ];
    
    // Obtener la página actual
    const currentPath = window.location.pathname;
    const currentPage = currentPath.split('/').pop() || 'index.html';
    
    console.log("📍 Página actual:", currentPage);
    
    // Verificar si estamos en una página protegida
    const isProtectedPage = PROTECTED_PAGES.some(page => currentPath.includes(page));
    const isPublicPage = PUBLIC_PAGES.some(page => currentPage.includes(page));
    
    // Función para verificar sesión
    async function checkSession() {
        try {
            const response = await fetch('PHP/check_session.php', {
                method: 'GET',
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                console.log("🔍 Estado de sesión:", data);
                
                if (data.logged_in) {
                    // Usuario tiene sesión activa
                    console.log("✅ Sesión activa para usuario:", data.username);
                    
                    // Si está en página pública pero tiene sesión, mostrar opciones
                    if (isPublicPage && currentPage !== 'index.html') {
                        showUserOptions(data);
                    }
                    
                    // Si está en login y tiene sesión, redirigir al dashboard
                    if (currentPage === 'login.php') {
                        console.log("🔄 Redirigiendo al dashboard...");
                        window.location.href = DASHBOARD_URL;
                    }
                } else {
                    // No hay sesión activa
                    console.log("❌ No hay sesión activa");
                    
                    // Si está en página protegida, redirigir al login
                    if (isProtectedPage) {
                        console.log("🔄 Redirigiendo al login...");
                        window.location.href = LOGIN_URL;
                    }
                }
            } else {
                console.error("❌ Error al verificar sesión");
            }
        } catch (error) {
            console.error("❌ Error en verificación de sesión:", error);
        }
    }
    
    // Función para mostrar opciones de usuario
    function showUserOptions(userData) {
        // Crear notificación flotante
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            font-family: Arial, sans-serif;
            max-width: 300px;
        `;
        
        notification.innerHTML = `
            <div style="font-weight: bold; margin-bottom: 8px;">
                👋 ¡Hola, ${userData.username}!
            </div>
            <div style="font-size: 14px; margin-bottom: 12px;">
                Ya tienes una sesión activa como ${userData.rol_nombre}.
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="${DASHBOARD_URL}" style="
                    background: white;
                    color: #28a745;
                    padding: 6px 12px;
                    border-radius: 4px;
                    text-decoration: none;
                    font-size: 12px;
                ">Ir al Dashboard</a>
                <a href="PHP/logout.php" style="
                    background: transparent;
                    color: white;
                    padding: 6px 12px;
                    border: 1px solid white;
                    border-radius: 4px;
                    text-decoration: none;
                    font-size: 12px;
                ">Cerrar Sesión</a>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-eliminar después de 8 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 8000);
        
        // Permitir cerrar manualmente
        notification.addEventListener('click', () => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        });
    }
    
    // Ejecutar verificación
    checkSession();
    
    // Verificar cada 30 segundos (opcional, para páginas largas)
    if (isProtectedPage) {
        setInterval(checkSession, 30000);
    }
});

// Función global para redirigir si la sesión expira
window.redirectIfNotLoggedIn = function() {
    window.location.href = 'login.php';
};

// Función para mostrar mensaje de sesión expirada
window.showSessionExpired = function() {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    
    modal.innerHTML = `
        <div style="
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            max-width: 400px;
        ">
            <h3 style="color: #dc3545; margin-bottom: 15px;">
                ⏰ Sesión Expirada
            </h3>
            <p style="margin-bottom: 20px; color: #666;">
                Tu sesión ha expirado por inactividad. Por favor, inicia sesión nuevamente.
            </p>
            <button onclick="window.location.href='login.php'" style="
                background: #007bff;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 14px;
            ">
                Iniciar Sesión
            </button>
        </div>
    `;
    
    document.body.appendChild(modal);
};
