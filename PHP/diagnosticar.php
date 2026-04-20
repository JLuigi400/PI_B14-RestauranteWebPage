<?php
/**
 * Sistema de Diagnóstico Completo - Salud Juárez
 * Monitoreo de todas las páginas y componentes del sistema
 * Versión: 2.1.0
 * Fecha: 19 de Abril de 2026
 */

session_start();
include 'db_config.php';

// Verificar si es una petición AJAX
$esAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Verificar que sea administrador
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 1) {
    if ($esAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
        exit;
    } else {
        die('<h1>Acceso Denegado</h1><p>Solo los administradores pueden acceder al diagnóstico del sistema.</p>');
    }
}

// Función para verificar un archivo
function verificarArchivo($ruta, $descripcion, $tipo) {
    if (!file_exists($ruta)) {
        return [
            'ruta' => $ruta,
            'descripcion' => $descripcion,
            'tipo' => $tipo,
            'clase' => 'file-error',
            'icono' => '❌',
            'mensaje' => 'Archivo inexistente',
            'existe' => false,
            'tamano' => 0
        ];
    }

    $tamano = filesize($ruta);
    $clase = 'file-ok';
    $icono = '✅';
    $mensaje = 'Funcional';
    $errorDetalle = null;

    // Verificar permisos de lectura
    if (!is_readable($ruta)) {
        $clase = 'file-warning';
        $icono = '⚠️';
        $mensaje = 'Sin permisos de lectura';
    }

    return [
        'ruta' => $ruta,
        'descripcion' => $descripcion,
        'tipo' => $tipo,
        'clase' => $clase,
        'icono' => $icono,
        'mensaje' => $mensaje,
        'existe' => true,
        'tamano' => $tamano,
        'tamano_kb' => round($tamano / 1024, 2),
        'error' => $errorDetalle
    ];
}

