<?php
/**
 * Sistema de Diagnóstico Completo - Salud Juárez
 * Monitoreo de todas las páginas y componentes del sistema
 */

session_start();
include 'db_config.php';

// Verificar que sea administrador
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 1) {
    die('<h1>❌ Acceso Denegado</h1><p>Solo los administradores pueden acceder al diagnóstico del sistema.</p>');
}

echo '<!DOCTYPE html>';
echo '<html lang="es">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>🔍 Diagnóstico del Sistema - Salud Juárez</title>';
echo '<style>';
echo 'body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }';
echo '.container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }';
echo 'h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }';
echo 'h2 { color: #3498db; margin-top: 30px; }';
echo '.status-ok { color: #27ae60; font-weight: bold; }';
echo '.status-warning { color: #f39c12; font-weight: bold; }';
echo '.status-error { color: #e74c3c; font-weight: bold; }';
echo '.file-item { padding: 8px; margin: 5px 0; border-left: 4px solid #ddd; background: #f9f9f9; }';
echo '.file-ok { border-left-color: #27ae60; }';
echo '.file-warning { border-left-color: #f39c12; }';
echo '.file-error { border-left-color: #e74c3c; }';
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
echo '</style>';
echo '</head>';
echo '<body>';
echo '<div class="container">';
echo '<h1>🔍 Diagnóstico del Sistema Salud Juárez</h1>';
echo '<p><strong>Fecha:</strong> ' . date('Y-m-d H:i:s') . ' | <strong>Administrador:</strong> ' . $_SESSION['nick'] . '</p>';
echo '<button class="refresh-btn" onclick="location.reload()">🔄 Actualizar Diagnóstico</button>';

// Función para verificar archivos
function verificarArchivo($ruta, $descripcion, $tipo = 'general') {
    $estado = ['clase' => 'file-ok', 'icono' => '✅', 'mensaje' => 'Ventana en funcionamiento'];
    
    if (!file_exists($ruta)) {
        $estado = ['clase' => 'file-error', 'icono' => '❌', 'mensaje' => 'Archivo inexistente / Corrompido'];
    } else {
        // Verificar sintaxis si es PHP
        if (pathinfo($ruta, PATHINFO_EXTENSION) === 'php') {
            $output = [];
            $return_code = 0;
            exec("php -l $ruta 2>&1", $output, $return_code);
            
            if ($return_code !== 0) {
                $estado = ['clase' => 'file-error', 'icono' => '❌', 'mensaje' => 'Error de sintaxis PHP'];
                $estado['error'] = implode('<br>', $output);
            }
        }
        
        // Verificar permisos de lectura
        if (!is_readable($ruta)) {
            $estado = ['clase' => 'file-warning', 'icono' => '⚠️', 'mensaje' => 'Archivo sin permisos de lectura'];
        }
    }
    
    return array_merge($estado, ['ruta' => $ruta, 'descripcion' => $descripcion, 'tipo' => $tipo]);
}

// Estructura de archivos a verificar
$archivos_a_verificar = [
    // Páginas principales
    '../index.html' => 'Página Principal',
    '../login.php' => 'Login de Usuarios',
    '../signup.php' => 'Registro de Usuarios',
    
    // Dashboard por rol
    '../DIRECCIONES/dashboard.php' => 'Dashboard Principal',
    
    // Administrador
    '../DIRECCIONES/admin_usuarios.php' => 'Admin - Gestión de Usuarios',
    '../DIRECCIONES/validar_negocios.php' => 'Admin - Validación de Negocios',
    '../DIRECCIONES/admin_ingredientes.php' => 'Admin - Administración de Ingredientes',
    '../DIRECCIONES/mapa_proveedores.php' => 'Admin - Mapa Central',
    '../DIRECCIONES/estado_sistema.php' => 'Admin - Estado del Sistema',
    
    // Dueño de Restaurante
    '../DIRECCIONES/mis_restaurantes.php' => 'Dueño - Mis Restaurantes',
    '../DIRECCIONES/editar_perfil_restaurante.php' => 'Dueño - Editar Perfil Restaurante',
    '../DIRECCIONES/gestion_platillos.php' => 'Dueño - Gestión de Platillos',
    '../DIRECCIONES/inventario.php' => 'Dueño - Inventario',
    '../DIRECCIONES/revisar_ingredientes.php' => 'Dueño - Revisar Ingredientes',
    '../DIRECCIONES/proveedores_cercanos.php' => 'Dueño - Proveedores Cercanos',
    
    // Cliente
    '../DIRECCIONES/buscar_restaurantes.php' => 'Cliente - Buscar Restaurantes',
    '../DIRECCIONES/ver_menu.php' => 'Cliente - Ver Menú',
    '../DIRECCIONES/mis_favoritos.php' => 'Cliente - Mis Favoritos',
    
    // Procesadores PHP
    'validar_login.php' => 'Procesador - Validar Login',
    'procesar_registro.php' => 'Procesador - Registro',
    'procesar_platillo.php' => 'Procesador - Gestión Platillos',
    'procesar_favoritos.php' => 'Procesador - Favoritos',
    'procesar_admin_usuarios.php' => 'Procesador - Admin Usuarios',
    'procesar_validacion.php' => 'Procesador - Validación',
    'procesar_geolocalizacion.php' => 'Procesador - Geolocalización',
    
    // Componentes
    'navbar.php' => 'Componente - Navegación',
    'header_meta.php' => 'Componente - Meta Headers',
    'logout.php' => 'Componente - Logout',
    
    // JavaScript
    '../JS/mapa_salud_juarez.js' => 'JavaScript - Mapa Interactivo'
];

