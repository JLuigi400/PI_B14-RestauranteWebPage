<?php
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_res      = $_POST['id_res'];
    $nombre      = $_POST['nombre_pla'];
    $desc        = $_POST['descripcion_pla'];
    $precio      = $_POST['precio_pla'];
    $id_cat      = isset($_POST['id_cat']) ? intval($_POST['id_cat']) : 0;
    $mostrar_ing = isset($_POST['mostrar_ing_pla']) ? 1 : 0;
    
    $ruta_img = "";

    if (isset($_FILES['img_pla']) && $_FILES['img_pla']['error'] == 0) {
        $dir = "../UPLOADS/PLATILLOS/";
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        
        $extension = pathinfo($_FILES['img_pla']['name'], PATHINFO_EXTENSION);
        $nom_archivo = "pla_" . time() . "_" . uniqid() . "." . $extension;
        $ruta_final = $dir . $nom_archivo;

        if (move_uploaded_file($_FILES['img_pla']['tmp_name'], $ruta_final)) {
            $ruta_img = $ruta_final;
        }
    }

    try {
        // Detectar si existe la columna id_cat en la tabla platillos
        $has_id_cat = false;
        try {
            $check = $conn->query("SHOW COLUMNS FROM platillos LIKE 'id_cat'");
            $has_id_cat = ($check && $check->num_rows > 0);
        } catch (mysqli_sql_exception $e) {
            $has_id_cat = false;
        }

        // Si NO existe id_cat en platillos, usamos compatibilidad con tipo_comida.
        // Tomamos el id_cat del formulario y resolvemos el nombre desde categorias.
        $tipo_comida = 'General';
        if (!$has_id_cat && $id_cat > 0) {
            $stmt_tipo = $conn->prepare("SELECT nombre_cat FROM categorias WHERE id_cat = ? LIMIT 1");
            if ($stmt_tipo) {
                $stmt_tipo->bind_param("i", $id_cat);
                $stmt_tipo->execute();
                $row_tipo = $stmt_tipo->get_result()->fetch_assoc();
                if (!empty($row_tipo['nombre_cat'])) {
                    $tipo_comida = $row_tipo['nombre_cat'];
                }
            }
        }

        if ($has_id_cat) {
            // Nuevo esquema: guardar id_cat en platillos
            $sql = "INSERT INTO platillos (id_res, id_cat, nombre_pla, precio_pla, descripcion_pla, img_pla, mostrar_ing_pla, visible)
                    VALUES (?, NULLIF(?, 0), ?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "iisdssi",
                $id_res,
                $id_cat,
                $nombre,
                $precio,
                $desc,
                $ruta_img,
                $mostrar_ing
            );
        } else {
            // Esquema viejo: guardar texto en tipo_comida
            $sql = "INSERT INTO platillos (id_res, nombre_pla, precio_pla, descripcion_pla, img_pla, mostrar_ing_pla, tipo_comida, visible)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "isdssis",
                $id_res,
                $nombre,
                $precio,
                $desc,
                $ruta_img,
                $mostrar_ing,
                $tipo_comida
            );
        }

        if ($stmt->execute()) {
            $id_pla = $conn->insert_id;
            
            // Procesar ingredientes del platillo
            if (isset($_POST['ingredientes']) && is_array($_POST['ingredientes'])) {
                foreach ($_POST['ingredientes'] as $ingrediente_data) {
                    $id_inv = intval($ingrediente_data['id_inv']);
                    $cantidad = floatval($ingrediente_data['cantidad']);
                    $unidad = $ingrediente_data['unidad'] ?? '';
                    
                    if ($id_inv > 0 && $cantidad > 0) {
                        $stmt_ing = $conn->prepare("
                            INSERT INTO platillo_ingredientes (id_pla, id_inv, cantidad_usada, unidad_usada) 
                            VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                            cantidad_usada = VALUES(cantidad_usada), 
                            unidad_usada = VALUES(unidad_usada)
                        ");
                        $stmt_ing->bind_param("iids", $id_pla, $id_inv, $cantidad, $unidad);
                        $stmt_ing->execute();
                    }
                }
            }
            
            // Mantener res_categorias alineado: si el dueño usa una categoría nueva, se vincula al restaurante.
            if ($id_cat > 0) {
                $stmt_link = $conn->prepare("INSERT IGNORE INTO res_categorias (id_res, id_cat) VALUES (?, ?)");
                if ($stmt_link) {
                    $stmt_link->bind_param("ii", $id_res, $id_cat);
                    $stmt_link->execute();
                }
            }
            header("Location: ../DIRECCIONES/gestion_platillos.php?status=ok");
        } else {
            throw new Exception($stmt->error);
        }

    } catch (Exception $e) {
        die("Error en la base de datos: " . $e->getMessage());
    }
}