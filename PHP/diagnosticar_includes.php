<?php
/**
 * Diagnóstico Completo de Includes - Salud Juárez
 * Verifica qué archivos tienen navbar, footer, modals y otros componentes
 * Versión: 2.1.0
 * Fecha: 19 de Abril de 2026
 */

$esAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Directorios
$directorio_direcciones = '../DIRECCIONES';
$directorio_componentes = '../DIRECCIONES/componentes';
$directorio_php = '../PHP';
$directorio_js = '../JS';

// Archivos a verificar por directorio
$archivos_a_verificar = [
    'dashboard.php',
    'buscar_restaurantes.php',
    'mis_favoritos.php',
    'gestion_platillos.php',
    'inventario.php',
    'mis_restaurantes.php',
    'mapa_proveedores.php',
    'admin_usuarios.php',
    'perfil.php',
    'notificaciones.php',
    'dashboard_usuario.php',
    'ver_menu.php',
    'editar_perfil_restaurante.php',
    'proveedores_cercanos.php'
];

$componentes_a_verificar = [
    'modal_editar_restaurante.php',
    'modal_editar_usuario.php',
    'modal_desarrollador.php'
];

$archivos_php_a_verificar = [
    'db_config.php',
    'conexion.php',
    'header_meta.php',
    'navbar.php',
    'footer.php',
    'check_session.php',
    'actualizar_restaurante.php',
    'actualizar_usuario.php',
    'obtener_usuario_actual.php',
    'verificar_correo_unico.php'
];

$archivos_js_a_verificar = [
    'editar_restaurante.js',
    'editar_usuario.js',
    'emailjs_config.js',
    'session_check.js',
    'mapa_salud_juarez.js'
];

// Función para verificar includes en un archivo
function verificarIncludes($ruta_archivo) {
    if (!file_exists($ruta_archivo)) {
        return [
            'existe' => false,
            'navbar' => false,
            'footer' => false,
            'session_check' => false,
            'header_meta' => false,
            'emailjs' => false,
            'leaflet' => false,
            'tamano' => 0,
            'lineas' => 0
        ];
    }

    $contenido = file_get_contents($ruta_archivo);

    return [
        'existe' => true,
        'navbar' => strpos($contenido, 'navbar.php') !== false,
        'footer' => strpos($contenido, 'footer.php') !== false,
        'session_check' => strpos($contenido, 'session_check') !== false,
        'header_meta' => strpos($contenido, 'header_meta') !== false,
        'emailjs' => strpos($contenido, 'emailjs') !== false,
        'leaflet' => strpos($contenido, 'leaflet') !== false,
        'tamano' => filesize($ruta_archivo),
        'lineas' => count(file($ruta_archivo))
    ];
}

// Función para diagnosticar un directorio
function diagnosticarDirectorio($directorio, $archivos) {
    $resultados = [];
    $existentes = 0;
    $total = count($archivos);

    foreach ($archivos as $archivo) {
        $ruta = $directorio . '/' . $archivo;
        $info = verificarIncludes($ruta);
        $info['archivo'] = $archivo;
        $info['ruta'] = $ruta;
        if ($info['existe']) $existentes++;
        $resultados[] = $info;
    }

    return [
        'resultados' => $resultados,
        'existentes' => $existentes,
        'total' => $total,
        'porcentaje' => $total > 0 ? round(($existentes / $total) * 100, 1) : 0
    ];
}

// Ejecutar diagnósticos
$diag_direcciones = diagnosticarDirectorio($directorio_direcciones, $archivos_a_verificar);
$diag_componentes = diagnosticarDirectorio($directorio_componentes, $componentes_a_verificar);
$diag_php = diagnosticarDirectorio($directorio_php, $archivos_php_a_verificar);
$diag_js = diagnosticarDirectorio($directorio_js, $archivos_js_a_verificar);

// Si es AJAX, devolver JSON
if ($esAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'diagnostico' => [
            'direcciones' => $diag_direcciones,
            'componentes' => $diag_componentes,
            'php' => $diag_php,
            'js' => $diag_js
        ]
    ]);
    exit;
}