// Verificar todos los archivos
$resultados = [];
foreach ($archivos_a_verificar as $ruta => $descripcion) {
    $resultados[] = verificarArchivo($ruta, $descripcion);
}

// Estadísticas
$total_archivos = count($resultados);
$archivos_ok = count(array_filter($resultados, function($r) { return strpos($r['clase'], 'ok') !== false; }));
$archivos_error = count(array_filter($resultados, function($r) { return strpos($r['clase'], 'error') !== false; }));
$archivos_warning = count(array_filter($resultados, function($r) { return strpos($r['clase'], 'warning') !== false; }));

echo '<div class="summary">';
echo '<h2>📊 Resumen General</h2>';
echo '<div class="stats">';
echo '<div class="stat-card">';
echo '<div class="stat-number">' . $total_archivos . '</div>';
echo '<div class="stat-label">Total Archivos</div>';
echo '</div>';
echo '<div class="stat-card">';
echo '<div class="stat-number status-ok">' . $archivos_ok . '</div>';
echo '<div class="stat-label">✅ Funcionales</div>';
echo '</div>';
echo '<div class="stat-card">';
echo '<div class="stat-number status-warning">' . $archivos_warning . '</div>';
echo '<div class="stat-label">⚠️ Advertencias</div>';
echo '</div>';
echo '<div class="stat-card">';
echo '<div class="stat-number status-error">' . $archivos_error . '</div>';
echo '<div class="stat-label">❌ Con Problemas</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Mostrar resultados por categoría
$categorias = [
    'general' => '🏠 Páginas Principales',
    'admin' => '🛡️ Administrador',
    'dueno' => '👑 Dueño de Restaurante',
    'cliente' => '👤 Cliente',
    'procesador' => '⚙️ Procesadores PHP',
    'componente' => '🔧 Componentes',
    'javascript' => '📜 JavaScript'
];

foreach ($categorias as $cat_key => $cat_nombre) {
    $archivos_categoria = array_filter($resultados, function($r) use ($cat_key) {
        return $r['tipo'] === $cat_key;
    });
    
    if (!empty($archivos_categoria)) {
        echo '<h2>' . $cat_nombre . '</h2>';
        
        foreach ($archivos_categoria as $archivo) {
            echo '<div class="file-item ' . $archivo['clase'] . '">';
            echo '<strong>' . $archivo['icono'] . ' ' . $archivo['descripcion'] . '</strong><br>';
            echo '<small>Ruta: ' . $archivo['ruta'] . '</small><br>';
            echo '<span class="status-' . str_replace('file-', '', $archivo['clase']) . '">' . $archivo['mensaje'] . '</span>';
            
            if (isset($archivo['error'])) {
                echo '<br><small style="color: #e74c3c;">' . htmlspecialchars($archivo['error']) . '</small>';
            }
            
            echo '</div>';
        }
    }
}

// Verificación de base de datos
echo '<h2>🗄️ Estado de la Base de Datos</h2>';
if (isset($conn) && $conn->ping()) {
    echo '<div class="file-item file-ok">';
    echo '<strong>✅ Conexión a MySQL exitosa</strong><br>';
    echo '<small>Servidor: ' . $conn->server_info . '</small>';
    echo '</div>';
    
    // Verificar tablas
    $tablas_esperadas = [
        'usuarios', 'roles', 'restaurante', 'platillos', 'inventario',
        'platillo_ingredientes', 'categorias', 'notificaciones',
        'pedidos_restock', 'pedido_detalle', 'proveedores_insumos',
        'solicitudes_restock', 'pedidos_clientes', 'favoritos', 'validacion_log'
    ];
    
    $tablas_existentes = [];
    $tablas_faltantes = [];
    
    foreach ($tablas_esperadas as $tabla) {
        $result = $conn->query("SHOW TABLES LIKE '$tabla'");
        if ($result->num_rows > 0) {
            $tablas_existentes[] = $tabla;
        } else {
            $tablas_faltantes[] = $tabla;
        }
    }
    
    echo '<div class="file-item file-ok">';
    echo '<strong>✅ Tablas encontradas: ' . count($tablas_existentes) . '/' . count($tablas_esperadas) . '</strong><br>';
    echo '<small>' . implode(', ', $tablas_existentes) . '</small>';
    echo '</div>';
    
    if (!empty($tablas_faltantes)) {
        echo '<div class="file-item file-error">';
        echo '<strong>❌ Tablas faltantes: ' . count($tablas_faltantes) . '</strong><br>';
        echo '<small>' . implode(', ', $tablas_faltantes) . '</small>';
        echo '</div>';
    }
    
} else {
    echo '<div class="file-item file-error">';
    echo '<strong>❌ Error en conexión a MySQL</strong>';
    echo '</div>';
}

