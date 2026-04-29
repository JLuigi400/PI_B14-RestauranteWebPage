<?php
// API para procesar pedidos B2B (Dueño -> Proveedor)
header('Content-Type: application/json');

session_start();
require_once '../PHP/conexion.php';

try {
    // Verificar que sea un dueño (Rol 2)
    if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
        throw new Exception('Acceso denegado. Se requiere rol de Dueño.');
    }

    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos del formulario
    $id_proveedor = (int)($_POST['id_proveedor'] ?? 0);
    $id_usuario = $_SESSION['id_usu'];
    $id_producto = (int)($_POST['id_producto'] ?? 0);
    $cantidad_solicitada = (float)($_POST['cantidad_solicitada'] ?? 0);
    $urgencia = $_POST['urgencia'] ?? 'Normal';
    $notas_pedido = trim($_POST['notas_pedido'] ?? '');

    // Validaciones básicas
    if ($id_proveedor === 0 || $id_producto === 0 || $cantidad_solicitada <= 0) {
        throw new Exception('Datos incompletos o inválidos');
    }

    // Validar urgencia
    $urgencias_validas = ['Normal', 'Urgente', 'Express'];
    if (!in_array($urgencia, $urgencias_validas)) {
        throw new Exception('Nivel de urgencia inválido');
    }

    // Iniciar transacción
    mysqli_begin_transaction($conn);

    // 1. Obtener información del producto y proveedor
    $query_producto = "SELECT pp.*, p.nombre_empresa, p.id_usu as id_proveedor_usuario 
                    FROM productos_proveedor pp 
                    JOIN proveedores p ON pp.id_proveedor = p.id_proveedor 
                    WHERE pp.id_producto = ? AND pp.id_proveedor = ? AND pp.disponibilidad = 1";
    $stmt_producto = mysqli_prepare($conn, $query_producto);
    mysqli_stmt_bind_param($stmt_producto, 'ii', $id_producto, $id_proveedor);
    mysqli_stmt_execute($stmt_producto);
    $resultado_producto = mysqli_stmt_get_result($stmt_producto);
    $producto = mysqli_fetch_assoc($resultado_producto);

    if (!$producto) {
        throw new Exception('Producto no encontrado o no disponible');
    }

    // 2. Obtener restaurante del dueño (para el pedido)
    $query_restaurante = "SELECT id_res, nombre_res FROM restaurante WHERE id_usu = ? AND estatus_res = 1 LIMIT 1";
    $stmt_restaurante = mysqli_prepare($conn, $query_restaurante);
    mysqli_stmt_bind_param($stmt_restaurante, 'i', $id_usuario);
    mysqli_stmt_execute($stmt_restaurante);
    $resultado_restaurante = mysqli_stmt_get_result($stmt_restaurante);
    $restaurante = mysqli_fetch_assoc($resultado_restaurante);

    if (!$restaurante) {
        throw new Exception('No se encontró restaurante activo para este usuario');
    }

    // 3. Generar número de pedido único
    $numero_pedido = 'REQ-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // 4. Calcular totales
    $precio_unitario = $producto['precio_unitario'];
    $subtotal_detalle = $cantidad_solicitada * $precio_unitario;
    
    // IVA (16%)
    $iva_pedido = $subtotal_detalle * 0.16;
    $total_pedido = $subtotal_detalle + $iva_pedido;

    // 5. Insertar pedido principal
    $query_pedido = "INSERT INTO pedidos_proveedores 
                    (id_proveedor, id_res, id_usu_solicitante, numero_pedido, fecha_pedido, 
                     urgencia, subtotal_pedido, iva_pedido, total_pedido, estatus_pedido, notas_pedido) 
                    VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, 'Pendiente', ?)";
    $stmt_pedido = mysqli_prepare($conn, $query_pedido);
    mysqli_stmt_bind_param($stmt_pedido, 'iiisssdds', 
        $id_proveedor, 
        $restaurante['id_res'], 
        $id_usuario, 
        $numero_pedido, 
        $urgencia, 
        $subtotal_detalle, 
        $iva_pedido, 
        $total_pedido, 
        $notas_pedido
    );
    
    if (!mysqli_stmt_execute($stmt_pedido)) {
        throw new Exception('Error al crear el pedido: ' . mysqli_stmt_error($stmt_pedido));
    }

    $id_pedido = mysqli_insert_id($conn);

    // 6. Insertar detalle del pedido
    $query_detalle = "INSERT INTO detalles_pedido_proveedor 
                     (id_pedido, id_producto, cantidad_solicitada, precio_unitario_pedido, subtotal_detalle) 
                     VALUES (?, ?, ?, ?, ?)";
    $stmt_detalle = mysqli_prepare($conn, $query_detalle);
    mysqli_stmt_bind_param($stmt_detalle, 'iiddd', 
        $id_pedido, 
        $id_producto, 
        $cantidad_solicitada, 
        $precio_unitario, 
        $subtotal_detalle
    );
    
    if (!mysqli_stmt_execute($stmt_detalle)) {
        throw new Exception('Error al insertar detalle del pedido: ' . mysqli_stmt_error($stmt_detalle));
    }

    // 7. (CRUCIAL) Crear notificación para el proveedor
    $mensaje_notificacion = "📦 Nuevo Pedido #{$numero_pedido}\n\n" .
                         "Restaurante: {$restaurante['nombre_res']}\n" .
                         "Producto: {$producto['nombre_producto']}\n" .
                         "Cantidad: {$cantidad_solicitada} {$producto['unidad_medida']}\n" .
                         "Urgencia: {$urgencia}\n" .
                         "Total: \${$total_pedido}\n\n" .
                         "Revisa tu panel para confirmar.";

    $query_notificacion = "INSERT INTO notificaciones 
                          (id_usu, tipo_notificacion, mensaje_notificacion, fecha_notificacion, leido) 
                          VALUES (?, 'pedido', ?, NOW(), 0)";
    $stmt_notificacion = mysqli_prepare($conn, $query_notificacion);
    mysqli_stmt_bind_param($stmt_notificacion, 'is', 
        $producto['id_proveedor_usuario'], 
        $mensaje_notificacion
    );
    
    if (!mysqli_stmt_execute($stmt_notificacion)) {
        throw new Exception('Error al crear notificación: ' . mysqli_stmt_error($stmt_notificacion));
    }

    // 8. Confirmar transacción
    mysqli_commit($conn);

    // 9. Logging para auditoría
    error_log("PEDIDO B2B CREADO: Pedido #{$numero_pedido}, Restaurante: {$restaurante['nombre_res']}, Proveedor: {$producto['nombre_empresa']}, Total: \${$total_pedido}");

    // 10. Preparar datos para EmailJS (opcional - se puede implementar después)
    $email_data = [
        'numero_pedido' => $numero_pedido,
        'nombre_restaurante' => $restaurante['nombre_res'],
        'nombre_proveedor' => $producto['nombre_empresa'],
        'nombre_producto' => $producto['nombre_producto'],
        'cantidad' => $cantidad_solicitada,
        'unidad' => $producto['unidad_medida'],
        'precio_unitario' => $precio_unitario,
        'subtotal' => $subtotal_detalle,
        'iva' => $iva_pedido,
        'total' => $total_pedido,
        'urgencia' => $urgencia,
        'notas' => $notas_pedido,
        'email_proveedor' => '', // Se puede obtener de la tabla usuario
        'email_admin' => '' // Se puede configurar
    ];

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => "Pedido #{$numero_pedido} creado correctamente. El proveedor ha sido notificado.",
        'data' => [
            'id_pedido' => $id_pedido,
            'numero_pedido' => $numero_pedido,
            'total_pedido' => $total_pedido,
            'email_data' => $email_data
        ]
    ]);

} catch (Exception $e) {
    // Revertir transacción si falla
    if (isset($conn) && mysqli_ping($conn)) {
        mysqli_rollback($conn);
    }
    
    error_log("ERROR EN PROCESAR_PEDIDO_B2B.PHP: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Cerrar conexión
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>
