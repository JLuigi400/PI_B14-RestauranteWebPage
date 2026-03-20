<?php
session_start();
include '../PHP/db_config.php';
include '../PHP/navbar.php';

// Verificación de sesión
if (!isset($_SESSION['id_usu'])) {
    header("Location: ../login.html");
    exit();
}

$id_usuario = $_SESSION['id_usu'];

// Obtenemos el id_res asociado al usuario actual (Dueño)
$query_res = $conn->prepare("SELECT id_res FROM restaurante WHERE id_usu = ?");
$query_res->bind_param("i", $id_usuario);
$query_res->execute();
$result_res = $query_res->get_result();
$restaurante = $result_res->fetch_assoc();

if (!$restaurante) {
    echo "Error: No se encontró un restaurante asociado a esta cuenta.";
    exit();
}

$id_res = $restaurante['id_res'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Inventario | Salud Juárez</title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/platillos.css">
</head>
<body>
    <div class="container">
        <h1 class="sj-page-title">📦 Inventario</h1>
        
        <section class="sj-card">
            <h2 style="margin:0 0 12px 0;">Registrar insumo</h2>

            <form action="../PHP/procesar_insumo.php" method="POST" enctype="multipart/form-data" class="sj-form-grid">
                <input type="hidden" name="id_res" value="<?php echo $id_res; ?>">
                
                <div class="sj-field">
                    <label>Nombre del Insumo</label>
                    <input type="text" name="nombre_insumo" placeholder="Ej: Espinaca" required>
                </div>

                <div class="sj-field">
                    <label>Cantidad (Stock)</label>
                    <input type="number" step="0.01" name="stock_inv" placeholder="0.00" required>
                </div>

                <div class="sj-field">
                    <label>Unidad de Medida</label>
                    <select name="medida_inv" required>
                        <option value="Kg">Kilogramos (Kg)</option>
                        <option value="Gr">Gramos (Gr)</option>
                        <option value="Lt">Litros (Lt)</option>
                        <option value="Mz">Mazo (Mz)</option>
                        <option value="Pza">Pieza (Pza)</option>
                    </select>
                </div>

                <div class="sj-field">
                    <label>Imagen del Ingrediente (opcional)</label>
                    <input type="file" name="img_insumo" accept="image/*">
                </div>

                <div class="sj-span-2">
                    <button type="submit" class="sj-btn sj-btn--primary" style="width:100%;">Registrar Insumo</button>
                </div>
            </form>
        </section>

        <hr class="sj-divider">

        <h2 style="margin:0 0 12px 0;">📋 Lista de ingredientes</h2>
        <table class="sj-table">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Insumo</th>
                    <th>Stock</th>
                    <th>Medida</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_lista = "SELECT * FROM inventario WHERE id_res = ? ORDER BY nombre_insumo ASC";
                $stmt_lista = $conn->prepare($sql_lista);
                $stmt_lista->bind_param("i", $id_res);
                $stmt_lista->execute();
                $res_lista = $stmt_lista->get_result();

                while ($row = $res_lista->fetch_assoc()) {
                    $img_path = !empty($row['img_insumo']) ? $row['img_insumo'] : '../IMG/default-insumo.png';
                    $img_safe = htmlspecialchars($img_path);
                    $nom_safe = htmlspecialchars($row['nombre_insumo']);
                    $stock_safe = htmlspecialchars($row['stock_inv']);
                    $med_safe = htmlspecialchars($row['medida_inv']);
                    echo "<tr>
                            <td><img src='{$img_safe}' width='54' height='40' style='border-radius:10px; object-fit:cover;' alt='Insumo'></td>
                            <td>{$nom_safe}</td>
                            <td>{$stock_safe}</td>
                            <td>{$med_safe}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>