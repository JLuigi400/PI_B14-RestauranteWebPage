<?php
// DIAGNÓSTICO DIRECTO A ARCHIVO
$log_file = __DIR__ . '/diagnostico_errores.log';
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] === INICIO TEST ===\n", FILE_APPEND);

try {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Antes de session_start...\n", FILE_APPEND);
    session_start();
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] session_start OK\n", FILE_APPEND);
    
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Antes de include db_config...\n", FILE_APPEND);
    include 'db_config.php';
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] db_config OK, conn=" . ($conn ? "SI" : "NO") . "\n", FILE_APPEND);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Diagnóstico OK', 'conn' => $conn ? 'conectado' : 'null']);
    
} catch (Exception $e) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] === FIN TEST ===\n\n", FILE_APPEND);
?>
