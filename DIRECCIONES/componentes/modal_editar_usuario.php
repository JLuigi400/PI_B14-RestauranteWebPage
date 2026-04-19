<?php
// Modal de Edición de Usuario para Administradores
session_start();
if (!isset($_SESSION['id_usu'])) {
    header('Location: ../index.php');
    exit;
}

// Obtener ID del usuario
$id_usu = isset($_GET['id_usu']) ? (int)$_GET['id_usu'] : 0;

if ($id_usu === 0) {
    echo '<div class="sj-error">ID de usuario no válido</div>';
    exit;
}

// Conexión a la base de datos
require_once '../PHP/conexion.php';

// Obtener datos del usuario con información de perfil
$query = "SELECT u.*, r.nombre_rol, p.nombre_per, p.apellidos_per, p.correo_per, p.cel_per
          FROM usuarios u 
          JOIN roles r ON u.id_rol = r.id_rol 
          LEFT JOIN perfiles p ON u.id_usu = p.id_usu 
          WHERE u.id_usu = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, 'i', $id_usu);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($resultado);

if (!$usuario) {
    echo '<div class="sj-error">Usuario no encontrado</div>';
    exit;
}

// Obtener roles disponibles
$query_roles = "SELECT id_rol, nombre_rol FROM roles ORDER BY id_rol";
$stmt_roles = mysqli_prepare($conexion, $query_roles);
mysqli_stmt_execute($stmt_roles);
$resultado_roles = mysqli_stmt_get_result($stmt_roles);
?>

<!-- Modal de Edición de Usuario -->
<div class="sj-modal-overlay" id="modalEditarUsuario">
    <div class="sj-modal">
        <div class="sj-modal-header">
            <h2 class="sj-modal-title">Editar Usuario</h2>
            <button class="sj-modal-close" onclick="cerrarModalEditarUsuario()">&times;</button>
        </div>
        
        <form id="formEditarUsuario" class="sj-form">
            <input type="hidden" name="id_usu" value="<?php echo $id_usu; ?>">
            
            <!-- Sección 1: Información de Cuenta -->
            <div class="sj-section">
                <h3 class="sj-section-title">Información de Cuenta</h3>
                
                <div class="sj-form-group">
                    <label for="username_usu" class="sj-label">Nombre de Usuario</label>
                    <input type="text" id="username_usu" name="username_usu" class="sj-input" 
                           value="<?php echo htmlspecialchars($usuario['username_usu']); ?>" readonly>
                    <small class="sj-help">El nombre de usuario no se puede modificar</small>
                </div>
                
                <div class="sj-form-group">
                    <label for="correo_usu" class="sj-label">Correo Electrónico</label>
                    <input type="email" id="correo_usu" name="correo_usu" class="sj-input" 
                           value="<?php echo htmlspecialchars($usuario['correo_usu']); ?>" required>
                </div>
                
                <div class="sj-form-group">
                    <label for="id_rol" class="sj-label">Rol del Usuario</label>
                    <select id="id_rol" name="id_rol" class="sj-select">
                        <?php while ($rol = mysqli_fetch_assoc($resultado_roles)): ?>
                            <option value="<?php echo $rol['id_rol']; ?>" 
                                    <?php echo ($usuario['id_rol'] == $rol['id_rol']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="sj-form-group">
                    <label class="sj-checkbox-label">
                        <input type="checkbox" id="estatus_usu" name="estatus_usu" class="sj-checkbox" 
                               <?php echo ($usuario['estatus_usu'] == 1) ? 'checked' : ''; ?>>
                        <span class="sj-checkbox-custom"></span>
                        Usuario Activo
                    </label>
                </div>
            </div>
            
            <!-- Sección 2: Información Personal -->
            <div class="sj-section">
                <h3 class="sj-section-title">Información Personal</h3>
                
                <div class="sj-form-row">
                    <div class="sj-form-group">
                        <label for="nombre_per" class="sj-label">Nombre</label>
                        <input type="text" id="nombre_per" name="nombre_per" class="sj-input" 
                               value="<?php echo htmlspecialchars($usuario['nombre_per']); ?>">
                    </div>
                    
                    <div class="sj-form-group">
                        <label for="apellidos_per" class="sj-label">Apellidos</label>
                        <input type="text" id="apellidos_per" name="apellidos_per" class="sj-input" 
                               value="<?php echo htmlspecialchars($usuario['apellidos_per']); ?>">
                    </div>
                </div>
                
                <div class="sj-form-row">
                    <div class="sj-form-group">
                        <label for="correo_per" class="sj-label">Correo Personal</label>
                        <input type="email" id="correo_per" name="correo_per" class="sj-input" 
                               value="<?php echo htmlspecialchars($usuario['correo_per']); ?>">
                    </div>
                    
                    <div class="sj-form-group">
                        <label for="cel_per" class="sj-label">Celular</label>
                        <input type="tel" id="cel_per" name="cel_per" class="sj-input" 
                               value="<?php echo htmlspecialchars($usuario['cel_per']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Sección 3: Información de Sistema -->
            <div class="sj-section">
                <h3 class="sj-section-title">Información de Sistema</h3>
                
                <div class="sj-form-row">
                    <div class="sj-form-group">
                        <label class="sj-label">Fecha de Registro</label>
                        <input type="text" class="sj-input" value="<?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?>" readonly>
                    </div>
                    
                    <div class="sj-form-group">
                        <label class="sj-label">Última Actualización</label>
                        <input type="text" class="sj-input" value="<?php echo date('d/m/Y H:i', strtotime($usuario['fecha_actualizacion'])); ?>" readonly>
                    </div>
                </div>
                
                <div class="sj-form-group">
                    <label class="sj-label">Token de Verificación</label>
                    <input type="text" class="sj-input" value="<?php echo htmlspecialchars($usuario['token_verificacion'] ?: 'No generado'); ?>" readonly>
                </div>
            </div>
            
            <!-- Botones de Acción -->
            <div class="sj-form-actions">
                <button type="button" class="sj-btn sj-btn-cancel" onclick="cerrarModalEditarUsuario()">
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

<!-- Scripts -->
<script src="../JS/editar_usuario.js"></script>

<style>
/* Estilos del Modal de Usuario */
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
    max-width: 700px;
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

.sj-input, .sj-select {
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

.sj-input:focus, .sj-select:focus {
    outline: none;
    border-color: #27ae60;
    box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
}

.sj-input[readonly] {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
}

.sj-help {
    color: #95a5a6;
    font-size: 12px;
    margin-top: 5px;
    display: block;
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
    content: '✓';
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

.sj-error {
    background: #e74c3c;
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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
mysqli_free_result($resultado_roles);
mysqli_close($conexion);
?>
