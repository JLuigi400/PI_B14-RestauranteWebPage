<?php
/**
 * Modal del Desarrollador - Salud Juárez
 * Muestra información sobre el desarrollador del sistema
 * Versión: 1.0.0
 */

$current_path = $_SERVER['PHP_SELF'] ?? '';
$is_in_direcciones = strpos($current_path, '/DIRECCIONES/') !== false;
$path = $is_in_direcciones ? "../" : "";
?>

<!-- Modal del Desarrollador -->
<div id="devModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:10000; justify-content:center; align-items:center;">
    <div class="modal-content" style="background:white; border-radius:16px; max-width:500px; width:90%; padding:30px; position:relative; margin:auto; margin-top:10vh; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
        <button onclick="closeDevModal()" style="position:absolute; top:15px; right:15px; background:none; border:none; font-size:1.5em; cursor:pointer; color:#999;">&times;</button>
        
        <div style="text-align:center; margin-bottom:20px;">
            <div style="font-size:4em; margin-bottom:10px;">👨‍💻</div>
            <h2 style="color:#2c3e50; margin:0;">Acerca del Desarrollador</h2>
        </div>

        <div style="background:#f8f9fa; padding:20px; border-radius:10px; margin-bottom:20px;">
            <h3 style="color:#27ae60; margin:0 0 10px;">Jorge Anibal Espinosa Perales</h3>
            <p style="color:#666; margin:0 0 5px;"><strong>Proyecto:</strong> Salud Juárez - Restaurantes Saludables</p>
            <p style="color:#666; margin:0 0 5px;"><strong>Ubicación:</strong> Ciudad Juárez, Chihuahua, México</p>
            <p style="color:#666; margin:0 0 5px;"><strong>GitHub:</strong> <a href="https://github.com/JLuigi400" target="_blank" style="color:#3498db;">@JLuigi400</a></p>
            <p style="color:#666; margin:0;"><strong>Tecnologías:</strong> PHP, MySQL, JavaScript, Leaflet, CSS3</p>
        </div>

        <div style="text-align:center; color:#999; font-size:0.85em;">
            <p>&copy; 2026 Salud Juárez · Proyecto Integrador B14</p>
        </div>
    </div>
</div>

<script>
    function openDevModal() {
        document.getElementById('devModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeDevModal() {
        document.getElementById('devModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Cerrar con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDevModal();
    });

    // Cerrar clickeando fuera
    document.getElementById('devModal').addEventListener('click', function(e) {
        if (e.target === this) closeDevModal();
    });
</script>
