<?php
// Verificar estructura real de la tabla usuarios
include 'db_config.php';
header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['error' => 'No hay conexión']);
    exit();
}

$result = $conn->query("DESCRIBE usuarios");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = ['Field' => $row['Field'], 'Type' => $row['Type']];
}

echo json_encode([
    'tabla' => 'usuarios',
    'columnas_existentes' => $columns
], JSON_PRETTY_PRINT);
?>
