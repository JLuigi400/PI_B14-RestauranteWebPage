# 🥗 Sistema de Gestión para Restaurantes - Ciudad Juárez

Plataforma web integral para la consulta y administración de espacios gastronómicos en Ciudad Juárez, facilitando la interacción entre usuarios finales y dueños/chefs.

## 👥 Roles del Sistema

- **Usuario**: Puede buscar, filtrar y ver detalles de restaurantes.
- **Administrador**: Puede crear, editar y eliminar restaurantes/comidas.
- **Dueño**: Puede registrar restaurante, gestionar menú e inventario.

## 🚀 Requisitos Previos

- **PHP** >= 7.4.0
- **MySQL** (XAMPP recomendado)
- **Apache** Server
- **Composer** (opcional, para autoloading)

## 📦 Instalación

1. **Clonar el repositorio**:
   ```bash
   git clone https://github.com/JLuigi400/PI_B14-RestauranteWebPage.git
   cd PI_B14-RestauranteWebPage
   ```

2. **Configurar entorno**:
   ```bash
   cp .env.example .env
   # Editar .env con tus credenciales de base de datos
   ```

3. **Configurar base de datos**:
   - Iniciar XAMPP (Apache + MySQL en puerto 3307)
   - Crear base de datos `restaurantes`
   - Importar los archivos SQL desde la carpeta `SQL/`

4. **Configurar archivos sensibles**:
   ```bash
   # Copiar configuración base
   cp config.php.example config.php
   # Ajustar credenciales en config.php
   ```

5. **Permisos de carpetas**:
   ```bash
   chmod 755 UPLOADS/
   chmod 755 IMG/
   ```

## 🌐 Acceso a la Aplicación

- **URL principal**: `http://localhost/restaurantes/`
- **Panel de administración**: `http://localhost/restaurantes/DIRECCIONES/dashboard.php`

## 📁 Estructura del Proyecto

```
restaurantes/
├── CSS/                    # Hojas de estilo
├── DIRECCIONES/           # Panel de administración y gestión
├── IMG/                   # Imágenes del sistema
├── JS/                    # Archivos JavaScript
├── PHP/                   # Lógica del backend
├── UPLOADS/               # Archivos subidos por usuarios
├── SQL/                   # Scripts de base de datos (local)
├── .htaccess              # Configuración de Apache
├── config.php             # Configuración centralizada
├── composer.json          # Dependencias PHP
├── index.html             # Página principal
├── login.php              # Inicio de sesión
└── signup.php             # Registro de usuarios
```

## 🗄️ Base de Datos

### Tablas Principales
- `usuarios`: Credenciales y roles
- `perfiles`: Datos personales
- `restaurante`: Información de negocios
- `platillos`: Menú de restaurantes
- `inventario`: Ingredientes y stock
- `categorias`: Tipos de comida

### Roles del Sistema
- **1**: Administrador
- **2**: Dueño de restaurante
- **3**: Comensal/Usuario

## 🔧 Configuración

### Variables de Entorno (.env)
```env
DB_HOST=localhost
DB_PORT=3307
DB_NAME=restaurantes
DB_USER=root
DB_PASS=
DEBUG_MODE=true
```

### Configuración de Apache (.htaccess)
- URLs amigables
- Cabeceras de seguridad
- Compresión gzip
- Caché de archivos estáticos

## 🎨 Características

### Para Usuarios
- Búsqueda avanzada de restaurantes
- Filtros por sector y tipo de comida
- Visualización de menús y precios
- Sistema de calificaciones

### Para Dueños
- Gestión completa del restaurante
- Administración de menú
- Control de inventario
- Análisis comparativo

### Para Administradores
- Gestión de usuarios
- Moderación de contenido
- Estadísticas del sistema

## 🛡️ Seguridad

- Tokens CSRF en formularios
- Hashing de contraseñas (PASSWORD_DEFAULT)
- Validación de entrada de datos
- Cabeceras de seguridad HTTP
- Prevención de inyección SQL con PDO

## 🧪 Testing

```bash
# Ejecutar tests (si se instala PHPUnit)
composer test
```

## 📝 Desarrollo

### Flujo de Trabajo
1. Crear rama feature: `git checkout -b feature/nueva-funcionalidad`
2. Hacer cambios y commitear: `git commit -m "Agregar nueva funcionalidad"`
3. Push al repositorio: `git push origin feature/nueva-funcionalidad`
4. Crear Pull Request

### Convenciones
- PHP siguiendo PSR-12
- Nombres de variables en español
- Comentarios descriptivos
- Código limpio y mantenible

## 🐛 Problemas Comunes

### Error de Conexión a BD
- Verificar que MySQL esté corriendo en puerto 3307
- Confirmar credenciales en `.env`
- Revisar que la base de datos `restaurantes` exista

### Problemas de Permisos
- Asegurar que Apache tenga permisos en `UPLOADS/` y `IMG/`
- Verificar configuración en `.htaccess`

### Imágenes no se muestran
- Revisar permisos de carpeta `IMG/`
- Verificar que `UPLOADS/` tenga permisos de escritura

## 📄 Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para detalles.

## 🤝 Contribuciones

¡Las contribuciones son bienvenidas! Por favor:

1. Fork el proyecto
2. Crear una rama feature
3. Hacer tus cambios
4. Commitear tus cambios
5. Push a la rama
6. Crear un Pull Request

## 📞 Contacto

- **Autor**: JLuigi400
- **Email**: contacto@restaurantesjuarez.com
- **Issues**: [GitHub Issues](https://github.com/JLuigi400/PI_B14-RestauranteWebPage/issues)

## 🙏 Agradecimientos

- Al equipo de desarrollo por su dedicación
- A la comunidad de Ciudad Juárez por su apoyo
- A todos los restaurantes participantes

---

**Nota**: Este es un proyecto educativo y de demostración para la gestión de restaurantes en Ciudad Juárez.
