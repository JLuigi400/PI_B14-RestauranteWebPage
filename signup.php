<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro | Salud Juárez</title>
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
            <h2>Crear Cuenta</h2>
            <form action="PHP/registro_usuario.php" method="POST" id="form-registro">
                <div class="sj-field">
                    <label>Nombre de usuario</label>
                    <input type="text" name="username_usu" required placeholder="Ej: juanperez123">
                </div>

                <div class="sj-field">
                    <label>Correo</label>
                    <input type="email" name="correo_usu" required placeholder="Ej: juan@ejemplo.com">
                </div>

                <div class="sj-field">
                    <label>Contraseña</label>
                    <input type="password" name="password_usu" id="password_usu" required minlength="8" placeholder="Mínimo 8 caracteres">
                </div>

                <div class="sj-field">
                    <label>Confirmar Contraseña</label>
                    <input type="password" name="confirmar_password" id="confirmar_password" required placeholder="Repite tu contraseña">
                    <small id="password_error" style="color: #ff4444; display: none;">Las contraseñas no coinciden</small>
                </div>

                <div class="sj-field">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono_usu" placeholder="+52 (555) 123-4567">
                </div>

                <div class="sj-field">
                    <label>Tipo de cuenta</label>
                    <select name="id_rol" id="selector_rol" onchange="mostrarCamposRol()">
                        <option value="3">Comensal (Buscar comida)</option>
                        <option value="2">Dueño (Registrar Restaurante)</option>
                        <option value="4">Proveedor de Insumos</option>
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

                <div id="seccion_proveedor" class="sj-panel" style="display:none;">
                    <h3 style="margin:0 0 10px 0;">📦 Datos del Proveedor</h3>
                    <div class="sj-field">
                        <label>Nombre de la Empresa *</label>
                        <input type="text" name="nombre_empresa" placeholder="Ej: Distribuidora del Norte">
                    </div>
                    <div class="sj-field">
                        <label>Tipo de Insumos *</label>
                        <select name="id_tipo_proveedor">
                            <option value="">Selecciona tipo...</option>
                            <option value="1">Alimentos</option>
                            <option value="2">Bebidas</option>
                            <option value="3">Insumos</option>
                            <option value="4">Equipamiento</option>
                            <option value="5">Limpieza</option>
                            <option value="6">Empaque</option>
                            <option value="7">Otros</option>
                        </select>
                    </div>
                    <div class="sj-field">
                        <label>Dirección de Bodega/Local *</label>
                        <input type="text" name="direccion_proveedor" placeholder="Ej: Calle Principal #123">
                    </div>
                    <div class="sj-field">
                        <label>Colonia *</label>
                        <input type="text" name="colonia_proveedor" placeholder="Ej: San Lorenzo">
                    </div>
                    <div class="sj-field">
                        <label>Ciudad</label>
                        <input type="text" name="ciudad_proveedor" placeholder="Ej: Ciudad Juárez" value="Ciudad Juárez">
                    </div>
                    <div class="sj-field">
                        <label>Estado</label>
                        <select name="id_estado_proveedor">
                            <option value="1">Aguascalientes</option>
                            <option value="2">Baja California</option>
                            <option value="3">Baja California Sur</option>
                            <option value="4">Campeche</option>
                            <option value="5">Chiapas</option>
                            <option value="6" selected>Chihuahua</option>
                            <option value="7">Coahuila</option>
                            <option value="8">Colima</option>
                            <option value="9">Durango</option>
                            <option value="10">Guanajuato</option>
                            <option value="11">Guerrero</option>
                            <option value="12">Hidalgo</option>
                            <option value="13">Jalisco</option>
                            <option value="14">México</option>
                            <option value="15">Michoacán</option>
                            <option value="16">Morelos</option>
                            <option value="17">Nayarit</option>
                            <option value="18">Nuevo León</option>
                            <option value="19">Oaxaca</option>
                            <option value="20">Puebla</option>
                            <option value="21">Querétaro</option>
                            <option value="22">Quintana Roo</option>
                            <option value="23">San Luis Potosí</option>
                            <option value="24">Sinaloa</option>
                            <option value="25">Sonora</option>
                            <option value="26">Tabasco</option>
                            <option value="27">Tamaulipas</option>
                            <option value="28">Tlaxcala</option>
                            <option value="29">Veracruz</option>
                            <option value="30">Yucatán</option>
                            <option value="31">Zacatecas</option>
                            <option value="32">Ciudad de México</option>
                        </select>
                    </div>
                    <div class="sj-field">
                        <label>Código Postal</label>
                        <input type="text" name="codigo_postal_proveedor" placeholder="Ej: 32310" maxlength="10">
                    </div>
                </div>

                <button type="submit" class="sj-submit">Finalizar Registro</button>
                
                <!-- Botón de prueba para debugging -->
                <button type="button" onclick="testFormSubmission()" style="margin-top: 10px; background: #ff6b6b; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer;">
                    Testear Envío (Debug)
                </button>
            </form>
        </div>
    </main>
    <!-- Scripts externos comentados temporalmente para debugging -->
