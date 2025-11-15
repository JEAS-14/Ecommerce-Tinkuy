<?php
// Auto-creación de la base de datos de prueba y datos mínimos
// Ejecutado desde test/bootstrap.php

$host = 'localhost';
$user = 'root';
$pass = '';
$testDb = 'tinkuy_db_test';

// Conexión inicial sin seleccionar BD
$rootConn = @new mysqli($host, $user, $pass);
if ($rootConn->connect_errno) {
    // Si falla, no interrumpimos los tests unitarios que no usan DB
    return;
}

$rootConn->query("CREATE DATABASE IF NOT EXISTS `$testDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$rootConn->close();

// Conectar a la BD de pruebas
$connTest = @new mysqli($host, $user, $pass, $testDb);
if ($connTest->connect_errno) {
    return;
}

// Crear tablas mínimas si no existen
$connTest->query("CREATE TABLE IF NOT EXISTS productos (\n  id_producto INT AUTO_INCREMENT PRIMARY KEY,\n  nombre_producto VARCHAR(150) NOT NULL,\n  imagen_principal VARCHAR(255) DEFAULT NULL\n) ENGINE=InnoDB");

$connTest->query("CREATE TABLE IF NOT EXISTS variantes_producto (\n  id_variante INT AUTO_INCREMENT PRIMARY KEY,\n  id_producto INT NOT NULL,\n  talla VARCHAR(20) DEFAULT NULL,\n  color VARCHAR(50) DEFAULT NULL,\n  precio DECIMAL(10,2) NOT NULL DEFAULT 0,\n  stock INT NOT NULL DEFAULT 0,\n  FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE\n) ENGINE=InnoDB");

$connTest->query("CREATE TABLE IF NOT EXISTS direcciones (\n  id_direccion INT AUTO_INCREMENT PRIMARY KEY,\n  id_usuario INT NOT NULL,\n  direccion VARCHAR(255) DEFAULT 'Direccion Test',\n  ciudad VARCHAR(100) DEFAULT 'Ciudad',\n  pais VARCHAR(100) DEFAULT 'Pais'\n) ENGINE=InnoDB");

$connTest->query("CREATE TABLE IF NOT EXISTS pedidos (\n  id_pedido INT AUTO_INCREMENT PRIMARY KEY,\n  id_usuario INT NOT NULL,\n  id_direccion_envio INT NOT NULL,\n  id_estado_pedido INT NOT NULL,\n  total_pedido DECIMAL(10,2) NOT NULL,\n  fecha_pedido DATETIME NOT NULL,\n  FOREIGN KEY (id_direccion_envio) REFERENCES direcciones(id_direccion)\n) ENGINE=InnoDB");

$connTest->query("CREATE TABLE IF NOT EXISTS detalle_pedido (\n  id_detalle INT AUTO_INCREMENT PRIMARY KEY,\n  id_pedido INT NOT NULL,\n  id_variante INT NOT NULL,\n  cantidad INT NOT NULL,\n  precio_historico DECIMAL(10,2) NOT NULL,\n  FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE CASCADE,\n  FOREIGN KEY (id_variante) REFERENCES variantes_producto(id_variante)\n) ENGINE=InnoDB");

// Seed de datos si están vacíos
$res = $connTest->query("SELECT COUNT(*) AS c FROM productos");
if ($res && ($row = $res->fetch_assoc()) && (int)$row['c'] === 0) {
    $connTest->query("INSERT INTO productos (nombre_producto, imagen_principal) VALUES\n      ('Producto Test', 'placeholder.jpg')");
    $idProd = $connTest->insert_id;
    $connTest->query("INSERT INTO variantes_producto (id_producto, talla, color, precio, stock) VALUES\n      ($idProd, 'M', 'Rojo', 10.00, 500),\n      ($idProd, 'L', 'Azul', 20.00, 500)");
} else {
    // Restaurar stock tras cada ejecución
    $connTest->query("UPDATE variantes_producto SET stock = 500 WHERE id_variante IN (1,2)");
}

$res = $connTest->query("SELECT COUNT(*) AS c FROM direcciones WHERE id_usuario = 1");
if ($res && ($row = $res->fetch_assoc()) && (int)$row['c'] === 0) {
    $connTest->query("INSERT INTO direcciones (id_usuario, direccion, ciudad, pais) VALUES (1, 'Direccion 1', 'Ciudad', 'Pais')");
}

$connTest->close();
?>