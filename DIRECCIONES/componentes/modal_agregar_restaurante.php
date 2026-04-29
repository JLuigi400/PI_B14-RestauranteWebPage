<?php
// Verificar sesión y permisos
session_start();
if (!isset($_SESSION['id_usu'])) {
    echo '<div class="sj-error">Sesión no iniciada. Por favor inicie sesión.</div>';
    exit;
}

// Conexión a la base de datos
require_once '../../PHP/conexion.php';

// Verificar que la conexión existe
if (!isset($conn) || !$conn) {
    error_log("Error de conexión en modal_agregar_restaurante.php: " . print_r($conn, true));
    echo '<div class="sj-error">Error de conexión a la base de datos</div>';
    exit;
}

// Debug de conexión
error_log("Conexión exitosa en modal_agregar_restaurante.php");

// Obtener colonias para el select
$query_colonias = "SELECT id_colonia, nombre_colonia, ciudad, estado FROM colonias WHERE estatus_colonia = 1 ORDER BY nombre_colonia";
$stmt_colonias = mysqli_prepare($conn, $query_colonias);
mysqli_stmt_execute($stmt_colonias);
$resultado_colonias = mysqli_stmt_get_result($stmt_colonias);
?>

<!-- Modal de Agregar Restaurante -->
<div class="sj-modal-overlay" id="modalAgregarRestaurante">
    <div class="sj-modal">
        <div class="sj-modal-header">
            <h2 class="sj-modal-title">Agregar Nuevo Restaurante</h2>
            <button class="sj-modal-close" onclick="cerrarModalAgregarRestaurante()">&times;</button>
        </div>
        
        <form id="formAgregarRestaurante" class="sj-form" enctype="multipart/form-data">
            <div class="sj-form-section">
                <h3>Información Básica</h3>
                
                <div class="sj-form-group">
                    <label for="nombre_res" class="sj-label">Nombre del Restaurante *</label>
                    <input type="text" id="nombre_res" name="nombre_res" class="sj-input" required
                           placeholder="Ej: La Cocina Saludable" maxlength="100">
                </div>

                <div class="sj-form-group">
                    <label for="descripcion_res" class="sj-label">Descripción</label>
                    <textarea id="descripcion_res" name="descripcion_res" class="sj-textarea" rows="3"
                              placeholder="Describe tu restaurante, especialidades, filosofía..."></textarea>
                </div>

                <div class="sj-form-row">
                    <div class="sj-form-group">
                        <label for="telefono_res" class="sj-label">Teléfono</label>
                        <input type="tel" id="telefono_res" name="telefono_res" class="sj-input"
                               placeholder="Ej: (656) 123-4567" maxlength="20">
                    </div>

                    <div class="sj-form-group">
                        <label for="url_web" class="sj-label">Sitio Web</label>
                        <input type="url" id="url_web" name="url_web" class="sj-input"
                               placeholder="https://ejemplo.com" maxlength="255">
                    </div>
                </div>
            </div>

            <div class="sj-form-section">
                <h3>Ubicación</h3>
                
                <div class="sj-form-group">
                    <label for="direccion_res" class="sj-label">Dirección *</label>
                    <input type="text" id="direccion_res" name="direccion_res" class="sj-input" required
                           placeholder="Ej: Av. Juárez #123, Colonia Centro">
                </div>

                <div class="sj-form-row">
                    <div class="sj-form-group">
                        <label for="sector_res" class="sj-label">Sector / Barrio</label>
                        <input type="text" id="sector_res" name="sector_res" class="sj-input"
                               placeholder="Ej: Centro, Zona Norte, Zona Centro">
                    </div>

                    <div class="sj-form-group">
                        <label for="id_colonia" class="sj-label">Colonia *</label>
                        <select id="id_colonia" name="id_colonia" class="sj-select" required>
                            <option value="">Selecciona una colonia</option>
                            <?php while ($colonia = mysqli_fetch_assoc($resultado_colonias)): ?>
                                <option value="<?php echo $colonia['id_colonia']; ?>">
                                    <?php echo htmlspecialchars($colonia['nombre_colonia'] . ', ' . $colonia['ciudad']); ?>
                                </option>
                            <?php endwhile; ?>
                            <option value="otro">Otro (especificar)</option>
                        </select>
                    </div>
                </div>

                <div class="sj-form-group" id="nueva_colonia_group" style="display: none;">
                    <label for="nueva_colonia" class="sj-label">¿En qué colonia se encuentra tu restaurante? *</label>
                    <input type="text" id="nueva_colonia" name="nueva_colonia" class="sj-input"
                           placeholder="Ej: San Lorenzo, Campestre, etc.">
                </div>

                <div class="sj-form-row" id="nueva_colonia_datos_group" style="display: none;">
                    <div class="sj-form-group">
                        <label for="nueva_colonia_ciudad" class="sj-label">Ciudad *</label>
                        <input type="text" id="nueva_colonia_ciudad" name="nueva_colonia_ciudad" class="sj-input"
                               placeholder="Ej: Ciudad Juárez">
                    </div>
                    <div class="sj-form-group">
                        <label for="nueva_colonia_estado" class="sj-label">Estado *</label>
                        <input type="text" id="nueva_colonia_estado" name="nueva_colonia_estado" class="sj-input"
                               placeholder="Ej: Chihuahua">
                    </div>
                </div>

                <div class="sj-form-row" id="nueva_colonia_geo_group" style="display: none;">
                    <div class="sj-form-group">
                        <label for="nueva_colonia_pais" class="sj-label">País *</label>
                        <input type="text" id="nueva_colonia_pais" name="nueva_colonia_pais" class="sj-input"
                               placeholder="Ej: México" value="México">
                    </div>
                    <div class="sj-form-group">
                        <label for="nueva_colonia_cp" class="sj-label">Código Postal</label>
                        <input type="text" id="nueva_colonia_cp" name="nueva_colonia_cp" class="sj-input"
                               placeholder="Ej: 32310" maxlength="10">
                    </div>
                </div>

                <div class="sj-form-group" id="nueva_colonia_coords_group" style="display: none;">
                    <label class="sj-label">Coordenadas de la Colonia (opcional)</label>
                    <div class="sj-form-row">
                        <input type="number" id="nueva_colonia_lat" name="nueva_colonia_lat" class="sj-input"
                               step="any" placeholder="Latitud de la colonia">
                        <input type="number" id="nueva_colonia_lng" name="nueva_colonia_lng" class="sj-input"
                               step="any" placeholder="Longitud de la colonia">
                    </div>
                    <small class="sj-help-text">Opcional: Puedes especificar las coordenadas generales de la colonia</small>
                </div>

                <div class="sj-form-group">
                    <label for="coordenadas" class="sj-label">Coordenadas</label>
                    <div class="sj-form-row">
                        <input type="number" id="latitud" name="latitud" class="sj-input" 
                               step="any" placeholder="Latitud (ej: 31.7386)">
                        <input type="number" id="longitud" name="longitud" class="sj-input" 
                               step="any" placeholder="Longitud (ej: -106.4844)">
                    </div>
                    <small class="sj-help-text">Haz clic en el mapa o ingresa las coordenadas manualmente</small>
                </div>

                <div class="sj-map-container">
                    <div id="mapaRestaurante" class="sj-map" style="height: 300px;"></div>
                    <small class="sj-help-text">Haz clic en el mapa para seleccionar la ubicación de tu restaurante</small>
                </div>
            </div>

            <div class="sj-form-section">
                <h3>Imágenes</h3>
                
                <div class="sj-form-row">
                    <div class="sj-form-group">
                        <label for="logo_res" class="sj-label">Logo del Restaurante</label>
                        <input type="file" id="logo_res" name="logo_res" class="sj-file"
                               accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small class="sj-help-text">Formato: JPG, PNG o WebP. Máximo 2MB</small>
                    </div>

                    <div class="sj-form-group">
                        <label for="banner_res" class="sj-label">Banner del Restaurante</label>
                        <input type="file" id="banner_res" name="banner_res" class="sj-file"
                               accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small class="sj-help-text">Formato: JPG, PNG o WebP. Máximo 2MB</small>
                    </div>
                </div>
            </div>

            <div class="sj-form-actions">
                <button type="button" class="sj-btn-secondary" onclick="cerrarModalAgregarRestaurante()">
                    Cancelar
                </button>
                <button type="submit" class="sj-btn-primary">
                    <span class="sj-btn-text">Agregar Restaurante</span>
                    <span class="sj-btn-loading" style="display: none;">
                        <span class="sj-spinner"></span>
                        Guardando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Estilos del Modal */
