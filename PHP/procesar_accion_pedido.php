<?php
session_start();
include 'db_config.php';

// Verificar que el usuario sea proveedor (rol 4)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 4) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

try {
    // Obtener datos del POST JSON
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id_pedido = intval($data['id_pedido'] ?? 0);
    $accion = $data['accion'] ?? '';
    
    // Obtener id_proveedor desde la tabla proveedores usando id_usu de sesión
    $id_usu = $_SESSION['id_usu'];
    $sql_proveedor = "SELECT id_proveedor, nombre_empresa FROM proveedores WHERE id_usu = ?";
    $stmt_proveedor = $conn->prepare($sql_proveedor);
    $stmt_proveedor->bind_param("i", $id_usu);
    $stmt_proveedor->execute();
    $result_proveedor = $stmt_proveedor->get_result();
    
    if ($result_proveedor->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Proveedor no encontrado']);
        exit();
    }
    
    $proveedor_data = $result_proveedor->fetch_assoc();
    $id_proveedor = $proveedor_data['id_proveedor'];
    $nombre_proveedor = $proveedor_data['nombre_empresa'];

    // Validaciones
    if ($id_pedido <= 0 || empty($accion)) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit();
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Obtener información del pedido para EmailJS y validación
        $sql_pedido = "SELECT p.*, r.nombre_restaurante, u.nombre_completo as nombre_usuario
                      FROM pedidos_proveedor p
                      LEFT JOIN restaurantes r ON p.id_restaurante = r.id_restaurante
                      LEFT JOIN usuarios u ON p.id_usuario_solicitante = u.id_usu
                      WHERE p.id_pedido = ? AND p.id_proveedor = ?";
        $stmt_pedido = $conn->prepare($sql_pedido);
        $stmt_pedido->bind_param("ii", $id_pedido, $id_proveedor);
        $stmt_pedido->execute();
        $result_pedido = $stmt_pedido->get_result();

        if ($result_pedido->num_rows === 0) {
            throw new Exception('Pedido no encontrado o no pertenece al proveedor');
        }

        $pedido_info = $result_pedido->fetch_assoc();

        // Procesar acción
        $fecha_actual = date('Y-m-d H:i:s');
        $nuevo_estado = '';
        $mensaje_accion = '';
        $urgencia = 'Normal';

        switch ($accion) {
            case 'confirmar':
                $nuevo_estado = 'Confirmado';
                $sql_update = "UPDATE pedidos_proveedor SET estado_pedido = ?, fecha_confirmacion = ? WHERE id_pedido = ? AND id_proveedor = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ssii", $nuevo_estado, $fecha_actual, $id_pedido, $id_proveedor);
                $mensaje_accion = 'Pedido confirmado correctamente';
                $urgencia = 'Media';
                break;

            case 'enviar':
                $nuevo_estado = 'Enviado';
                $sql_update = "UPDATE pedidos_proveedor SET estado_pedido = ?, fecha_envio = ? WHERE id_pedido = ? AND id_proveedor = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ssii", $nuevo_estado, $fecha_actual, $id_pedido, $id_proveedor);
                $mensaje_accion = 'Pedido marcado como enviado';
                $urgencia = 'Alta';
                break;

            case 'entregar':
                $nuevo_estado = 'Entregado';
                $sql_update = "UPDATE pedidos_proveedor SET estado_pedido = ?, fecha_entrega = ? WHERE id_pedido = ? AND id_proveedor = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ssii", $nuevo_estado, $fecha_actual, $id_pedido, $id_proveedor);
                $mensaje_accion = 'Pedido marcado como entregado';
                $urgencia = 'Baja';

                // LÓGICA DE INVENTARIO: Sumar stock al entregar
                $sql_detalles = "SELECT dp.id_producto, dp.cantidad_solicitada 
                                 FROM detalles_pedido_proveedor dp
                                 WHERE dp.id_pedido = ?";
                $stmt_detalles = $conn->prepare($sql_detalles);
                $stmt_detalles->bind_param("i", $id_pedido);
                $stmt_detalles->execute();
                $result_detalles = $stmt_detalles->get_result();

                while ($detalle = $result_detalles->fetch_assoc()) {
                    $id_producto = $detalle['id_producto'];
                    $cantidad = $detalle['cantidad_solicitada'];

                    // Verificar si existe el producto en inventario_proveedor
                    $sql_check_inventario = "SELECT id_inventario, stock_actual FROM inventario_proveedor WHERE id_producto = ? AND id_proveedor = ?";
                    $stmt_check = $conn->prepare($sql_check_inventario);
                    $stmt_check->bind_param("ii", $id_producto, $id_proveedor);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();

                    if ($result_check->num_rows > 0) {
                        // Actualizar stock existente
                        $inventario = $result_check->fetch_assoc();
                        $nuevo_stock = $inventario['stock_actual'] + $cantidad;
                        
                        $sql_update_inventario = "UPDATE inventario_proveedor SET stock_actual = ?, ultima_actualizacion = NOW() WHERE id_inventario = ?";
                        $stmt_update_inventario = $conn->prepare($sql_update_inventario);
                        $stmt_update_inventario->bind_param("di", $nuevo_stock, $inventario['id_inventario']);
                        $stmt_update_inventario->execute();
                    } else {
                        // Insertar nuevo registro en inventario
                        $sql_insert_inventario = "INSERT INTO inventario_proveedor (id_producto, id_proveedor, stock_actual, stock_minimo, ultima_actualizacion) VALUES (?, ?, ?, 0, NOW())";
                        $stmt_insert_inventario = $conn->prepare($sql_insert_inventario);
                        $stmt_insert_inventario->bind_param("iid", $id_producto, $id_proveedor, $cantidad);
                        $stmt_insert_inventario->execute();
                    }

                    // Insertar movimiento de inventario
                    $sql_movimiento = "INSERT INTO movimientos_inventario (id_producto, id_proveedor, tipo_movimiento, cantidad, motivo, fecha_movimiento, id_usuario) VALUES (?, ?, 'entrada', ?, 'Entrada por entrega de pedido #$id_pedido', NOW(), ?)";
                    $stmt_movimiento = $conn->prepare($sql_movimiento);
                    $stmt_movimiento->bind_param("iiii", $id_producto, $id_proveedor, $cantidad, $id_usu);
                    $stmt_movimiento->execute();
                }
                break;

            case 'cancelar':
                $nuevo_estado = 'Cancelado';
                $sql_update = "UPDATE pedidos_proveedor SET estado_pedido = ?, fecha_cancelacion = ? WHERE id_pedido = ? AND id_proveedor = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ssii", $nuevo_estado, $fecha_actual, $id_pedido, $id_proveedor);
                $mensaje_accion = 'Pedido cancelado';
                $urgencia = 'Baja';
                break;

            default:
                throw new Exception('Acción no válida');
        }

        if (!$stmt_update->execute()) {
            throw new Exception('Error al actualizar el estado del pedido');
        }

        // Confirmar transacción
        $conn->commit();

        // ENVIAR EMAIL CON EMAILJS
        $email_data = [
            'numero_pedido' => $id_pedido,
            'nombre_proveedor' => $nombre_proveedor,
            'nombre_restaurante' => $pedido_info['nombre_restaurante'],
            'fecha_solicitud' => date('d/m/Y H:i', strtotime($pedido_info['fecha_solicitud'])),
            'urgencia' => $urgencia,
            'total_pedido' => '$' . number_format($pedido_info['total_pedido'], 2)
        ];

        // Preparar respuesta con EmailJS
        $response = [
            'success' => true,
            'message' => $mensaje_accion,
            'emailjs_data' => $email_data,
            'emailjs_config' => [
                'service_id' => 'service_t8yl29t',
                'template_id' => 'template_tnrferf',
                'public_key' => 'bJjfLm9SYVJvjQSNk'
            ]
        ];

        echo json_encode($response);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error en procesar_accion_pedido.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
