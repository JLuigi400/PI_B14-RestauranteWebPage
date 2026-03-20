-- ========================================
-- Migración del Sistema de Gestión de Ingredientes (CORREGIDA)
-- Sistema de Restaurantes - Ciudad Juárez
-- ========================================

-- 1. Agregar columnas a la tabla inventario para ingredientes secretos y alergenos
ALTER TABLE inventario 
ADD COLUMN IF NOT EXISTS es_ingrediente_secreto TINYINT(1) DEFAULT 0 COMMENT '1 = No visible para clientes',
ADD COLUMN IF NOT EXISTS alergenos TEXT NULL COMMENT 'Lista de alergenos separados por comas',
ADD COLUMN IF NOT EXISTS fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 2. Crear tabla para relación de platillos e ingredientes con cantidades
CREATE TABLE IF NOT EXISTS platillo_ingredientes (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pla INT NOT NULL,
    id_inv INT NOT NULL,
    cantidad_usada DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Cantidad de este ingrediente usada en el platillo',
    unidad_usada VARCHAR(20) NULL COMMENT 'Unidad de medida usada en el platillo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_platillo_ingrediente (id_pla, id_inv),
    FOREIGN KEY (id_pla) REFERENCES platillos(id_pla) ON DELETE CASCADE,
    FOREIGN KEY (id_inv) REFERENCES inventario(id_inv) ON DELETE CASCADE
) COMMENT='Relación entre platillos e ingredientes con cantidades';

-- 3. Crear tabla para pedidos de re-stock
CREATE TABLE IF NOT EXISTS pedidos_restock (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_res INT NOT NULL,
    proveedor VARCHAR(100) NOT NULL COMMENT 'Nombre del proveedor',
    fecha_estimada DATE NOT NULL COMMENT 'Fecha estimada de entrega',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_entrega TIMESTAMP NULL COMMENT 'Fecha real de entrega',
    estado ENUM('pendiente', 'enviado', 'recibido', 'cancelado') DEFAULT 'pendiente',
    prioridad ENUM('normal', 'urgente') DEFAULT 'normal',
    notas TEXT NULL COMMENT 'Notas adicionales del pedido',
    total_productos INT DEFAULT 0 COMMENT 'Total de productos en el pedido',
    creado_por INT NOT NULL COMMENT 'ID del usuario que creó el pedido',
    FOREIGN KEY (id_res) REFERENCES restaurante(id_res) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id_usu) ON DELETE CASCADE
) COMMENT='Pedidos de re-stock de ingredientes';

-- 4. Crear tabla para detalles de pedidos
CREATE TABLE IF NOT EXISTS pedido_detalle (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_inv INT NOT NULL,
    cantidad_solicitada DECIMAL(10,2) NOT NULL DEFAULT 0,
    cantidad_recibida DECIMAL(10,2) DEFAULT 0 COMMENT 'Cantidad realmente recibida',
    precio_unitario DECIMAL(10,2) DEFAULT 0 COMMENT 'Precio unitario del producto',
    notas_detalle TEXT NULL COMMENT 'Notas específicas de este producto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pedido) REFERENCES pedidos_restock(id_pedido) ON DELETE CASCADE,
    FOREIGN KEY (id_inv) REFERENCES inventario(id_inv) ON DELETE CASCADE
) COMMENT='Detalles de cada pedido de re-stock';

-- 5. Crear tabla para notificaciones del sistema
CREATE TABLE IF NOT EXISTS notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usu INT NOT NULL,
    id_res INT NULL COMMENT 'ID del restaurante si aplica',
    tipo ENUM('stock_bajo', 'pedido_pendiente', 'pedido_entregado', 'sistema') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    leida TINYINT(1) DEFAULT 0 COMMENT '1 = Leída',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura TIMESTAMP NULL,
    enlace VARCHAR(500) NULL COMMENT 'Enlace a la acción relacionada',
    FOREIGN KEY (id_usu) REFERENCES usuarios(id_usu) ON DELETE CASCADE,
    FOREIGN KEY (id_res) REFERENCES restaurante(id_res) ON DELETE CASCADE
) COMMENT='Notificaciones del sistema para usuarios';

-- 6. Crear índices para mejor rendimiento
CREATE INDEX IF NOT EXISTS idx_inventario_stock ON inventario(stock_inv);
CREATE INDEX IF NOT EXISTS idx_inventario_restaurante ON inventario(id_res);
CREATE INDEX IF NOT EXISTS idx_inventario_secreto ON inventario(es_ingrediente_secreto);
CREATE INDEX IF NOT EXISTS idx_platillo_ingredientes_platillo ON platillo_ingredientes(id_pla);
CREATE INDEX IF NOT EXISTS idx_platillo_ingredientes_ingrediente ON platillo_ingredientes(id_inv);
CREATE INDEX IF NOT EXISTS idx_pedidos_restaurante ON pedidos_restock(id_res);
CREATE INDEX IF NOT EXISTS idx_pedidos_estado ON pedidos_restock(estado);
CREATE INDEX IF NOT EXISTS idx_notificaciones_usuario ON notificaciones(id_usu);
CREATE INDEX IF NOT EXISTS idx_notificaciones_leida ON notificaciones(leida);

