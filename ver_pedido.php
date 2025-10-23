<?php
session_start();
include 'assets/admin/db.php';

// ----------------------------------------------------
// 1. CONTROL DE ACCESO (Seguridad)
// ----------------------------------------------------
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$id_pedido = intval($_GET['id'] ?? 0);

// ----------------------------------------------------
// 2. CONSULTAR INFORMACIÓN DEL PEDIDO
// ----------------------------------------------------
$stmt = $conn->prepare("
    SELECT 
        p.id_pedido, 
        p.fecha_pedido, 
        p.total_pedido, 
        e.nombre_estado, 
        d.direccion, 
        d.ciudad, 
        d.pais
    FROM pedidos AS p
    JOIN estados_pedido AS e ON p.id_estado_pedido = e.id_estado
    JOIN direcciones AS d ON p.id_direccion_envio = d.id_direccion
    WHERE p.id_pedido = ? AND p.id_usuario = ?
");
$stmt->bind_param("ii", $id_pedido, $id_usuario);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    die("<div style='margin:50px; font-family:sans-serif;'>
        <h3 style='color:red;'>Pedido no encontrado o no autorizado.</h3>
        <a href='pedidos.php' style='text-decoration:none; color:#0d6efd;'>Volver a mis pedidos</a>
    </div>");
}

// ----------------------------------------------------
// 3. DETALLES DE LOS PRODUCTOS DEL PEDIDO
// ----------------------------------------------------
$stmt_det = $conn->prepare("
    SELECT 
        pr.nombre_producto, 
        dp.cantidad, 
        dp.precio_historico
    FROM detalle_pedido AS dp
    JOIN variantes_producto AS v ON dp.id_variante = v.id_variante
    JOIN productos AS pr ON v.id_producto = pr.id_producto
    WHERE dp.id_pedido = ?
");
$stmt_det->bind_param("i", $id_pedido);
$stmt_det->execute();
$detalles = $stmt_det->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Pedido #<?= $id_pedido ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'assets/component/navbar.php'; ?>

    <div class="container my-5">
        <h3 class="mb-3"><i class="bi bi-receipt"></i> Pedido #<?= htmlspecialchars($id_pedido) ?></h3>
        
        <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></p>
        <p>
            <strong>Estado:</strong>
            <span class="badge 
                <?php
                    switch ($pedido['nombre_estado']) {
                        case 'Pagado': echo 'bg-success'; break;
                        case 'Pendiente de Pago': echo 'bg-warning text-dark'; break;
                        case 'Enviado': echo 'bg-info text-dark'; break;
                        case 'Entregado': echo 'bg-primary'; break;
                        case 'Cancelado': echo 'bg-danger'; break;
                        default: echo 'bg-secondary';
                    }
                ?>">
                <?= htmlspecialchars($pedido['nombre_estado']) ?>
            </span>
        </p>
        <p><strong>Enviado a:</strong> <?= htmlspecialchars($pedido['direccion']) ?>, <?= htmlspecialchars($pedido['ciudad']) ?> (<?= htmlspecialchars($pedido['pais']) ?>)</p>

        <hr>

        <h5>Productos del pedido</h5>
        <table class="table table-striped mt-3">
            <thead class="table-dark">
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                foreach ($detalles as $d):
                    $subtotal = $d['cantidad'] * $d['precio_historico'];
                    $total += $subtotal;
                ?>
                <tr>
                    <td><?= htmlspecialchars($d['nombre_producto']) ?></td>
                    <td><?= (int)$d['cantidad'] ?></td>
                    <td>S/ <?= number_format($d['precio_historico'], 2) ?></td>
                    <td>S/ <?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <th colspan="3" class="text-end">Total</th>
                    <th>S/ <?= number_format($total, 2) ?></th>
                </tr>
            </tfoot>
        </table>

        <a href="pedidos.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Mis Pedidos
        </a>
    </div>
</body>
</html>
