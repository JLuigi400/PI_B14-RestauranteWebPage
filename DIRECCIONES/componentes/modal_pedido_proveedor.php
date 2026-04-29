<?php
// Verificar sesión y permisos
session_start();
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    echo '<div class="modal-error">Acceso denegado</div>';
    exit;
}

// Conexión a la base de datos
require_once '../../PHP/conexion.php';

// Obtener datos del proveedor
$id_proveedor = isset($_GET['id_proveedor']) ? (int)$_GET['id_proveedor'] : 0;
$id_producto = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : 0;

if ($id_proveedor === 0) {
    echo '<div class="modal-error">Proveedor no especificado</div>';
    exit;
}

// Obtener información del proveedor
$query_proveedor = "SELECT p.*, u.nombre_usu, u.email_usu 
                   FROM proveedores p 
                   JOIN usuario u ON p.id_usu = u.id_usu 
                   WHERE p.id_proveedor = ? AND p.estatus_proveedor = 1";
$stmt_proveedor = mysqli_prepare($conn, $query_proveedor);
mysqli_stmt_bind_param($stmt_proveedor, 'i', $id_proveedor);
mysqli_stmt_execute($stmt_proveedor);
$resultado_proveedor = mysqli_stmt_get_result($stmt_proveedor);
$proveedor = mysqli_fetch_assoc($resultado_proveedor);

if (!$proveedor) {
    echo '<div class="modal-error">Proveedor no encontrado o inactivo</div>';
    exit;
}

// Obtener productos del proveedor
$query_productos = "SELECT * FROM productos_proveedor 
                     WHERE id_proveedor = ? AND disponibilidad = 1 
                     ORDER BY categoria_producto, nombre_producto";
$stmt_productos = mysqli_prepare($conn, $query_productos);
mysqli_stmt_bind_param($stmt_productos, 'i', $id_proveedor);
mysqli_stmt_execute($stmt_productos);
$resultado_productos = mysqli_stmt_get_result($stmt_productos);
?>

<!-- Modal de Pedido a Proveedor -->
<div class="modal-overlay" id="modalPedidoProveedor">
    <div class="modal-container">
        <div class="modal-header">
            <div class="proveedor-info">
                <h2>📦 Nuevo Pedido</h2>
                <div class="proveedor-datos">
                    <strong><?php echo htmlspecialchars($proveedor['nombre_empresa']); ?></strong>
                    <span><?php echo htmlspecialchars($proveedor['nombre_contacto']); ?></span>
                    <small><?php echo htmlspecialchars($proveedor['telefono_proveedor']); ?></small>
                </div>
            </div>
            <button class="modal-close" onclick="cerrarModalPedido()">×</button>
        </div>
        
        <form id="formPedidoProveedor" class="pedido-form">
            <input type="hidden" name="id_proveedor" value="<?php echo $id_proveedor; ?>">
            <input type="hidden" name="id_usuario" value="<?php echo $_SESSION['id_usu']; ?>">
            
            <div class="form-section">
                <h3>🏪 Selección de Productos</h3>
                
                <div class="form-group">
                    <label for="id_producto">Producto *</label>
                    <select id="id_producto" name="id_producto" required onchange="actualizarPrecio()">
                        <option value="">Selecciona un producto...</option>
                        <?php 
                        $categoria_actual = '';
                        while ($producto = mysqli_fetch_assoc($resultado_productos)) {
                            if ($categoria_actual !== $producto['categoria_producto']) {
                                if ($categoria_actual !== '') echo '</optgroup>';
                                echo '<optgroup label="' . htmlspecialchars($producto['categoria_producto']) . '">';
                                $categoria_actual = $producto['categoria_producto'];
                            }
                            $selected = ($id_producto == $producto['id_producto']) ? 'selected' : '';
                            echo '<option value="' . $producto['id_producto'] . '" ' . $selected . ' data-precio="' . $producto['precio_unitario'] . '" data-unidad="' . htmlspecialchars($producto['unidad_medida']) . '">';
                            echo htmlspecialchars($producto['nombre_producto']) . ' - $' . number_format($producto['precio_unitario'], 2) . '/' . $producto['unidad_medida'];
                            echo '</option>';
                        }
                        if ($categoria_actual !== '') echo '</optgroup>';
                        ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cantidad_solicitada">Cantidad *</label>
                        <input type="number" id="cantidad_solicitada" name="cantidad_solicitada" 
                               step="0.01" min="0.01" required onchange="calcularSubtotal()">
                        <span class="unidad-medida" id="unidad_medida_display">-</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="precio_unitario">Precio Unitario</label>
                        <input type="text" id="precio_unitario" readonly value="$0.00">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="subtotal_pedido">Subtotal</label>
                    <input type="text" id="subtotal_pedido" readonly value="$0.00">
                </div>
            </div>
            
            <div class="form-section">
                <h3>🚚 Detalles de Entrega</h3>
                
                <div class="form-group">
                    <label for="urgencia">Nivel de Urgencia *</label>
                    <select id="urgencia" name="urgencia" required>
                        <option value="Normal">Normal (3-5 días)</option>
                        <option value="Urgente">Urgente (1-2 días)</option>
                        <option value="Express">Express (mismo día)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notas_pedido">Notas del Pedido</label>
                    <textarea id="notas_pedido" name="notas_pedido" rows="3" 
                              placeholder="Especifica si es para ingrediente secreto, conjunto especial, o cualquier otra indicación..."></textarea>
                    <small>Puedes especificar si este es un ingrediente secreto o un conjunto predefinido</small>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModalPedido()">Cancelar</button>
                <button type="submit" class="btn-primary">
                    <span class="btn-text">Enviar Pedido</span>
                    <span class="btn-loading" style="display: none;">Enviando...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Modal Overlay */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 10000;
    backdrop-filter: blur(5px);
}

