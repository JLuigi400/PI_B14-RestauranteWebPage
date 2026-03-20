<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro | Salud Juárez</title>
    <link rel="stylesheet" href="CSS/stylesheet.css">
    <link rel="stylesheet" href="CSS/navegador.css">
    <link rel="stylesheet" href="CSS/inicio.css">
</head>
<body>
    <?php include 'PHP/navbar.php'; ?>

    <main class="container sj-form-wrap">
        <div class="sj-form">
            <h2>Crear Cuenta</h2>
            <form action="PHP/registro_usuario.php" method="POST" id="form-registro">
                <div class="sj-field">
                    <label>Nombre de usuario</label>
                    <input type="text" name="username_usu" required>
                </div>

                <div class="sj-field">
                    <label>Correo</label>
                    <input type="email" name="correo_usu" required>
                </div>

                <div class="sj-field">
                    <label>Tipo de cuenta</label>
                    <select name="id_rol" id="selector_rol">
                        <option value="3">Comensal (Buscar comida)</option>
                        <option value="2">Dueño (Registrar Restaurante)</option>
                    </select>
                </div>

                <div id="seccion_sucursal" class="sj-panel" style="display:none;">
                    <h3 style="margin:0 0 10px 0;">Datos del Restaurante</h3>
                    <div class="sj-field">
                        <label>Nombre del local</label>
                        <input type="text" name="nombre_res" placeholder="Nombre local">
                    </div>
                    <div class="sj-field">
                        <label>Dirección</label>
                        <input type="text" name="direccion_res" placeholder="Dirección">
                    </div>
                </div>

                <button type="submit" class="sj-submit">Finalizar Registro</button>
            </form>
        </div>
    </main>
    <script src="JS/auth.js"></script>
    <script src="JS/session_check.js"></script>
</body>
</html>