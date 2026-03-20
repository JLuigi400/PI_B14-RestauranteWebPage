<?php
session_start();
include '../PHP/db_config.php';

// Permitido para invitado o comensal (y en general cualquiera), pero solo muestra platillos visibles.
$id_res = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_res <= 0) {
    header("Location: buscar_restaurantes.php?status=bad_id");
    exit();
}

// 1) Datos del restaurante
$stmt_res = $conn->prepare("
    SELECT id_res, nombre_res, direccion_res, sector_res, telefono_res, url_web, logo_res, banner_res, estatus_res
    FROM restaurante
    WHERE id_res = ?
    LIMIT 1
");
$stmt_res->bind_param("i", $id_res);
$stmt_res->execute();
$restaurante = $stmt_res->get_result()->fetch_assoc();

if (!$restaurante) {
    header("Location: buscar_restaurantes.php?status=not_found");
    exit();
}

// Si se decide ocultar por estatus (1 activo). Si no existe esa regla, se puede retirar.
if (isset($restaurante['estatus_res']) && (int)$restaurante['estatus_res'] !== 1) {
    header("Location: buscar_restaurantes.php?status=inactive");
    exit();
}

$logo = !empty($restaurante['logo_res']) ? $restaurante['logo_res'] : '../IMG/default_logo.png';
$banner = !empty($restaurante['banner_res']) ? $restaurante['banner_res'] : '../IMG/default_banner.png';

// 2) Platillos visibles (Modo dual: si existe id_cat hacemos JOIN; si no, usamos tipo_comida)
$stmt_pla = null;
$sql_pla = "
    SELECT p.*, c.nombre_cat
    FROM platillos p
    LEFT JOIN categorias c ON c.id_cat = p.id_cat
    WHERE p.id_res = ? AND p.visible = 1
    ORDER BY p.id_pla DESC
";

try {
    $stmt_pla = $conn->prepare($sql_pla);
} catch (mysqli_sql_exception $e) {
    $stmt_pla = null;
}

if (!$stmt_pla) {
    $sql_pla = "
        SELECT *
        FROM platillos
        WHERE id_res = ? AND visible = 1
        ORDER BY id_pla DESC
    ";
    $stmt_pla = $conn->prepare($sql_pla);
}

$stmt_pla->bind_param("i", $id_res);
$stmt_pla->execute();
$platillos = $stmt_pla->get_result();

// 3) Ingredientes disponibles (para mostrar solo si el platillo lo permite)
$stmt_ing = $conn->prepare("
    SELECT nombre_insumo, stock_inv, medida_inv
    FROM inventario
    WHERE id_res = ? AND stock_inv > 0
    ORDER BY nombre_insumo ASC
");
$stmt_ing->bind_param("i", $id_res);
$stmt_ing->execute();
$ingredientes = $stmt_ing->get_result();
$ingredientes_list = [];
while ($row = $ingredientes->fetch_assoc()) {
    $ingredientes_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú | <?php echo htmlspecialchars($restaurante['nombre_res']); ?></title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/platillos.css">
    <style>
        /* Banner con imagen dinámica del restaurante */
        .sj-res-banner {
            background:
                linear-gradient(120deg, rgba(34,48,66,0.90), rgba(45,90,39,0.86)),
                url('<?php echo htmlspecialchars($banner); ?>');
            background-size: cover;
            background-position: center;
            color: #fff;
        }
    </style>
</head>
<body>
    <?php include '../PHP/navbar.php'; ?>

    <main class="container">
        <section class="sj-card sj-res-banner" style="padding: 0;">
            <div style="display:flex; gap:14px; align-items:flex-end; padding: 18px;">
                <div style="width:86px; height:86px; border-radius: 18px; overflow:hidden; background:#fff; border: 1px solid rgba(255,255,255,0.25);">
                    <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div>
                    <h1 style="margin:0; font-size: 1.6rem; line-height:1.1;"><?php echo htmlspecialchars($restaurante['nombre_res']); ?></h1>
                    <div style="margin-top:6px; opacity:0.95;">📍 <?php echo htmlspecialchars($restaurante['sector_res']); ?></div>
                </div>
            </div>
            <div class="sj-card" style="margin: 0; border-radius: 0 0 var(--radio-15) var(--radio-15); box-shadow: none;">
                <div class="sj-row" style="align-items:flex-start; flex-wrap: wrap;">
                    <div style="display:grid; gap:6px;">
                        <div><b>Dirección:</b> <?php echo htmlspecialchars($restaurante['direccion_res']); ?></div>
                        <?php if (!empty($restaurante['telefono_res'])): ?>
                            <div><b>Teléfono:</b> <?php echo htmlspecialchars($restaurante['telefono_res']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($restaurante['url_web'])): ?>
                            <div><b>Sitio:</b> <a href="<?php echo htmlspecialchars($restaurante['url_web']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($restaurante['url_web']); ?></a></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="buscar_restaurantes.php" class="sj-btn sj-btn--accent" style="text-decoration:none;">⬅ Volver</a>
                    </div>
                </div>
            </div>
        </section>

        <h2 class="sj-page-title">🍽️ Platillos disponibles</h2>

        <?php if ($platillos && $platillos->num_rows > 0): ?>
            <div class="sj-grid">
                <?php while ($p = $platillos->fetch_assoc()):
                    $foto = !empty($p['img_pla']) ? $p['img_pla'] : '../IMG/default-food.png';
                    $cat = 'General';
                    if (!empty($p['nombre_cat'])) {
                        $cat = $p['nombre_cat'];
                    } elseif (!empty($p['tipo_comida'])) {
                        $cat = $p['tipo_comida'];
                    }
                    $show_ing = !empty($p['mostrar_ing_pla']);
                ?>
                    <article class="sj-menu-card">
                        <span class="sj-badge" style="background: var(--azul-frontera);"><?php echo htmlspecialchars($cat); ?></span>
                        <div class="sj-media">
                            <img src="<?php echo htmlspecialchars($foto); ?>" alt="Platillo">
                        </div>
                        <div class="sj-body">
                            <h3 class="sj-title"><?php echo htmlspecialchars($p['nombre_pla']); ?></h3>
                            <p class="sj-desc"><?php echo htmlspecialchars($p['descripcion_pla']); ?></p>
                            <div class="sj-row">
                                <div class="sj-price">$<?php echo number_format((float)$p['precio_pla'], 2); ?></div>
                                <?php if ($show_ing): ?>
                                <button class="sj-pill" onclick="abrirModalIngredientes(<?php echo $p['id_pla']; ?>)">👁️ Ingredientes</button>
                            <?php endif; ?>
                            </div>

                            <?php if ($show_ing): ?>
                                <details class="sj-details">
                                    <summary>Ver ingredientes</summary>
                                    <?php if (!empty($ingredientes_list)): ?>
                                        <ul>
                                            <?php foreach ($ingredientes_list as $ing): ?>
                                                <li><?php echo htmlspecialchars($ing['nombre_insumo']); ?> (<?php echo htmlspecialchars($ing['stock_inv']); ?> <?php echo htmlspecialchars($ing['medida_inv']); ?>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div style="margin-top:10px;">Este restaurante no tiene insumos con stock disponible.</div>
                                    <?php endif; ?>
                                </details>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="margin-top: 10px; color: var(--gris-suave);">Este restaurante aún no tiene platillos visibles.</p>
        <?php endif; ?>
    </main>

    <!-- Modal de ingredientes -->
    <div id="modalIngredientes" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModalIngredientes()">&times;</span>
            <iframe id="iframeIngredientes" src="" width="100%" height="600" frameborder="0"></iframe>
        </div>
    </div>

    <script>
        function abrirModalIngredientes(idPla) {
            const modal = document.getElementById('modalIngredientes');
            const iframe = document.getElementById('iframeIngredientes');
            
            iframe.src = 'modales/modal_ingredientes_cliente.php?id_pla=' + idPla;
            modal.style.display = 'block';
            
            // Ajustar tamaño del modal
            setTimeout(function() {
                ajustarTamanoModal();
            }, 100);
        }
        
        function cerrarModalIngredientes() {
            const modal = document.getElementById('modalIngredientes');
            const iframe = document.getElementById('iframeIngredientes');
            
            modal.style.display = 'none';
            iframe.src = '';
        }
        
        function ajustarTamanoModal() {
            const modal = document.getElementById('modalIngredientes');
            const iframe = document.getElementById('iframeIngredientes');
            
            // Escuchar mensajes del iframe para ajustar tamaño
            window.addEventListener('message', function(event) {
                if (event.data.type === 'resizeModal') {
                    iframe.style.height = event.data.height + 'px';
                    modal.style.height = 'auto';
                }
            });
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalIngredientes');
            if (event.target == modal) {
                cerrarModalIngredientes();
            }
        }
        
        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModalIngredientes();
            }
        });
    </script>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 20px auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            position: relative;
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .close:hover {
            color: #000;
            background: #f0f0f0;
        }
        
        .sj-pill {
            background: var(--azul-frontera);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .sj-pill:hover {
            background: #1e3a5a;
            transform: translateY(-1px);
        }
        
        iframe {
            border: none;
            width: 100%;
            min-height: 600px;
        }
    </style>
</body>
</html>