// Estructura de archivos a verificar
$archivos_a_verificar = [
    // Páginas principales
    ['ruta' => '../index.html', 'desc' => 'Página Principal', 'tipo' => 'general'],
    ['ruta' => '../login.php', 'desc' => 'Login de Usuarios', 'tipo' => 'general'],
    ['ruta' => '../signup.php', 'desc' => 'Registro de Usuarios', 'tipo' => 'general'],

    // Dashboard por rol
    ['ruta' => '../DIRECCIONES/dashboard.php', 'desc' => 'Dashboard Principal', 'tipo' => 'general'],
    ['ruta' => '../DIRECCIONES/dashboard_usuario.php', 'desc' => 'Dashboard Usuario', 'tipo' => 'cliente'],

    // Administrador
    ['ruta' => '../DIRECCIONES/admin_usuarios.php', 'desc' => 'Admin - Gestión de Usuarios', 'tipo' => 'admin'],
    ['ruta' => '../DIRECCIONES/validar_negocios.php', 'desc' => 'Admin - Validación de Negocios', 'tipo' => 'admin'],
    ['ruta' => '../DIRECCIONES/admin_ingredientes.php', 'desc' => 'Admin - Administración de Ingredientes', 'tipo' => 'admin'],
    ['ruta' => '../DIRECCIONES/mapa_proveedores.php', 'desc' => 'Admin - Mapa Central', 'tipo' => 'admin'],
    ['ruta' => '../DIRECCIONES/estado_sistema.php', 'desc' => 'Admin - Estado del Sistema', 'tipo' => 'admin'],

    // Dueño de Restaurante
    ['ruta' => '../DIRECCIONES/mis_restaurantes.php', 'desc' => 'Dueño - Mis Restaurantes', 'tipo' => 'dueno'],
    ['ruta' => '../DIRECCIONES/editar_perfil_restaurante.php', 'desc' => 'Dueño - Editar Perfil Restaurante', 'tipo' => 'dueno'],
    ['ruta' => '../DIRECCIONES/gestion_platillos.php', 'desc' => 'Dueño - Gestión de Platillos', 'tipo' => 'dueno'],
    ['ruta' => '../DIRECCIONES/inventario.php', 'desc' => 'Dueño - Inventario', 'tipo' => 'dueno'],
    ['ruta' => '../DIRECCIONES/revisar_ingredientes.php', 'desc' => 'Dueño - Revisar Ingredientes', 'tipo' => 'dueno'],
    ['ruta' => '../DIRECCIONES/proveedores_cercanos.php', 'desc' => 'Dueño - Proveedores Cercanos', 'tipo' => 'dueno'],

    // Cliente
    ['ruta' => '../DIRECCIONES/buscar_restaurantes.php', 'desc' => 'Cliente - Buscar Restaurantes', 'tipo' => 'cliente'],
    ['ruta' => '../DIRECCIONES/ver_menu.php', 'desc' => 'Cliente - Ver Menú', 'tipo' => 'cliente'],
    ['ruta' => '../DIRECCIONES/mis_favoritos.php', 'desc' => 'Cliente - Mis Favoritos', 'tipo' => 'cliente'],

    // Procesadores PHP
    ['ruta' => 'validar_login.php', 'desc' => 'Procesador - Validar Login', 'tipo' => 'procesador'],
    ['ruta' => 'procesar_registro.php', 'desc' => 'Procesador - Registro', 'tipo' => 'procesador'],
    ['ruta' => 'procesar_platillo.php', 'desc' => 'Procesador - Gestión Platillos', 'tipo' => 'procesador'],
    ['ruta' => 'procesar_favoritos.php', 'desc' => 'Procesador - Favoritos', 'tipo' => 'procesador'],
    ['ruta' => 'procesar_admin_usuarios.php', 'desc' => 'Procesador - Admin Usuarios', 'tipo' => 'procesador'],
    ['ruta' => 'procesar_validacion.php', 'desc' => 'Procesador - Validación', 'tipo' => 'procesador'],
    ['ruta' => 'procesar_geolocalizacion.php', 'desc' => 'Procesador - Geolocalización', 'tipo' => 'procesador'],
    ['ruta' => 'actualizar_restaurante.php', 'desc' => 'Procesador - Actualizar Restaurante', 'tipo' => 'procesador'],
    ['ruta' => 'actualizar_usuario.php', 'desc' => 'Procesador - Actualizar Usuario', 'tipo' => 'procesador'],
    ['ruta' => 'obtener_usuario_actual.php', 'desc' => 'Procesador - Obtener Usuario Actual', 'tipo' => 'procesador'],
    ['ruta' => 'verificar_correo_unico.php', 'desc' => 'Procesador - Verificar Correo', 'tipo' => 'procesador'],

    // Componentes
    ['ruta' => 'navbar.php', 'desc' => 'Componente - Navegación', 'tipo' => 'componente'],
    ['ruta' => 'header_meta.php', 'desc' => 'Componente - Meta Headers', 'tipo' => 'componente'],
    ['ruta' => 'footer.php', 'desc' => 'Componente - Footer', 'tipo' => 'componente'],
    ['ruta' => 'logout.php', 'desc' => 'Componente - Logout', 'tipo' => 'componente'],
    ['ruta' => 'db_config.php', 'desc' => 'Componente - Configuración DB', 'tipo' => 'componente'],
    ['ruta' => '../DIRECCIONES/componentes/modal_editar_restaurante.php', 'desc' => 'Modal - Editar Restaurante', 'tipo' => 'componente'],
    ['ruta' => '../DIRECCIONES/componentes/modal_editar_usuario.php', 'desc' => 'Modal - Editar Usuario', 'tipo' => 'componente'],

    // JavaScript
    ['ruta' => '../JS/mapa_salud_juarez.js', 'desc' => 'JavaScript - Mapa Interactivo', 'tipo' => 'javascript'],
    ['ruta' => '../JS/editar_restaurante.js', 'desc' => 'JavaScript - Editar Restaurante', 'tipo' => 'javascript'],
    ['ruta' => '../JS/editar_usuario.js', 'desc' => 'JavaScript - Editar Usuario', 'tipo' => 'javascript'],
    ['ruta' => '../JS/emailjs_config.js', 'desc' => 'JavaScript - EmailJS Config', 'tipo' => 'javascript'],
    ['ruta' => '../JS/session_check.js', 'desc' => 'JavaScript - Session Check', 'tipo' => 'javascript'],

    // Diagnóstico
    ['ruta' => 'diagnosticar_db.php', 'desc' => 'Diagnóstico - Base de Datos', 'tipo' => 'diagnostico'],
    ['ruta' => 'diagnosticar_includes.php', 'desc' => 'Diagnóstico - Includes', 'tipo' => 'diagnostico'],
];

