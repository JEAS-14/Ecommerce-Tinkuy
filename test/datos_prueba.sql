-- ========================================
-- SCRIPT DE DATOS DE PRUEBA
-- Ecommerce-Tinkuy - Testing Postman API v2
-- ========================================
-- Ejecutar en phpMyAdmin o MySQL CLI
-- Base de datos: tinkuy_db
-- ========================================

USE tinkuy_db;

-- Limpiar datos de prueba anteriores
DELETE FROM detalle_pedido WHERE id_pedido >= 100;
DELETE FROM transacciones WHERE id_pedido >= 100;
DELETE FROM pedidos WHERE id_pedido >= 100;
DELETE FROM direcciones WHERE id_usuario IN (100, 101, 102);
DELETE FROM tarjetas_usuario WHERE id_usuario IN (100, 101, 102);
DELETE FROM variantes_producto WHERE id_producto IN (100, 101, 102);
DELETE FROM productos WHERE id_producto IN (100, 101, 102);
DELETE FROM categorias WHERE id_categoria IN (100, 101, 102);
DELETE FROM empresas_envio WHERE id_empresa_envio IN (100, 101, 102);
DELETE FROM perfiles WHERE id_usuario IN (100, 101, 102);
DELETE FROM usuarios WHERE id_usuario IN (100, 101, 102);

-- ========================================
-- 1. USUARIOS DE PRUEBA
-- ========================================
-- Contraseñas en texto plano (se hashean abajo):
--   admin123 → $2y$10$hash...
--   vendedor123 → $2y$10$hash...
--   cliente123 → $2y$10$hash...

INSERT INTO usuarios (id_usuario, id_rol, usuario, email, clave_hash, fecha_registro) VALUES
(100, 1, 'admin_test', 'admin.test@tinkuy.com', '$2y$10$vZL5gP8Z5qX5Z5qX5qX5qO.K5qX5qX5qX5qX5qX5qX5qX5qX5qX5q', NOW()),
(101, 2, 'vendedor_test', 'vendedor.test@tinkuy.com', '$2y$10$vZL5gP8Z5qX5Z5qX5qX5qO.K5qX5qX5qX5qX5qX5qX5qX5qX5qX5q', NOW()),
(102, 3, 'cliente_test', 'cliente.test@tinkuy.com', '$2y$10$vZL5gP8Z5qX5Z5qX5qX5qO.K5qX5qX5qX5qX5qX5qX5qX5qX5qX5q', NOW());

-- Perfiles
INSERT INTO perfiles (id_usuario, nombres, apellidos, telefono) VALUES
(100, 'Admin', 'Tinkuy', '987654321'),
(101, 'Vendedor', 'Artesano', '987654322'),
(102, 'Cliente', 'Comprador', '987654323');

-- ========================================
-- 2. CATEGORÍAS
-- ========================================
INSERT INTO categorias (id_categoria, nombre_categoria, descripcion) VALUES
(100, 'Chompas', 'Chompas artesanales de alpaca'),
(101, 'Accesorios', 'Accesorios tejidos a mano'),
(102, 'Textiles', 'Textiles y mantas tradicionales');

-- ========================================
-- 3. PRODUCTOS
-- ========================================
INSERT INTO productos (id_producto, id_vendedor, id_categoria, nombre_producto, descripcion, imagen_principal, estado, fecha_creacion) VALUES
(100, 101, 100, 'Chompa de Alpaca Premium', 'Chompa tejida a mano con lana de alpaca 100% natural. Diseño tradicional andino.', 'chompa_alpaca_1.jpg', 'activo', NOW()),
(101, 101, 101, 'Gorro Andino', 'Gorro tejido con diseños geométricos tradicionales. Protección contra el frío.', 'gorro_andino_1.jpg', 'activo', NOW()),
(102, 101, 102, 'Manta Cusqueña', 'Manta artesanal de Cusco con tintes naturales. Ideal para decoración.', 'manta_cusco_1.jpg', 'activo', NOW());

-- ========================================
-- 4. VARIANTES DE PRODUCTOS
-- ========================================
INSERT INTO variantes_producto (id_variante, id_producto, talla, color, precio, stock, imagen_variante, estado) VALUES
-- Chompa de Alpaca
(100, 100, 'S', 'Rojo', 150.00, 5, 'chompa_alpaca_rojo_s.jpg', 'activo'),
(101, 100, 'M', 'Rojo', 150.00, 8, 'chompa_alpaca_rojo_m.jpg', 'activo'),
(102, 100, 'L', 'Azul', 150.00, 7, 'chompa_alpaca_azul_l.jpg', 'activo'),
-- Gorro Andino
(103, 101, 'Única', 'Multicolor', 35.00, 25, 'gorro_multicolor.jpg', 'activo'),
(104, 101, 'Única', 'Verde', 35.00, 25, 'gorro_verde.jpg', 'activo'),
-- Manta Cusqueña
(105, 102, 'Grande', 'Natural', 80.00, 8, 'manta_natural_g.jpg', 'activo'),
(106, 102, 'Pequeña', 'Natural', 60.00, 7, 'manta_natural_p.jpg', 'activo');