$secciones = [
    'direcciones' => ['titulo' => '🏠 Archivos DIRECCIONES', 'data' => $diag_direcciones],
    'componentes' => ['titulo' => '🧩 Componentes Modales', 'data' => $diag_componentes],
    'php' => ['titulo' => '⚙️ Archivos PHP', 'data' => $diag_php],
    'js' => ['titulo' => '📜 Archivos JavaScript', 'data' => $diag_js]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Includes - Salud Juárez</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: #333; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .summary { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; border: 1px solid #e9ecef; }
        .stat-number { font-size: 2.2em; font-weight: bold; }
        .stat-label { color: #666; margin-top: 5px; font-size: 0.9em; }
        .status-ok { color: #27ae60; }
        .status-warning { color: #f39c12; }
        .status-error { color: #e74c3c; }
        .file-item { background: white; padding: 15px 20px; margin-bottom: 8px; border-radius: 8px; border-left: 5px solid #ddd; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .file-ok { border-left-color: #27ae60; }
        .file-error { border-left-color: #e74c3c; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.75em; font-weight: 600; margin: 2px; }
        .badge-ok { background: #d4edda; color: #155724; }
        .badge-error { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .progress-bar { width: 100%; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; margin-top: 10px; }
        .progress-fill { height: 100%; border-radius: 4px; background: #3498db; transition: width 0.5s ease; }
        .btn { display: inline-block; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; color: white; font-weight: 600; margin: 5px; transition: all 0.2s; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        .btn-info { background: #3498db; }
        .btn-success { background: #27ae60; }
        .btn-primary { background: #9b59b6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Diagnóstico de Includes y Componentes</h1>
            <p>Sistema Salud Juárez - Verificación completa de archivos y dependencias</p>
            <small>Generado: <?php echo date('Y-m-d H:i:s'); ?></small>
        </div>

        <!-- Resumen General -->
        <div class="summary">
            <h2>📊 Resumen General</h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $diag_direcciones['existentes']; ?>/<?php echo $diag_direcciones['total']; ?></div>
                    <div class="stat-label">DIRECCIONES</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $diag_componentes['existentes']; ?>/<?php echo $diag_componentes['total']; ?></div>
                    <div class="stat-label">Componentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $diag_php['existentes']; ?>/<?php echo $diag_php['total']; ?></div>
                    <div class="stat-label">PHP</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $diag_js['existentes']; ?>/<?php echo $diag_js['total']; ?></div>
                    <div class="stat-label">JavaScript</div>
                </div>
            </div>
        </div>

        <!-- Secciones por directorio -->
        <?php foreach ($secciones as $key => $seccion): ?>
            <div class="summary">
                <h2><?php echo $seccion['titulo']; ?> (<?php echo $seccion['data']['porcentaje']; ?>%)</h2>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $seccion['data']['porcentaje']; ?>%"></div>
                </div>
                <?php foreach ($seccion['data']['resultados'] as $archivo): ?>
                    <div class="file-item <?php echo $archivo['existe'] ? 'file-ok' : 'file-error'; ?>" style="margin-top:8px;">
                        <div>
                            <strong><?php echo htmlspecialchars($archivo['archivo']); ?></strong>
                            <?php if (!$archivo['existe']): ?>
                                <span class="badge badge-error">NO EXISTE</span>
                            <?php else: ?>
                                <span class="badge badge-ok">OK</span>
                                <span class="badge badge-info"><?php echo $archivo['lineas']; ?> líneas</span>
                                <?php if ($archivo['navbar']): ?><span class="badge badge-ok">Navbar</span><?php endif; ?>
                                <?php if ($archivo['footer']): ?><span class="badge badge-ok">Footer</span><?php endif; ?>
                                <?php if ($archivo['session_check']): ?><span class="badge badge-info">Session</span><?php endif; ?>
                                <?php if ($archivo['emailjs']): ?><span class="badge badge-info">EmailJS</span><?php endif; ?>
                                <?php if ($archivo['leaflet']): ?><span class="badge badge-info">Leaflet</span><?php endif; ?>
                            <?php endif; ?>
                            <br>
                            <small style="color:#888;"><?php echo htmlspecialchars($archivo['ruta']); ?>
                            <?php if ($archivo['existe']): ?> | <?php echo round($archivo['tamano'] / 1024, 2); ?> KB<?php endif; ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <!-- Herramientas -->
        <div class="summary">
            <h2>🔧 Herramientas Adicionales</h2>
            <a href="diagnosticar_includes.php?ajax=1" class="btn btn-info">📄 Ver JSON</a>
            <a href="diagnosticar_db.php" class="btn btn-success">🗄️ Diagnosticar DB</a>
            <a href="diagnosticar.php" class="btn btn-primary">🔍 Diagnóstico General</a>
            <a href="../DIRECCIONES/admin_usuarios.php" class="btn btn-success">🛡️ Panel Admin</a>
            <button class="btn btn-info" onclick="location.reload()">🔄 Actualizar</button>
        </div>

        <hr style="margin:30px 0; border:none; border-top:1px solid #ddd;">
        <p style="text-align:center; color:#888;"><small><em>Diagnóstico generado automáticamente el <?php echo date('Y-m-d H:i:s'); ?></em></small></p>
    </div>
</body>
</html>