// Verificar todos los archivos
$resultados = [];
foreach ($archivos_a_verificar as $archivo) {
    $resultados[] = verificarArchivo($archivo['ruta'], $archivo['desc'], $archivo['tipo']);
}

// Estadísticas
$total_archivos = count($resultados);
$archivos_ok = count(array_filter($resultados, function($r) { return $r['clase'] === 'file-ok'; }));
$archivos_error = count(array_filter($resultados, function($r) { return $r['clase'] === 'file-error'; }));
$archivos_warning = count(array_filter($resultados, function($r) { return $r['clase'] === 'file-warning'; }));

// Si es AJAX, devolver JSON
if ($esAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'resumen' => [
            'total' => $total_archivos,
            'ok' => $archivos_ok,
            'error' => $archivos_error,
            'warning' => $archivos_warning
        ],
        'archivos' => $resultados
    ]);
    exit;
}

// Verificación de base de datos
$db_conectada = false;
$db_info = '';
$tablas_existentes = [];
$tablas_faltantes = [];
$tablas_esperadas = [
    'usuarios', 'roles', 'restaurante', 'platillos', 'inventario',
    'platillo_ingredientes', 'categorias', 'notificaciones',
    'pedidos_restock', 'pedido_detalle', 'proveedores_insumos',
    'solicitudes_restock', 'pedidos_clientes', 'favoritos', 'validacion_log',
    'perfiles'
];

if (isset($conn)) {
    try {
        if ($conn->ping()) {
            $db_conectada = true;
            $db_info = $conn->server_info;

            foreach ($tablas_esperadas as $tabla) {
                $result = $conn->query("SHOW TABLES LIKE '$tabla'");
                if ($result && $result->num_rows > 0) {
                    $tablas_existentes[] = $tabla;
                } else {
                    $tablas_faltantes[] = $tabla;
                }
            }
        }
    } catch (Exception $e) {
        $db_conectada = false;
        $db_info = $e->getMessage();
    }
}

// Extensiones PHP
$extensiones_requeridas = ['mysqli', 'pdo', 'pdo_mysql', 'session', 'json', 'mbstring', 'curl'];
$extensiones_estado = [];
foreach ($extensiones_requeridas as $ext) {
    $extensiones_estado[$ext] = extension_loaded($ext);
}

// Carpetas del sistema
$carpetas = [
    '../UPLOADS' => 'Uploads de Archivos',
    '../IMG' => 'Imágenes del Sistema',
    '../CSS' => 'Estilos CSS',
    '../JS' => 'JavaScript',
    '../DIRECCIONES' => 'Páginas del Sistema',
    '../PHP' => 'Procesadores PHP',
    '../SQL' => 'Scripts SQL',
    '../UPLOADS/RESTAURANTES' => 'Uploads Restaurantes'
];
$carpetas_estado = [];
foreach ($carpetas as $carpeta => $desc) {
    $existe = file_exists($carpeta);
    $escribible = $existe && is_writable($carpeta);
    $carpetas_estado[$carpeta] = [
        'descripcion' => $desc,
        'existe' => $existe,
        'escribible' => $escribible
    ];
}