.sj-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
}

.sj-modal {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.sj-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 1px solid #e9ecef;
}

.sj-modal-title {
    margin: 0;
    font-size: 1.5rem;
    color: #2c3e50;
}

.sj-modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: #6c757d;
    padding: 0;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.sj-modal-close:hover {
    background: #f8f9fa;
    color: #495057;
}

.sj-form {
    padding: 24px;
}

.sj-form-section {
    margin-bottom: 32px;
}

.sj-form-section h3 {
    margin: 0 0 16px 0;
    color: #2c3e50;
    font-size: 1.1rem;
    border-bottom: 2px solid #27ae60;
    padding-bottom: 8px;
}

.sj-form-group {
    margin-bottom: 20px;
}

.sj-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.sj-label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #495057;
}

.sj-input, .sj-textarea, .sj-select {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.sj-input:focus, .sj-textarea:focus, .sj-select:focus {
    outline: none;
    border-color: #27ae60;
    box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
}

.sj-textarea {
    resize: vertical;
    min-height: 80px;
}

.sj-file {
    width: 100%;
    padding: 8px;
    border: 2px dashed #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.sj-file:focus {
    outline: none;
    border-color: #27ae60;
}

.sj-help-text {
    display: block;
    margin-top: 4px;
    color: #6c757d;
    font-size: 0.875rem;
}

.sj-map-container {
    margin-top: 16px;
}

.sj-map {
    border-radius: 8px;
    border: 2px solid #e9ecef;
}

.sj-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid #e9ecef;
}

.sj-btn-primary, .sj-btn-secondary {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.sj-btn-primary {
    background: #27ae60;
    color: white;
}

.sj-btn-primary:hover:not(:disabled) {
    background: #219a52;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

.sj-btn-secondary {
    background: #6c757d;
    color: white;
}

.sj-btn-secondary:hover {
    background: #5a6268;
}

.sj-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.sj-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .sj-modal {
        width: 95%;
        margin: 20px;
    }
    
    .sj-form-row {
        grid-template-columns: 1fr;
    }
    
    .sj-form {
        padding: 16px;
    }
}
</style>

<?php
// Liberar memoria
mysqli_free_result($resultado_colonias);
mysqli_close($conn);
?>
