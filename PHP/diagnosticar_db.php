<?php
/**
 * Diagnóstico Completo de Base de Datos - Salud Juárez
 * Versión: 2.1.0
 * Fecha: 19 de Abril de 2026
 */

require_once 'db_config.php';

header('Content-Type: text/html; charset=utf-8');

$esAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Función para verificar si una tabla existe
function tablaExiste($conn, $nombreTabla) {
    try {
        $result = $conn->query("SHOW TABLES LIKE '$nombreTabla'");
        return $result && $result->num_rows > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Función para verificar si una columna existe
function columnaExiste($conn, $tabla, $columna) {
    try {
        $result = $conn->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
        return $result && $result->num_rows > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Función para contar registros
function contarRegistros($conn, $tabla) {
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM `$tabla`");
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }
        return 0;
    } catch (Exception $e) {
        return 0;
    }
}

// Tablas requeridas con columnas clave
$tablasRequeridas = [
    'usuarios' => ['id_usu', 'username_usu', 'correo_usu', 'id_rol', 'estatus_usu'],
    'roles' => ['id_rol', 'nombre_rol'],
    'restaurante' => ['id_res', 'id_usu', 'nombre_res', 'direccion_res', 'estatus_res'],
    'platillos' => ['id_pla', 'id_res', 'nombre_pla', 'precio_pla', 'visible'],
    'categorias' => ['id_cat', 'nombre_cat'],
    'inventario' => ['id_inv', 'id_res', 'nombre_insumo', 'stock_inv'],
    'favoritos' => ['id_favorito', 'id_usu', 'id_res'],
    'perfiles' => ['id_per', 'id_usu', 'nombre_per', 'apellidos_per'],
    'proveedores_insumos' => ['id_proveedor', 'nombre_tienda', 'categoria_insumo'],
    'platillo_ingredientes' => ['id_pla', 'id_inv', 'cantidad_usada'],
    'notificaciones' => ['id_notificacion', 'id_usu'],
    'pedidos_restock' => ['id_pedido'],
    'pedido_detalle' => ['id_detalle'],
    'solicitudes_restock' => ['id_solicitud'],
    'pedidos_clientes' => ['id_pedido', 'id_res', 'id_usu'],
    'validacion_log' => ['id_log']
];

// Ejecutar diagnóstico
$resultados = [];
$totalTablas = count($tablasRequeridas);
$tablasOk = 0;
$tablasError = 0;
$totalRegistros = 0;

foreach ($tablasRequeridas as $tabla => $columnas) {
    $existe = tablaExiste($conn, $tabla);
    $registros = $existe ? contarRegistros($conn, $tabla) : 0;
    $columnasOk = [];
    $columnasFaltantes = [];

    if ($existe) {
        foreach ($columnas as $columna) {
            if (columnaExiste($conn, $tabla, $columna)) {
                $columnasOk[] = $columna;
            } else {
                $columnasFaltantes[] = $columna;
            }
        }
    }

    $estado = $existe && empty($columnasFaltantes) ? 'ok' : ($existe ? 'warning' : 'error');
    if ($estado === 'ok') $tablasOk++;
    else $tablasError++;
    $totalRegistros += $registros;

    $resultados[] = [
        'tabla' => $tabla,
        'existe' => $existe,
        'registros' => $registros,
        'columnas_ok' => $columnasOk,
        'columnas_faltantes' => $columnasFaltantes,
        'estado' => $estado
    ];
}

// Si es AJAX, devolver JSON
if ($esAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'diagnostico' => [
            'tablas' => $resultados,
            'total_tablas' => $totalTablas,
            'tablas_ok' => $tablasOk,
            'tablas_error' => $tablasError,
            'total_registros' => $totalRegistros
        ]
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Base de Datos - Salud Juárez</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .summary { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; border: 1px solid #e9ecef; }
        .stat-number { font-size: 2.2em; font-weight: bold; }
        .stat-label { color: #666; margin-top: 5px; font-size: 0.9em; }
        .status-ok { color: #27ae60; }
        .status-warning { color: #f39c12; }
        .status-error { color: #e74c3c; }
        .table-item { background: white; padding: 15px 20px; margin-bottom: 8px; border-radius: 8px; border-left: 5px solid #ddd; box-shadow: 0 1px 4px rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; }
        .table-ok { border-left-color: #27ae60; }
        .table-warning { border-left-color: #f39c12; }
        .table-error { border-left-color: #e74c3c; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.8em; font-weight: 600; }
        .badge-ok { background: #d4edda; color: #155724; }
        .badge-error { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .progress-bar { width: 100%; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; margin-top: 10px; }
        .progress-fill { height: 100%; border-radius: 4px; background: #27ae60; transition: width 0.5s ease; }
        .btn { display: inline-block; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; color: white; font-weight: 600; margin: 5px; transition: all 0.2s; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .btn-success { background: #27ae60; }
        .btn-info { background: #3498db; }
        .btn-primary { background: #9b59b6; }
        .code { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #27ae60; font-family: monospace; margin: 10px 0; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Diagnóstico de Base de Datos</h1>
            <p>Sistema Salud Juárez - Verificación completa del estado de la base de datos</p>
            <small>Generado: <?php echo date('Y-m-d H:i:s'); ?></small>
        </div>

        <!-- Resumen General -->
        <div class="summary">
            <h2>📊 Resumen General</h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalTablas; ?></div>
                    <div class="stat-label">Tablas Requeridas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number status-ok"><?php echo $tablasOk; ?></div>
                    <div class="stat-label">✅ Tablas OK</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number status-error"><?php echo $tablasError; ?></div>
                    <div class="stat-label">❌ Con Problemas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalRegistros; ?></div>
                    <div class="stat-label">Total Registros</div>
                </div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $totalTablas > 0 ? round(($tablasOk / $totalTablas) * 100) : 0; ?>%"></div>
            </div>
            <small style="color: #888;"><?php echo $totalTablas > 0 ? round(($tablasOk / $totalTablas) * 100, 1) : 0; ?>% de tablas funcionales</small>
        </div>

        <!-- Estado de Tablas -->
        <div class="summary">
            <h2>📋 Estado de Tablas</h2>
            <?php foreach ($resultados as $tabla): ?>
                <div class="table-item table-<?php echo $tabla['estado']; ?>">
                    <div style="flex:1;">
                        <strong><?php echo htmlspecialchars($tabla['tabla']); ?></strong>
                        <?php if ($tabla['existe']): ?>
                            <?php if ($tabla['estado'] === 'ok'): ?>
                                <span class="badge badge-ok">OK</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Columnas faltantes</span>
                            <?php endif; ?>
                            <span class="badge badge-info"><?php echo $tabla['registros']; ?> registros</span>
                        <?php else: ?>
                            <span class="badge badge-error">NO EXISTE</span>
                        <?php endif; ?>
                        <br>
                        <?php if ($tabla['existe'] && !empty($tabla['columnas_ok'])): ?>
                            <small style="color:#27ae60;">Columnas OK: <?php echo implode(', ', $tabla['columnas_ok']); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($tabla['columnas_faltantes'])): ?>
                            <br><small style="color:#e74c3c;">Columnas faltantes: <?php echo implode(', ', $tabla['columnas_faltantes']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($tabla['estado'] === 'ok'): ?>
                            <span class="status-ok">✅</span>
                        <?php elseif ($tabla['estado'] === 'warning'): ?>
                            <span class="status-warning">⚠️</span>
                        <?php else: ?>
                            <span class="status-error">❌</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Acciones Recomendadas -->
        <div class="summary">
            <h2>🚀 Acciones Recomendadas</h2>
            <?php if ($tablasError > 0): ?>
                <div class="code">
                    <strong>⚠️ Tablas faltantes o con problemas:</strong><br>
                    1. Ejecuta el script: <code>SQL/alter_tablas_simple.sql</code><br>
                    2. Verifica los permisos del usuario de la base de datos<br>
                    3. Recarga esta página para verificar los cambios
                </div>
            <?php endif; ?>

            <?php if ($totalRegistros === 0): ?>
                <div class="code">
                    <strong>⚠️ Base de datos vacía:</strong><br>
                    1. Importa el archivo: <code>SQL/restaurantes-marzo_25.sql</code> (solo INSERTs)<br>
                    2. O registra datos manualmente desde el panel de administración
                </div>
            <?php endif; ?>

            <?php if ($tablasOk === $totalTablas && $totalRegistros > 0): ?>
                <p><strong class="status-ok">🎉 ¡Excelente! El sistema está completamente funcional.</strong></p>
            <?php endif; ?>
        </div>

        <!-- Herramientas -->
        <div class="summary">
            <h2>🔧 Herramientas Adicionales</h2>
            <a href="diagnosticar_db.php?ajax=1" class="btn btn-info">📄 Ver JSON</a>
            <a href="diagnosticar_includes.php" class="btn btn-success">📋 Diagnosticar Includes</a>
            <a href="diagnosticar.php" class="btn btn-primary">🔍 Diagnóstico General</a>
            <a href="../DIRECCIONES/admin_usuarios.php" class="btn btn-success">🛡️ Panel Admin</a>
            <button class="btn btn-success" onclick="location.reload()">🔄 Actualizar</button>
        </div>

        <hr style="margin:30px 0; border:none; border-top:1px solid #ddd;">
        <p style="text-align:center; color:#888;"><small><em>Diagnóstico generado automáticamente el <?php echo date('Y-m-d H:i:s'); ?></em></small></p>
    </div>
</body>
</html>
