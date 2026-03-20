~~ LISTADO DE LOS COMPONENTES NECESARIOS ~~

Para iniciar el index.html se requiere escribir lo siguiente:

- Todo este proyecto tiene que estar en el siguiente directorio: C:\xampp\htdocs\
- Abrir XAMPP
- Abrir Apache & MySQL
- Escribir en el ordenador - http://localhost/restaurantes/index.html - Esto hará que se muestre el contenido de la página en localhost

~~ ESPACIO DE MARCADORES / GEMINI / COPILOT GITHUB~~
# 🥗 Directorio Restaurantes Saludables - Cd. Juárez

## 👥 Roles del Sistema
- **Usuario**: Puede buscar, filtrar y ver detalles de restaurantes.
- **Administrador**: Puede crear, editar y eliminar restaurantes/comidas.
- **Dueño**: Puede registrar restaurante, gestionar menú e inventario.

## 📁 Organización de Rutas
- `/`: index, login, signup.
- `/DIRECCIONES`: dashboard, menú dueño, inventario, exploración.
- `/PHP`: lógica y conexión.
- `/IMG`: logos y fotos de platillos.   
- `/CSS`: Estilos, fuentes, etc.   
- `/JS`: Programa, logica, etc.   

## 🧭 Guía del sistema (por bloques)

Esta guía resume **la intención del código actual** y cómo se conecta cada parte.

### Bloque 0: Inicio / Landing

- `index.html`: página inicial con botones a `login.php` y `signup.php`.

### Bloque 1: Autenticación (Login / Signup)

- `login.php`: formulario de inicio de sesión.
- `signup.php`: formulario de registro y selector de rol (Comensal / Dueño).
- `JS/auth.js`: muestra/oculta la sección “Datos del Restaurante” cuando el rol es Dueño.
- `PHP/validar_login.php`:
  - valida credenciales contra `usuarios.password_usu` (hash),
  - crea variables de sesión (`id_usu`, `id_rol`, `nick`, `nombre_completo`, etc.),
  - redirige a `DIRECCIONES/dashboard.php`.
- `PHP/registro_usuario.php` (transacción):
  - crea `usuarios`,
  - crea `perfiles`,
  - si el rol es Dueño: crea fila en `restaurante` y (si se envían) vincula `categorias` en `res_categorias`.

### Bloque 2: Dashboard por roles

- `DIRECCIONES/dashboard.php`: muestra opciones según `$_SESSION['id_rol']`:
  - Admin (1): consola/gestión (enlaces preparados).
  - Dueño (2): `gestion_platillos.php` y `inventario.php`.
  - Comensal (3): `buscar_restaurantes.php`.

### Bloque 3: Exploración (Comensal)

- `DIRECCIONES/buscar_restaurantes.php`:
  - lista restaurantes,
  - permite buscar por nombre/sector,
  - enlaza a `ver_menu.php?id=...` (menú público por restaurante).

- `DIRECCIONES/ver_menu.php`:
  - vista pública (comensal o invitado) del restaurante y sus platillos **visibles** (`visible = 1`),
  - muestra la categoría en **modo dual** (`id_cat` con JOIN o `tipo_comida` como fallback),
  - si `mostrar_ing_pla = 1`, permite abrir un **desplegable** “Ver ingredientes” (stock > 0 del inventario).

### Bloque 4: Inventario (Dueño)

- `DIRECCIONES/inventario.php`:
  - formulario para registrar insumos,
  - tabla de ingredientes del restaurante.
- `PHP/procesar_insumo.php`:
  - sube imagen (opcional) a `UPLOADS/INSUMOS/`,
  - inserta en `inventario` (`stock_inv`, `medida_inv`, etc.),
  - redirige con `status=success|error`.

### Bloque 5: Menú (Dueño) + Categorías normalizadas + Ingredientes

- `DIRECCIONES/gestion_platillos.php`:
  - registra platillos del restaurante (Dueño),
  - el selector de **categoría** se carga desde BD (catálogo completo de `categorias`),
  - lista platillos y permite ocultar/mostrar/borrar.
  - botón **Ingredientes** por platillo (consulta inventario del restaurante).
- `PHP/procesar_platillo.php`:
  - sube imagen (opcional) a `UPLOADS/PLATILLOS/`,
  - inserta platillo con `mostrar_ing_pla` y categoría en **modo dual** (ver abajo).
- `DIRECCIONES/editar_platillo.php`:
  - vista para editar un platillo (solo Dueño y solo si el platillo pertenece a su restaurante).
- `PHP/actualizar_platillo.php`:
  - backend para guardar cambios del editor (incluye imagen opcional).
- `DIRECCIONES/revisar_ingredientes.php`:
  - si hay insumos con `stock_inv > 0`, los lista,
  - si no hay stock, muestra una alerta y vuelve a “Mi Menú”.

