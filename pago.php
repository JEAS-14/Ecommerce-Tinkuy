<?php
session_start();
require_once 'assets/admin/db.php';

$carrito = $_SESSION['carrito'] ?? [];
$usuario_id = $_SESSION['usuario_id'] ?? null; // Asegúrate que este ID esté disponible tras el login

if (empty($carrito) || !$usuario_id) {
    header('Location: cart.php');
    exit;
}

$total = 0;
foreach ($carrito as $producto) {
    $total += $producto['precio'] * $producto['cantidad'];
}

// 1. Insertar pedido
$stmt = $conn->prepare("INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)");
$stmt->bind_param("id", $usuario_id, $total);
$stmt->execute();
$pedido_id = $stmt->insert_id;

// 2. Insertar detalle del pedido y actualizar stock
foreach ($carrito as $id => $producto) {
    $producto_id = $id;
    $cantidad = $producto['cantidad'];
    $precio_unitario = $producto['precio'];

    // Insertar en detalle_pedido
    $stmt_det = $conn->prepare("INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmt_det->bind_param("iiid", $pedido_id, $producto_id, $cantidad, $precio_unitario);
    $stmt_det->execute();

    // Actualizar stock
    $stmt_stock = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?");
    $stmt_stock->bind_param("iii", $cantidad, $producto_id, $cantidad);
    $stmt_stock->execute();
}

// Vaciar carrito
unset($_SESSION['carrito']);

// Redirigir a página de gracias
header('Location: gracias.php');
exit;
