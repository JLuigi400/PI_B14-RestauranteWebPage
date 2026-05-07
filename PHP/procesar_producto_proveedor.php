<?php
session_start();
include 'db_config.php';

// Verificar que el usuario sea proveedor (rol 4)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 4) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

try {
    // Obtener datos del formulario
    $id_producto = $_POST['id_producto'] ?? null;
    $nombre_producto = trim($_POST['nombre_producto'] ?? '');
    $descripcion_producto = trim($_POST['descripcion_producto'] ?? '');
    $categoria_producto = $_POST['categoria_producto'] ?? '';
    $unidad_medida = $_POST['unidad_medida'] ?? '';
    $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
    $precio_mayoreo = floatval($_POST['precio_mayoreo'] ?? 0);
    $cantidad_minima_mayoreo = intval($_POST['cantidad_minima_mayoreo'] ?? 0);
    $disponibilidad = 1; // Por defecto disponible
    
    // Obtener id_proveedor desde la tabla proveedores usando id_usu de sesión
    $id_usu = $_SESSION['id_usu'];
    $sql_proveedor = "SELECT id_proveedor FROM proveedores WHERE id_usu = ?";
    $stmt_proveedor = $conn->prepare($sql_proveedor);
    $stmt_proveedor->bind_param("i", $id_usu);
    $stmt_proveedor->execute();
    $result_proveedor = $stmt_proveedor->get_result();
    
    if ($result_proveedor->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Proveedor no encontrado']);
        exit();
    }
    
    $proveedor_data = $result_proveedor->fetch_assoc();
    $id_proveedor = $proveedor_data['id_proveedor'];

    // Validaciones básicas
    if (empty($nombre_producto) || empty($descripcion_producto) || empty($categoria_producto) || empty($unidad_medida) || $precio_unitario <= 0) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben ser completados']);
        exit();
    }

    // Validar lógica de precios
    if ($precio_mayoreo > 0) {
        if ($precio_mayoreo >= $precio_unitario) {
            echo json_encode(['success' => false, 'message' => 'El precio de mayoreo debe ser menor al precio unitario']);
            exit();
        }
        if ($cantidad_minima_mayoreo <= 0) {
            echo json_encode(['success' => false, 'message' => 'Si hay precio de mayoreo, debe especificar una cantidad mínima']);
            exit();
        }
    } else {
        $cantidad_minima_mayoreo = 0;
    }

    // Manejo de imagen
    $nombre_imagen = null;
    if (isset($_FILES['imagen_producto']) && $_FILES['imagen_producto']['error'] === UPLOAD_ERR_OK) {
        $imagen = $_FILES['imagen_producto'];
        
        // Validar tipo de archivo
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imagen['type'], $tipos_permitidos)) {
            echo json_encode(['success' => false, 'message' => 'Solo se permiten imágenes (JPEG, PNG, GIF, WebP)']);
            exit();
        }

        // Validar tamaño (máximo 5MB)
        if ($imagen['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'La imagen no debe superar los 5MB']);
            exit();
        }

        // Crear directorio si no existe
        $directorio_uploads = '../IMG/UPLOADS/INSUMOS/';
        if (!file_exists($directorio_uploads)) {
            mkdir($directorio_uploads, 0777, true);
        }

        // Generar nombre único para la imagen
        $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
        $nombre_imagen = 'producto_' . $id_proveedor . '_' . time() . '.' . $extension;
        $ruta_destino = $directorio_uploads . $nombre_imagen;

        // Mover archivo
        if (!move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
            echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
            exit();
        }
    }

    // Iniciar transacción
    $conn->begin_transaction();

    try {
        if ($id_producto) {
            // ACTUALIZAR producto existente
            $sql = "UPDATE productos_proveedor SET 
                    nombre_producto = ?, 
                    descripcion_producto = ?, 
                    categoria_producto = ?, 
                    unidad_medida = ?, 
                    precio_unitario = ?, 
                    precio_mayoreo = ?, 
                    cantidad_minima_mayoreo = ?, 
                    disponibilidad = ?";

            $params = [$nombre_producto, $descripcion_producto, $categoria_producto, $unidad_medida, $precio_unitario, $precio_mayoreo, $cantidad_minima_mayoreo, $disponibilidad];
            $types = "ssssddii";

            // Si hay nueva imagen, actualizarla también
            if ($nombre_imagen) {
                $sql .= ", imagen_producto = ?";
                $params[] = $nombre_imagen;
                $types .= "s";
            }

            $sql .= " WHERE id_producto = ? AND id_proveedor = ?";
            $params[] = $id_producto;
            $params[] = $id_proveedor;
            $types .= "ii";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar producto: " . $stmt->error);
            }

            $mensaje = "Producto actualizado correctamente";

        } else {
            // INSERTAR nuevo producto
            $sql = "INSERT INTO productos_proveedor (
                id_proveedor, nombre_producto, descripcion_producto, categoria_producto, 
                unidad_medida, precio_unitario, precio_mayoreo, cantidad_minima_mayoreo, 
                disponibilidad, imagen_producto, fecha_registro_producto
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssddiis", 
                $id_proveedor, $nombre_producto, $descripcion_producto, $categoria_producto,
                $unidad_medida, $precio_unitario, $precio_mayoreo, $cantidad_minima_mayoreo,
                $disponibilidad, $nombre_imagen
            );

            if (!$stmt->execute()) {
                throw new Exception("Error al crear producto: " . $stmt->error);
            }

            $mensaje = "Producto creado correctamente";
        }

        // Confirmar transacción
        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => $mensaje,
            'id_producto' => $id_producto ?? $conn->insert_id
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Si se subió una imagen y hubo error, eliminarla
    if (isset($nombre_imagen) && file_exists('../IMG/UPLOADS/INSUMOS/' . $nombre_imagen)) {
        unlink('../IMG/UPLOADS/INSUMOS/' . $nombre_imagen);
    }

    error_log("Error en procesar_producto_proveedor.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>