> Nota de compatibilidad: `gestion_platillos.php` intenta usar `platillos.id_cat` con `JOIN categorias`.
> Si aún no ejecutaste la migración o tu BD no tiene esa columna, hace fallback automático al listado viejo.

#### Modo dual (compatibilidad BD vieja vs nueva)

Para poder avanzar sin romper entornos donde aún no existe `platillos.id_cat`, el sistema trabaja en 2 modos:

- **Modo A (nuevo / recomendado)**: existe `platillos.id_cat`
  - `PHP/procesar_platillo.php` y `PHP/actualizar_platillo.php` guardan `id_cat`
  - `DIRECCIONES/gestion_platillos.php` muestra `categorias.nombre_cat` vía `LEFT JOIN`
- **Modo B (compatibilidad)**: NO existe `platillos.id_cat` (por ejemplo, dumps tipo `restaurantes-marzo_18.sql`)
  - el formulario igual envía `id_cat`, pero el backend resuelve el nombre con `SELECT nombre_cat FROM categorias WHERE id_cat = ?`
  - y lo guarda en `platillos.tipo_comida` (texto)
  - el listado muestra `tipo_comida` como etiqueta si no hay `nombre_cat`

Ambos backends detectan el modo usando:

- `SHOW COLUMNS FROM platillos LIKE 'id_cat'`

Además, cuando se usa una categoría (por ID), se intenta mantener la tabla puente al día:

- `INSERT IGNORE INTO res_categorias (id_res, id_cat) VALUES (?, ?)`

## 🗄️ Estructura de la Base de Datos (DB: restaurantes)

Para evitar confusiones entre nombres en plural y singular:

1.  **usuarios**: Almacena credenciales (correo, nick, password_hash) y el `id_rol`.
2.  **perfiles**: Datos personales vinculados 1-a-1 con `usuarios`.
3.  **roles**: Define quién es Administrador (1), Dueño (2) o Usuario (3).
4.  **restaurante**: Datos del negocio (nombre, sector, dirección). Solo los Dueños tienen uno vinculado.
5.  **categorias**: Catálogo maestro de tipos de comida (Vegano, Mariscos, etc.).
6.  **res_categorias**: La "llave maestra". Une un `restaurante` con sus múltiples `categorias`.
7.  **platillos / inventario**: Tablas operativas que dependen de cada `restaurante`.

## 🔄 Flujo de Registro (Signup)

El archivo `registro_usuario.php` realiza cuatro pasos en una sola transacción:

1.  **Crea el Usuario**: Inserta en la tabla `usuarios`.
2.  **Crea el Perfil**: Inserta en `perfiles` usando el ID recién generado.
3.  **Crea la Sucursal**: Si el rol es "Dueño", inserta en `restaurante`.
4.  **Vincula Categorías**: Recorre los checkboxes del formulario e inserta en `res_categorias`.

## ⚠️ Reglas de Oro para Desarrollo

* **Integridad**: No puedes borrar un restaurante sin que se borren sus categorías vinculadas (esto lo hace el `ON DELETE CASCADE`).
* **Orden de Inserción**: No puedes registrar una categoría en un restaurante si el ID de esa categoría no existe previamente en la tabla `categorias`.
* **Nomenclatura**: La base de datos es `restaurantes` (plural), pero la tabla del negocio es `restaurante` (singular).

## ✅ Actualización (Marzo 18, 2026): Categorías en Platillos + Botón Ingredientes

### Platillos ahora guardan categoría por ID (no texto)

Antes, el formulario de `DIRECCIONES/gestion_platillos.php` tenía un `<select>` hardcodeado (Desayuno/Comida/etc.) y el backend guardaba `tipo_comida` como texto.

Ahora:

- El formulario carga las categorías desde la BD (tabla `categorias`) como **catálogo completo**.
- La tabla `platillos` guarda `id_cat` (FK a `categorias`) y en el listado se muestra el nombre con un `LEFT JOIN`.

**Nota**: si importas dumps donde `platillos` aún trae `tipo_comida` (texto) y no existe `id_cat`, el sistema funcionará en **Modo B (compatibilidad)** hasta que ejecutes la migración.

### Migración SQL (phpMyAdmin)

Ejecuta el archivo `SQL/migracion-platillos-categorias.sql` en tu phpMyAdmin (puerto 3307) sobre la DB `restaurantes`.

> Nota: Si tu tabla `platillos` aún tenía `tipo_comida`, puedes mantenerla o eliminarla; el sistema ya no la usa.

### Botón “Ingredientes”

En cada tarjeta de platillo (en `gestion_platillos.php`) se agregó un botón **Ingredientes**:

- Si el restaurante tiene al menos un insumo con `stock_inv > 0`, se muestra la lista.
- Si no tiene ingredientes con stock, aparece una **alerta** y vuelve a “Mi Menú”.

