<?php
/**
 * Carrusel de Restaurantes - Salud Juárez
 * Carga dinámica desde base de datos con imágenes reales
 * Versión: 1.0.0
 * Fecha: 27 de Marzo de 2026
 */

// Conexión a la base de datos
require_once 'conexion.php';

// Consulta para obtener restaurantes activos y validados
$query = "SELECT 
    r.id_res,
    r.nombre_res,
    r.descripcion_res,
    r.direccion_res,
    r.telefono_res,
    r.sector_res,
    r.latitud,
    r.longitud,
    r.logo_res,
    r.banner_res,
    u.nombre_usu AS nombre_dueño,
    u.apellido_usu AS apellido_dueño,
    (SELECT AVG(p.precio_pla) FROM platillos p WHERE p.id_res = r.id_res AND p.visible = 1) AS precio_promedio,
    (SELECT COUNT(*) FROM platillos p WHERE p.id_res = r.id_res AND p.visible = 1) AS total_platillos,
    (SELECT AVG(CASE WHEN f.id_favorito IS NOT NULL THEN 5 ELSE 0 END) 
     FROM usuarios u2 
     LEFT JOIN favoritos f ON f.id_res = r.id_res AND f.id_usu = u2.id_usu 
     WHERE u2.id_rol = 3) AS rating_promedio
FROM restaurante r
INNER JOIN usuarios u ON r.id_usu = u.id_usu
WHERE r.estatus_res = 1 
AND r.validado_admin = 1
ORDER BY r.fecha_registro DESC
LIMIT 10";

$resultado = $conn->query($query);
$restaurantes = [];

if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $restaurantes[] = $row;
    }
}

// Función para obtener imagen con fallback
function getImagenConFallback($imagenBD, $imagenReal, $imagenDefault) {
    // Si la imagen de la BD es NULL o vacía
    if (empty($imagenBD) || $imagenBD === 'default_logo.png' || $imagenBD === 'default_banner.png') {
        return $imagenDefault;
    }
    
    // Verificar si el archivo existe físicamente
    $rutaCompleta = $_SERVER['DOCUMENT_ROOT'] . '/restaurantes/' . $imagenBD;
    if (file_exists($rutaCompleta) && is_file($rutaCompleta)) {
        return $imagenBD;
    }
    
    // Si existe imagen real específica
    if (!empty($imagenReal)) {
        $rutaReal = $_SERVER['DOCUMENT_ROOT'] . '/restaurantes/' . $imagenReal;
        if (file_exists($rutaReal) && is_file($rutaReal)) {
            return $imagenReal;
        }
    }
    
    // Fallback final
    return $imagenDefault;
}

// Función para obtener imagen del carrusel
function getImagenCarrusel($nombreRestaurante, $imagenBD, $tipo = 'banner') {
    $imagenes_reales = [
        'banner' => [
            'Restaurante Verde' => 'IMG/UPLOADS/RESTAURANTES/Restaurante_Verde_01.jpeg',
            'Sabor Saludable' => 'IMG/UPLOADS/RESTAURANTES/Sabor_Saludable_02.png',
            'Nutri Kitchen' => 'IMG/UPLOADS/RESTAURANTES/Nutri_Kitchen_02.jpeg',
            'Vida Verde' => 'IMG/UPLOADS/RESTAURANTES/Vida_Verde_01.png',
            'El Rincón Saludable' => 'IMG/UPLOADS/RESTAURANTES/El_Rincón_Saludable_02.png'
        ],
        'logo' => [
            'Restaurante Verde' => 'IMG/LOGOTIPOS/RESTAURANTE/logo_restaurante_verde.png',
            'Sabor Saludable' => 'IMG/LOGOTIPOS/RESTAURANTE/logo_sabor_saludable.jpeg',
            'Nutri Kitchen' => 'IMG/LOGOTIPOS/RESTAURANTE/logo_nutri_kitchen.png',
            'Vida Verde' => 'IMG/LOGOTIPOS/RESTAURANTE/logo_vida_verde.jpeg',
            'El Rincón Saludable' => 'IMG/LOGOTIPOS/RESTAURANTE/logo_rincon_saludable.png'
        ]
    ];
    
    $imagenReal = $imagenes_reales[$tipo][$nombreRestaurante] ?? null;
    $imagenDefault = ($tipo === 'banner') 
        ? 'IMG/UPLOADS/RESTAURANTES/default_banner.png'
        : 'IMG/UPLOADS/RESTAURANTES/default_logo.jpeg';
    
    return getImagenConFallback($imagenBD, $imagenReal, $imagenDefault);
}

// Función para obtener certificación basada en rating
function getCertificacion($rating) {
    if ($rating >= 4.8) return ['Oro', '#ffd700'];
    if ($rating >= 4.5) return ['Plata', '#c0c0c0'];
    return ['Bronce', '#cd7f32'];
}

