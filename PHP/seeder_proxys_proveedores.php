<?php
/**
 * =========================================================
 * SEEDER DE PROVEEDORES PROXY - PRUEBAS DE MAPAS B2B
 * =========================================================
 * Genera proveedores de prueba con ubicaciones estratégicas
 * para validar la lógica de "Cercanos vs Lejanos" en el mapa.
 * 
 * Fase Final: Integración EmailJS y Datos Proxy (Mapas B2B)
 * 
 * Ubicaciones:
 * - GRUPO 1: Cd. Juárez (Cercanos) - 0-30km
 * - GRUPO 2: Seattle (Lejanos) - ~2,500km  
 * - GRUPO 3: Honduras (Muy Lejanos) - ~3,500km
 */

// Configuración de base de datos
require_once '../PHP/db_config.php';

// Activar visualización de errores para debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Respuesta en formato texto plano para CLI/HTTP
header('Content-Type: text/plain; charset=utf-8');

echo "=================================================================\n";
echo "   SEEDER DE PROVEEDORES PROXY - MAPAS B2B\n";
echo "   Salud Juárez - Testing de Proximidad Geográfica\n";
echo "=================================================================\n\n";

// Verificar conexión
if ($conn->connect_error) {
    die("❌ Error de conexión: " . $conn->connect_error . "\n");
}

echo "✅ Conexión a base de datos establecida\n\n";

// =================================================================
// CONFIGURACIÓN DE PROVEEDORES DE PRUEBA
// =================================================================

$proveedores_proxy = [
    // GRUPO 1: CERCANOS (Cd. Juárez) - Radio 0-30km
    'cercanos' => [
        [
            'username' => 'proveedor_cercano_1',
            'email' => 'cercano1@test.com',
            'empresa' => 'Proveedor Cercano Juárez Norte',
            'contacto' => 'Juan Pérez',
            'telefono' => '+52 (656) 111-0001',
            'direccion' => 'Av. Tecnológico #1500',
            'colonia' => 'Partido Iglesias',
            'ciudad' => 'Ciudad Juárez',
            'latitud' => 31.7200,
            'longitud' => -106.4100,
            'descripcion' => 'Proveedor de prueba - Grupo Cercano (Norte)'
        ],
        [
            'username' => 'proveedor_cercano_2',
            'email' => 'cercano2@test.com',
            'empresa' => 'Proveedor Cercano Juárez Sur',
            'contacto' => 'María López',
            'telefono' => '+52 (656) 111-0002',
            'direccion' => 'Blvd. Zaragoza #3200',
            'colonia' => 'Zaragoza',
            'ciudad' => 'Ciudad Juárez',
            'latitud' => 31.6800,
            'longitud' => -106.4500,
            'descripcion' => 'Proveedor de prueba - Grupo Cercano (Sur)'
        ],
        [
            'username' => 'proveedor_cercano_3',
            'email' => 'cercano3@test.com',
            'empresa' => 'Proveedor Cercano Juárez Centro',
            'contacto' => 'Carlos Ramírez',
            'telefono' => '+52 (656) 111-0003',
            'direccion' => 'Calle 16 de Septiembre #450',
            'colonia' => 'Centro',
            'ciudad' => 'Ciudad Juárez',
            'latitud' => 31.7386,
            'longitud' => -106.4844,
            'descripcion' => 'Proveedor de prueba - Grupo Cercano (Centro)'
        ]
    ],
    
    // GRUPO 2: LEJANOS (Seattle) - ~2,500km
    'lejanos' => [
        [
            'username' => 'proveedor_seattle_1',
            'email' => 'seattle1@test.com',
            'empresa' => 'Pacific Northwest Supplies',
            'contacto' => 'John Smith',
            'telefono' => '+1 (206) 555-0101',
            'direccion' => '1500 Pike Street',
            'colonia' => 'Downtown',
            'ciudad' => 'Seattle',
            'latitud' => 47.6100,
            'longitud' => -122.3400,
            'descripcion' => 'Proveedor de prueba - Grupo Lejano (Seattle Central)'
        ],
        [
            'username' => 'proveedor_seattle_2',
            'email' => 'seattle2@test.com',
            'empresa' => 'Seattle Gourmet Imports',
            'contacto' => 'Emma Wilson',
            'telefono' => '+1 (206) 555-0102',
            'direccion' => '3200 Ballard Ave NW',
            'colonia' => 'Ballard',
            'ciudad' => 'Seattle',
            'latitud' => 47.6700,
            'longitud' => -122.3900,
            'descripcion' => 'Proveedor de prueba - Grupo Lejano (Seattle Ballard)'
        ]
    ],
    
    // GRUPO 3: MUY LEJANOS (Honduras) - ~3,500km
    'muy_lejanos' => [
        [
            'username' => 'proveedor_honduras_1',
            'email' => 'honduras1@test.com',
            'empresa' => 'Distribuidores Centroamericanos SA',
            'contacto' => 'Roberto Hernández',
            'telefono' => '+504 2555-0101',
            'direccion' => 'Blvd. Suyapa #1250',
            'colonia' => 'Col. Universidad',
            'ciudad' => 'Tegucigalpa',
            'latitud' => 15.5000,
            'longitud' => -88.0000,
            'descripcion' => 'Proveedor de prueba - Grupo Muy Lejano (Tegucigalpa)'
        ],
        [
            'username' => 'proveedor_honduras_2',
            'email' => 'honduras2@test.com',
            'empresa' => 'Importaciones del Caribe',
            'contacto' => 'Ana Castillo',
            'telefono' => '+504 2555-0102',
            'direccion' => 'Av. Circunvalación #890',
            'colonia' => 'Barrio La Granja',
            'ciudad' => 'San Pedro Sula',
            'latitud' => 15.5257,
            'longitud' => -88.0320,
            'descripcion' => 'Proveedor de prueba - Grupo Muy Lejano (San Pedro Sula)'
        ]
    ]
];