## 🧩 SQL disponibles en el repo

- `SQL/restaurantes-marzo_17.sql`: dump más completo (roles, usuarios, perfiles, restaurante, platillos, inventario, categorias, res_categorias).
- `SQL/restaurantes-marzo_18.sql`: dump donde `platillos` incluye `tipo_comida` (y puede no incluir `id_cat`).
- `SQL/migracion-platillos-categorias.sql`: migración puntual para agregar `platillos.id_cat` y su FK.

## 🎨 Rediseño visual “Salud Juárez” (Tríada & Modular)

Se implementó una identidad visual modular basada en paleta tríada (Juárez: alegre, cálida y social):

- **Primario (Agave)**: `#2D5A27`
- **Secundario (Ámbar)**: `#FFBF00`
- **Acento (Frontera)**: `#2E5A88`
- **Fondo**: arena claro (`--fondo-arena`)

### CSS modular agregado

- `CSS/inicio.css`: estilo de acceso (Hero + formularios con glassmorphism ligero)
  - aplica a: `index.html`, `login.php`, `signup.php`
- `CSS/navegador.css`: navegación fija/limpia + componentes de dashboard
  - aplica a: `PHP/navbar.php`, `DIRECCIONES/dashboard.php` (y páginas que incluyen navbar)
- `CSS/platillos.css`: “cartillas de menú” + grids/formularios/tables para gestión
  - aplica a: `DIRECCIONES/gestion_platillos.php`, `inventario.php`, `editar_platillo.php`, `ver_menu.php`

### Regla UI aplicada

- Imágenes en tarjetas: `aspect-ratio: 16/9` + `object-fit: cover` para uniformidad.

## 🧪 Checklist rápido (para probar en local)

- Inicia XAMPP (Apache + MySQL en puerto 3307).
- Importa `SQL/restaurantes-marzo_17.sql` o tu dump actual.
- Ejecuta `SQL/migracion-platillos-categorias.sql` (recomendado).
- Entra a `index.html` → crea cuenta Dueño → entra al Dashboard.
- En “Inventario” agrega insumos con stock.
- En “Mi Menú” crea platillos, revisa:
  - etiqueta de categoría,
  - toggle de visibilidad,
  - botón “Ingredientes” (lista o alerta si no hay stock).

## 🖼️ Evidencia visual (screenshots)

Capturas tomadas en local para validar que los cambios quedaron funcionales.

- **Gestión de menú (botones y tarjetas)**:
  - valida: selector de categorías activo, registro de platillos, tarjetas renderizando, y acciones **Editar / Ingredientes / Ocultar / Borrar** disponibles.
  - archivo (ruta local): `C:\Users\Peque\.cursor\projects\c-xampp-htdocs-restaurantes\assets\c__Users_Peque_AppData_Roaming_Cursor_User_workspaceStorage_d793067f61017702c7580cca75c4a02a_images_Captura_de_pantalla_2026-03-18_051030-29915bf8-0614-460d-b850-2bb45ce3eba7.png`

- **phpMyAdmin (tabla `platillos`)**:
  - valida: inserción real en base de datos (registro visible en `platillos`).
  - archivo (ruta local): `C:\Users\Peque\.cursor\projects\c-xampp-htdocs-restaurantes\assets\c__Users_Peque_AppData_Roaming_Cursor_User_workspaceStorage_d793067f61017702c7580cca75c4a02a_images_Captura_de_pantalla_2026-03-18_050947-2b72b706-1636-446b-8f11-5c85128edb66.png`

  Marzo - 20 Lectura para Windsurf y revisar los demas espacios

  # Sistema de Gestión para Restaurantes - Ciudad Juárez

## Contexto y Objetivo
Desarrollar una plataforma web integral para la consulta y administración de espacios gastronómicos en Ciudad Juárez, facilitando la interacción entre usuarios finales y dueños/chefs.

## Roles del Sistema
- **Usuarios:** Consulta de restaurantes mediante filtros personalizados. Visualización de detalles de platillos (precios, imágenes, ingredientes).
- **Dueños/Chefs:** Administración de perfil de restaurante, gestión de inventario de platillos e ingredientes. Herramientas de análisis comparativo con restaurantes del mismo segmento.

## Stack Tecnológico
- **Frontend:** HTML5, CSS3, JavaScript (ES6+).
- **Backend:** PHP.
- **Base de Datos:** MySQL (Gestión vía XAMPP / phpMyAdmin).
- **Persistencia SQL:** Carpeta `/sql` para scripts de estructura y datos actualizados.

## Estructura de Trabajo
Se requiere una arquitectura organizada y modular, separando funciones lógicas de la visualización (estilo MVC simplificado) para optimizar el mantenimiento y escalabilidad del código.


