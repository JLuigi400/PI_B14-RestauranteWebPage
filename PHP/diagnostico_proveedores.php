<?php
/**
 * =========================================================
 * DIAGNÓSTICO DE PROVEEDORES - VISUALIZADOR DE LOGS
 * =========================================================
 * Ejecuta el diagnóstico y muestra los resultados en pantalla
 * para identificar el problema exacto.
 */

session_start();
include 'db_config.php';

// Forzar que sea un dueño para pruebas (o quitar esto en producción)
if (!isset($_SESSION['id_usu'])) {
    echo "<h1>⚠️ SESIÓN NO INICIADA</h1>";
    echo "<p>Debes iniciar sesión como dueño (rol 2) para ver el diagnóstico.</p>";
    echo "<a href='../login.php'>Ir a Login</a>";
    exit();
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Proveedores - Salud Juárez</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        h1 { color: #fff; border-bottom: 2px solid #27ae60; padding-bottom: 10px; }
        h2 { color: #f39c12; margin-top: 30px; }
        .section { background: #2c3e50; padding: 15px; margin: 15px 0; border-radius: 8px; }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .warning { color: #f39c12; }
        .info { color: #3498db; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #34495e; }
        th { background: #34495e; color: white; }
        tr:nth-child(even) { background: #2c3e50; }
        tr:hover { background: #34495e; }
        .metric { display: inline-block; background: #34495e; padding: 15px; margin: 10px; border-radius: 8px; min-width: 200px; }
        .metric-value { font-size: 2em; font-weight: bold; }
        .metric-label { font-size: 0.9em; color: #95a5a6; }
    </style>
</head>
<body>
    <h1>🔍 DIAGNÓSTICO DE PROVEEDORES</h1>
    <p class='info'>Usuario actual: ID=" . $_SESSION['id_usu'] . ", Rol=" . $_SESSION['id_rol'] . "</p>
";

// ============================================================================
// MÉTRICAS CLAVE
// ============================================================================
echo "<h2>📊 MÉTRICAS CLAVE</h2>";

// Total proveedores
$sql_total = "SELECT COUNT(*) as total FROM proveedores";
$result_total = $conn->query($sql_total);
$total_proveedores = $result_total->fetch_assoc()['total'];

// Proveedores activos
$sql_activos = "SELECT COUNT(*) as total FROM proveedores WHERE estado_visibilidad = 'activo'";
$result_activos = $conn->query($sql_activos);
$proveedores_activos = $result_activos->fetch_assoc()['total'];

// Proveedores validados
$sql_validados = "SELECT COUNT(*) as total FROM proveedores WHERE validado_admin = 1";
$result_validados = $conn->query($sql_validados);
$proveedores_validados = $result_validados->fetch_assoc()['total'];

// Productos totales
$sql_prod_total = "SELECT COUNT(*) as total FROM productos_proveedor";
$result_prod_total = $conn->query($sql_prod_total);
$total_productos = $result_prod_total->fetch_assoc()['total'];

// Productos disponibles
$sql_prod_disp = "SELECT COUNT(*) as total FROM productos_proveedor WHERE disponibilidad = 1";
$result_prod_disp = $conn->query($sql_prod_disp);
$productos_disponibles = $result_prod_disp->fetch_assoc()['total'];

echo "
<div class='metric'>
    <div class='metric-value'>$total_proveedores</div>
    <div class='metric-label'>Total Proveedores</div>
</div>
<div class='metric'>
    <div class='metric-value'>$proveedores_activos</div>
    <div class='metric-label'>Proveedores Activos</div>
</div>
<div class='metric'>
    <div class='metric-value'>$proveedores_validados</div>
    <div class='metric-label'>Proveedores Validados</div>
</div>
<div class='metric'>
    <div class='metric-value'>$total_productos</div>
    <div class='metric-label'>Total Productos</div>
</div>
<div class='metric'>
    <div class='metric-value'>$productos_disponibles</div>
    <div class='metric-label'>Productos Disponibles</div>
</div>
";

// ============================================================================
// DETALLE DE TODOS LOS PROVEEDORES
// ============================================================================
echo "<h2>📋 DETALLE DE TODOS LOS PROVEEDORES</h2>";
echo "<table>";
echo "<tr>
    <th>ID</th>
    <th>Empresa</th>
    <th>Estado</th>
    <th>Validado</th>
    <th>Latitud</th>
    <th>Longitud</th>
    <th>Productos</th>
</tr>";

$sql_detalle = "SELECT 
    p.id_proveedor,
    p.nombre_empresa,
    p.estado_visibilidad,
    p.validado_admin,
    p.latitud_proveedor,
    p.longitud_proveedor,
    COUNT(pp.id_producto) as num_productos
FROM proveedores p
LEFT JOIN productos_proveedor pp ON p.id_proveedor = pp.id_proveedor AND pp.disponibilidad = 1
GROUP BY p.id_proveedor
ORDER BY p.id_proveedor";

$result_detalle = $conn->query($sql_detalle);

while ($row = $result_detalle->fetch_assoc()) {
    $estado_class = $row['estado_visibilidad'] == 'activo' ? 'success' : 'error';
    $validado_class = $row['validado_admin'] == 1 ? 'success' : 'warning';
    $tiene_coords = ($row['latitud_proveedor'] && $row['longitud_proveedor']) ? '✅' : '❌';
    
    echo "<tr>
        <td>{$row['id_proveedor']}</td>
        <td>{$row['nombre_empresa']}</td>
        <td class='$estado_class'>{$row['estado_visibilidad']}</td>
        <td class='$validado_class'>" . ($row['validado_admin'] ? 'SÍ' : 'NO') . "</td>
        <td>{$row['latitud_proveedor']}</td>
        <td>{$row['longitud_proveedor']}</td>
        <td class='info'>{$row['num_productos']}</td>
    </tr>";
}

echo "</table>";

// ============================================================================
// PROBLEMAS DETECTADOS
// ============================================================================
echo "<h2>⚠️ PROBLEMAS DETECTADOS</h2>";

$problemas = [];

// Problema 1: Proveedores sin coordenadas
$sql_no_coords = "SELECT id_proveedor, nombre_empresa FROM proveedores 
                  WHERE latitud_proveedor IS NULL OR longitud_proveedor IS NULL 
                  OR latitud_proveedor = 0 OR longitud_proveedor = 0";
$result_no_coords = $conn->query($sql_no_coords);
$count_no_coords = $result_no_coords->num_rows;

if ($count_no_coords > 0) {
    $problemas[] = [
        'titulo' => 'Proveedores SIN coordenadas GPS',
        'cantidad' => $count_no_coords,
        'detalle' => 'Estos proveedores no aparecerán en el mapa'
    ];
    echo "<div class='section warning'>
        <h3>🔴 PROBLEMA: $count_no_coords proveedores sin coordenadas GPS</h3>
        <p>Estos no aparecerán en el mapa:</p>
        <ul>";
    while ($row = $result_no_coords->fetch_assoc()) {
        echo "<li>ID {$row['id_proveedor']}: {$row['nombre_empresa']}</li>";
    }
    echo "</ul></div>";
}

// Problema 2: Proveedores inactivos
$sql_inactivos = "SELECT id_proveedor, nombre_empresa FROM proveedores 
                  WHERE estado_visibilidad != 'activo' OR estado_visibilidad IS NULL";
$result_inactivos = $conn->query($sql_inactivos);
$count_inactivos = $result_inactivos->num_rows;

if ($count_inactivos > 0) {
    $problemas[] = [
        'titulo' => 'Proveedores INACTIVOS',
        'cantidad' => $count_inactivos,
        'detalle' => 'No aparecerán en la lista de proveedores disponibles'
    ];
    echo "<div class='section error'>
        <h3>🔴 PROBLEMA: $count_inactivos proveedores inactivos</h3>
        <ul>";
    while ($row = $result_inactivos->fetch_assoc()) {
        echo "<li>ID {$row['id_proveedor']}: {$row['nombre_empresa']}</li>";
    }
    echo "</ul></div>";
}

// Problema 3: Proveedores no validados
$sql_no_validados = "SELECT id_proveedor, nombre_empresa FROM proveedores WHERE validado_admin != 1";
$result_no_validados = $conn->query($sql_no_validados);
$count_no_validados = $result_no_validados->num_rows;

if ($count_no_validados > 0) {
    $problemas[] = [
        'titulo' => 'Proveedores NO validados',
        'cantidad' => $count_no_validados,
        'detalle' => 'Pendientes de validación por admin'
    ];
    echo "<div class='section warning'>
        <h3>⚠️ ADVERTENCIA: $count_no_validados proveedores no validados</h3>
        <ul>";
    while ($row = $result_no_validados->fetch_assoc()) {
        echo "<li>ID {$row['id_proveedor']}: {$row['nombre_empresa']}</li>";
    }
    echo "</ul></div>";
}

// Problema 4: Proveedores sin productos
$sql_sin_productos = "SELECT p.id_proveedor, p.nombre_empresa 
                      FROM proveedores p
                      LEFT JOIN productos_proveedor pp ON p.id_proveedor = pp.id_proveedor
                      WHERE pp.id_producto IS NULL";
$result_sin_productos = $conn->query($sql_sin_productos);
$count_sin_productos = $result_sin_productos->num_rows;

if ($count_sin_productos > 0) {
    $problemas[] = [
        'titulo' => 'Proveedores SIN productos',
        'cantidad' => $count_sin_productos,
        'detalle' => 'No aparecerán en el formulario de pedidos B2B'
    ];
    echo "<div class='section error'>
        <h3>🔴 PROBLEMA CRÍTICO: $count_sin_productos proveedores sin productos</h3>
        <p>Estos no aparecerán en el formulario de pedidos B2B:</p>
        <ul>";
    while ($row = $result_sin_productos->fetch_assoc()) {
        echo "<li>ID {$row['id_proveedor']}: {$row['nombre_empresa']}</li>";
    }
    echo "</ul></div>";
}

// ============================================================================
// RESUMEN DE RESULTADO ESPERADO
// ============================================================================
echo "<h2>📈 RESUMEN - ¿QUÉ DEBERÍA VER EL USUARIO?</h2>";

$sql_esperado = "SELECT COUNT(DISTINCT p.id_proveedor) as total
                 FROM proveedores p
                 INNER JOIN productos_proveedor pp ON p.id_proveedor = pp.id_proveedor
                 WHERE p.estado_visibilidad = 'activo'
                 AND pp.disponibilidad = 1";
$result_esperado = $conn->query($sql_esperado);
$total_esperado = $result_esperado->fetch_assoc()['total'];

echo "
<div class='section'>
    <h3 class='success'>✅ Proveedores que DEBERÍAN aparecer en el dropdown:</h3>
    <div class='metric'>
        <div class='metric-value success'>$total_esperado</div>
        <div class='metric-label'>Proveedores con productos disponibles</div>
    </div>
    <p class='info'>Estos son los proveedores que cumplen TODOS los requisitos:</p>
    <ul>
        <li>✅ estado_visibilidad = 'activo'</li>
        <li>✅ Tienen al menos 1 producto con disponibilidad = 1</li>
    </ul>
</div>
";

if (count($problemas) === 0) {
    echo "<div class='section success'>
        <h2>✅ TODO CORRECTO</h2>
        <p>No se detectaron problemas. Los proveedores deberían cargar correctamente.</p>
    </div>";
} else {
    echo "<div class='section error'>
        <h2>⚠️ SE ENCONTRARON " . count($problemas) . " PROBLEMAS</h2>
        <p>Arregla estos problemas para que los proveedores aparezcan correctamente.</p>
    </div>";
}

// ============================================================================
// ACCIONES SUGERIDAS
// ============================================================================
echo "
<h2>🔧 ACCIONES SUGERIDAS</h2>
<div class='section'>
    <ol>
        <li><strong>Si faltan productos:</strong> Ejecuta <a href='seeder_proxys_proveedores.php'>seeder_proxys_proveedores.php</a></li>
        <li><strong>Si faltan coordenadas:</strong> Actualiza latitud/longitud en la tabla proveedores</li>
        <li><strong>Si están inactivos:</strong> Cambia estado_visibilidad a 'activo'</li>
        <li><strong>Si no están validados:</strong> Cambia validado_admin a 1</li>
    </ol>
</div>
";

echo "
<hr>
<p style='text-align: center; color: #95a5a6;'>
    Generado: " . date('Y-m-d H:i:s') . " | Salud Juárez - Diagnóstico de Sistema
</p>
</body>
</html>";

$conn->close();
?>