// Función para generar estrellas
function generarEstrellas($rating) {
    $estrellas = '';
    $entero = floor($rating);
    $mitad = ($rating - $entero) >= 0.5;
    
    for ($i = 0; $i < 5; $i++) {
        if ($i < $entero) {
            $estrellas .= '⭐';
        } elseif ($i == $entero && $mitad) {
            $estrellas .= '✨';
        } else {
            $estrellas .= '☆';
        }
    }
    return $estrellas;
}
?>

<!-- Carrusel de Restaurantes con Datos Reales -->
<section class="sj-restaurantes">
    <div class="container">
        <div class="carrusel-header">
            <h2 class="carrusel-titulo">🌟 Restaurantes Certificados</h2>
            <p class="carrusel-subtitulo">Descubre los mejores establecimientos con certificación nutricional</p>
        </div>
        
        <div class="carrusel-wrapper">
            <!-- Controles de navegación -->
            <button class="carrusel-control prev" aria-label="Anterior">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </button>
            <button class="carrusel-control next" aria-label="Siguiente">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </button>
            
            <div class="carrusel-contenedor">
                <div class="carrusel-track">
                    <?php if (!empty($restaurantes)): ?>
                        <?php foreach ($restaurantes as $index => $restaurante): ?>
                            <?php
                            // Usar funciones de fallback mejoradas
                            $banner_final = getImagenCarrusel($restaurante['nombre_res'], $restaurante['banner_res'], 'banner');
                            $logo_final = getImagenCarrusel($restaurante['nombre_res'], $restaurante['logo_res'], 'logo');
                            
                            $rating = floatval($restaurante['rating_promedio'] ?? 4.5);
                            $certificacion = getCertificacion($rating);
                            $estrellas = generarEstrellas($rating);
                            ?>
                            
                            <div class="carrusel-slide" data-index="<?php echo $index; ?>">
                                <div class="slide-card">
                                    <div class="slide-imagen">
                                        <img src="<?php echo htmlspecialchars($banner_final); ?>" 
                                             alt="<?php echo htmlspecialchars($restaurante['nombre_res']); ?>" 
                                             loading="lazy"
                                             style="object-fit: cover; width: 100%; height: 100%;"
                                             onerror="this.src='IMG/UPLOADS/RESTAURANTES/default_banner.png'">
                                        <div class="slide-certificacion" 
                                             style="background: <?php echo $certificacion[1]; ?>;">
                                            <?php echo $certificacion[0]; ?>
                                        </div>
                                    </div>
                                    <div class="slide-contenido">
                                        <div class="slide-header">
                                            <img src="<?php echo htmlspecialchars($logo_final); ?>" 
                                                 alt="Logo <?php echo htmlspecialchars($restaurante['nombre_res']); ?>" 
                                                 class="slide-logo"
                                                 style="object-fit: cover; width: 40px; height: 40px;"
                                                 onerror="this.src='IMG/UPLOADS/RESTAURANTES/default_logo.jpeg'">
                                            <h3 class="slide-titulo"><?php echo htmlspecialchars($restaurante['nombre_res']); ?></h3>
                                        </div>
                                        <p class="slide-descripcion"><?php echo htmlspecialchars($restaurante['descripcion_res']); ?></p>
                                        <div class="slide-meta">
                                            <div class="slide-rating">
                                                <span class="rating-estrellas"><?php echo $estrellas; ?></span>
                                                <span class="rating-numero"><?php echo number_format($rating, 1); ?></span>
                                            </div>
                                            <div class="slide-info">
                                                <span class="slide-platillos"><?php echo $restaurante['total_platillos']; ?> platillos</span>
                                                <?php if ($restaurante['precio_promedio']): ?>
                                                    <span class="slide-precio">Desde $<?php echo number_format($restaurante['precio_promedio'], 2); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="slide-actions">
                                            <a href="DIRECCIONES/ver_restaurante.php?id=<?php echo $restaurante['id_res']; ?>" 
                                               class="slide-boton primary">Ver Restaurantes</a>
                                            <button class="slide-boton secondary" 
                                                    onclick="agregarFavorito(<?php echo $restaurante['id_res']; ?>)">
                                                ⭐ Favorito
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Mensaje si no hay restaurantes -->
                        <div class="carrusel-vacio">
                            <div class="vacio-icono">🍽️</div>
                            <h3>No hay restaurantes disponibles</h3>
                            <p>Pronto tendrán nuevos establecimientos certificados</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Indicadores -->
        <div class="carrusel-indicadores">
            <?php if (!empty($restaurantes)): ?>
                <?php foreach ($restaurantes as $index => $restaurante): ?>
                    <button class="indicador <?php echo $index === 0 ? 'activo' : ''; ?>" 
                            data-index="<?php echo $index; ?>" 
                            aria-label="Ir al restaurante <?php echo $index + 1; ?>">
                        <span class="indicador-dot"></span>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Script para el carrusel -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    class CarruselRestaurantes {
        constructor() {
            this.container = document.querySelector('.sj-restaurantes');
            if (!this.container) return;
            
            this.track = this.container.querySelector('.carrusel-track');
            this.slides = Array.from(this.container.querySelectorAll('.carrusel-slide'));
            this.controles = {
                prev: this.container.querySelector('.carrusel-control.prev'),
                next: this.container.querySelector('.carrusel-control.next')
            };
            this.indicadores = Array.from(this.container.querySelectorAll('.indicador'));
            
            this.currentIndex = 0;
            this.slidesPorVista = this.getSlidesPorVista();
            this.totalSlides = this.slides.length;
            
            this.init();
        }
        
        init() {
            this.configurarEventos();
            this.actualizarCarrusel();
            this.iniciarAutoplay();
        }
        
        configurarEventos() {
            // Controles
            this.controles.prev?.addEventListener('click', () => this.mover('prev'));
            this.controles.next?.addEventListener('click', () => this.mover('next'));
            
            // Indicadores
            this.indicadores.forEach((indicador, index) => {
                indicador.addEventListener('click', () => this.irASlide(index));
            });
            
            // Touch events
            this.configurarTouchEvents();
            
            // Keyboard
            this.container.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') this.mover('prev');
                if (e.key === 'ArrowRight') this.mover('next');
            });
            
            // Responsive
            window.addEventListener('resize', () => {
                this.slidesPorVista = this.getSlidesPorVista();
                this.actualizarCarrusel();
            });
        }
        
        mover(direccion) {
            if (direccion === 'next') {
                this.currentIndex = (this.currentIndex + 1) % this.totalSlides;
            } else {
                this.currentIndex = (this.currentIndex - 1 + this.totalSlides) % this.totalSlides;
            }
            this.actualizarCarrusel();
        }
        
        irASlide(index) {
            this.currentIndex = index;
            this.actualizarCarrusel();
        }
        
        actualizarCarrusel() {
            const slideWidth = this.slides[0]?.offsetWidth || 0;
            const espacio = 20;
            const offset = slideWidth * this.currentIndex + espacio * this.currentIndex;
            
            this.track.style.transform = `translateX(-${offset}px)`;
            this.actualizarIndicadores();
        }
        
        actualizarIndicadores() {
            this.indicadores.forEach((indicador, index) => {
                if (index === this.currentIndex) {
                    indicador.classList.add('activo');
                } else {
                    indicador.classList.remove('activo');
                }
            });
        }
        
        getSlidesPorVista() {
            const containerWidth = this.container.querySelector('.carrusel-contenedor').offsetWidth;
            const slideWidth = this.slides[0]?.offsetWidth || 0;
            
            if (window.innerWidth <= 768) return 1;
            if (window.innerWidth <= 1024) return 2;
            return Math.min(3, Math.floor(containerWidth / (slideWidth + 20)));
        }
        
        configurarTouchEvents() {
            let startX = 0;
            let currentX = 0;
            let isDragging = false;
            
            const contenedor = this.container.querySelector('.carrusel-contenedor');
            
            contenedor.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                isDragging = true;
            });
            
            contenedor.addEventListener('touchmove', (e) => {
                if (!isDragging) return;
                currentX = e.touches[0].clientX;
            });
            
            contenedor.addEventListener('touchend', () => {
                if (!isDragging) return;
                
                const diff = startX - currentX;
                const threshold = 50;
                
                if (diff > threshold) {
                    this.mover('next');
                } else if (diff < -threshold) {
                    this.mover('prev');
                }
                
                isDragging = false;
            });
        }
        
        iniciarAutoplay() {
            setInterval(() => {
                this.mover('next');
            }, 5000);
        }
    }
    
    // Inicializar carrusel
    new CarruselRestaurantes();
});

