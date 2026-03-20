<?php
include 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Recolección de Datos Base
    $nick      = $_POST['username_usu'] ?? '';
    $nombre    = $_POST['nombre_per'] ?? '';
    $apellido  = $_POST['apellidos_per'] ?? '';
    $correo    = $_POST['correo_usu'] ?? '';
    $cel       = $_POST['cel_per'] ?? '';
    $pass      = $_POST['password_usu'] ?? '';
    $pass_conf = $_POST['confirm_password_usu'] ?? '';
    
    // Rol seleccionado (3 = Usuario Normal, 2 = Dueño)
    $id_rol    = $_POST['id_rol'] ?? 3; 

    // Validar contraseñas
    if ($pass !== $pass_conf) {
        echo "<script>alert('❌ Las contraseñas no coinciden.'); window.history.back();</script>";
        exit();
    }

    $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

    // Iniciamos transacción (Si algo falla, no se guarda nada a medias)
    $conn->begin_transaction();

    try {
        // TRIGGER 1: Verificación de Identidad
        // Esto detendrá el proceso y te mostrará qué ID de rol está recibiendo PHP realmente.
        // die("Debug: El rol recibido es: " . $id_rol); 
    
        /* PASO 1: CREAR CREDENCIALES */
        $stmt_usu = $conn->prepare("INSERT INTO usuarios (username_usu, correo_usu, password_usu, id_rol) VALUES (?, ?, ?, ?)");
        $stmt_usu->bind_param("sssi", $nick, $correo, $pass_hash, $id_rol);
        
        if (!$stmt_usu->execute()) {
            throw new Exception("Error en Usuarios: " . $stmt_usu->error);
        }
        $id_generado = $stmt_usu->insert_id;
    
        /* PASO 2: PERFIL */
        $stmt_per = $conn->prepare("INSERT INTO perfiles (id_usu, nombre_per, apellidos_per, correo_per, password_per, cel_per) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_per->bind_param("isssss", $id_generado, $nombre, $apellido, $correo, $pass_hash, $cel);
        $stmt_per->execute();
    
        /* PASO 3: EL MOMENTO DE LA VERDAD (RESTAURANTE) */
        if ($id_rol == 2) {
            // ALERT: Si entra aquí, el PHP sí reconoce que es un dueño.
            $nombre_res = $_POST['nombre_res'] ?? 'SIN NOMBRE';
            
            $stmt_res = $conn->prepare("INSERT INTO restaurante (id_usu, nombre_res, direccion_res, sector_res, telefono_res) VALUES (?, ?, ?, ?, ?)");
            $stmt_res->bind_param("issss", $id_generado, $nombre_res, $_POST['direccion_res'], $_POST['sector_res'], $_POST['telefono_res']);
            
            if (!$stmt_res->execute()) {
                throw new Exception("Culpable encontrado en Restaurante: " . $stmt_res->error);
            }
            
            $id_restaurante = $stmt_res->insert_id;
    
            // Categorías
            if (!empty($_POST['categorias'])) {
                $stmt_cat = $conn->prepare("INSERT INTO res_categorias (id_res, id_cat) VALUES (?, ?)");
                foreach ($_POST['categorias'] as $id_cat) {
                    $stmt_cat->bind_param("ii", $id_restaurante, $id_cat);
                    $stmt_cat->execute();
                }
            }
        } else {
            // TRIGGER 2: Si el usuario es dueño pero llegas aquí, el ID 2 no está llegando bien.
            // error_log("Aviso: Registro completado como rol " . $id_rol . " (No es dueño)");
        }
    
        $conn->commit();
        echo "<script>alert('✅ Todo guardado correctamente para el ID: $id_generado'); window.location.href='../login.html';</script>";

    } catch (Exception $e) {
        $conn->rollback();
        // ALERT FINAL: Este te dirá el error exacto de SQL si algo falla internamente.
        die("🚨 Culpable capturado: " . $e->getMessage());
    }
}
?>