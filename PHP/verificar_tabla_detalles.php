<?php
// Verificar estructura real de la tabla detalles_pedido_proveedor
include 'db_config.php';
header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['error' => 'No hay conexión']);
    exit();
}

$result = $conn->query("DESCRIBE detalles_pedido_proveedor");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

echo json_encode([
    'tabla' => 'detalles_pedido_proveedor',
    'columnas_existentes' => $columns,
    'falta_precio_unitario' => !in_array('precio_unitario', $columns),
    'sql_para_agregar' => "ALTER TABLE detalles_pedido_proveedor ADD COLUMN precio_unitario decimal(10,2) NOT NULL AFTER cantidad_solicitada;"
], JSON_PRETTY_PRINT);
?>