// Función para agregar a favoritos
function agregarFavorito(idRestaurante) {
    // Aquí iría la lógica AJAX para agregar a favoritos
    console.log('Agregando a favoritos:', idRestaurante);
    
    // Mostrar notificación temporal
    const notificacion = document.createElement('div');
    notificacion.className = 'notificacion-temporal';
    notificacion.textContent = '⭐ Agregado a favoritos';
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #27ae60;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 10000;
        font-weight: 600;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notificacion);
    
    setTimeout(() => {
        notificacion.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notificacion.parentNode) {
                notificacion.parentNode.removeChild(notificacion);
            }
        }, 300);
    }, 3000);
}
</script>

<style>
/* Estilos adicionales para el carrusel dinámico */
.slide-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.slide-logo {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #f0f0f0;
}

.slide-titulo {
    font-size: 1.2rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.slide-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 0.9rem;
}

.slide-platillos {
    color: #7f8c8d;
    font-weight: 500;
}

.slide-precio {
    color: #27ae60;
    font-weight: 600;
}

.slide-actions {
    display: flex;
    gap: 10px;
    margin-top: auto;
}

.carrusel-vacio {
    text-align: center;
    padding: 60px 20px;
    color: #7f8c8d;
}

.vacio-icono {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
</style>
?>