// Password hasheada para todos los usuarios de prueba (password: 'test123')
$password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Contadores
$usuarios_creados = 0;
$proveedores_creados = 0;
$productos_creados = 0;
$errores = [];

// Productos de muestra para cada proveedor proxy
$productos_muestra = [
    ['nombre' => 'Cajas de Cartón (50x50cm)', 'descripcion' => 'Cajas resistentes para transporte', 'categoria' => 'Empaque', 'unidad' => 'pieza', 'precio' => 15.50],
    ['nombre' => 'Bolsas de Papel Kraft', 'descripcion' => 'Bolsas ecológicas para delivery', 'categoria' => 'Empaque', 'unidad' => 'paquete', 'precio' => 45.00],
    ['nombre' => 'Servilletas de Lino', 'descripcion' => 'Servilletas de alta calidad', 'categoria' => 'Mesa', 'unidad' => 'docena', 'precio' => 120.00],
    ['nombre' => 'Aceite Vegetal (5L)', 'descripcion' => 'Aceite para cocina industrial', 'categoria' => 'Cocina', 'unidad' => 'garrafón', 'precio' => 85.00],
    ['nombre' => 'Sal de Mar (1kg)', 'descripcion' => 'Sal marina orgánica', 'categoria' => 'Condimentos', 'unidad' => 'bolsa', 'precio' => 25.00],
];

// =================================================================
// FUNCIÓN PARA CREAR USUARIO Y PROVEEDOR
// =================================================================

