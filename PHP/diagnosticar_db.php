<?php
/**
 * Diagnóstico de Base de Datos - Salud Juárez
 * Compatible con Infinity Free Hosting
 * Versión: 1.0.0
 * Fecha: 25 de Marzo de 2026
 * Descripción: Verifica el estado de la base de datos sin usar information_schema
 */

// Incluir configuración de base de datos
require_once 'db_config.php';

// Configurar headers
header('Content-Type: text/html; charset=utf-8');

// Función para verificar si una tabla existe
function tablaExiste($conn, $nombreTabla) {
    try {
        $result = $conn->query("SHOW TABLES LIKE '$nombreTabla'");
        return $result->num_rows > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Función para verificar si una columna existe en una tabla
function columnaExiste($conn, $tabla, $columna) {
    try {
        $result = $conn->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
        return $result->num_rows > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Función para obtener conteo de registros
function contarRegistros($conn, $tabla) {
    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM `$tabla`");
        $row = $result->fetch_assoc();
        return $row['total'];
    } catch (Exception $e) {
        return 0;
    }
}

// Función para obtener estructura de tabla
function obtenerEstructuraTabla($conn, $tabla) {
    try {
        $result = $conn->query("DESCRIBE `$tabla`");
        $estructura = [];
        while ($row = $result->fetch_assoc()) {
            $estructura[] = $row;
        }
        return $estructura;
    } catch (Exception $e) {
        return [];
    }
}

// Iniciar diagnóstico
echo '<!DOCTYPE html>';
echo '<html lang="es">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>🔍 Diagnóstico de Base de Datos - Salud Juárez</title>';
echo '<style>';
echo 'body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }';
echo '.container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }';
echo 'h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }';
echo 'h2 { color: #3498db; margin-top: 30px; }';
echo '.status-ok { color: #27ae60; font-weight: bold; }';
echo '.status-warning { color: #f39c12; font-weight: bold; }';
echo '.status-error { color: #e74c3c; font-weight: bold; }';
echo '.table-item { padding: 15px; margin: 10px 0; border-left: 4px solid #ddd; background: #f9f9f9; border-radius: 5px; }';
echo '.table-ok { border-left-color: #27ae60; }';
echo '.table-warning { border-left-color: #f39c12; }';
echo '.table-error { border-left-color: #e74c3c; }';
echo '.column-item { padding: 8px; margin: 5px 0; border-left: 2px solid #ecf0f1; background: #fafafa; font-size: 0.9em; }';
echo '.column-ok { border-left-color: #27ae60; }';
echo '.column-missing { border-left-color: #e74c3c; }';
echo '.summary { background: #ecf0f1; padding: 15px; border-radius: 8px; margin: 20px 0; }';
echo '.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }';
echo '.stat-card { background: white; padding: 15px; border-radius: 8px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }';
echo '.stat-number { font-size: 2em; font-weight: bold; color: #2c3e50; }';
echo '.stat-label { color: #7f8c8d; font-size: 0.9em; }';
echo 'table { width: 100%; border-collapse: collapse; margin: 15px 0; }';
echo 'th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }';
echo 'th { background: #3498db; color: white; }';
echo '.refresh-btn { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 10px; }';
echo '.refresh-btn:hover { background: #2980b9; }';
echo '.code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 0.9em; border: 1px solid #dee2e6; }';
echo '</style>';
echo '</head>';
echo '<body>';
echo '<div class="container">';
echo '<h1>🔍 Diagnóstico de Base de Datos - Salud Juárez</h1>';
echo '<p><strong>Fecha:</strong> ' . date('Y-m-d H:i:s') . '</p>';
echo '<button class="refresh-btn" onclick="location.reload()">🔄 Actualizar Diagnóstico</button>';

try {
    // Conexión a la base de datos
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    echo '<div class="summary">';
    echo '<h2>📊 Conexión a Base de Datos</h2>';
    echo '<div class="table-item table-ok">';
    echo '<strong>✅ Conexión exitosa</strong><br>';
    echo '<small>Servidor: ' . $conn->server_info . '</small>';
    echo '</div>';
    echo '</div>';
    
    // Tablas a verificar
    $tablas = [
        'usuarios' => [
            'campos_requeridos' => ['id_usu', 'nombre_usu', 'apellido_usu', 'nick', 'email_usu', 'password_usu', 'id_rol', 'telefono_usu', 'token_verificacion', 'estatus_usu', 'fecha_registro', 'fecha_actualizacion'],
            'descripcion' => 'Usuarios del sistema'
        ],
        'roles' => [
            'campos_requeridos' => ['id_rol', 'nombre_rol', 'descripcion_rol'],
            'descripcion' => 'Roles del sistema'
        ],
        'restaurante' => [
            'campos_requeridos' => ['id_res', 'nombre_res', 'descripcion_res', 'direccion_res', 'sector_res', 'telefono_res', 'logo_res', 'banner_res', 'id_usu', 'estatus_res', 'fecha_registro', 'fecha_actualizacion', 'latitud', 'longitud', 'validado_admin', 'motivo_rechazo', 'fecha_validacion'],
            'descripcion' => 'Restaurantes registrados'
        ],
        'categorias' => [
            'campos_requeridos' => ['id_cat', 'nombre_cat', 'descripcion_cat'],
            'descripcion' => 'Categorías de platillos'
        ],
        'platillos' => [
            'campos_requeridos' => ['id_pla', 'nombre_pla', 'descripcion_pla', 'precio_pla', 'img_pla', 'id_res', 'id_cat', 'visible', 'fecha_registro', 'fecha_actualizacion'],
            'descripcion' => 'Platillos de restaurantes'
        ],
        'inventario' => [
            'campos_requeridos' => ['id_inv', 'id_res', 'nombre_insumo', 'stock_inv', 'medida_inv', 'img_insumo', 'es_ingrediente_secreto', 'alergenos', 'calorias_base', 'fecha_actualizacion'],
            'descripcion' => 'Inventario de insumos'
        ],
        'platillo_ingredientes' => [
            'campos_requeridos' => ['id_pi', 'id_pla', 'id_inv', 'cantidad_usada', 'fecha_registro'],
            'descripcion' => 'Ingredientes de platillos'
        ],
        'favoritos' => [
            'campos_requeridos' => ['id_favorito', 'id_usu', 'id_res', 'fecha_agregado'],
            'descripcion' => 'Favoritos de restaurantes'
        ],
        'validacion_log' => [
            'campos_requeridos' => ['id_log', 'id_res', 'id_admin', 'accion', 'motivo', 'fecha_accion'],
            'descripcion' => 'Log de validaciones'
        ],
        'notificaciones' => [
            'campos_requeridos' => ['id_not', 'id_usu', 'id_res', 'tipo', 'titulo', 'mensaje', 'enlace', 'leida', 'fecha_creacion'],
            'descripcion' => 'Notificaciones del sistema'
        ],
        'proveedores_insumos' => [
            'campos_requeridos' => ['id_proveedor', 'nombre_proveedor', 'telefono_proveedor', 'email_proveedor', 'direccion_proveedor', 'tipo_proveedor', 'latitud', 'longitud', 'estatus_proveedor', 'fecha_registro', 'fecha_actualizacion'],
            'descripcion' => 'Proveedores de insumos'
        ],
        'solicitudes_restock' => [
            'campos_requeridos' => ['id_solicitud', 'id_res', 'id_proveedor', 'id_inv', 'cantidad_solicitada', 'estado_solicitud', 'fecha_solicitud', 'fecha_respuesta', 'notas'],
            'descripcion' => 'Solicitudes de restock'
        ],
        'pedidos_clientes' => [
            'campos_requeridos' => ['id_pedido', 'id_usu', 'id_res', 'total_pedido', 'estado_pedido', 'direccion_entrega', 'notas_pedido', 'fecha_pedido', 'fecha_entrega'],
            'descripcion' => 'Pedidos de clientes'
        ],
        'pedido_detalle' => [
            'campos_requeridos' => ['id_detalle', 'id_pedido', 'id_pla', 'cantidad', 'precio_unitario', 'subtotal'],
            'descripcion' => 'Detalle de pedidos'
        ]
    ];
    
    // Verificar cada tabla
    $totalTablas = count($tablas);
    $tablasOk = 0;
    $tablasError = 0;
    $totalRegistros = 0;
    
    echo '<h2>📋 Análisis de Tablas</h2>';
    
    foreach ($tablas as $nombreTabla => $info) {
        $existeTabla = tablaExiste($conn, $nombreTabla);
        $claseTabla = $existeTabla ? 'table-ok' : 'table-error';
        $iconoTabla = $existeTabla ? '✅' : '❌';
        
        if ($existeTabla) {
            $tablasOk++;
            $numRegistros = contarRegistros($conn, $nombreTabla);
            $totalRegistros += $numRegistros;
            
            echo "<div class='table-item $claseTabla'>";
            echo "<strong>$iconoTabla $nombreTabla</strong> - {$info['descripcion']}<br>";
            echo "<small>Registros: <strong>$numRegistros</strong></small><br>";
            
            // Verificar campos requeridos
            $camposFaltantes = [];
            $estructura = obtenerEstructuraTabla($conn, $nombreTabla);
            $camposExistentes = array_column($estructura, 'Field');
            
            foreach ($info['campos_requeridos'] as $campoRequerido) {
                if (!in_array($campoRequerido, $camposExistentes)) {
                    $camposFaltantes[] = $campoRequerido;
                }
            }
            
            if (!empty($camposFaltantes)) {
                echo "<div class='column-item column-missing'>";
                echo "<strong>⚠️ Campos faltantes:</strong> " . implode(', ', $camposFaltantes);
                echo "</div>";
            } else {
                echo "<div class='column-item column-ok'>";
                echo "<strong>✅ Todos los campos requeridos presentes</strong>";
                echo "</div>";
            }
            
            echo "</div>";
        } else {
            $tablasError++;
            echo "<div class='table-item $claseTabla'>";
            echo "<strong>$iconoTabla $nombreTabla</strong> - {$info['descripcion']}<br>";
            echo "<small>Tabla no existe</small>";
            echo "</div>";
        }
    }
    
    // Estadísticas generales
    echo '<div class="summary">';
    echo '<h2>📊 Estadísticas Generales</h2>';
    echo '<div class="stats">';
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . $totalTablas . '</div>';
    echo '<div class="stat-label">Total Tablas</div>';
    echo '</div>';
    echo '<div class="stat-card">';
    echo '<div class="stat-number status-ok">' . $tablasOk . '</div>';
    echo '<div class="stat-label">✅ Tablas OK</div>';
    echo '</div>';
    echo '<div class="stat-card">';
    echo '<div class="stat-number status-error">' . $tablasError . '</div>';
    echo '<div class="stat-label">❌ Tablas Faltantes</div>';
    echo '</div>';
    echo '<div class="stat-card">';
    echo '<div class="stat-number">' . $totalRegistros . '</div>';
    echo '<div class="stat-label">Total Registros</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Verificación de datos críticos
    echo '<h2>🔍 Verificación de Datos Críticos</h2>';
    
    // Usuarios
    if (tablaExiste($conn, 'usuarios')) {
        $totalUsuarios = contarRegistros($conn, 'usuarios');
        $usuariosActivos = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estatus_usu = 1")->fetch_assoc()['total'];
        
        echo '<div class="table-item table-ok">';
        echo '<strong>👥 Usuarios</strong><br>';
        echo "<small>Total: $totalUsuarios | Activos: $usuariosActivos</small>";
        echo '</div>';
    }
    
    // Restaurantes
    if (tablaExiste($conn, 'restaurante')) {
        $totalRestaurantes = contarRegistros($conn, 'restaurante');
        $restaurantesValidados = $conn->query("SELECT COUNT(*) as total FROM restaurante WHERE validado_admin = 1")->fetch_assoc()['total'];
        
        echo '<div class="table-item table-ok">';
        echo '<strong>🏢 Restaurantes</strong><br>';
        echo "<small>Total: $totalRestaurantes | Validados: $restaurantesValidados</small>";
        echo '</div>';
    }
    
    // Platillos
    if (tablaExiste($conn, 'platillos')) {
        $totalPlatillos = contarRegistros($conn, 'platillos');
        $platillosVisibles = $conn->query("SELECT COUNT(*) as total FROM platillos WHERE visible = 1")->fetch_assoc()['total'];
        
        echo '<div class="table-item table-ok">';
        echo '<strong>🍽️ Platillos</strong><br>';
        echo "<small>Total: $totalPlatillos | Visibles: $platillosVisibles</small>";
        echo '</div>';
    }
    
    // Inventario
    if (tablaExiste($conn, 'inventario')) {
        $totalInventario = contarRegistros($conn, 'inventario');
        $stockBajo = $conn->query("SELECT COUNT(*) as total FROM inventario WHERE stock_inv <= 5")->fetch_assoc()['total'];
        
        echo '<div class="table-item table-ok">';
        echo '<strong>📦 Inventario</strong><br>';
        echo "<small>Total: $totalInventario | Stock Bajo: $stockBajo</small>";
        echo '</div>';
    }
    
    // Recomendaciones
    echo '<h2>🚀 Recomendaciones</h2>';
    echo '<div class="summary">';
    
    if ($tablasError > 0) {
        echo '<p><strong class="status-error">⚠️ Se requieren acciones:</strong></p>';
        echo '<ul>';
        echo '<li>Ejecuta el script <code>alter_tablas_simple.sql</code> para crear las tablas faltantes</li>';
        echo '<li>Verifica los permisos del usuario de la base de datos</li>';
        echo '</ul>';
    }
    
    if ($tablasOk === $totalTablas) {
        echo '<p><strong class="status-ok">🎉 ¡Excelente! Todas las tablas están presentes.</strong></p>';
        
        if ($totalRegistros === 0) {
            echo '<p><strong class="status-warning">⚠️ Las tablas están vacías:</strong></p>';
            echo '<ul>';
            echo '<li>Importa el archivo <code>restaurantes-marzo_25.sql</code> (solo INSERTs)</li>';
            echo '<li>O registra datos manualmente desde el panel de administración</li>';
            echo '</ul>';
        } else {
            echo '<p><strong class="status-ok">✅ El sistema tiene datos y está funcional.</strong></p>';
        }
    }
    
    echo '</div>';
    
    // Scripts recomendados
    echo '<h2>📝 Scripts Recomendados</h2>';
    echo '<div class="summary">';
    echo '<p><strong>Si tienes tablas faltantes:</strong></p>';
    echo '<div class="code">1. Ejecuta: SQL/alter_tablas_simple.sql</div>';
    echo '<p><strong>Si las tablas están vacías:</strong></p>';
    echo '<div class="code">1. Edita SQL/restaurantes-marzo_25.sql<br>2. Elimina las líneas CREATE TABLE<br>3. Ejecuta solo los INSERTs</div>';
    echo '<p><strong>Para verificar el estado:</strong></p>';
    echo '<div class="code">1. Ejecuta: SQL/diagnosticar_tablas.sql<br>2. O visita: PHP/diagnosticar_db.php</div>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="table-item table-error">';
    echo '<strong>❌ Error en el diagnóstico:</strong><br>';
    echo '<small>' . htmlspecialchars($e->getMessage()) . '</small>';
    echo '</div>';
}

echo '<hr>';
echo '<p><small><em>Diagnóstico generado automáticamente el ' . date('Y-m-d H:i:s') . '</em></small></p>';
echo '</div>';
echo '</body>';
echo '</html>';
?>
