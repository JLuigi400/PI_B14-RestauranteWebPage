<?php
session_start();
include '../PHP/db_config.php';

// Validar que el usuario haya iniciado sesión
if (!isset($_SESSION['id_usu'])) {
    header("Location: ../login.html");
    exit();
}

// Capturamos lo que el usuario busque en la barra (si existe)
$busqueda = $_GET['q'] ?? '';

// Construimos la consulta SQL
$sql = "SELECT id_res, nombre_res, direccion_res, sector_res, telefono_res FROM restaurante";

// Si el usuario escribió algo en el buscador, filtramos
if (!empty($busqueda)) {
    $busqueda_segura = $conn->real_escape_string($busqueda);
    $sql .= " WHERE nombre_res LIKE '%$busqueda_segura%' OR sector_res LIKE '%$busqueda_segura%'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Explorar Restaurantes | Salud Juárez</title>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <style>
        /* Estilos rápidos para las tarjetas de restaurantes */
        .grid-restaurantes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card-res {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card-res h3 { margin-top: 0; color: #2ecc71; }
        .tag-sector {
            display: inline-block;
            background: #f1c40f;
            color: #333;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-bottom: 10px;
        }
        .barra-busqueda {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .barra-busqueda input { flex: 1; padding: 10px; border-radius: 5px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <header>
        <h1>🔍 Descubre Opciones Saludables</h1>
        <div class="user-info">
            <a href="dashboard.php" class="btn" style="background: #34495e; color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px;">⬅ Volver al Dashboard</a>
        </div>
    </header>

    <main style="max-width: 1000px; margin: 0 auto; padding: 20px;">
        
        <form method="GET" action="buscar_restaurantes.php" class="barra-busqueda">
            <input type="text" name="q" placeholder="Buscar por nombre o sector (Ej. Centro, Las Misiones)..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <button type="submit" class="btn" style="background: #27ae60;">Buscar</button>
            <?php if(!empty($busqueda)): ?>
                <a href="buscar_restaurantes.php" class="btn" style="background: #e74c3c; text-decoration:none;">Limpiar</a>
            <?php endif; ?>
        </form>

        <h2>Restaurantes Disponibles</h2>
        
        <div class="grid-restaurantes">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="card-res">
                        <h3><?php echo htmlspecialchars($row['nombre_res']); ?></h3>
                        <span class="tag-sector">📍 <?php echo htmlspecialchars($row['sector_res']); ?></span>
                        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($row['direccion_res']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($row['telefono_res']); ?></p>
                        
                        <a href="ver_menu.php?id=<?php echo $row['id_res']; ?>" class="btn" style="display: block; text-align: center; margin-top: 15px;">Ver Menú</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No se encontraron restaurantes con esa búsqueda. ¡Prueba con otra palabra!</p>
            <?php endif; ?>
        </div>

    </main>
</body>
</html>