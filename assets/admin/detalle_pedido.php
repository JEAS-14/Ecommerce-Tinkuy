<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Obtener ID del pedido
if (!isset($_GET['id'])) {
    echo "ID de pedido no proporcionado.";
    exit();
}

$id_pedido = $_GET['id'];

// Actualizar estado si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estado'])) {
    $nuevo_estado = $_POST['estado'];
    $stmt = $conn->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $id_pedido);
    $stmt->execute();
    $stmt->close();
}

// Obtener detalles del pedido
$stmt = $conn->prepare("SELECT p.*, u.usuario FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();
$stmt->close();

if (!$pedido) {
    echo "Pedido no encontrado.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h2>Detalle del Pedido #<?= $pedido['id'] ?></h2>
    <p><strong>Cliente:</strong> <?= htmlspecialchars($pedido['usuario']) ?></p>
    <p><strong>Fecha:</strong> <?= $pedido['fecha'] ?></p>
    <p><strong>Estado actual:</strong> <?= ucfirst($pedido['estado']) ?></p>

    <!-- Formulario para cambiar el estado -->
    <form method="POST" class="mb-4">
        <label for="estado" class="form-label">Cambiar estado:</label>
        <select name="estado" id="estado" class="form-select" required>
            <option value="pendiente" <?= $pedido['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="procesado" <?= $pedido['estado'] == 'procesado' ? 'selected' : '' ?>>Procesado</option>
            <option value="enviado" <?= $pedido['estado'] == 'enviado' ? 'selected' : '' ?>>Enviado</option>
            <option value="cancelado" <?= $pedido['estado'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
        </select>
        <button type="submit" class="btn btn-primary mt-2">Actualizar estado</button>
    </form>

    <a href="pedidos.php" class="btn btn-secondary">← Volver a pedidos</a>
</div>
</body>
</html>
