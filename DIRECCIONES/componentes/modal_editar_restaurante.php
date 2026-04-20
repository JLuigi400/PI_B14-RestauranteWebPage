<?php
// Verificar sesión y permisos
session_start();
if (!isset($_SESSION['id_usu'])) {
    echo '<div class="sj-error">Sesión no iniciada. Por favor inicie sesión.</div>';
    exit;
}

// Obtener ID del restaurante
$id_res = isset($_GET['id_res']) ? (int)$_GET['id_res'] : 0;

if ($id_res === 0) {
    echo '<div class="sj-error">ID de restaurante no válido</div>';
    exit;
}

// Conexión a la base de datos
require_once '../../PHP/conexion.php';

// Verificar que la conexión existe
if (!isset($conn) || !$conn) {
    echo '<div class="sj-error">Error de conexión a la base de datos</div>';
    exit;
}

// Obtener datos del restaurante
$query = "SELECT r.*, c.nombre_colonia, c.ciudad, c.estado 
          FROM restaurante r 
          LEFT JOIN colonias c ON r.id_colonia = c.id_colonia 
          WHERE r.id_res = ?";
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo '<div class="sj-error">Error al preparar consulta</div>';
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $id_res);
if (!mysqli_stmt_execute($stmt)) {
    echo '<div class="sj-error">Error al ejecutar consulta</div>';
    exit;
}

$resultado = mysqli_stmt_get_result($stmt);
$restaurante = mysqli_fetch_assoc($resultado);

if (!$restaurante) {
    echo '<div class="sj-error">Restaurante no encontrado</div>';
    exit;
}

// Verificar permisos (Admin puede editar todos, Dueño solo los suyos)
$id_rol = $_SESSION['id_rol'];
$id_usuario = $_SESSION['id_usu'];

if ($id_rol != 1 && $restaurante['id_usu'] != $id_usuario) {
    echo '<div class="sj-error">No tienes permisos para editar este restaurante</div>';
    exit;
}

// Obtener colonias para el select
$query_colonias = "SELECT id_colonia, nombre_colonia, ciudad, estado FROM colonias WHERE estatus_colonia = 1 ORDER BY nombre_colonia";
$stmt_colonias = mysqli_prepare($conn, $query_colonias);
mysqli_stmt_execute($stmt_colonias);
$resultado_colonias = mysqli_stmt_get_result($stmt_colonias);
?>

