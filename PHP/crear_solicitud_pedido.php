<?php
// DEBUG: Capturar todos los errores y convertirlos a JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // NO mostrar errores en output, solo log

session_start();
include 'db_config.php';

header('Content-Type: application/json');

// Verificar que el usuario sea dueño (rol 2)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

try {
    error_log("[DEBUG crear_solicitud_pedido.php] === INICIO ===");
    error_log("[DEBUG] Usuario: " . $_SESSION['id_usu'] . ", Rol: " . $_SESSION['id_rol']);
    // Obtener datos del POST JSON
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id_proveedor = intval($data['id_proveedor'] ?? 0);
    $productos = $data['productos'] ?? [];
    $direccion_entrega = trim($data['direccion_entrega'] ?? '');
    $metodo_pago = trim($data['metodo_pago'] ?? '');
    $notas_pedido = trim($data['notas_pedido'] ?? '');
    $id_usuario_solicitante = $_SESSION['id_usu'];

    // Validaciones
    error_log("[DEBUG] Datos recibidos: id_proveedor=$id_proveedor, productos=" . count($productos) . ", direccion=$direccion_entrega, metodo=$metodo_pago");
    
    if ($id_proveedor <= 0 || empty($productos) || empty($direccion_entrega) || empty($metodo_pago)) {
        error_log("[DEBUG] ERROR: Validación fallida");
        echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados']);
        exit();
    }

    // Calcular totales
    $subtotal_productos = 0;
    foreach ($productos as $producto) {
        $subtotal_productos += $producto['subtotal'];
    }
    $costo_envio = 0; // Podría calcularse según distancia
    $total_pedido = $subtotal_productos + $costo_envio;

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        // Crear el pedido
        // FIX: Todos los valores deben ser placeholders ? para bind_param
        $sql_pedido = "INSERT INTO pedidos_proveedor (
            id_proveedor, 
            id_restaurante, 
            id_usuario_solicitante, 
            estado_pedido, 
            subtotal_productos, 
            costo_envio, 
            total_pedido, 
            metodo_pago, 
            direccion_entrega, 
            notas_pedido
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_pedido = $conn->prepare($sql_pedido);
        
        if (!$stmt_pedido) {
            error_log("[DEBUG] ERROR PREPARE pedido: " . $conn->error);
            throw new Exception('Error preparando pedido: ' . $conn->error);
        }
        
        // FIX: Todas las variables deben ser referencias, no literales
        $id_restaurante_placeholder = 1; // placeholder temporal
        $estado_pedido = 'Pendiente';
        
        // FIX: 10 tipos para 10 variables: iiisdddsss
        // i=int(id_proveedor), i=int(id_restaurante), i=int(id_usuario), s=str(estado), 
        // d=dbl(subtotal), d=dbl(envio), d=dbl(total), s=str(metodo), s=str(direccion), s=str(notas)
        $stmt_pedido->bind_param("iiisdddsss", 
            $id_proveedor, 
            $id_restaurante_placeholder,
            $id_usuario_solicitante, 
            $estado_pedido,
            $subtotal_productos, 
            $costo_envio, 
            $total_pedido, 
            $metodo_pago, 
            $direccion_entrega, 
            $notas_pedido
        );

        if (!$stmt_pedido->execute()) {
            error_log("[DEBUG] ERROR EXECUTE pedido: " . $stmt_pedido->error);
            throw new Exception('Error al crear el pedido: ' . $stmt_pedido->error);
        }

        $id_pedido = $conn->insert_id;
        error_log("[DEBUG] Pedido creado exitosamente, ID: " . $id_pedido);

        // Crear detalles del pedido
        // CORREGIDO: columna es 'precio_unitario_pedido' no 'precio_unitario'
        $sql_detalle = "INSERT INTO detalles_pedido_proveedor (
            id_pedido, 
            id_producto, 
            cantidad_solicitada, 
            precio_unitario_pedido, 
            subtotal_detalle
        ) VALUES (?, ?, ?, ?, ?)";

        $stmt_detalle = $conn->prepare($sql_detalle);
        
        if (!$stmt_detalle) {
            error_log("[DEBUG] ERROR PREPARE detalle: " . $conn->error);
            throw new Exception('Error preparando detalle: ' . $conn->error);
        }

        error_log("[DEBUG] Insertando " . count($productos) . " detalles...");
        
        foreach ($productos as $index => $producto) {
            // FIX: Extraer valores a variables para poder pasarlas por referencia
            $id_prod = $producto['id_producto'];
            $cantidad_prod = $producto['cantidad'];
            $precio_prod = $producto['precio_unitario'];
            $subtotal_prod = $producto['subtotal'];
            
            $stmt_detalle->bind_param("iiddd", 
                $id_pedido, 
                $id_prod, 
                $cantidad_prod, 
                $precio_prod, 
                $subtotal_prod
            );

            if (!$stmt_detalle->execute()) {
                error_log("[DEBUG] ERROR EXECUTE detalle #$index: " . $stmt_detalle->error);
                throw new Exception('Error al crear detalle del pedido: ' . $stmt_detalle->error);
            }
        }
        
        error_log("[DEBUG] Detalles insertados correctamente");

        // Confirmar transacción
        $conn->commit();

        // ENVIAR EMAIL CON EMAILJS AL PROVEEDOR
        // OBTENER INFORMACIÓN PARA EMAILJS
        // CORREGIDO: No usar u.nombre_completo (no existe), usar datos de proveedor y restaurante
        $sql_info = "SELECT 
                        p.nombre_empresa, 
                        p.email_proveedor,
                        r.nombre_res as nombre_restaurante
                     FROM proveedores p
                     LEFT JOIN restaurante r ON r.id_usu = ?
                     WHERE p.id_proveedor = ?
                     LIMIT 1";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("ii", $id_usuario_solicitante, $id_proveedor);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        $info = $result_info->fetch_assoc();
        
        // Calcular impuestos (16% IVA)
        $tasa_iva = 0.16;
        $impuestos = $subtotal_productos * $tasa_iva;
        $total_con_iva = $subtotal_productos + $impuestos;
        
        // Formatear lista de productos para el email
        $lista_productos = "";
        foreach ($productos as $prod) {
            $lista_productos .= "• " . $prod['nombre_producto'] . ": " . $prod['cantidad'] . " x $" . number_format($prod['precio_unitario'], 2) . " = $" . number_format($prod['subtotal'], 2) . "\n";
        }

        // Preparar datos_email para EmailJS - Template: Alerta de Solicitud de Re-stock B2B
        // Variables mapeadas: {{nombre_empresa_proveedor}}, {{nombre_restaurante}}, {{direccion_entrega}}, etc.
        $datos_email = [
            'nombre_empresa_proveedor' => $info['nombre_empresa'] ?? 'Proveedor',
            'nombre_restaurante' => $info['nombre_restaurante'] ?? 'Restaurante',
            'direccion_entrega' => $direccion_entrega,
            'metodo_pago' => $metodo_pago,
            'notas_pedido' => $notas_pedido ?: 'Sin notas adicionales',
            'lista_productos' => $lista_productos,
            'cost_shipping' => number_format($costo_envio, 2),  // {{cost.shipping}}
            'cost_tax' => number_format($impuestos, 2),          // {{cost.tax}}
            'cost_total' => number_format($total_pedido, 2),     // {{cost.total}}
            'subtotal' => number_format($subtotal_productos, 2),
            'id_pedido' => $id_pedido,
            'numero_pedido' => 'REQ-' . date('Y') . '-' . str_pad($id_pedido, 4, '0', STR_PAD_LEFT)
        ];

        // Preparar respuesta exitosa con datos para EmailJS
        $response = [
            'success' => true,
            'message' => 'Pedido creado correctamente',
            'id_pedido' => $id_pedido,
            'datos_email' => $datos_email  // Datos listos para EmailJS
        ];

        error_log("[DEBUG] === FIN === Respuesta enviada con éxito");
        echo json_encode($response);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("[DEBUG] EXCEPCIÓN CAPTURADA: " . $e->getMessage());
    error_log("[DEBUG] Stack trace: " . $e->getTraceAsString());
    // Asegurar que siempre se devuelva JSON válido
    ob_clean(); // Limpiar cualquier output anterior
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