function crearProveedorProxy($conn, $datos, $password_hash, $grupo, $productos_muestra) {
    $resultado = ['success' => false, 'mensaje' => '', 'id_usu' => null, 'id_proveedor' => null, 'productos_creados' => 0];
    
    try {
        // Determinar estado según grupo
        $id_estado = 6; // Chihuahua por defecto
        $pais = 'México';
        
        if ($grupo === 'lejanos') {
            $id_estado = 1; // Washington state (usaremos 1 como código genérico)
            $pais = 'Estados Unidos';
        } elseif ($grupo === 'muy_lejanos') {
            $id_estado = 99; // Honduras (código personalizado)
            $pais = 'Honduras';
        }
        
        // 1. CREAR USUARIO CON ROL 4 (PROVEEDOR)
        $stmt_usu = $conn->prepare("
            INSERT INTO usuarios 
            (username_usu, correo_usu, password_usu, id_rol, telefono_usu, estatus_usu)
            VALUES (?, ?, ?, 4, ?, 1)
        ");
        
        $stmt_usu->bind_param("ssss", 
            $datos['username'], 
            $datos['email'], 
            $password_hash,
            $datos['telefono']
        );
        
        if (!$stmt_usu->execute()) {
            throw new Exception("Error creando usuario: " . $stmt_usu->error);
        }
        
        $id_usu = $conn->insert_id;
        $resultado['id_usu'] = $id_usu;
        
        // 2. CREAR REGISTRO EN TABLA PROVEEDORES
        $stmt_prov = $conn->prepare("
            INSERT INTO proveedores 
            (id_usu, nombre_empresa, nombre_contacto, telefono_proveedor, email_proveedor, 
             direccion_proveedor, colonia_proveedor, ciudad_proveedor, id_estado_proveedor, 
             pais_proveedor, latitud_proveedor, longitud_proveedor, 
             estado_visibilidad, validado_admin, descripcion_destacada,
             especialidad)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', 1, ?, ?)
        ");
        
        $stmt_prov->bind_param("issssssisiddsss", 
            $id_usu,
            $datos['empresa'],
            $datos['contacto'],
            $datos['telefono'],
            $datos['email'],
            $datos['direccion'],
            $datos['colonia'],
            $datos['ciudad'],
            $id_estado,
            $pais,
            $datos['latitud'],
            $datos['longitud'],
            $datos['descripcion'],
            $datos['descripcion']
        );
        
        if (!$stmt_prov->execute()) {
            throw new Exception("Error creando proveedor: " . $stmt_prov->error);
        }
        
        $id_proveedor = $conn->insert_id;
        $resultado['id_proveedor'] = $id_proveedor;
        
        // 3. CREAR PRODUCTOS DE PRUEBA PARA ESTE PROVEEDOR
        $productos_creados = 0;
        $stmt_prod = $conn->prepare("
            INSERT INTO productos_proveedor 
            (id_proveedor, nombre_producto, descripcion_producto, categoria_producto, 
             unidad_medida, precio_unitario, precio_mayoreo, cantidad_minima_mayoreo, 
             disponibilidad, especificaciones, fecha_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())
        ");
        
        foreach ($productos_muestra as $prod) {
            $precio_mayoreo = $prod['precio'] * 0.85; // 15% descuento
            $cantidad_minima = rand(10, 50);
            $especificaciones = 'Producto de prueba para testing B2B. Proveedor: ' . $datos['empresa'];
            
            $stmt_prod->bind_param("issssddds", 
                $id_proveedor,
                $prod['nombre'],
                $prod['descripcion'],
                $prod['categoria'],
                $prod['unidad'],
                $prod['precio'],
                $precio_mayoreo,
                $cantidad_minima,
                $especificaciones
            );
            
            if ($stmt_prod->execute()) {
                $productos_creados++;
            }
        }
        
        $resultado['productos_creados'] = $productos_creados;
        $resultado['success'] = true;
        $resultado['mensaje'] = "Creado exitosamente con $productos_creados productos";
        
    } catch (Exception $e) {
        $resultado['mensaje'] = $e->getMessage();
    }
    
    return $resultado;
}

// =================================================================
// PROCESAR CREACIÓN DE PROVEEDORES
// =================================================================

foreach ($proveedores_proxy as $grupo => $proveedores) {
    $grupo_nombre = strtoupper($grupo);
    echo "-----------------------------------------------------------------\n";
    echo "GRUPO: $grupo_nombre\n";
    echo "-----------------------------------------------------------------\n";
    
    foreach ($proveedores as $proveedor) {
        echo "Creando: {$proveedor['empresa']}... ";
        
        $resultado = crearProveedorProxy($conn, $proveedor, $password_hash, $grupo, $productos_muestra);
        
        if ($resultado['success']) {
            echo "✅ OK\n";
            echo "   ID Usuario: {$resultado['id_usu']}\n";
            echo "   ID Proveedor: {$resultado['id_proveedor']}\n";
            echo "   Productos: {$resultado['productos_creados']}\n";
            echo "   Ubicación: {$proveedor['latitud']}, {$proveedor['longitud']}\n";
            $usuarios_creados++;
            $proveedores_creados++;
            $productos_creados += $resultado['productos_creados'];
        } else {
            echo "❌ ERROR\n";
            echo "   {$resultado['mensaje']}\n";
            $errores[] = $proveedor['empresa'] . ": " . $resultado['mensaje'];
        }
        echo "\n";
    }
}

// =================================================================
// RESUMEN FINAL
// =================================================================

echo "=================================================================\n";
echo "   RESUMEN DE EJECUCIÓN\n";
echo "=================================================================\n";
echo "Usuarios creados (rol 4): $usuarios_creados\n";
echo "Proveedores creados: $proveedores_creados\n";
echo "Productos creados: $productos_creados\n";
echo "Errores: " . count($errores) . "\n";

if (count($errores) > 0) {
    echo "\nDetalle de errores:\n";
    foreach ($errores as $error) {
        echo "   - $error\n";
    }
}

echo "\n=================================================================\n";
echo "   DATOS DE PRUEBA GENERADOS\n";
echo "=================================================================\n";
echo "\nCoordenadas disponibles para testing:\n\n";

echo "GRUPO 1 - CERCANOS (Cd. Juárez, 0-30km):\n";
echo "   Lat: 31.7200, Lng: -106.4100 (Norte)\n";
echo "   Lat: 31.6800, Lng: -106.4500 (Sur)\n";
echo "   Lat: 31.7386, Lng: -106.4844 (Centro)\n\n";

echo "GRUPO 2 - LEJANOS (Seattle, ~2,500km):\n";
echo "   Lat: 47.6100, Lng: -122.3400 (Downtown)\n";
echo "   Lat: 47.6700, Lng: -122.3900 (Ballard)\n\n";

echo "GRUPO 3 - MUY LEJANOS (Honduras, ~3,500km):\n";
echo "   Lat: 15.5000, Lng: -88.0000 (Tegucigalpa)\n";
echo "   Lat: 15.5257, Lng: -88.0320 (San Pedro Sula)\n\n";

echo "=================================================================\n";
echo "   NOTAS IMPORTANTES\n";
echo "=================================================================\n";
echo "- Todos los usuarios tienen password: 'test123'\n";
echo "- Todos los proveedores tienen estado_visibilidad = 'activo'\n";
echo "- Todos están validados (validado_admin = 1)\n";
echo "- Cada proveedor tiene 5 productos con disponibilidad = 1\n";
echo "- Usar estos IDs para probar la lógica de proximidad en mapas\n";
echo "- Usar solicitar_pedido_proveedor.php para probar pedidos B2B\n";
echo "=================================================================\n";

$conn->close();
?>
