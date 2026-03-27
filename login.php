<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión | Salud Juárez</title>
    <link rel="stylesheet" href="CSS/stylesheet.css">
    <link rel="stylesheet" href="CSS/navegador.css">
    <link rel="stylesheet" href="CSS/inicio.css">
    <link rel="stylesheet" href="CSS/modal_theme.css">
    <link rel="stylesheet" href="CSS/modal_social_icons.css">
</head>
<body>
    <?php include 'PHP/navbar.php'; ?>

    <main class="container sj-form-wrap">
        <div class="sj-form">
            <h2>Bienvenido de nuevo</h2>
            <form id="form_login" action="PHP/validar_login.php" method="POST">
                <div class="sj-field">
                    <label>Usuario o Correo</label>
                    <input type="text" name="identificador" placeholder="Nick o correo" required>
                </div>
                <div class="sj-field">
                    <label>Contraseña</label>
                    <input type="password" name="password_usu" placeholder="Tu contraseña" required>
                </div>
                <button type="submit" class="sj-submit">Entrar</button>
            </form>
        </div>
    </main>
    <script src="JS/auth.js"></script>
    <script src="JS/session_check.js"></script>
    <script src="JS/modal_html.js"></script>
</body>
</html>