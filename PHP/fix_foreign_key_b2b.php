<?php
/**
 * FIX CRÍTICO: Corrección de Foreign Key en detalles_pedido_proveedor
 * Ejecuta el SQL necesario para que el flujo B2B funcione correctamente
 */

include 'db_config.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Corrección FK - Flujo B2B</h1>";
echo "<pre>";

if (!$conn) {
    die("❌ Error: No hay conexión a la base de datos\n");
}

echo "✅ Conexión establecida\n\n";

// Paso 1: Verificar el estado actual de la FK
$sql_check = "
    SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = 'detalles_pedido_proveedor' 
    AND CONSTRAINT_NAME = 'fk_detalle_pedido_prov'
    AND TABLE_SCHEMA = DATABASE()
";

$result = $conn->query($sql_check);
if ($result && $row = $result->fetch_assoc()) {
    echo "📋 FK actual encontrada:\n";
    echo "   - Nombre: " . $row['CONSTRAINT_NAME'] . "\n";
    echo "   - Referencia: " . $row['REFERENCED_TABLE_NAME'] . "\n\n";
    
    if ($row['REFERENCED_TABLE_NAME'] === 'pedidos_proveedores') {
        echo "⚠️  Detectada referencia INCORRECTA a 'pedidos_proveedores'\n";
        echo "🔄 Procediendo a corregir...\n\n";
        
        // Paso 2: Eliminar la FK incorrecta
        $sql_drop = "ALTER TABLE detalles_pedido_proveedor DROP FOREIGN KEY fk_detalle_pedido_prov";
        if ($conn->query($sql_drop)) {
            echo "✅ FK incorrecta eliminada\n";
        } else {
            echo "❌ Error al eliminar FK: " . $conn->error . "\n";
        }
        
        // Paso 3: Crear la FK correcta
        $sql_add = "ALTER TABLE detalles_pedido_proveedor 
                     ADD CONSTRAINT fk_detalle_pedido_prov 
                     FOREIGN KEY (id_pedido) REFERENCES pedidos_proveedor(id_pedido) 
                     ON DELETE CASCADE";
        
        if ($conn->query($sql_add)) {
            echo "✅ FK correcta creada (apunta a pedidos_proveedor)\n\n";
        } else {
            echo "❌ Error al crear FK: " . $conn->error . "\n\n";
        }
    } else {
        echo "✅ La FK ya está correctamente configurada\n\n";
    }
} else {
    echo "⚠️  No se encontró la FK. Verificando si existe la tabla...\n";
}

// Verificación final
$result = $conn->query($sql_check);
if ($result && $row = $result->fetch_assoc()) {
    echo "📋 ESTADO FINAL:\n";
    echo "   - FK: " . $row['CONSTRAINT_NAME'] . "\n";
    echo "   - Referencia: " . $row['REFERENCED_TABLE_NAME'] . "\n\n";
    
    if ($row['REFERENCED_TABLE_NAME'] === 'pedidos_proveedor') {
        echo "🎉 ¡CORRECCIÓN EXITOSA! El flujo B2B ahora debería funcionar.\n";
        echo "   Ya puedes probar enviar pedidos desde solicitar_pedido_proveedor.php\n\n";
        
        // Prueba de inserción
        echo "🧪 Realizando prueba de inserción de prueba...\n";
        $conn->begin_transaction();
        
        try {
            // Insertar pedido de prueba
            $stmt = $conn->prepare("INSERT INTO pedidos_proveedor 
                (id_proveedor, id_restaurante, id_usuario_solicitante, estado_pedido, 
                 subtotal_productos, costo_envio, total_pedido, metodo_pago, direccion_entrega) 
                VALUES (1, 1, 1, 'Pendiente', 100.00, 0.00, 100.00, 'Efectivo', 'Test')");
            $stmt->execute();
            $id_test = $conn->insert_id;
            
            // Insertar detalle (esto probará la FK)
            $stmt2 = $conn->prepare("INSERT INTO detalles_pedido_proveedor 
                (id_pedido, id_producto, cantidad_solicitada, precio_unitario_pedido, subtotal_detalle) 
                VALUES (?, 1, 1.00, 50.00, 50.00)");
            $stmt2->bind_param("i", $id_test);
            $stmt2->execute();
            
            $conn->rollback(); // No guardamos el test
            echo "✅ Prueba exitosa: La FK ahora permite insertar detalles\n";
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "❌ Prueba falló: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "⚠️  La FK sigue apuntando a la tabla incorrecta. Revisar manualmente.\n";
    }
}

echo "</pre>";
echo "<p><a href='../DIRECCIONES/solicitar_pedido_proveedor.php'>🚀 Ir a Solicitar Pedido B2B</a></p>";
echo "<p><a href='ver_logs.php'>📋 Ver Logs de Error</a></p>";
?>
