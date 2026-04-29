<?php
// Log inmediato para confirmar ejecución
error_log("=== BACKEND EJECUTÁNDOSE registro_usuario.php ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("REQUEST_METHOD: " . $_SERVER["REQUEST_METHOD"]);

// Probar conexión básica primero
try {
    error_log("Intentando incluir db_config.php...");
    include 'db_config.php';
    error_log("db_config.php incluido exitosamente");
} catch (Exception $e) {
    error_log("ERROR en db_config.php: " . $e->getMessage());
    die("Error en conexión: " . $e->getMessage());
}

// Logging extensivo desde el inicio
error_log("=== INICIO PROCESAMIENTO BACKEND registro_usuario.php ===");
error_log("CONTENT_TYPE: " . ($_SERVER["CONTENT_TYPE"] ?? 'NO DEFINIDO'));
error_log("SCRIPT_NAME: " . $_SERVER["SCRIPT_NAME"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("=== MÉTODO POST DETECTADO ===");
    
    // Debug: Log de datos recibidos
    error_log("=== DATOS RECIBIDOS EN registro_usuario.php ===");
    error_log("POST completo: " . print_r($_POST, true));
    error_log("POST keys: " . implode(', ', array_keys($_POST)));
    error_log("POST count: " . count($_POST));
    
    // Log específico para campos de proveedor
    echo "<script>console.log('🔍 DEPURACIÓN POST:', {todos: " . json_encode($_POST) . ", nombre_empresa_raw: '" . ($_POST['nombre_empresa'] ?? 'NO_EXISTE') . "', id_tipo_proveedor_raw: '" . ($_POST['id_tipo_proveedor'] ?? 'NO_EXISTE') . "'});</script>";
    
    // 1. Recolección de Datos Base (actualizado para coincidir con formulario)
    $nick      = $_POST['username_usu'] ?? '';
    $correo    = $_POST['correo_usu'] ?? '';
    $telefono  = $_POST['telefono_usu'] ?? '';
    $pass      = $_POST['password_usu'] ?? '';
    $pass_conf = $_POST['confirmar_password'] ?? '';
    
    // Rol seleccionado (3 = Usuario Normal, 2 = Dueño, 4 = Proveedor)
    $id_rol    = $_POST['id_rol'] ?? 3; 

    // Debug: Log de contraseñas
    error_log("Contraseña recibida: " . $pass);
    error_log("Confirmar contraseña: " . $pass_conf);
    error_log("¿Son iguales?: " . ($pass === $pass_conf ? 'SÍ' : 'NO'));

    // Validar contraseñas
    if ($pass !== $pass_conf) {
        error_log("ERROR: Las contraseñas no coinciden en backend");
        echo "<script>alert('Las contraseñas no coinciden.'); window.history.back();</script>";
        exit();
    }
    
    error_log("Contraseñas válidas, continuando con el registro...");

    $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

    // Iniciamos transacción (Si algo falla, no se guarda nada a medias)
    $conn->begin_transaction();

    try {
        // TRIGGER 1: Verificación de Identidad
        // Esto detendrá el proceso y te mostrará qué ID de rol está recibiendo PHP realmente.
        // die("Debug: El rol recibido es: " . $id_rol); 
    
        /* PASO 1: CREAR CREDENCIALES */
        $stmt_usu = $conn->prepare("INSERT INTO usuarios (username_usu, correo_usu, password_usu, id_rol, telefono_usu) VALUES (?, ?, ?, ?, ?)");
        $stmt_usu->bind_param("sssis", $nick, $correo, $pass_hash, $id_rol, $telefono);
        
        if (!$stmt_usu->execute()) {
            throw new Exception("Error en Usuarios: " . $stmt_usu->error);
        }
        $id_generado = $stmt_usu->insert_id;
    
        /* PASO 2: PERFIL */
        // Usar el nick como nombre ya que no tenemos campos nombre/apellido
        $nombre_perfil = $nick;
        $stmt_per = $conn->prepare("INSERT INTO perfiles (id_usu, nombre_per, correo_per, cel_per) VALUES (?, ?, ?, ?)");
        $stmt_per->bind_param("isss", $id_generado, $nombre_perfil, $correo, $telefono);
        $stmt_per->execute();
    
        /* PASO 3: REGISTRO ESPECÍFICO POR ROL */
        
        if ($id_rol == 2) {
            // REGISTRO DE DUEÑO (RESTAURANTE)
            error_log("Registrando como Dueño (rol 2)");
            $nombre_res = $_POST['nombre_res'] ?? 'SIN NOMBRE';
            
            $stmt_res = $conn->prepare("INSERT INTO restaurante (id_usu, nombre_res, direccion_res, sector_res, telefono_res) VALUES (?, ?, ?, ?, ?)");
            $stmt_res->bind_param("issss", $id_generado, $nombre_res, $_POST['direccion_res'], $_POST['sector_res'], $_POST['telefono_res']);
            
            if (!$stmt_res->execute()) {
                throw new Exception("Culpable encontrado en Restaurante: " . $stmt_res->error);
            }
            
            $id_restaurante = $stmt_res->insert_id;
    
            // Categorías
            if (!empty($_POST['categorias'])) {
                $stmt_cat = $conn->prepare("INSERT INTO res_categorias (id_res, id_cat) VALUES (?, ?)");
                foreach ($_POST['categorias'] as $id_cat) {
                    $stmt_cat->bind_param("ii", $id_restaurante, $id_cat);
                    $stmt_cat->execute();
                }
            }
            
        } elseif ($id_rol == 4) {
            // REGISTRO DE PROVEEDOR - CONSOLE LOGS ESPECÍFICOS
            echo "<script>console.log('🔴 ENTRANDO A REGISTRO DE PROVEEDOR (ROL 4)');</script>";
            error_log("🔴 REGISTRO DE PROVEEDOR INICIADO (ROL 4)");
            
            // Obtener datos específicos de proveedor (actualizado para nueva estructura con IDs)
            echo "<script>console.log('📥 RECOPILANDO DATOS DE PROVEEDOR...');</script>";
            error_log("📥 RECOPILANDO DATOS DE PROVEEDOR");
            
            // Depuración específica para id_tipo_proveedor
            echo "<script>console.log('🔍 VERIFICANDO id_tipo_proveedor en POST:', {existe: " . (array_key_exists('id_tipo_proveedor', $_POST) ? 'true' : 'false') . ", valor: '" . ($_POST['id_tipo_proveedor'] ?? 'NO_EXISTE') . "', tipo: '" . gettype($_POST['id_tipo_proveedor'] ?? null) . "'});</script>";
            error_log("🔍 VERIFICACIÓN id_tipo_proveedor:");
            error_log("  - array_key_exists: " . (array_key_exists('id_tipo_proveedor', $_POST) ? 'SÍ' : 'NO'));
            error_log("  - valor_raw: '" . ($_POST['id_tipo_proveedor'] ?? 'NO_EXISTE') . "'");
            error_log("  - valor_después_de_???: '" . ($_POST['id_tipo_proveedor'] ?? 1) . "'");
            
            $nombre_empresa = $_POST['nombre_empresa'] ?? '';
            
            // Manejo especial para id_tipo_proveedor
            $id_tipo_proveedor_raw = $_POST['id_tipo_proveedor'] ?? '';
            echo "<script>console.log('🔍 id_tipo_proveedor_raw ANTES de procesar: \"" . $id_tipo_proveedor_raw . "\"');</script>";
            
            // Si está vacío o es string vacío, asignar valor por defecto
            if (empty($id_tipo_proveedor_raw) || $id_tipo_proveedor_raw === '') {
                $id_tipo_proveedor = 1; // Default: Alimentos
                echo "<script>console.log('⚠️ id_tipo_proveedor vacío, asignando valor por defecto: 1');</script>";
                error_log("⚠️ id_tipo_proveedor vacío, asignando valor por defecto: 1");
            } else {
                $id_tipo_proveedor = (int)$id_tipo_proveedor_raw;
                echo "<script>console.log('✅ id_tipo_proveedor convertido a entero: ' + $id_tipo_proveedor);</script>";
                error_log("✅ id_tipo_proveedor convertido a entero: " . $id_tipo_proveedor);
            }
            
            $direccion_proveedor = $_POST['direccion_proveedor'] ?? '';
            $colonia_proveedor = $_POST['colonia_proveedor'] ?? '';
            $ciudad_proveedor = $_POST['ciudad_proveedor'] ?? 'Ciudad Juárez';
            $id_estado_proveedor = $_POST['id_estado_proveedor'] ?? 6; // Default: Chihuahua
            $codigo_postal_proveedor = $_POST['codigo_postal_proveedor'] ?? '';
            
            echo "<script>console.log('📋 DATOS RECIBIDOS:', {nombre_empresa: '" . $nombre_empresa . "', id_tipo_proveedor: '" . $id_tipo_proveedor . "', direccion: '" . $direccion_proveedor . "'});</script>";
            error_log("📋 DATOS DE PROVEEDOR RECIBIDOS:");
            error_log("  - nombre_empresa: '" . $nombre_empresa . "'");
            error_log("  - id_tipo_proveedor: '" . $id_tipo_proveedor . "'");
            error_log("  - direccion_proveedor: '" . $direccion_proveedor . "'");
            error_log("  - colonia_proveedor: '" . $colonia_proveedor . "'");
            error_log("  - ciudad_proveedor: '" . $ciudad_proveedor . "'");
            error_log("  - id_estado_proveedor: '" . $id_estado_proveedor . "'");
            error_log("  - codigo_postal_proveedor: '" . $codigo_postal_proveedor . "'");
            
            // Validar campos requeridos para proveedor
            echo "<script>console.log('✅ VALIDANDO CAMPOS REQUERIDOS...');</script>";
            error_log("✅ INICIANDO VALIDACIÓN DE CAMPOS");
            
            $campos_vacios = [];
            if (empty($nombre_empresa)) $campos_vacios[] = 'nombre_empresa';
            if (empty($id_tipo_proveedor)) $campos_vacios[] = 'id_tipo_proveedor';
            if (empty($direccion_proveedor)) $campos_vacios[] = 'direccion_proveedor';
            if (empty($colonia_proveedor)) $campos_vacios[] = 'colonia_proveedor';
            
            if (!empty($campos_vacios)) {
                echo "<script>console.error('❌ CAMPOS VACÍOS:', " . json_encode($campos_vacios) . ");</script>";
                error_log("❌ ERROR: Campos vacíos detectados: " . implode(', ', $campos_vacios));
                throw new Exception("Faltan datos obligatorios del proveedor: " . implode(', ', $campos_vacios));
            }
            
            echo "<script>console.log('✅ VALIDACIÓN DE CAMPOS COMPLETADA');</script>";
            error_log("✅ VALIDACIÓN DE CAMPOS EXITOSA");
            
            // Preparar INSERT de proveedores
            echo "<script>console.log('💾 PREPARANDO INSERT EN TABLA PROVEEDORES...');</script>";
            error_log("💾 PREPARANDO INSERT EN TABLA PROVEEDORES");
            
            $stmt_proveedor = $conn->prepare("
                INSERT INTO proveedores (
                    id_usu, nombre_empresa, nombre_contacto, telefono_proveedor, email_proveedor,
                    id_tipo_proveedor, especialidad, direccion_proveedor, colonia_proveedor, ciudad_proveedor,
                    id_estado_proveedor, pais_proveedor, codigo_postal_proveedor
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $nombre_contacto = $nick; // Usar nick como nombre de contacto
            $especialidad = 'General'; // Valor por defecto
            $pais_proveedor = 'México'; // Valor por defecto
            
            echo "<script>console.log('🔗 BINDEANDO PARÁMETROS...');</script>";
            error_log("🔗 BINDEANDO PARÁMETROS PARA INSERT");
            error_log("Parámetros a vincular: id_generado=$id_generado, nombre_empresa=$nombre_empresa, nombre_contacto=$nombre_contacto, telefono=$telefono, correo=$correo, id_tipo_proveedor=$id_tipo_proveedor, especialidad=$especialidad, direccion=$direccion_proveedor, colonia=$colonia_proveedor, ciudad=$ciudad_proveedor, id_estado=$id_estado_proveedor, pais=$pais_proveedor, cp=$codigo_postal_proveedor");
            
            $stmt_proveedor->bind_param(
                "issssssssssss",
                $id_generado, $nombre_empresa, $nombre_contacto, $telefono, $correo,
                $id_tipo_proveedor, $especialidad, $direccion_proveedor, $colonia_proveedor, $ciudad_proveedor,
                $id_estado_proveedor, $pais_proveedor, $codigo_postal_proveedor
            );
            
            echo "<script>console.log('🚀 EJECUTANDO INSERT DE PROVEEDOR...');</script>";
            error_log("🚀 EJECUTANDO INSERT DE PROVEEDOR");
            
            if (!$stmt_proveedor->execute()) {
                echo "<script>console.error('❌ ERROR EN INSERT:', '" . $stmt_proveedor->error . "');</script>";
                error_log("❌ ERROR AL REGISTRAR PROVEEDOR: " . $stmt_proveedor->error);
                throw new Exception("Error al registrar proveedor: " . $stmt_proveedor->error);
            }
            
            echo "<script>console.log('✅ PROVEEDOR REGISTRADO EXITOSAMENTE');</script>";
            error_log("✅ PROVEEDOR REGISTRADO EXITOSAMENTE: $nombre_empresa");
            
        } else {
            // REGISTRO DE USUARIO NORMAL (rol 3)
            error_log("Registrando como Usuario Normal (rol 3)");
        }
    
        $conn->commit();
        error_log("Transacción completada exitosamente. ID generado: $id_generado");
        error_log("Preparando respuesta para el usuario...");
        
        // Respuesta exitosa
        $mensaje_exito = ($id_rol == 4) 
            ? "Proveedor registrado exitosamente. Tu cuenta será revisada por un administrador antes de ser activada."
            : "Usuario registrado exitosamente. Revisa tu correo para verificar tu cuenta.";
            
        echo "<script>alert('{$mensaje_exito} (ID: $id_generado)'); window.location.href='../login.php';</script>";
        error_log("Respuesta enviada al usuario");

    } catch (Exception $e) {
        $conn->rollback();
        // ALERT FINAL: Este te dirá el error exacto de SQL si algo falla internamente.
        die("🚨 Culpable capturado: " . $e->getMessage());
    }
}

// Log de salida para confirmar que el script termina
error_log("=== FIN DEL SCRIPT registro_usuario.php ===");
?>