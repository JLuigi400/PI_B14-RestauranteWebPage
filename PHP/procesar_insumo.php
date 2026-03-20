<?php
// Iniciar sesión para la gestión de seguridad y redirecciones
session_start();
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recolección de datos del formulario
    $id_res         = $_POST['id_res'];
    $nombre_insumo  = $_POST['nombre_insumo'];
    $stock_inv      = $_POST['stock_inv'];
    $medida_inv     = $_POST['medida_inv'];
    
    $ruta_final = "";

    // 2. Gestión y subida de la Imagen (si el chef subió una)
    if (isset($_FILES['img_insumo']) && $_FILES['img_insumo']['error'] == 0) {
        $directorio = "../UPLOADS/INSUMOS/";
        
        // Crear carpeta automáticamente si no existe en el servidor
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        // Generar un nombre único para evitar que las imágenes se sobreescriban
        $extension = pathinfo($_FILES['img_insumo']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = "insumo_" . time() . "_" . uniqid() . "." . $extension;
        $ruta_destino = $directorio . $nombre_archivo;

        // Mover el archivo temporal a su destino final
        if (move_uploaded_file($_FILES['img_insumo']['tmp_name'], $ruta_destino)) {
            $ruta_final = $ruta_destino;
        }
    }

    // 3. Inserción en la Base de Datos
    try {
        $stmt = $conn->prepare("INSERT INTO inventario (id_res, nombre_insumo, stock_inv, medida_inv, img_insumo) VALUES (?, ?, ?, ?, ?)");
        
        // "isdss" = Integer, String, Double(decimal), String, String
        $stmt->bind_param("isdss", $id_res, $nombre_insumo, $stock_inv, $medida_inv, $ruta_final);

        if ($stmt->execute()) {
            // REDIRECCIÓN DE ÉXITO: Todo salió perfecto
            header("Location: ../DIRECCIONES/inventario/inventario_crud.php?status=success");
            exit();
        } else {
            // Si la ejecución falla, forzamos a que salte al bloque "catch"
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        // REDIRECCIÓN DE ERROR: Atrapa el fallo de SQL y lo manda por la URL
        $error_msg = urlencode($e->getMessage());
        header("Location: ../DIRECCIONES/inventario/inventario_crud.php?status=error&msg=$error_msg");
        exit();
    }
} else {
    // 4. Seguridad: Si alguien intenta abrir este archivo escribiendo la URL directamente
    header("Location: ../DIRECCIONES/inventario/inventario_crud.php");
    exit();
}
?>