// Categorías para mostrar
$categorias = [
    'general' => '🏠 Páginas Principales',
    'admin' => '🛡️ Administrador',
    'dueno' => '👑 Dueño de Restaurante',
    'cliente' => '👤 Cliente',
    'procesador' => '⚙️ Procesadores PHP',
    'componente' => '🔧 Componentes',
    'javascript' => '📜 JavaScript',
    'diagnostico' => '🔍 Diagnóstico'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico del Sistema - Salud Juárez</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: #333; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .header h1 { margin-bottom: 8px; }
        .header p { opacity: 0.9; }
        .summary { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; border: 1px solid #e9ecef; }
        .stat-number { font-size: 2.2em; font-weight: bold; }
        .stat-label { color: #666; margin-top: 5px; font-size: 0.9em; }
        .status-ok { color: #27ae60; }
        .status-warning { color: #f39c12; }
        .status-error { color: #e74c3c; }
        .file-item { background: white; padding: 15px 20px; margin-bottom: 8px; border-radius: 8px; border-left: 5px solid #ddd; box-shadow: 0 1px 4px rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; }
        .file-ok { border-left-color: #27ae60; }
        .file-warning { border-left-color: #f39c12; }
        .file-error { border-left-color: #e74c3c; }
        .file-info { flex: 1; }
        .file-info strong { font-size: 0.95em; }
        .file-info small { color: #888; display: block; margin-top: 3px; }
        .file-status { text-align: right; min-width: 120px; }
        .section-title { color: #8e44ad; margin: 30px 0 15px 0; padding-bottom: 8px; border-bottom: 2px solid #8e44ad; font-size: 1.3em; }
        .btn { display: inline-block; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; color: white; font-weight: 600; margin: 5px; transition: all 0.2s; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .btn-primary { background: #9b59b6; }
        .btn-success { background: #27ae60; }
        .btn-info { background: #3498db; }
        .btn-danger { background: #e74c3c; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.8em; font-weight: 600; }
        .badge-ok { background: #d4edda; color: #155724; }
        .badge-error { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .progress-bar { width: 100%; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; margin-top: 10px; }
        .progress-fill { height: 100%; border-radius: 4px; transition: width 0.5s ease; }
        .progress-ok { background: #27ae60; }
        .progress-error { background: #e74c3c; }
        .tools-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .tool-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: transform 0.2s; }
        .tool-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.12); }
        .tool-card h4 { margin: 10px 0 5px; color: #8e44ad; }
        .tool-card p { color: #888; font-size: 0.85em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico del Sistema Salud Juárez</h1>
            <p>Monitoreo completo del estado del sistema</p>
            <small>Generado: <?php echo date('Y-m-d H:i:s'); ?> | Admin: <?php echo htmlspecialchars($_SESSION['nick'] ?? 'Desconocido'); ?></small>
        </div>

        <!-- Resumen General -->
        <div class="summary">
            <h2>📊 Resumen General</h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_archivos; ?></div>
                    <div class="stat-label">Total Archivos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number status-ok"><?php echo $archivos_ok; ?></div>
                    <div class="stat-label">✅ Funcionales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number status-warning"><?php echo $archivos_warning; ?></div>
                    <div class="stat-label">⚠️ Advertencias</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number status-error"><?php echo $archivos_error; ?></div>
                    <div class="stat-label">❌ Con Problemas</div>
                </div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill progress-ok" style="width: <?php echo $total_archivos > 0 ? round(($archivos_ok / $total_archivos) * 100) : 0; ?>%"></div>
            </div>
            <small style="color: #888;"><?php echo $total_archivos > 0 ? round(($archivos_ok / $total_archivos) * 100, 1) : 0; ?>% de archivos funcionales</small>
        </div>

        <!-- Base de Datos -->
        <div class="summary">
            <h2>🗄️ Estado de la Base de Datos</h2>
            <?php if ($db_conectada): ?>
                <div class="file-item file-ok">
                    <div class="file-info">
                        <strong>✅ Conexión a MySQL exitosa</strong>
                        <small>Servidor: <?php echo htmlspecialchars($db_info); ?></small>
                    </div>
                    <div class="file-status"><span class="badge badge-ok">CONECTADA</span></div>
                </div>
                <div class="file-item file-ok">
                    <div class="file-info">
                        <strong>Tablas encontradas: <?php echo count($tablas_existentes); ?> / <?php echo count($tablas_esperadas); ?></strong>
                        <small><?php echo implode(', ', $tablas_existentes); ?></small>
                    </div>
                    <div class="file-status"><span class="badge badge-ok"><?php echo count($tablas_existentes); ?> tablas</span></div>
                </div>
                <?php if (!empty($tablas_faltantes)): ?>
                    <div class="file-item file-error">
                        <div class="file-info">
                            <strong>❌ Tablas faltantes: <?php echo count($tablas_faltantes); ?></strong>
                            <small><?php echo implode(', ', $tablas_faltantes); ?></small>
                        </div>
                        <div class="file-status"><span class="badge badge-error">FALTANTES</span></div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="file-item file-error">
                    <div class="file-info">
                        <strong>❌ Error en conexión a MySQL</strong>
                        <small><?php echo htmlspecialchars($db_info); ?></small>
                    </div>
                    <div class="file-status"><span class="badge badge-error">ERROR</span></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Archivos por Categoría -->
        <?php foreach ($categorias as $cat_key => $cat_nombre): ?>
            <?php
            $archivos_cat = array_filter($resultados, function($r) use ($cat_key) { return $r['tipo'] === $cat_key; });
            if (empty($archivos_cat)) continue;
            $ok_cat = count(array_filter($archivos_cat, function($r) { return $r['clase'] === 'file-ok'; }));
            $total_cat = count($archivos_cat);
            ?>
            <div class="summary">
                <h2 class="section-title" style="margin-top:0; border:none; padding:0;"><?php echo $cat_nombre; ?>
                    <span style="font-size:0.7em; color:#888;">(<?php echo $ok_cat; ?>/<?php echo $total_cat; ?> OK)</span>
                </h2>
                <?php foreach ($archivos_cat as $archivo): ?>
                    <div class="file-item <?php echo $archivo['clase']; ?>">
                        <div class="file-info">
                            <strong><?php echo $archivo['icono']; ?> <?php echo htmlspecialchars($archivo['descripcion']); ?></strong>
                            <small>Ruta: <?php echo htmlspecialchars($archivo['ruta']); ?>
                            <?php if ($archivo['existe']): ?> | Tamaño: <?php echo $archivo['tamano_kb']; ?> KB<?php endif; ?></small>
                            <?php if (isset($archivo['error']) && $archivo['error']): ?>
                                <small style="color:#e74c3c;"><?php echo htmlspecialchars($archivo['error']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="file-status">
                            <?php if ($archivo['clase'] === 'file-ok'): ?>
                                <span class="badge badge-ok">OK</span>
                            <?php elseif ($archivo['clase'] === 'file-warning'): ?>
                                <span class="badge badge-warning">ADVERTENCIA</span>
                            <?php else: ?>
                                <span class="badge badge-error">ERROR</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <!-- Extensiones PHP -->
        <div class="summary">
            <h2>🔌 Extensiones PHP Requeridas</h2>
            <?php foreach ($extensiones_estado as $ext => $cargada): ?>
                <div class="file-item <?php echo $cargada ? 'file-ok' : 'file-error'; ?>">
                    <div class="file-info">
                        <strong><?php echo $cargada ? '✅' : '❌'; ?> Extensión: <?php echo $ext; ?></strong>
                    </div>
                    <div class="file-status">
                        <span class="badge <?php echo $cargada ? 'badge-ok' : 'badge-error'; ?>"><?php echo $cargada ? 'CARGADA' : 'FALTANTE'; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Carpetas del Sistema -->
        <div class="summary">
            <h2>📁 Carpetas del Sistema</h2>
            <?php foreach ($carpetas_estado as $ruta => $info): ?>
                <?php
                if (!$info['existe']) {
                    $clase = 'file-error';
                    $icono = '❌';
                    $estado_txt = 'No existe';
                } elseif (!$info['escribible']) {
                    $clase = 'file-warning';
                    $icono = '⚠️';
                    $estado_txt = 'No escribible';
                } else {
                    $clase = 'file-ok';
                    $icono = '✅';
                    $estado_txt = 'OK';
                }
                ?>
                <div class="file-item <?php echo $clase; ?>">
                    <div class="file-info">
                        <strong><?php echo $icono; ?> <?php echo htmlspecialchars($info['descripcion']); ?></strong>
                        <small>Ruta: <?php echo htmlspecialchars($ruta); ?></small>
                    </div>
                    <div class="file-status">
                        <span class="badge <?php echo $clase === 'file-ok' ? 'badge-ok' : ($clase === 'file-warning' ? 'badge-warning' : 'badge-error'); ?>"><?php echo $estado_txt; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recomendaciones -->
        <div class="summary">
            <h2>🚀 Acciones Recomendadas</h2>
            <?php if ($archivos_error > 0): ?>
                <p style="margin-bottom:10px;"><strong class="status-error">⚠️ Se encontraron <?php echo $archivos_error; ?> archivos con problemas:</strong></p>
                <ul style="margin-left:20px; margin-bottom:15px;">
                <?php foreach ($resultados as $archivo): ?>
                    <?php if ($archivo['clase'] === 'file-error'): ?>
                        <li><strong><?php echo htmlspecialchars($archivo['descripcion']); ?></strong> (<?php echo htmlspecialchars($archivo['ruta']); ?>)</li>
                    <?php endif; ?>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($archivos_warning > 0): ?>
                <p style="margin-bottom:10px;"><strong class="status-warning">⚠️ Se encontraron <?php echo $archivos_warning; ?> advertencias:</strong></p>
                <ul style="margin-left:20px; margin-bottom:15px;">
                <?php foreach ($resultados as $archivo): ?>
                    <?php if ($archivo['clase'] === 'file-warning'): ?>
                        <li>Revisar permisos: <strong><?php echo htmlspecialchars($archivo['descripcion']); ?></strong></li>
                    <?php endif; ?>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($archivos_error === 0 && $archivos_warning === 0): ?>
                <p><strong class="status-ok">🎉 ¡Excelente! Todos los archivos están funcionando correctamente.</strong></p>
            <?php endif; ?>

            <?php if (!empty($tablas_faltantes)): ?>
                <p style="margin-top:10px;"><strong class="status-error">🗄️ Tablas faltantes en la base de datos:</strong></p>
                <ul style="margin-left:20px;">
                    <li>Ejecuta el script: <code>SQL/alter_tablas_simple.sql</code></li>
                    <li>Tablas faltantes: <?php echo implode(', ', $tablas_faltantes); ?></li>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Herramientas -->
        <div class="summary">
            <h2>🔧 Herramientas de Diagnóstico</h2>
            <div class="tools-grid">
                <a href="diagnosticar_db.php" class="tool-card" style="text-decoration:none; color:inherit;">
                    <div style="font-size:2em;">🗄️</div>
                    <h4>Diagnosticar DB</h4>
                    <p>Estado de tablas y registros</p>
                </a>
                <a href="diagnosticar_includes.php" class="tool-card" style="text-decoration:none; color:inherit;">
                    <div style="font-size:2em;">📋</div>
                    <h4>Diagnosticar Includes</h4>
                    <p>Verificación de dependencias</p>
                </a>
                <a href="diagnosticar.php?ajax=1" class="tool-card" style="text-decoration:none; color:inherit;">
                    <div style="font-size:2em;">📄</div>
                    <h4>Ver JSON</h4>
                    <p>Respuesta en formato JSON</p>
                </a>
                <a href="../DIRECCIONES/admin_usuarios.php" class="tool-card" style="text-decoration:none; color:inherit;">
                    <div style="font-size:2em;">🛡️</div>
                    <h4>Panel Admin</h4>
                    <p>Gestión de usuarios</p>
                </a>
            </div>
            <div style="margin-top:15px; text-align:center;">
                <button class="btn btn-primary" onclick="location.reload()">🔄 Actualizar Diagnóstico</button>
            </div>
        </div>

        <hr style="margin:30px 0; border:none; border-top:1px solid #ddd;">
        <p style="text-align:center; color:#888;"><small><em>Diagnóstico generado automáticamente el <?php echo date('Y-m-d H:i:s'); ?></em></small></p>
    </div>
</body>
</html>
