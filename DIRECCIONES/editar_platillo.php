<?php
session_start();
include '../PHP/db_config.php';

if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];
$id_pla = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_pla <= 0) {
    header("Location: gestion_platillos.php");
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

// Traer platillo y validar pertenencia
$stmt_pla = $conn->prepare("SELECT * FROM platillos WHERE id_pla = ? AND id_res = ? LIMIT 1");
$stmt_pla->bind_param("ii", $id_pla, $id_res);
$stmt_pla->execute();
$platillo = $stmt_pla->get_result()->fetch_assoc();
if (!$platillo) {
    header("Location: gestion_platillos.php");
    exit();
}

// Catálogo de categorías (si existe tabla categorias)
$cats_result = null;
try {
    $stmt_cats = $conn->prepare("SELECT id_cat, nombre_cat FROM categorias ORDER BY nombre_cat ASC");
    $stmt_cats->execute();
    $cats_result = $stmt_cats->get_result();
} catch (mysqli_sql_exception $e) {
    $cats_result = null;
}

$foto = !empty($platillo['img_pla']) ? $platillo['img_pla'] : '../IMG/default-food.png';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar platillo | Salud Juárez</title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/platillos.css">
</head>
<body>
    <?php include '../PHP/navbar.php'; ?>

    <div class="container" style="max-width: 980px;">
        <h1 class="sj-page-title">✏️ Editar platillo</h1>

        <section class="sj-card">
            <form action="../PHP/actualizar_platillo.php" method="POST" enctype="multipart/form-data" class="sj-form-grid">
                <input type="hidden" name="id_pla" value="<?php echo (int)$platillo['id_pla']; ?>">

                <div class="sj-span-2">
                    <div class="sj-media" style="max-width: 460px; border-radius: var(--radio-15); border: 1px solid var(--borde-suave);">
                        <img src="<?php echo htmlspecialchars($foto); ?>" alt="Imagen">
                    </div>
                </div>

                <div class="sj-field">
                    <label>Nombre del platillo</label>
                    <input type="text" name="nombre_pla" required value="<?php echo htmlspecialchars($platillo['nombre_pla']); ?>">
                </div>

                <div class="sj-field">
                    <label>Precio ($)</label>
                    <input type="number" step="0.01" name="precio_pla" required value="<?php echo htmlspecialchars($platillo['precio_pla']); ?>">
                </div>

                <div class="sj-field sj-span-2">
                    <label>Descripción</label>
                    <textarea name="descripcion_pla" rows="3"><?php echo htmlspecialchars($platillo['descripcion_pla']); ?></textarea>
                </div>

                <?php if ($cats_result): ?>
                    <div class="sj-field">
                        <label>Categoría</label>
                        <select name="id_cat">
                            <?php while ($cat = $cats_result->fetch_assoc()):
                                $selected = (!empty($platillo['id_cat']) && (int)$platillo['id_cat'] === (int)$cat['id_cat']) ? 'selected' : '';
                            ?>
                                <option value="<?php echo (int)$cat['id_cat']; ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($cat['nombre_cat']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="sj-field">
                        <label>Tipo (compatibilidad)</label>
                        <input type="text" name="tipo_comida" value="<?php echo htmlspecialchars($platillo['tipo_comida'] ?? 'General'); ?>">
                    </div>
                <?php endif; ?>

                <div class="sj-field">
                    <label>Actualizar imagen (opcional)</label>
                    <input type="file" name="img_pla" accept="image/*">
                </div>

                <div class="sj-span-2">
                    <label class="sj-check">
                        <input type="checkbox" name="mostrar_ing_pla" value="1" <?php echo !empty($platillo['mostrar_ing_pla']) ? 'checked' : ''; ?>>
                        Mostrar ingredientes en la ficha pública
                    </label>
                </div>

                <div class="sj-span-2" style="display:flex; gap: 10px; flex-wrap: wrap;">
                    <button type="submit" class="sj-btn sj-btn--accent" style="background: rgba(46,90,136,0.14);">Guardar cambios</button>
                    <a href="gestion_platillos.php" class="sj-btn sj-btn--accent" style="text-decoration:none;">Cancelar</a>
                </div>
            </form>
        </section>
    </div>
</body>
</html>