<!-- <script src="JS/auth.js"></script> -->
<!-- <script src="JS/session_check.js"></script> -->
<!-- <script src="JS/modal_html.js"></script> -->
    
    <script>
        // Función para validar contraseñas en tiempo real
        function validarContraseñas() {
            const password = document.getElementById('password_usu');
            const confirmarPassword = document.getElementById('confirmar_password');
            const passwordError = document.getElementById('password_error');
            
            console.log('=== INICIO VALIDACIÓN ===');
            console.log('Elementos encontrados:', {
                password: !!password,
                confirmarPassword: !!confirmarPassword,
                passwordError: !!passwordError
            });
            
            // Verificar que los elementos existan
            if (!password || !confirmarPassword || !passwordError) {
                console.error('Elementos no encontrados');
                return false;
            }
            
            // Obtener valores brutos y limpios
            const passwordRaw = password.value;
            const confirmarRaw = confirmarPassword.value;
            const passwordValue = passwordRaw.trim();
            const confirmarValue = confirmarRaw.trim();
            
            console.log('Análisis detallado:', {
                passwordRaw: JSON.stringify(passwordRaw),
                confirmarRaw: JSON.stringify(confirmarRaw),
                passwordValue: JSON.stringify(passwordValue),
                confirmarValue: JSON.stringify(confirmarValue),
                passwordLength: passwordValue.length,
                confirmarLength: confirmarValue.length,
                comparacionDirecta: passwordValue === confirmarValue,
                tipoPassword: typeof passwordValue,
                tipoConfirmar: typeof confirmarValue
            });
            
            // Si ambos campos tienen contenido
            if (passwordValue.length > 0 && confirmarValue.length > 0) {
                if (passwordValue === confirmarValue) {
                    // Contraseñas coinciden
                    passwordError.style.display = 'none';
                    password.style.borderColor = '#00ff88';
                    confirmarPassword.style.borderColor = '#00ff88';
                    console.log('¿Contraseñas VÁLIDAS - permitiendo envío');
                    return true;
                } else {
                    // Contraseñas no coinciden
                    passwordError.style.display = 'block';
                    password.style.borderColor = '#ff4444';
                    confirmarPassword.style.borderColor = '#ff4444';
                    console.log('¿Contraseñas INVÁLIDAS - bloqueando envío');
                    return false;
                }
            } else {
                // Campos vacíos o incompletos
                passwordError.style.display = 'none';
                password.style.borderColor = '#555';
                confirmarPassword.style.borderColor = '#555';
                console.log('¿Campos INCOMPLETOS - esperando más datos');
                return false;
            }
        }
        
        // Función de prueba para debugging
        function debugPasswords() {
            const password = document.getElementById('password_usu');
            const confirmarPassword = document.getElementById('confirmar_password');
            
            if (password && confirmarPassword) {
                console.log('=== DEBUG MANUAL ===');
                console.log('Password value:', JSON.stringify(password.value));
                console.log('Confirmar value:', JSON.stringify(confirmarPassword.value));
                console.log('Password trim:', JSON.stringify(password.value.trim()));
                console.log('Confirmar trim:', JSON.stringify(confirmarPassword.value.trim()));
                console.log('Son iguales?:', password.value.trim() === confirmarPassword.value.trim());
            }
        }
        
        // Función para mostrar campos según el rol seleccionado
        function mostrarCamposRol() {
            const selectorRol = document.getElementById('selector_rol');
            const seccionSucursal = document.getElementById('seccion_sucursal');
            const seccionProveedor = document.getElementById('seccion_proveedor');
            const rolSeleccionado = selectorRol ? selectorRol.value : '';
            
            // Ocultar todas las secciones primero
            if (seccionSucursal) seccionSucursal.style.display = 'none';
            if (seccionProveedor) seccionProveedor.style.display = 'none';
            
            // Mostrar sección correspondiente
            if (rolSeleccionado === '2') {
                // Mostrar campos de Dueño
                seccionSucursal.style.display = 'block';
            } else if (rolSeleccionado === '4') {
                // Mostrar campos de Proveedor
                seccionProveedor.style.display = 'block';
            }
            
            console.log('Rol seleccionado:', rolSeleccionado);
        }
        
        // Función de prueba para debugging del formulario
        function testFormSubmission() {
            console.log('=== FUNCIÓN DE PRUEBA INICIADA ===');
            
            const form = document.getElementById('form-registro');
            if (!form) {
                console.error('ERROR: Formulario no encontrado');
                return;
            }
            
            console.log('Formulario encontrado:', form);
            console.log('Action:', form.action);
            console.log('Method:', form.method);
            
            // Recopilar datos
            const formData = new FormData(form);
            const formDataObj = {};
            for (let [key, value] of formData.entries()) {
                formDataObj[key] = value;
            }
            
            console.log('Datos del formulario:', formDataObj);
            console.log('Campos requeridos para proveedor:');
            console.log('- nombre_empresa:', formDataObj['nombre_empresa']);
            console.log('- id_tipo_proveedor:', formDataObj['id_tipo_proveedor']);
            console.log('- direccion_proveedor:', formDataObj['direccion_proveedor']);
            console.log('- colonia_proveedor:', formDataObj['colonia_proveedor']);
            
            // Validar campos requeridos
            const rol = formDataObj['id_rol'];
            console.log('Rol seleccionado:', rol);
            
            if (rol === '4') {
                const camposRequeridos = ['nombre_empresa', 'id_tipo_proveedor', 'direccion_proveedor', 'colonia_proveedor'];
                const camposFaltantes = camposRequeridos.filter(campo => !formDataObj[campo]);
                
                if (camposFaltantes.length > 0) {
                    console.error('CAMPOS FALTANTES:', camposFaltantes);
                    alert('Faltan campos requeridos: ' + camposFaltantes.join(', '));
                    return;
                } else {
                    console.log('Todos los campos requeridos están presentes');
                }
            }
            
            // Intentar envío manual
            console.log('Intentando envío manual...');
            try {
                form.submit();
                console.log('Formulario enviado con submit()');
            } catch (error) {
                console.error('ERROR al enviar formulario:', error);
            }
        }
        
        // Inicializar cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== DOM CARGADO ===');
            
            // Verificar si hay un rol preseleccionado
            const selectorRol = document.getElementById('selector_rol');
            if (selectorRol && selectorRol.value) {
                mostrarCamposRol();
            }
            
            // Agregar event listeners para validación de contraseñas
            const passwordInput = document.getElementById('password_usu');
            const confirmarPasswordInput = document.getElementById('confirmar_password');
            
            if (passwordInput) {
                passwordInput.addEventListener('input', validarContraseñas);
                console.log('Event listener agregado a password');
            }
            
            if (confirmarPasswordInput) {
                confirmarPasswordInput.addEventListener('input', validarContraseñas);
                console.log('Event listener agregado a confirmar password');
            }
            
            // Hacer disponible la función debug en la consola
            window.debugPasswords = debugPasswords;
            window.testFormSubmission = testFormSubmission;
            console.log('Funciones disponibles: debugPasswords(), testFormSubmission()');
            
            // Validar formulario antes de enviar
            const form = document.getElementById('form-registro');
            if (form) {
                console.log('Formulario encontrado, agregando event listener submit');
                
                form.addEventListener('submit', function(e) {
                    console.log('=== EVENTO SUBMIT DETECTADO ===');
                    console.log('Timestamp:', new Date().toISOString());
                    console.log('Event object:', e);
                    
                    // Recopilar todos los datos del formulario
                    const formData = new FormData(form);
                    const formDataObj = {};
                    for (let [key, value] of formData.entries()) {
                        formDataObj[key] = value;
                    }
                    
                    console.log('Datos completos del formulario:', formDataObj);
                    console.log('Action del formulario:', form.action);
                    console.log('Method del formulario:', form.method);
                    
                    // Validar contraseñas al momento del envío
                    const esValido = validarContraseñas();
                    
                    if (!esValido) {
                        console.log('VALIDACIÓN FALLIDA - Bloqueando envío');
                        e.preventDefault();
                        alert('Las contraseñas no coinciden o son inválidas. Por favor, verifica ambos campos.');
                        return false;
                    } else {
                        console.log('VALIDACIÓN EXITOSA - Enviando formulario...');
                        console.log('URL de destino:', form.action);
                        
                        // Agregar timeout para detectar si la página se queda bloqueada
                        setTimeout(() => {
                            console.log('=== TIMEOUT DE 5 SEGUNDOS ===');
                            console.log('Si ves este mensaje, la página podría estar bloqueada');
                            console.log('Verifica la pestaña Network para ver si se envió la solicitud');
                        }, 5000);
                        
                        // Permitir que el formulario se envíe normalmente
                        return true;
                    }
                });
            } else {
                console.error('ERROR: Formulario no encontrado al cargar DOM');
            }
        });
    </script>
</body>
</html>