/* Modal Container - Industrial Dark */
.modal-container {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    border: 1px solid #444;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Modal Header */
.modal-header {
    background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
    border-bottom: 1px solid #444;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.proveedor-info h2 {
    color: #00ff88;
    font-size: 24px;
    margin: 0 0 10px 0;
    text-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
}

.proveedor-datos {
    color: #ccc;
}

.proveedor-datos strong {
    color: #fff;
    display: block;
    font-size: 16px;
    margin-bottom: 5px;
}

.proveedor-datos span {
    color: #00ff88;
    font-size: 14px;
}

.proveedor-datos small {
    color: #888;
    font-size: 12px;
}

.modal-close {
    background: #ff4444;
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #ff6666;
    transform: rotate(90deg);
}

/* Form Styles */
.pedido-form {
    padding: 20px;
}

.form-section {
    margin-bottom: 30px;
}

.form-section h3 {
    color: #00ff88;
    font-size: 18px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #444;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group label {
    display: block;
    color: #ccc;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    background: #2a2a2a;
    border: 1px solid #555;
    border-radius: 4px;
    color: #fff;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #00ff88;
    box-shadow: 0 0 10px rgba(0, 255, 136, 0.2);
}

.form-group input[readonly] {
    background: #1a1a1a;
    color: #00ff88;
    font-weight: 600;
}

.unidad-medida {
    color: #00ff88;
    font-weight: 600;
    margin-left: 10px;
}

/* Select Optgroup Styling */
select optgroup {
    background: #333;
    color: #00ff88;
    font-weight: 600;
}

select option {
    background: #2a2a2a;
    color: #fff;
    padding: 10px;
}

/* Modal Footer */
.modal-footer {
    background: #1a1a1a;
    border-top: 1px solid #444;
    padding: 20px;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
}

.btn-primary,
.btn-secondary {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
    color: #000;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #00ff99 0%, #00dd77 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 255, 136, 0.3);
}

.btn-secondary {
    background: #444;
    color: #ccc;
}

.btn-secondary:hover {
    background: #555;
}

.btn-loading {
    display: inline-flex;
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid #000;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .modal-container {
        width: 95%;
        margin: 10px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .btn-primary,
    .btn-secondary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Variables globales
let precioUnitario = 0;
let unidadMedida = '';

// Función para actualizar precio y unidad
function actualizarPrecio() {
    const select = document.getElementById('id_producto');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        precioUnitario = parseFloat(selectedOption.dataset.precio) || 0;
        unidadMedida = selectedOption.dataset.unidad || '';
        
        document.getElementById('precio_unitario').value = '$' + precioUnitario.toFixed(2);
        document.getElementById('unidad_medida_display').textContent = unidadMedida;
        
        // Recalcular subtotal si hay cantidad
        calcularSubtotal();
    } else {
        document.getElementById('precio_unitario').value = '$0.00';
        document.getElementById('unidad_medida_display').textContent = '-';
        document.getElementById('subtotal_pedido').value = '$0.00';
    }
}

// Función para calcular subtotal
function calcularSubtotal() {
    const cantidad = parseFloat(document.getElementById('cantidad_solicitada').value) || 0;
    const subtotal = cantidad * precioUnitario;
    
    document.getElementById('subtotal_pedido').value = '$' + subtotal.toFixed(2);
}

// Función para cerrar modal
function cerrarModalPedido() {
    const modal = document.getElementById('modalPedidoProveedor');
    if (modal) {
        modal.remove();
    }
}

// Event listener para el formulario
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formPedidoProveedor');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('.btn-primary');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            // Mostrar estado de carga
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-flex';
            
            // Crear FormData
            const formData = new FormData(form);
            
            // Enviar pedido
            fetch('../API/procesar_pedido_b2b.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    cerrarModalPedido();
                    // Recargar página para mostrar actualizaciones
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error al procesar el pedido. Intenta nuevamente.');
            })
            .finally(() => {
                // Restaurar botón
                submitBtn.disabled = false;
                btnText.style.display = 'flex';
                btnLoading.style.display = 'none';
            });
        });
    }
    
    // Si hay un producto preseleccionado, actualizar precio
    const idProductoSelect = document.getElementById('id_producto');
    if (idProductoSelect.value) {
        actualizarPrecio();
    }
});
</script>

<?php
// Liberar memoria
mysqli_free_result($resultado_proveedor);
mysqli_free_result($resultado_productos);
mysqli_close($conn);
?>
