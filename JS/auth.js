// ==========================================
// ARCHIVO: JS/auth.js
// Maneja las funciones de Login y Signup
// ==========================================

document.addEventListener("DOMContentLoaded", function() {
    console.log("Sistema de autenticación cargado.");

    const selectorRol = document.getElementById('selector_rol');
    const seccionSucursal = document.getElementById('seccion_sucursal');

    if (selectorRol && seccionSucursal) {
        // Función para limpiar y alternar requisitos
        function gestionarFormulario() {
            const esDueno = selectorRol.value === "2";
            const inputsSucursal = seccionSucursal.querySelectorAll('input');

            if (esDueno) {
                seccionSucursal.style.display = "block";
                console.log("Cambiado a modo: DUEÑO. Activando validación de sucursal.");
                inputsSucursal.forEach(input => {
                    // Solo pedimos obligatorios los de texto y tel, no los checkboxes
                    if (input.type === 'text' || input.type === 'tel') {
                        input.required = true;
                    }
                });
            } else {
                seccionSucursal.style.display = "none";
                console.log("Cambiado a modo: USUARIO. Desactivando campos de sucursal.");
                inputsSucursal.forEach(input => {
                    input.required = false;
                    if (input.type !== 'checkbox') input.value = ""; 
                });
            }
        }

        // Escuchar cambios
        selectorRol.addEventListener('change', gestionarFormulario);
        
        // Ejecutar al inicio por si el navegador recordó una selección previa
        gestionarFormulario();
    }
});