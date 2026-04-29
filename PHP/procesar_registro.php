<?php
/**
 * Procesador de Registro de Usuarios - Salud Juárez
 * Maneja el registro de nuevos usuarios en el sistema
 * Versión: 1.0.0
 * Fecha: 2026-03-25
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Incluir configuración
require_once 'db_config.php';

try {
    // Conexión a la base de datos
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    // Establecer charset
    $conn->set_charset("utf8mb4");
    
    // Obtener acción solicitada
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {
        case 'registrar_usuario':
            echo json_encode(registrarUsuario($conn));
            break;
            
        case 'verificar_email':
            echo json_encode(verificarEmail($conn));
            break;
            
        case 'verificar_usuario':
            echo json_encode(verificarUsuario($conn));
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * Registrar nuevo usuario
 */
function registrarUsuario($conn) {
    try {
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Obtener y sanitizar datos
        $nick = trim($_POST['username_usu'] ?? '');
        $email = trim($_POST['correo_usu'] ?? '');
        $password = $_POST['password_usu'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';
        $id_rol = intval($_POST['id_rol'] ?? 3); // Por defecto: cliente
        $telefono = trim($_POST['telefono_usu'] ?? '');
        
        // Debug: Log de contraseñas recibidas
        error_log("Contraseña recibida: " . $password);
        error_log("Confirmar contraseña: " . $confirmar_password);
        error_log("¿Son iguales?: " . ($password === $confirmar_password ? 'SÍ' : 'NO'));
        
        // Validaciones básicas
        if (empty($nick) || empty($email) || empty($password)) {
            throw new Exception('Todos los campos obligatorios deben ser llenados');
        }
        
        // Validar formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El formato del correo electrónico no es válido');
        }
        
        // Validar longitud de contraseña
        if (strlen($password) < 8) {
            throw new Exception('La contraseña debe tener al menos 8 caracteres');
        }
        
        // Validar que las contraseñas coincidan (comparación de texto plano)
        if ($password !== $confirmar_password) {
            error_log("ERROR: Las contraseñas no coinciden - Password: '$password', Confirmar: '$confirmar_password'");
            throw new Exception('Las contraseñas no coinciden');
        }
        
        // Solo después de validar que coinciden, hacer el hash
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        error_log("Hash generado: " . $password_hash);
        
        // Verificar si el email ya existe
        $stmt_email = $conn->prepare("SELECT id_usu FROM usuarios WHERE email_usu = ?");
        $stmt_email->bind_param("s", $email);
        $stmt_email->execute();
        if ($stmt_email->get_result()->num_rows > 0) {
            throw new Exception('El correo electrónico ya está registrado');
        }
        $stmt_email->close();
        
        // Verificar si el nick ya existe
        $stmt_nick = $conn->prepare("SELECT id_usu FROM usuarios WHERE nick = ?");
        $stmt_nick->bind_param("s", $nick);
        $stmt_nick->execute();
        if ($stmt_nick->get_result()->num_rows > 0) {
            throw new Exception('El nombre de usuario ya está en uso');
        }
        $stmt_nick->close();
        
        // Validar que el rol exista
        $stmt_rol = $conn->prepare("SELECT id_rol FROM roles WHERE id_rol = ?");
        $stmt_rol->bind_param("i", $id_rol);
        $stmt_rol->execute();
        if ($stmt_rol->get_result()->num_rows === 0) {
            throw new Exception('El rol seleccionado no es válido');
        }
        $stmt_rol->close();
        
        // Validaciones específicas para Proveedor (rol 4)
        if ($id_rol == 4) {
            $nombre_empresa = trim($_POST['nombre_empresa'] ?? '');
            $tipo_proveedor = trim($_POST['tipo_proveedor'] ?? '');
            $direccion_proveedor = trim($_POST['direccion_proveedor'] ?? '');
            $colonia_proveedor = trim($_POST['colonia_proveedor'] ?? '');
            $ciudad_proveedor = trim($_POST['ciudad_proveedor'] ?? '');
            $estado_proveedor = trim($_POST['estado_proveedor'] ?? '');
            $codigo_postal_proveedor = trim($_POST['codigo_postal_proveedor'] ?? '');
            
            if (empty($nombre_empresa) || empty($tipo_proveedor) || empty($direccion_proveedor) || empty($colonia_proveedor)) {
                throw new Exception('Todos los campos del proveedor son obligatorios');
            }
        }
        
        // Hash de contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Generar token de verificación
        $token_verificacion = bin2hex(random_bytes(32));
        
        // Insertar nuevo usuario
        $stmt_insert = $conn->prepare("
            INSERT INTO usuarios (
                nick, email_usu, password_usu, 
                id_rol, telefono_usu, token_verificacion, estatus_usu, 
                fecha_registro, fecha_actualizacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        
        $stmt_insert->bind_param(
            "sssiis", 
            $nick, $email, $password_hash, 
            $id_rol, $telefono, $token_verificacion
        );
        
        if (!$stmt_insert->execute()) {
            throw new Exception("Error al registrar usuario: " . $stmt_insert->error);
        }
        
        $id_usuario = $conn->insert_id;
        $stmt_insert->close();
        
        // Si es Proveedor (rol 4), insertar en tabla proveedores
        if ($id_rol == 4) {
            $stmt_proveedor = $conn->prepare("
                INSERT INTO proveedores (
                    id_usu, nombre_empresa, nombre_contacto, telefono_proveedor, email_proveedor,
                    tipo_proveedor, direccion_proveedor, colonia_proveedor, ciudad_proveedor,
                    estado_proveedor, pais_proveedor, codigo_postal_proveedor, estatus_proveedor,
                    validado_admin, fecha_registro_proveedor
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'México', ?, 1, 0, NOW())
            ");
            
            $nombre_contacto = $nick; // Usar el nombre de usuario como contacto
            
            $stmt_proveedor->bind_param(
                "issssssssi",
                $id_usuario, $nombre_empresa, $nombre_contacto, $telefono, $email,
                $tipo_proveedor, $direccion_proveedor, $colonia_proveedor, $ciudad_proveedor,
                $estado_proveedor, $codigo_postal_proveedor
            );
            
            if (!$stmt_proveedor->execute()) {
                throw new Exception("Error al registrar proveedor: " . $stmt_proveedor->error);
            }
            
            $stmt_proveedor->close();
            
            error_log("PROVEEDOR REGISTRADO: $nombre_empresa (ID: $id_usuario) - Esperando validación admin");
        }
        
        // Confirmar transacción
        $conn->commit();
        
        // Enviar email de verificación (simulado)
        enviarEmailVerificacion($email, $nombre, $token_verificacion);
        
        $mensaje_exito = ($id_rol == 4) 
            ? 'Proveedor registrado exitosamente. Tu cuenta será revisada por un administrador antes de ser activada.'
            : 'Usuario registrado exitosamente. Revisa tu correo para verificar tu cuenta.';
        
        return [
            'success' => true,
            'message' => $mensaje_exito,
            'data' => [
                'id_usuario' => $id_usuario,
                'nick' => $nick,
                'email' => $email,
                'rol' => $id_rol,
                'nombre_empresa' => $nombre_empresa ?? null
            ]
        ];
        
    } catch (Exception $e) {
        // Revertir transacción si falla
        if (isset($conn) && $conn->ping($conn)) {
            $conn->rollback();
        }
        
        error_log("Error en registrarUsuario: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al registrar el usuario. Por favor, intenta nuevamente.'
        ];
    }
}

/**
 * Verificar si email existe
 */
function verificarEmail($conn) {
    try {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            return ['success' => false, 'message' => 'Email requerido'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        $stmt = $conn->prepare("SELECT id_usu FROM usuarios WHERE email_usu = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        return [
            'success' => true,
            'existe' => $existe,
            'message' => $existe ? 'Email ya registrado' : 'Email disponible'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al verificar email'];
    }
}

/**
 * Verificar si usuario existe
 */
function verificarUsuario($conn) {
    try {
        $nick = trim($_POST['nick'] ?? '');
        
        if (empty($nick)) {
            return ['success' => false, 'message' => 'Usuario requerido'];
        }
        
        if (strlen($nick) < 3) {
            return ['success' => false, 'message' => 'Usuario debe tener al menos 3 caracteres'];
        }
        
        // Validar formato del nick (solo letras, números y guiones bajos)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $nick)) {
            return ['success' => false, 'message' => 'Usuario solo puede contener letras, números y guiones bajos'];
        }
        
        $stmt = $conn->prepare("SELECT id_usu FROM usuarios WHERE nick = ?");
        $stmt->bind_param("s", $nick);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        return [
            'success' => true,
            'existe' => $existe,
            'message' => $existe ? 'Usuario ya existe' : 'Usuario disponible'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error al verificar usuario'];
    }
}

/**
 * Enviar email de verificación (simulado)
 */
function enviarEmailVerificacion($email, $nombre, $token) {
    // En un entorno real, aquí se enviaría el email
    // Por ahora, solo registramos en el log
    $asunto = "Verifica tu cuenta en Salud Juárez";
    $mensaje = "
        <h2>¡Bienvenido a Salud Juárez, $nombre!</h2>
        <p>Gracias por registrarte en nuestra plataforma de restaurantes saludables.</p>
        <p>Para activar tu cuenta, haz clic en el siguiente enlace:</p>
        <p><a href='https://tudominio.com/verificar_email.php?token=$token'>Activar Cuenta</a></p>
        <p>Si no creaste esta cuenta, simplemente ignora este mensaje.</p>
        <p>Saludos,<br>El equipo de Salud Juárez</p>
    ";
    
    // Log para desarrollo
    error_log("Email de verificación para $email: Token = $token");
    
    return true;
}

/**
 * Validar datos de entrada
 */
function validarDatosRegistro($datos) {
    $errores = [];
    
    // Validar nombre
    if (empty($datos['nombre'])) {
        $errores[] = 'El nombre es obligatorio';
    } elseif (strlen($datos['nombre']) < 2) {
        $errores[] = 'El nombre debe tener al menos 2 caracteres';
    }
    
    // Validar apellido
    if (empty($datos['apellido'])) {
        $errores[] = 'El apellido es obligatorio';
    } elseif (strlen($datos['apellido']) < 2) {
        $errores[] = 'El apellido debe tener al menos 2 caracteres';
    }
    
    // Validar nick
    if (empty($datos['nick'])) {
        $errores[] = 'El nombre de usuario es obligatorio';
    } elseif (strlen($datos['nick']) < 3) {
        $errores[] = 'El nombre de usuario debe tener al menos 3 caracteres';
    }
    
    // Validar email
    if (empty($datos['email'])) {
        $errores[] = 'El email es obligatorio';
    } elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El formato del email no es válido';
    }
    
    // Validar contraseña
    if (empty($datos['password'])) {
        $errores[] = 'La contraseña es obligatoria';
    } elseif (strlen($datos['password']) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres';
    }
    
    // Validar confirmación de contraseña
    if (!empty($datos['password']) && !empty($datos['password_confirm'])) {
        if ($datos['password'] !== $datos['password_confirm']) {
            $errores[] = 'Las contraseñas no coinciden';
        }
    }
    
    return $errores;
}
?>
