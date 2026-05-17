<?php
// Verificar estructura real de la tabla proveedores
include 'db_config.php';
header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['error' => 'No hay conexión']);
    exit();
}

$result = $conn->query("DESCRIBE proveedores");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = ['Field' => $row['Field'], 'Type' => $row['Type']];
}

// También obtener datos de un proveedor de ejemplo
$result2 = $conn->query("SELECT * FROM proveedores LIMIT 1");
$ejemplo = $result2 ? $result2->fetch_assoc() : null;

echo json_encode([
    'tabla' => 'proveedores',
    'columnas_existentes' => $columns,
    'ejemplo_proveedor' => $ejemplo
], JSON_PRETTY_PRINT);
?>
