<?php
session_start();
include '../PHP/db_config.php';

if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 2) {
    header("Location: ../login.php");
    exit();
}

$id_usuario = $_SESSION['id_usu'];
$id_pla = isset($_GET['id_pla']) ? intval($_GET['id_pla']) : 0;

// Restaurante del dueño
$query_res = $conn->prepare("SELECT id_res FROM restaurante WHERE id_usu = ?");
$query_res->bind_param("i", $id_usuario);
$query_res->execute();
$res_data = $query_res->get_result()->fetch_assoc();

if (!$res_data) {
    die("Error: No tienes un restaurante registrado. Contacta al administrador.");
}
$id_res = (int)$res_data['id_res'];

// Validar que el platillo pertenezca al restaurante (si se mandó id_pla)
if ($id_pla > 0) {
    $stmt_val = $conn->prepare("SELECT id_pla FROM platillos WHERE id_pla = ? AND id_res = ? LIMIT 1");
    $stmt_val->bind_param("ii", $id_pla, $id_res);
    $stmt_val->execute();
    $ok = $stmt_val->get_result()->fetch_assoc();
    if (!$ok) {
        header("Location: gestion_platillos.php");
        exit();
    }
}

// Ingredientes disponibles (stock > 0)
$stmt_ing = $conn->prepare("
    SELECT nombre_insumo, stock_inv, medida_inv, img_insumo
    FROM inventario
    WHERE id_res = ? AND stock_inv > 0
    ORDER BY nombre_insumo ASC
");
$stmt_ing->bind_param("i", $id_res);
$stmt_ing->execute();
$ingredientes = $stmt_ing->get_result();

if (!$ingredientes || $ingredientes->num_rows === 0) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Ingredientes | Salud Juárez</title>
    </head>
    <body>
        <script>
            alert("Este restaurante no posee ingredientes con stock disponible.");
            window.location.href = "gestion_platillos.php";
        </script>
    </body>
    </html>
    <?php
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingredientes disponibles | Salud Juárez</title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
</head>
<body>
    <?php include '../PHP/navbar.php'; ?>

    <div class="container" style="max-width: 900px; margin: 30px auto; padding: 0 20px;">
        <h1>🧾 Ingredientes disponibles</h1>
        <p style="color:#666;">Se muestran solo los insumos con stock mayor a 0.</p>

        <table border="1" class="tabla-inventario" style="width:100%; margin-top: 15px;">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Insumo</th>
                    <th>Stock</th>
                    <th>Medida</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $ingredientes->fetch_assoc()):
                    $img_path = !empty($row['img_insumo']) ? $row['img_insumo'] : '../IMG/default-insumo.png';
                ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($img_path); ?>" width="50" alt="Insumo"></td>
                        <td><?php echo htmlspecialchars($row['nombre_insumo']); ?></td>
                        <td><?php echo htmlspecialchars($row['stock_inv']); ?></td>
                        <td><?php echo htmlspecialchars($row['medida_inv']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div style="margin-top: 15px;">
            <a href="gestion_platillos.php" class="btn">Volver a Mi Menú</a>
        </div>
    </div>
</body>
</html>