<!-- Modal de Edición de Restaurante -->
<div class="sj-modal-overlay" id="modalEditarRestaurante">
    <div class="sj-modal">
        <div class="sj-modal-header">
            <h2 class="sj-modal-title">Editar Restaurante</h2>
            <button class="sj-modal-close" onclick="cerrarModalEditarRestaurante()">&times;</button>
        </div>
        
        <form id="formEditarRestaurante" class="sj-form" enctype="multipart/form-data">
            <input type="hidden" name="id_res" value="<?php echo $id_res; ?>">
            
            <!-- Sección 1: Información Principal -->
            <div class="sj-section">
                <h3 class="sj-section-title">Información Principal</h3>
                
                <div class="sj-form-group">
                    <label for="nombre_res" class="sj-label">Nombre del Restaurante *</label>
                    <input type="text" id="nombre_res" name="nombre_res" class="sj-input" 
                           value="<?php echo htmlspecialchars($restaurante['nombre_res']); ?>" required>
                </div>
                
                <div class="sj-form-group">
                    <label for="descripcion_res" class="sj-label">Descripción</label>
                    <textarea id="descripcion_res" name="descripcion_res" class="sj-textarea" 
                              rows="4"><?php echo htmlspecialchars($restaurante['descripcion_res']); ?></textarea>
                </div>
                
                <div class="sj-form-row">
                    <div class="sj-form-group">
                        <label for="telefono_res" class="sj-label">Teléfono</label>
                        <input type="tel" id="telefono_res" name="telefono_res" class="sj-input" 
                               value="<?php echo htmlspecialchars($restaurante['telefono_res']); ?>">
                    </div>
                    
                    <div class="sj-form-group">
                        <label for="url_web" class="sj-label">Sitio Web</label>
                        <input type="url" id="url_web" name="url_web" class="sj-input" 
                               value="<?php echo htmlspecialchars($restaurante['url_web']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Sección 2: Ubicación & Mapa -->
            <div class="sj-section">
                <h3 class="sj-section-title">Ubicación</h3>
                
                <div class="sj-form-group">
                    <label for="direccion_res" class="sj-label">Dirección Completa *</label>
                    <input type="text" id="direccion_res" name="direccion_res" class="sj-input" 
                           value="<?php echo htmlspecialchars($restaurante['direccion_res']); ?>" required>
                </div>

                <div class="sj-form-group">
                    <label for="sector_res" class="sj-label">Sector / Barrio</label>
                    <input type="text" id="sector_res" name="sector_res" class="sj-input"
                           value="<?php echo htmlspecialchars($restaurante['sector_res']); ?>"
                           placeholder="Ej: Centro, Zona Norte, Zona Centro">
                </div>

                <div class="sj-form-group">
                    <label for="id_colonia" class="sj-label">Colonia</label>
                    <select id="id_colonia" name="id_colonia" class="sj-select">
                        <option value="">Seleccionar colonia...</option>
                        <?php while ($colonia = mysqli_fetch_assoc($resultado_colonias)): ?>
                            <option value="<?php echo $colonia['id_colonia']; ?>" 
                                    <?php echo ($restaurante['id_colonia'] == $colonia['id_colonia']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($colonia['nombre_colonia'] . ', ' . $colonia['ciudad']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <!-- Coordenadas (ocultas) -->
                <input type="hidden" id="latitud" name="latitud" value="<?php echo $restaurante['latitud']; ?>">
                <input type="hidden" id="longitud" name="longitud" value="<?php echo $restaurante['longitud']; ?>">
                
                <!-- Mini Mapa -->
                <div class="sj-map-container">
                    <div id="mapaRestaurante" class="sj-map"></div>
                    <div class="sj-map-instructions">
                        <small>Haz clic en el mapa para actualizar la ubicación</small>
                    </div>
                </div>
            </div>
            
            <!-- Sección 3: Identidad Visual -->
            <div class="sj-section">
                <h3 class="sj-section-title">Identidad Visual</h3>
                
                <div class="sj-form-row">
                    <div class="sj-form-group">
                        <label for="logo_res" class="sj-label">Logo del Restaurante</label>
                        <input type="file" id="logo_res" name="logo_res" class="sj-file" accept="image/*">
                        <div class="sj-current-image">
                            <?php if ($restaurante['logo_res'] && $restaurante['logo_res'] !== 'default_logo.png'): ?>
                                <img src="../<?php echo htmlspecialchars($restaurante['logo_res']); ?>" alt="Logo actual" class="sj-thumb">
                                <small>Logo actual</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="sj-form-group">
                        <label for="banner_res" class="sj-label">Banner Principal</label>
                        <input type="file" id="banner_res" name="banner_res" class="sj-file" accept="image/*">
                        <div class="sj-current-image">
                            <?php if ($restaurante['banner_res'] && $restaurante['banner_res'] !== 'default_banner.png'): ?>
                                <img src="../<?php echo htmlspecialchars($restaurante['banner_res']); ?>" alt="Banner actual" class="sj-thumb">
                                <small>Banner actual</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sección 4: Moderación (Solo Admin) -->
            <?php if ($id_rol == 1): ?>
            <div class="sj-section sj-admin-section">
                <h3 class="sj-section-title">Moderación (Administrador)</h3>
                
                <div class="sj-form-row">
                    <div class="sj-form-group">
                        <label class="sj-checkbox-label">
                            <input type="checkbox" id="estatus_res" name="estatus_res" class="sj-checkbox" 
                                   <?php echo ($restaurante['estatus_res'] == 1) ? 'checked' : ''; ?>>
                            <span class="sj-checkbox-custom"></span>
                            Restaurante Activo
                        </label>
                    </div>
                    
                    <div class="sj-form-group">
                        <label for="validado_admin" class="sj-label">Estado de Validación</label>
                        <select id="validado_admin" name="validado_admin" class="sj-select">
                            <option value="1" <?php echo ($restaurante['validado_admin'] == 1) ? 'selected' : ''; ?>>Aprobado</option>
                            <option value="0" <?php echo ($restaurante['validado_admin'] == 0) ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="2" <?php echo ($restaurante['validado_admin'] == 2) ? 'selected' : ''; ?>>Rechazado</option>
                        </select>
                    </div>
                </div>
                
                <div class="sj-form-group">
                    <label for="motivo_rechazo" class="sj-label">Motivo de Rechazo</label>
                    <textarea id="motivo_rechazo" name="motivo_rechazo" class="sj-textarea" 
                              rows="3" placeholder="Especificar motivo si se rechaza..."><?php 
                        echo htmlspecialchars($restaurante['motivo_rechazo']); 
                    ?></textarea>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Botones de Acción -->
            <div class="sj-form-actions">
                <button type="button" class="sj-btn sj-btn-cancel" onclick="cerrarModalEditarRestaurante()">
                    Cancelar
                </button>
                <button type="submit" class="sj-btn sj-btn-primary">
                    <span class="sj-btn-text">Guardar Cambios</span>
                    <span class="sj-btn-loading" style="display: none;">
                        <span class="sj-spinner"></span>
                        Guardando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Script EmailJS -->
<script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>

<style>
/* Estilos del Modal */
.sj-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.sj-modal {
    background: #1a1a1a;
    border: 2px solid #27ae60;
    border-radius: 15px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    color: #ffffff;
}

.sj-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    border-bottom: 1px solid #27ae60;
}

.sj-modal-title {
    margin: 0;
    color: #27ae60;
    font-size: 24px;
    font-weight: 600;
}

.sj-modal-close {
    background: none;
    border: none;
    color: #e74c3c;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sj-form {
    padding: 30px;
}

.sj-section {
    margin-bottom: 30px;
}

.sj-section-title {
    color: #27ae60;
    font-size: 18px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #333;
}

.sj-form-group {
    margin-bottom: 20px;
}

.sj-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.sj-label {
    display: block;
    margin-bottom: 8px;
    color: #ecf0f1;
    font-weight: 500;
}

.sj-input, .sj-select, .sj-textarea {
    width: 100%;
    padding: 12px 16px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    color: #2c3e50;
    font-size: 14px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.sj-input:focus, .sj-select:focus, .sj-textarea:focus {
    outline: none;
    border-color: #27ae60;
    box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
}

.sj-textarea {
    resize: vertical;
    min-height: 100px;
}

.sj-file {
    background: #34495e;
    color: #ecf0f1;
    border: 2px solid #27ae60;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
}

.sj-current-image {
    margin-top: 10px;
}

.sj-thumb {
    max-width: 80px;
    max-height: 80px;
    border-radius: 8px;
    border: 2px solid #27ae60;
}

.sj-map-container {
    margin-top: 20px;
}

.sj-map {
    height: 300px;
    border-radius: 8px;
    border: 2px solid #27ae60;
}

.sj-map-instructions {
    margin-top: 10px;
    text-align: center;
    color: #95a5a6;
}

.sj-admin-section {
    background: #2c3e50;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #27ae60;
}

.sj-checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    color: #ecf0f1;
}

.sj-checkbox {
    display: none;
}

.sj-checkbox-custom {
    width: 20px;
    height: 20px;
    border: 2px solid #27ae60;
    border-radius: 4px;
    margin-right: 10px;
    position: relative;
    transition: all 0.3s ease;
}

.sj-checkbox:checked + .sj-checkbox-custom {
    background: #27ae60;
}

.sj-checkbox:checked + .sj-checkbox-custom::after {
    content: '×';
    position: absolute;
    top: -2px;
    left: 4px;
    color: white;
    font-size: 16px;
}

.sj-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #333;
}

.sj-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sj-btn-cancel {
    background: #e74c3c;
    color: white;
}

.sj-btn-cancel:hover {
    background: #c0392b;
}

.sj-btn-primary {
    background: #27ae60;
    color: white;
}

.sj-btn-primary:hover {
    background: #2ecc71;
}

.sj-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.sj-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.sj-error {
    background: #e74c3c;
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

@media (max-width: 768px) {
    .sj-form-row {
        grid-template-columns: 1fr;
    }
    
    .sj-modal {
        width: 95%;
        margin: 10px;
    }
    
    .sj-form {
        padding: 20px;
    }
}
</style>

<?php
// Liberar memoria
mysqli_free_result($resultado);
mysqli_free_result($resultado_colonias);
mysqli_close($conn);
?>

<!-- Script de EmailJS -->
<script src="../../JS/emailjs_config.js"></script>