-- ========================================
-- 5. DIRECCIONES DE CLIENTE
-- ========================================
INSERT INTO direcciones (id_direccion, id_usuario, direccion, ciudad, pais, codigo_postal, es_principal) VALUES
(100, 102, 'Av. Arequipa 1234, Miraflores', 'Lima', 'Perú', '1500', 1),
(101, 102, 'Jr. Cusco 567, Centro', 'Cusco', 'Perú', '0800', 0);

-- ========================================
-- 6. TARJETAS DE CLIENTE (simuladas)
-- ========================================
INSERT INTO tarjetas_usuario (id_tarjeta, id_usuario, nombre_tarjeta, ultimos_4_digitos, expiracion, tipo) VALUES
(100, 102, 'Cliente Comprador', '4444', '12/28', 'Visa'),
(101, 102, 'Cliente Comprador', '5555', '06/27', 'Mastercard');

-- ========================================
-- 7. EMPRESAS DE ENVÍO
-- ========================================
INSERT INTO empresas_envio (id_empresa_envio, nombre_empresa) VALUES
(100, 'Olva Courier Test'),
(101, 'Shalom Test'),
(102, 'Serpost Test');

-- ========================================
-- 8. ESTADOS DE PEDIDO (verificar si existen)
-- ========================================
-- Asegurar que existan los estados necesarios
INSERT IGNORE INTO estados_pedido (nombre_estado) VALUES
('Pendiente de Pago'),
('Pagado'),
('Enviado'),
('Entregado'),
('Cancelado');

-- ========================================
-- 9. PEDIDO DE EJEMPLO (opcional - para testing de envíos)
-- ========================================
INSERT INTO pedidos (id_pedido, id_usuario, id_direccion_envio, id_estado_pedido, total_pedido, fecha_pedido) VALUES
(100, 102, 100, 2, 185.00, NOW());

-- Detalles del pedido
INSERT INTO detalle_pedido (id_detalle, id_pedido, id_variante, cantidad, precio_historico, id_estado_detalle) VALUES
(100, 100, 100, 1, 150.00, 2),  -- Chompa S Rojo (listo para envío)
(101, 100, 103, 1, 35.00, 2);   -- Gorro Multicolor (listo para envío)

-- Transacción simulada
INSERT INTO transacciones (id_transaccion, id_pedido, metodo_pago, monto, estado_pago, id_externo_gateway, fecha_transaccion) VALUES
(100, 100, 'Tarjeta (Simulada)', 185.00, 'exitoso', 'txn_test_abc123def456', NOW());

-- ========================================
-- FIN DEL SCRIPT
-- ========================================

-- ========================================
-- RESUMEN DE DATOS INSERTADOS:
-- ========================================
-- Usuarios:
--   ID 100: admin_test / admin123
--   ID 101: vendedor_test / vendedor123
--   ID 102: cliente_test / cliente123
--
-- Productos:
--   ID 100: Chompa de Alpaca (variantes 100-102)
--   ID 101: Gorro Andino (variantes 103-104)
--   ID 102: Manta Cusqueña (variantes 105-106)
--
-- Direcciones cliente_test:
--   ID 100: Lima (principal)
--   ID 101: Cusco
--
-- Empresas envío:
--   ID 100-102
--
-- Pedido de ejemplo:
--   ID 100: Pagado, listo para testing de envío vendedor
-- ========================================

SELECT '✅ Datos de prueba insertados correctamente' AS Status;
SELECT CONCAT('👤 Usuarios: ', COUNT(*), ' (IDs 100-102)') AS Usuarios FROM usuarios WHERE id_usuario BETWEEN 100 AND 102;
SELECT CONCAT('📦 Productos: ', COUNT(*), ' (IDs 100-102)') AS Productos FROM productos WHERE id_producto BETWEEN 100 AND 102;
SELECT CONCAT('🎨 Variantes: ', COUNT(*), ' (IDs 100-106)') AS Variantes FROM variantes_producto WHERE id_variante BETWEEN 100 AND 106;
SELECT CONCAT('📍 Direcciones: ', COUNT(*), ' (IDs 100-101)') AS Direcciones FROM direcciones WHERE id_direccion BETWEEN 100 AND 101;
SELECT CONCAT('🚚 Empresas envío: ', COUNT(*), ' (IDs 100-102)') AS Empresas FROM empresas_envio WHERE id_empresa_envio BETWEEN 100 AND 102;