// Verificación de extensiones PHP
echo '<h2>🔌 Extensiones PHP Requeridas</h2>';
$extensiones_requeridas = ['mysqli', 'pdo', 'pdo_mysql', 'session', 'json', 'mbstring', 'curl'];

foreach ($extensiones_requeridas as $ext) {
    $status = extension_loaded($ext);
    $clase = $status ? 'file-ok' : 'file-error';
    $icono = $status ? '✅' : '❌';
    
    echo '<div class="file-item ' . $clase . '">';
    echo '<strong>' . $icono . ' Extensión: ' . $ext . '</strong>';
    echo '</div>';
}

// Verificación de carpetas importantes
echo '<h2>📁 Carpetas del Sistema</h2>';
$carpetas = [
    '../UPLOADS' => 'Uploads de Archivos',
    '../IMG' => 'Imágenes del Sistema',
    '../CSS' => 'Estilos CSS',
    '../JS' => 'JavaScript',
    '../DIRECCIONES' => 'Páginas del Sistema',
    '../PHP' => 'Procesadores PHP',
    '../SQL' => 'Scripts SQL',
    '../README' => 'Documentación'
];

foreach ($carpetas as $carpeta => $descripcion) {
    $existe = file_exists($carpeta);
    $escribible = $existe && is_writable($carpeta);
    
    if (!$existe) {
        $clase = 'file-error';
        $icono = '❌';
        $mensaje = 'No existe';
    } elseif (!$escribible) {
        $clase = 'file-warning';
        $icono = '⚠️';
        $mensaje = 'Existe pero no es escribible';
    } else {
        $clase = 'file-ok';
        $icono = '✅';
        $mensaje = 'Existente y escribible';
    }
    
    echo '<div class="file-item ' . $clase . '">';
    echo '<strong>' . $icono . ' ' . $descripcion . '</strong><br>';
    echo '<small>Ruta: ' . $carpeta . ' | Estado: ' . $mensaje . '</small>';
    echo '</div>';
}

// Recomendaciones
echo '<h2>🚀 Acciones Recomendadas</h2>';
echo '<div class="summary">';

if ($archivos_error > 0) {
    echo '<p><strong class="status-error">⚠️ Se encontraron ' . $archivos_error . ' archivos con problemas:</strong></p>';
    echo '<ul>';
    foreach ($resultados as $archivo) {
        if (strpos($archivo['clase'], 'error') !== false) {
            echo '<li>Revisar: <strong>' . $archivo['descripcion'] . '</strong> (' . $archivo['ruta'] . ')</li>';
        }
    }
    echo '</ul>';
}

if ($archivos_warning > 0) {
    echo '<p><strong class="status-warning">⚠️ Se encontraron ' . $archivos_warning . ' advertencias:</strong></p>';
    echo '<ul>';
    foreach ($resultados as $archivo) {
        if (strpos($archivo['clase'], 'warning') !== false) {
            echo '<li>Revisar permisos: <strong>' . $archivo['descripcion'] . '</strong></li>';
        }
    }
    echo '</ul>';
}

if ($archivos_error === 0 && $archivos_warning === 0) {
    echo '<p><strong class="status-ok">🎉 ¡Excelente! Todos los archivos están funcionando correctamente.</strong></p>';
}

echo '<p><strong>Notificaciones para el Administrador:</strong></p>';
echo '<ul>';
echo '<li>✅ Ventana en funcionamiento - Sistema operativo</li>';
echo '<li>❌ Archivo inexistente / Corrompido - Requiere atención inmediata</li>';
echo '<li>⚠️ No se muestra la pantalla al usuario - Requiere revisión</li>';
echo '</ul>';

echo '</div>';

echo '<hr>';
echo '<p><small><em>Diagnóstico generado automáticamente el ' . date('Y-m-d H:i:s') . '</em></small></p>';
echo '</div>';
echo '</body>';
echo '</html>';
?>
