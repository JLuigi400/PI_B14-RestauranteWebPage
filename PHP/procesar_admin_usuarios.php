<?php
/**
 * Procesamiento de Administración de Usuarios - Salud Juárez
 * Manejo de acciones administrativas sobre usuarios
 */

session_start();
include 'db_config.php';

header('Content-Type: application/json');

// Verificar sesión y rol de administrador
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id_admin = $_SESSION['id_usu'];

// Función principal de enrutamiento
$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'cambiar_estatus':
        cambiarEstatusUsuario();
        break;
        
    case 'eliminar':
        eliminarUsuario();
        break;
        
    case 'editar':
        editarUsuario();
        break;
        
    case 'crear':
        crearUsuario();
        break;
        
    case 'obtener_usuario':
        obtenerUsuario();
        break;
        
    case 'listar':
        listarUsuarios();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

/**
 * Cambiar estatus de usuario (activar/desactivar)
 */
function cambiarEstatusUsuario() {
    global $conn, $id_admin;
    
    $id_usuario = intval($_POST['id_usu'] ?? 0);
    $nuevo_estatus = intval($_POST['estatus'] ?? 0);
    
    if ($id_usuario <= 0 || !in_array($nuevo_estatus, [0, 1])) {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        return;
    }
    
    // No permitir que un administrador se desactive a sí mismo
    if ($id_usuario == $id_admin) {
        echo json_encode(['success' => false, 'message' => 'No puedes cambiar tu propio estatus']);
        return;
    }
    
    // Verificar que el usuario exista
    $stmt_verificar = $conn->prepare("SELECT id_usu, nombre_rol FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol WHERE u.id_usu = ?");
    $stmt_verificar->bind_param("i", $id_usuario);
    $stmt_verificar->execute();
    $usuario = $stmt_verificar->get_result()->fetch_assoc();
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        return;
    }
    
    // Actualizar estatus
    $stmt_actualizar = $conn->prepare("
        UPDATE usuarios 
        SET estatus_usu = ?, 
            fecha_actualizacion = NOW()
        WHERE id_usu = ?
    ");
    $stmt_actualizar->bind_param("ii", $nuevo_estatus, $id_usuario);
    
    if ($stmt_actualizar->execute()) {
        $accion_texto = $nuevo_estatus == 1 ? 'activado' : 'desactivado';
        echo json_encode([
            'success' => true, 
            'message' => "Usuario {$usuario['nombre_rol']} {$accion_texto} correctamente"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cambiar estatus del usuario']);
    }
}

/**
 * Eliminar usuario (con todas sus relaciones)
 */
function eliminarUsuario() {
    global $conn, $id_admin;
    
    $id_usuario = intval($_POST['id_usu'] ?? 0);
    
    if ($id_usuario <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
        return;
    }
    
    // No permitir que un administrador se elimine a sí mismo
    if ($id_usuario == $id_admin) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta']);
        return;
    }
    
    // Verificar que el usuario exista
    $stmt_verificar = $conn->prepare("SELECT id_usu, nombre_rol FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol WHERE u.id_usu = ?");
    $stmt_verificar->bind_param("i", $id_usuario);
    $stmt_verificar->execute();
    $usuario = $stmt_verificar->get_result()->fetch_assoc();
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        return;
    }
    
    // Iniciar transacción para eliminación segura
    $conn->begin_transaction();
    
    try {
        // Eliminar favoritos si es cliente
        if ($usuario['nombre_rol'] == 'Cliente') {
            $stmt_eliminar_favoritos = $conn->prepare("DELETE FROM favoritos WHERE id_usu = ?");
            $stmt_eliminar_favoritos->bind_param("i", $id_usuario);
            $stmt_eliminar_favoritos->execute();
        }
        
        // Eliminar restaurantes si es dueño (CASCADE eliminará platillos, inventario, etc.)
        if ($usuario['nombre_rol'] == 'Dueño') {
            $stmt_eliminar_restaurantes = $conn->prepare("DELETE FROM restaurante WHERE id_usu = ?");
            $stmt_eliminar_restaurantes->bind_param("i", $id_usuario);
            $stmt_eliminar_restaurantes->execute();
        }
        
        // Eliminar el usuario
        $stmt_eliminar_usuario = $conn->prepare("DELETE FROM usuarios WHERE id_usu = ?");
        $stmt_eliminar_usuario->bind_param("i", $id_usuario);
        $stmt_eliminar_usuario->execute();
        
        // Confirmar transacción
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => "Usuario {$usuario['nombre_rol']} eliminado correctamente con todos sus datos asociados"
        ]);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario: ' . $e->getMessage()]);
    }
}

/**
 * Editar información de usuario
 */