-- 7. Crear vista para ingredientes con stock bajo
CREATE OR REPLACE VIEW vista_ingredientes_bajo AS
SELECT 
    i.id_inv,
    i.nombre_insumo,
    i.stock_inv,
    i.medida_inv,
    r.nombre_res,
    r.id_res,
    CASE 
        WHEN i.stock_inv <= 5 THEN 'crítico'
        WHEN i.stock_inv <= 10 THEN 'bajo'
        ELSE 'normal'
    END as nivel_stock
FROM inventario i
JOIN restaurante r ON i.id_res = r.id_res
WHERE i.stock_inv <= 10
ORDER BY i.stock_inv ASC;

-- 8. Crear vista para pedidos pendientes
CREATE OR REPLACE VIEW vista_pedidos_pendientes AS
SELECT 
    p.id_pedido,
    p.proveedor,
    p.fecha_estimada,
    p.estado,
    p.prioridad,
    r.nombre_res,
    COUNT(pd.id_detalle) as total_productos,
    SUM(pd.cantidad_solicitada) as total_cantidad
FROM pedidos_restock p
JOIN restaurante r ON p.id_res = r.id_res
LEFT JOIN pedido_detalle pd ON p.id_pedido = pd.id_pedido
WHERE p.estado = 'pendiente'
GROUP BY p.id_pedido
ORDER BY p.fecha_estimada ASC;

-- 9. Crear trigger para actualizar total_productos en pedidos
DELIMITER //
CREATE TRIGGER IF NOT EXISTS actualizar_total_productos_pedido
AFTER INSERT ON pedido_detalle
FOR EACH ROW
BEGIN
    UPDATE pedidos_restock 
    SET total_productos = (
        SELECT COUNT(*) 
        FROM pedido_detalle 
        WHERE id_pedido = NEW.id_pedido
    )
    WHERE id_pedido = NEW.id_pedido;
END//
DELIMITER ;

-- 10. Crear trigger para notificar stock bajo
DELIMITER //
CREATE TRIGGER IF NOT EXISTS notificar_stock_bajo
AFTER UPDATE ON inventario
FOR EACH ROW
BEGIN
    IF NEW.stock_inv <= 5 AND OLD.stock_inv > 5 THEN
        -- Insertar notificación de stock crítico
        INSERT INTO notificaciones (id_usu, id_res, tipo, titulo, mensaje, enlace)
        SELECT 
            u.id_usu,
            i.id_res,
            'stock_bajo',
            '¡Alerta Crítica de Inventario!',
            CONCAT('El ingrediente "', i.nombre_insumo, '" tiene stock crítico: ', NEW.stock_inv, ' ', i.medida_inv),
            '../DIRECCIONES/inventario_crud.php'
        FROM inventario i
        JOIN restaurante r ON i.id_res = r.id_res
        JOIN usuarios u ON r.id_usu = u.id_usu
        WHERE i.id_inv = NEW.id_inv;
    END IF;
END//
DELIMITER ;

-- 11. Insertar datos de ejemplo para pruebas (opcional)
INSERT IGNORE INTO inventario (id_res, nombre_insumo, stock_inv, medida_inv, es_ingrediente_secreto, alergenos) VALUES
(1, 'Tomate', 15.5, 'Kg', 0, NULL),
(1, 'Cebolla', 8.2, 'Kg', 0, NULL),
(1, 'Ajo', 3.1, 'Kg', 1, NULL),
(1, 'Harina', 25.0, 'Kg', 0, 'gluten'),
(1, 'Queso Oaxaca', 12.8, 'Kg', 0, 'lactosa'),
(1, 'Chile Jalapeño', 4.5, 'Kg', 0, NULL),
(1, 'Cilantro', 2.3, 'Mz', 0, NULL),
(1, 'Pollo', 18.7, 'Kg', 0, NULL),
(1, 'Arroz', 35.2, 'Kg', 0, NULL),
(1, 'Frijoles', 22.1, 'Kg', 0, NULL);

-- ========================================
-- Notas importantes sobre esta migración:
-- ========================================
-- 
-- 1. Esta migración agrega funcionalidades avanzadas de gestión de ingredientes
-- 2. Los ingredientes secretos no se mostrarán a los clientes
-- 3. Los alergenos se mostrarán como advertencias para clientes con alergias
-- 4. El sistema de re-stock permite gestionar pedidos a proveedores
-- 5. Las notificaciones alertan sobre stock bajo y eventos importantes
-- 6. Los triggers automáticos mantienen el sistema sincronizado
--
-- Para ejecutar esta migración:
-- 1. Abre phpMyAdmin (puerto 3307)
-- 2. Selecciona la base de datos "restaurantes"
-- 3. Haz clic en "Importar" y selecciona este archivo
-- 4. Ejecuta la migración
--
-- Después de la migración, limpia el caché del navegador y prueba las nuevas funcionalidades.
