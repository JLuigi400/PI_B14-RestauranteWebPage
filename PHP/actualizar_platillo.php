<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../DIRECCIONES/gestion_platillos.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];
$id_pla = isset($_POST['id_pla']) ? intval($_POST['id_pla']) : 0;

if ($id_pla <= 0) {
    header("Location: ../DIRECCIONES/gestion_platillos.php");
    exit();
}

// Restaurante del dueño
$query_res = $conn->prepare("SELECT id_res FROM restaurante WHERE id_usu = ?");
$query_res->bind_param("i", $id_usuario);
$query_res->execute();
$res_data = $query_res->get_result()->fetch_assoc();
if (!$res_data) {
    die("Error: No tienes un restaurante registrado.");
}
$id_res = (int)$res_data['id_res'];

// Validar platillo pertenece al restaurante
$stmt_val = $conn->prepare("SELECT img_pla FROM platillos WHERE id_pla = ? AND id_res = ? LIMIT 1");
$stmt_val->bind_param("ii", $id_pla, $id_res);
$stmt_val->execute();
$row_val = $stmt_val->get_result()->fetch_assoc();
if (!$row_val) {
    header("Location: ../DIRECCIONES/gestion_platillos.php");
    exit();
}

$nombre = $_POST['nombre_pla'] ?? '';
$desc = $_POST['descripcion_pla'] ?? '';
$precio = $_POST['precio_pla'] ?? 0;
$mostrar_ing = isset($_POST['mostrar_ing_pla']) ? 1 : 0;
$id_cat = isset($_POST['id_cat']) ? intval($_POST['id_cat']) : 0;
$tipo_comida = $_POST['tipo_comida'] ?? null; // compatibilidad si no hay categorias

$ruta_img = $row_val['img_pla'] ?? '';

// Subida de imagen (opcional)
if (isset($_FILES['img_pla']) && $_FILES['img_pla']['error'] == 0) {
    $dir = "../UPLOADS/PLATILLOS/";
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }

    $extension = pathinfo($_FILES['img_pla']['name'], PATHINFO_EXTENSION);
    $nom_archivo = "pla_" . time() . "_" . uniqid() . "." . $extension;
    $ruta_final = $dir . $nom_archivo;

    if (move_uploaded_file($_FILES['img_pla']['tmp_name'], $ruta_final)) {
        $ruta_img = $ruta_final;
    }
}

// Detectar si existe la columna id_cat en platillos
$has_id_cat = false;
try {
    $check = $conn->query("SHOW COLUMNS FROM platillos LIKE 'id_cat'");
    $has_id_cat = ($check && $check->num_rows > 0);
} catch (mysqli_sql_exception $e) {
    $has_id_cat = false;
}

try {
    if ($has_id_cat) {
        $stmt_upd = $conn->prepare("
            UPDATE platillos
            SET nombre_pla = ?, precio_pla = ?, descripcion_pla = ?, img_pla = ?, mostrar_ing_pla = ?, id_cat = NULLIF(?, 0)
            WHERE id_pla = ? AND id_res = ?
        ");
        $stmt_upd->bind_param("sdssiiii", $nombre, $precio, $desc, $ruta_img, $mostrar_ing, $id_cat, $id_pla, $id_res);
        $stmt_upd->execute();

        // Auto-vincular en res_categorias
        if ($id_cat > 0) {
            $stmt_link = $conn->prepare("INSERT IGNORE INTO res_categorias (id_res, id_cat) VALUES (?, ?)");
            if ($stmt_link) {
                $stmt_link->bind_param("ii", $id_res, $id_cat);
                $stmt_link->execute();
            }
        }
    } else {
        // Modo compatibilidad: actualizar tipo_comida si existe
        $stmt_upd = $conn->prepare("
            UPDATE platillos
            SET nombre_pla = ?, precio_pla = ?, descripcion_pla = ?, img_pla = ?, mostrar_ing_pla = ?, tipo_comida = ?
            WHERE id_pla = ? AND id_res = ?
        ");
        $tipo = $tipo_comida ?: 'General';
        $stmt_upd->bind_param("sdssisii", $nombre, $precio, $desc, $ruta_img, $mostrar_ing, $tipo, $id_pla, $id_res);
        $stmt_upd->execute();
    }

    header("Location: ../DIRECCIONES/gestion_platillos.php?status=updated");
    exit();
} catch (Exception $e) {
    die("Error al actualizar platillo: " . $e->getMessage());
}

?>