function editarUsuario() {
    global $conn;
    
    $id_usuario = intval($_POST['id_usu'] ?? 0);
    $nombre = trim($_POST['nombre_usu'] ?? '');
    $apellido = trim($_POST['apellido_usu'] ?? '');
    $email = trim($_POST['email_usu'] ?? '');
    $telefono = trim($_POST['telefono_usu'] ?? '');
    $rol = intval($_POST['id_rol'] ?? 0);
    
    if ($id_usuario <= 0 || empty($nombre) || empty($apellido) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos']);
        return;
    }
    
    if (!in_array($rol, [1, 2, 3])) {
        echo json_encode(['success' => false, 'message' => 'Rol inválido']);
        return;
    }
    
    // Verificar que el usuario exista
    $stmt_verificar = $conn->prepare("SELECT id_usu FROM usuarios WHERE id_usu = ?");
    $stmt_verificar->bind_param("i", $id_usuario);
    $stmt_verificar->execute();
    
    if ($stmt_verificar->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        return;
    }
    
    // Verificar que el email no esté en uso por otro usuario
    $stmt_email = $conn->prepare("SELECT id_usu FROM usuarios WHERE email_usu = ? AND id_usu != ?");
    $stmt_email->bind_param("si", $email, $id_usuario);
    $stmt_email->execute();
    
    if ($stmt_email->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El email ya está en uso por otro usuario']);
        return;
    }
    
    // Actualizar usuario
    $stmt_actualizar = $conn->prepare("
        UPDATE usuarios 
        SET nombre_usu = ?, apellido_usu = ?, email_usu = ?, telefono_usu = ?, 
            id_rol = ?, fecha_actualizacion = NOW()
        WHERE id_usu = ?
    ");
    $stmt_actualizar->bind_param("ssssii", $nombre, $apellido, $email, $telefono, $rol, $id_usuario);
    
    if ($stmt_actualizar->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Usuario actualizado correctamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario']);
    }
}

/**
 * Crear nuevo usuario
 */
function crearUsuario() {
    global $conn;
    
    $nombre = trim($_POST['nombre_usu'] ?? '');
    $apellido = trim($_POST['apellido_usu'] ?? '');
    $nick = trim($_POST['nick'] ?? '');
    $email = trim($_POST['email_usu'] ?? '');
    $telefono = trim($_POST['telefono_usu'] ?? '');
    $password = $_POST['password_usu'] ?? '';
    $rol = intval($_POST['id_rol'] ?? 0);
    
    if (empty($nombre) || empty($apellido) || empty($nick) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        return;
    }
    
    if (!in_array($rol, [1, 2, 3])) {
        echo json_encode(['success' => false, 'message' => 'Rol inválido']);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
        return;
    }
    
    // Verificar que el nick no esté en uso
    $stmt_nick = $conn->prepare("SELECT id_usu FROM usuarios WHERE nick = ?");
    $stmt_nick->bind_param("s", $nick);
    $stmt_nick->execute();
    
    if ($stmt_nick->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso']);
        return;
    }
    
    // Verificar que el email no esté en uso
    $stmt_email = $conn->prepare("SELECT id_usu FROM usuarios WHERE email_usu = ?");
    $stmt_email->bind_param("s", $email);
    $stmt_email->execute();
    
    if ($stmt_email->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El email ya está en uso']);
        return;
    }
    
    // Encriptar contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Crear usuario
    $stmt_crear = $conn->prepare("
        INSERT INTO usuarios (nombre_usu, apellido_usu, nick, email_usu, telefono_usu, password_usu, id_rol, estatus_usu, fecha_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt_crear->bind_param("ssssssi", $nombre, $apellido, $nick, $email, $telefono, $password_hash, $rol);
    
    if ($stmt_crear->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Usuario creado correctamente',
            'id_usuario' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear usuario']);
    }
}

/**
 * Obtener información de un usuario específico
 */
function obtenerUsuario() {
    global $conn;
    
    $id_usuario = intval($_GET['id_usu'] ?? 0);
    
    if ($id_usuario <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
        return;
    }
    
    $stmt_usuario = $conn->prepare("
        SELECT u.*, r.nombre_rol
        FROM usuarios u
        JOIN roles r ON u.id_rol = r.id_rol
        WHERE u.id_usu = ?
    ");
    $stmt_usuario->bind_param("i", $id_usuario);
    $stmt_usuario->execute();
    
    $usuario = $stmt_usuario->get_result()->fetch_assoc();
    
    if ($usuario) {
        // Eliminar contraseña del resultado
        unset($usuario['password_usu']);
        echo json_encode(['success' => true, 'usuario' => $usuario]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
}

/**
 * Listar usuarios con filtros
 */
function listarUsuarios() {
    global $conn;
    
    $rol = intval($_GET['rol'] ?? 0);
    $estatus = intval($_GET['estatus'] ?? 0);
    $limite = intval($_GET['limite'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    
    $sql = "
        SELECT u.*, r.nombre_rol,
               CASE 
                   WHEN u.id_rol = 2 THEN (SELECT COUNT(*) FROM restaurante WHERE id_usu = u.id_usu AND estatus_res = 1)
                   WHEN u.id_rol = 3 THEN (SELECT COUNT(*) FROM favoritos WHERE id_usu = u.id_usu)
                   ELSE 0
               END as elementos_relacionados
        FROM usuarios u
        JOIN roles r ON u.id_rol = r.id_rol
        WHERE 1=1
    ";
    
    $params = [];
    $types = '';
    
    if ($rol > 0) {
        $sql .= " AND u.id_rol = ?";
        $params[] = $rol;
        $types .= 'i';
    }
    
    if ($estatus >= 0) {
        $sql .= " AND u.estatus_usu = ?";
        $params[] = $estatus;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY u.fecha_registro DESC LIMIT ? OFFSET ?";
    $params[] = $limite;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Eliminar contraseñas
    foreach ($usuarios as &$usuario) {
        unset($usuario['password_usu']);
    }
    
    echo json_encode([
        'success' => true, 
        'usuarios' => $usuarios,
        'total' => count($usuarios)
    ]);
}
?>
