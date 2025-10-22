<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$sql = "SELECT pedidos.id, usuarios.usuario, pedidos.fecha, pedidos.estado, pedidos.total
        FROM pedidos
        JOIN usuarios ON pedidos.usuario_id = usuarios.id
        ORDER BY pedidos.fecha DESC";
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos | Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h2 class="mb-4">Listado de Pedidos</h2>
        <table class="table table-bordered table-hover bg-white">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total (S/)</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($pedido = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?= $pedido['id'] ?></td>
                        <td><?= htmlspecialchars($pedido['usuario']) ?></td>
                        <td><?= $pedido['fecha'] ?></td>
                        <td><?= ucfirst($pedido['estado']) ?></td>
                        <td><?= number_format($pedido['total'], 2) ?></td>
                        <td><a href="detalle_pedido.php?id=<?= $pedido['id'] ?>" class="btn btn-sm btn-primary">Ver</a></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="dashboard.php" class="btn btn-secondary mt-3">← Volver al panel</a>
    </div>
</body>
</html>
