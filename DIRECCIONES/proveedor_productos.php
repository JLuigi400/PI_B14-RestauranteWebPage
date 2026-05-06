<?php
session_start();
include '../PHP/navbar.php';

// Verificar que el usuario sea proveedor (rol 4)
if (!isset($_SESSION['id_usu']) || $_SESSION['id_rol'] != 4) {
    header("Location: ../login.php");
    exit();
}

$id_proveedor = $_SESSION['id_usu'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos | Salud Juárez</title>
    <?php include '../PHP/header_meta.php'; ?>
    <link rel="stylesheet" href="../CSS/stylesheet.css">
    <link rel="stylesheet" href="../CSS/navegador.css">
    <link rel="stylesheet" href="../CSS/modal_theme.css">
    <link rel="stylesheet" href="../CSS/modal_social_icons.css">
    <style>
        /* Estilos específicos para el grid de productos - Tema Industrial Dark */
        .sj-productos-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .sj-productos-header {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid #27ae60;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .sj-productos-title {
            color: #27ae60;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-shadow: 0 0 20px rgba(39, 174, 96, 0.5);
        }

        .sj-productos-subtitle {
            color: #bdc3c7;
            font-size: 1rem;
            margin: 0;
        }

        /* Filtros de categorías */
        .sj-filtros-container {
            background: rgba(52, 73, 94, 0.8);
            border: 1px solid #34495e;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .sj-filtro-btn {
            background: #2c3e50;
            color: #ecf0f1;
            border: 1px solid #34495e;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .sj-filtro-btn:hover {
            background: #34495e;
            border-color: #27ae60;
            transform: translateY(-2px);
        }

        .sj-filtro-btn.active {
            background: #27ae60;
            border-color: #27ae60;
            box-shadow: 0 0 15px rgba(39, 174, 96, 0.5);
        }

        /* Grid de productos */
        .sj-productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .sj-producto-card {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border: 1px solid #34495e;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .sj-producto-card:hover {
            transform: translateY(-5px);
            border-color: #27ae60;
            box-shadow: 0 10px 30px rgba(39, 174, 96, 0.3);
        }

        .sj-producto-imagen {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #1a1a1a;
        }

        .sj-producto-info {
            padding: 15px;
        }

        .sj-producto-nombre {
            color: #ecf0f1;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 8px 0;
        }

        .sj-producto-categoria {
            color: #27ae60;
            font-size: 0.85rem;
            font-weight: 500;
            margin: 0 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sj-producto-descripcion {
            color: #95a5a6;
            font-size: 0.9rem;
            margin: 0 0 12px 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .sj-producto-precios {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .sj-precio-unitario {
            color: #f39c12;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .sj-precio-mayoreo {
            color: #e74c3c;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .sj-producto-acciones {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .sj-disponibilidad-toggle {
            position: relative;
            width: 50px;
            height: 24px;
            background: #7f8c8d;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .sj-disponibilidad-toggle.active {
            background: #27ae60;
        }

        .sj-disponibilidad-toggle::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s ease;
        }

        .sj-disponibilidad-toggle.active::after {
            transform: translateX(26px);
        }

        .sj-acciones-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .sj-acciones-btn:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .sj-acciones-btn.editar {
            background: #f39c12;
        }

        .sj-acciones-btn.editar:hover {
            background: #e67e22;
        }

        .sj-acciones-btn.eliminar {
            background: #e74c3c;
        }

        .sj-acciones-btn.eliminar:hover {
            background: #c0392b;
        }

        /* Botón flotante para agregar producto */
        .sj-fab-agregar {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: #27ae60;
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(39, 174, 96, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sj-fab-agregar:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(39, 174, 96, 0.6);
        }

        /* Modal de producto */
        .sj-modal-producto {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .sj-modal-content {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border: 1px solid #34495e;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .sj-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .sj-modal-title {
            color: #27ae60;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .sj-modal-close {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }

        .sj-form-grupo {
            margin-bottom: 20px;
        }

        .sj-form-label {
            color: #ecf0f1;
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .sj-form-input,
        .sj-form-select,
        .sj-form-textarea {
            width: 100%;
            padding: 10px;
            background: #1a1a1a;
            border: 1px solid #34495e;
            border-radius: 6px;
            color: #ecf0f1;
            font-size: 1rem;
        }

        .sj-form-input:focus,
        .sj-form-select:focus,
        .sj-form-textarea:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 10px rgba(39, 174, 96, 0.3);
        }

        .sj-form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .sj-form-precios {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .sj-imagen-preview {
            width: 100%;
            height: 200px;
            background: #1a1a1a;
            border: 2px dashed #34495e;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
            margin-top: 10px;
            overflow: hidden;
        }

        .sj-imagen-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sj-modal-footer {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .sj-btn-primary {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .sj-btn-primary:hover {
            background: #219a52;
            transform: translateY(-2px);
        }

        .sj-btn-secondary {
            background: #7f8c8d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .sj-btn-secondary:hover {
            background: #6c7a7d;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sj-productos-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 15px;
            }

            .sj-filtros-container {
                justify-content: center;
            }

            .sj-form-precios {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sj-productos-container">
        <!-- Header -->
        <div class="sj-productos-header">
            <h1 class="sj-productos-title">Gestión de Productos</h1>
            <p class="sj-productos-subtitle">Administra tu catálogo de productos para restaurantes</p>
        </div>

        <!-- Filtros de Categorías -->
        <div class="sj-filtros-container">
            <span style="color: #ecf0f1; font-weight: 500;">Filtrar por categoría:</span>
            <button class="sj-filtro-btn active" data-categoria="todos">Todos</button>
            <button class="sj-filtro-btn" data-categoria="Abarrotes">Abarrotes</button>
            <button class="sj-filtro-btn" data-categoria="Frescos">Frescos</button>
            <button class="sj-filtro-btn" data-categoria="Lácteos">Lácteos</button>
            <button class="sj-filtro-btn" data-categoria="Carnes">Carnes</button>
            <button class="sj-filtro-btn" data-categoria="Bebidas">Bebidas</button>
            <button class="sj-filtro-btn" data-categoria="Embalaje">Embalaje</button>
        </div>

        <!-- Grid de Productos -->
        <div class="sj-productos-grid" id="productosGrid">
            <!-- Los productos se cargarán dinámicamente con JavaScript -->
        </div>

        <!-- Botón flotante para agregar producto -->
        <button class="sj-fab-agregar" id="btnAgregarProducto">+</button>

        <!-- Modal de Producto -->
        <div class="sj-modal-producto" id="modalProducto">
            <div class="sj-modal-content">
                <div class="sj-modal-header">
                    <h2 class="sj-modal-title" id="modalTitle">Agregar Producto</h2>
                    <button class="sj-modal-close" id="btnCerrarModal">×</button>
                </div>
                
                <form id="formProducto">
                    <input type="hidden" id="idProducto" name="id_producto">
                    
                    <div class="sj-form-grupo">
                        <label class="sj-form-label" for="nombreProducto">Nombre del Producto *</label>
                        <input type="text" class="sj-form-input" id="nombreProducto" name="nombre_producto" required>
                    </div>

                    <div class="sj-form-grupo">
                        <label class="sj-form-label" for="descripcionProducto">Descripción *</label>
                        <textarea class="sj-form-textarea" id="descripcionProducto" name="descripcion_producto" required></textarea>
                    </div>

                    <div class="sj-form-grupo">
                        <label class="sj-form-label" for="categoriaProducto">Categoría *</label>
                        <select class="sj-form-select" id="categoriaProducto" name="categoria_producto" required>
                            <option value="">Selecciona una categoría</option>
                            <option value="Abarrotes">Abarrotes</option>
                            <option value="Frescos">Frescos</option>
                            <option value="Lácteos">Lácteos</option>
                            <option value="Carnes">Carnes</option>
                            <option value="Bebidas">Bebidas</option>
                            <option value="Embalaje">Embalaje</option>
                        </select>
                    </div>

                    <div class="sj-form-grupo">
                        <label class="sj-form-label" for="unidadMedida">Unidad de Medida *</label>
                        <select class="sj-form-select" id="unidadMedida" name="unidad_medida" required>
                            <option value="">Selecciona unidad</option>
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="l">l</option>
                            <option value="ml">ml</option>
                            <option value="pieza">pieza</option>
                            <option value="caja">caja</option>
                            <option value="paquete">paquete</option>
                            <option value="saco">saco</option>
                            <option value="tonel">tonel</option>
                        </select>
                    </div>

                    <div class="sj-form-grupo">
                        <label class="sj-form-label">Precios</label>
                        <div class="sj-form-precios">
                            <div>
                                <label class="sj-form-label" for="precioUnitario">Precio Unitario *</label>
                                <input type="number" class="sj-form-input" id="precioUnitario" name="precio_unitario" step="0.01" min="0" required>
                            </div>
                            <div>
                                <label class="sj-form-label" for="precioMayoreo">Precio Mayoreo</label>
                                <input type="number" class="sj-form-input" id="precioMayoreo" name="precio_mayoreo" step="0.01" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="sj-form-grupo" id="grupoCantidadMinima" style="display: none;">
                        <label class="sj-form-label" for="cantidadMinima">Cantidad Mínima para Mayoreo *</label>
                        <input type="number" class="sj-form-input" id="cantidadMinima" name="cantidad_minima_mayoreo" step="1" min="1">
                    </div>

                    <div class="sj-form-grupo">
                        <label class="sj-form-label" for="imagenProducto">Imagen del Producto</label>
                        <input type="file" class="sj-form-input" id="imagenProducto" name="imagen_producto" accept="image/*">
                        <div class="sj-imagen-preview" id="imagenPreview">
                            <span>Previsualización de imagen</span>
                        </div>
                    </div>

                    <div class="sj-modal-footer">
                        <button type="button" class="sj-btn-secondary" id="btnCancelar">Cancelar</button>
                        <button type="submit" class="sj-btn-primary">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../JS/gestion_productos_proveedor.js"></script>
    <script src="../JS/session_check.js"></script>
</body>
</html>
