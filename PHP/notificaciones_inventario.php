<?php
/**
 * Sistema de Notificaciones de Inventario - Alternativa a TRIGGERS
 * Compatible con InfinityFree (sin TRIGGERS en BD)
 * Versión: 1.0.0
 * Fecha: 3 de Abril de 2026
 */

require_once 'conexion.php';

class NotificacionesInventario {
    private $conn;
    private $umbral_stock_bajo = 5; // Umbral para notificaciones
    
    public function __construct($conexion) {
        $this->conn = $conexion;
    }
    
    /**
     * Verificar y notificar stock bajo después de actualizar inventario
     * Esta función reemplaza al TRIGGER `notificar_stock_bajo`
     */
    public function verificarNotificarStockBajo($id_inv, $nuevo_stock, $stock_anterior) {
        // Solo notificar si el stock bajó del umbral y antes estaba por encima
        if ($nuevo_stock <= $this->umbral_stock_bajo && $stock_anterior > $this->umbral_stock_bajo) {
            $this->crearNotificacionStockBajo($id_inv, $nuevo_stock);
        }
    }
    
    /**
     * Crear notificación de stock bajo
     */
    private function crearNotificacionStockBajo($id_inv, $stock_actual) {
        try {
            // Obtener información del insumo y restaurante
            $stmt = $this->conn->prepare("
                SELECT i.nombre_insumo, i.medida_inv, i.id_res, r.nombre_res, r.id_usu
                FROM inventario i
                JOIN restaurante r ON i.id_res = r.id_res
                WHERE i.id_inv = ?
            ");
            $stmt->execute([$id_inv]);
            $insumo_info = $stmt->fetch_assoc();
            
            if ($insumo_info) {
                // Crear notificación
                $stmt_notif = $this->conn->prepare("
                    INSERT INTO notificaciones (
                        id_usu, 
                        id_res, 
                        tipo, 
                        titulo, 
                        mensaje, 
                        enlace,
                        fecha_creacion
                    ) VALUES (?, ?, 'warning', ?, ?, ?, NOW())
                ");
                
                $titulo = '⚠️ Stock Bajo';
                $mensaje = sprintf(
                    'El insumo "%s" tiene stock bajo (%.2f %s). Es necesario reponer pronto.',
                    $insumo_info['nombre_insumo'],
                    $stock_actual,
                    $insumo_info['medida_inv']
                );
                $enlace = 'DIRECCIONES/inventario/inventario_crud.php?id_res=' . $insumo_info['id_res'];
                
                $stmt_notif->execute([
                    $insumo_info['id_usu'],
                    $insumo_info['id_res'],
                    $titulo,
                    $mensaje,
                    $enlace
                ]);
                
                // Registrar en log para debugging
                $this->registrarLogNotificacion($insumo_info['id_usu'], $insumo_info['id_res'], 'stock_bajo', $mensaje);
                
                return true;
            }
        } catch (Exception $e) {
            error_log("Error creando notificación de stock bajo: " . $e->getMessage());
            return false;
        }
        
        return false;
    }
    
    /**
     * Verificar todo el inventario de un restaurante para stock bajo
     */
    public function verificarInventarioCompleto($id_res) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id_inv, nombre_insumo, stock_inv, medida_inv
                FROM inventario 
                WHERE id_res = ? AND stock_inv <= ?
            ");
            $stmt->execute([$id_res, $this->umbral_stock_bajo]);
            $insumos_bajos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            foreach ($insumos_bajos as $insumo) {
                $this->crearNotificacionStockBajo($insumo['id_inv'], $insumo['stock_inv']);
            }
            
            return count($insumos_bajos);
        } catch (Exception $e) {
            error_log("Error verificando inventario completo: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtener notificaciones no leídas de un usuario
     */
    public function obtenerNotificacionesNoLeidas($id_usu) {
        try {
            $stmt = $this->conn->prepare("
                SELECT n.*, r.nombre_res
                FROM notificaciones n
                LEFT JOIN restaurante r ON n.id_res = r.id_res
                WHERE n.id_usu = ? AND n.leida = 0
                ORDER BY n.fecha_creacion DESC
                LIMIT 10
            ");
            $stmt->execute([$id_usu]);
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error obteniendo notificaciones: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida($id_notif, $id_usu) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notificaciones 
                SET leida = 1 
                WHERE id_notif = ? AND id_usu = ?
            ");
            return $stmt->execute([$id_notif, $id_usu]);
        } catch (Exception $e) {
            error_log("Error marcando notificación como leída: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasComoLeidas($id_usu) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE notificaciones 
                SET leida = 1 
                WHERE id_usu = ? AND leida = 0
            ");
            return $stmt->execute([$id_usu]);
        } catch (Exception $e) {
            error_log("Error marcando todas notificaciones como leídas: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear notificación personalizada
     */
    public function crearNotificacionPersonalizada($id_usu, $id_res, $tipo, $titulo, $mensaje, $enlace = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notificaciones (
                    id_usu, 
                    id_res, 
                    tipo, 
                    titulo, 
                    mensaje, 
                    enlace,
                    fecha_creacion
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $resultado = $stmt->execute([$id_usu, $id_res, $tipo, $titulo, $mensaje, $enlace]);
            
            if ($resultado) {
                $this->registrarLogNotificacion($id_usu, $id_res, $tipo, $mensaje);
            }
            
            return $resultado;
        } catch (Exception $e) {
            error_log("Error creando notificación personalizada: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar log de notificaciones (reemplaza TRIGGER de validación)
     */
    private function registrarLogNotificacion($id_usu, $id_res, $accion, $mensaje) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO validacion_log (id_res, id_admin, accion, motivo, fecha_accion)
                VALUES (?, ?, 'notificacion', ?, NOW())
            ");
            $stmt->execute([$id_res, $id_usu, $accion . ': ' . $mensaje]);
        } catch (Exception $e) {
            error_log("Error registrando log de notificación: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener contador de notificaciones no leídas
     */
    public function obtenerContadorNoLeidas($id_usu) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total
                FROM notificaciones 
                WHERE id_usu = ? AND leida = 0
            ");
            $stmt->execute([$id_usu]);
            $resultado = $stmt->fetch_assoc();
            return $resultado['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error obteniendo contador de notificaciones: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Eliminar notificaciones antiguas (más de 30 días)
     */
    public function limpiarNotificacionesAntiguas() {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM notificaciones 
                WHERE fecha_creacion < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error limpiando notificaciones antiguas: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Función helper para usar en archivos de actualización de inventario
 */
function verificarStockBajoDespuesActualizar($conn, $id_inv, $nuevo_stock, $stock_anterior) {
    $notificaciones = new NotificacionesInventario($conn);
    return $notificaciones->verificarNotificarStockBajo($id_inv, $nuevo_stock, $stock_anterior);
}

/**
 * Función helper para verificar inventario completo
 */
function verificarInventarioCompleto($conn, $id_res) {
    $notificaciones = new NotificacionesInventario($conn);
    return $notificaciones->verificarInventarioCompleto($id_res);
}
?>
