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
$mensaje_error = "";

// ----------------------------------------------------
// 2. CONSULTAR LOS PEDIDOS DEL USUARIO
// ----------------------------------------------------
try {
    $stmt = $conn->prepare("
        SELECT 
            p.id_pedido, 
            p.fecha_pedido, 
            p.total_pedido, 
            e.nombre_estado
        FROM pedidos AS p
        JOIN estados_pedido AS e ON p.id_estado_pedido = e.id_estado
        WHERE p.id_usuario = ?
        ORDER BY p.fecha_pedido DESC
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    $mensaje_error = "Error al obtener tus pedidos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis Pedidos | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'assets/component/navbar.php'; ?>

    <div class="container my-5">
        <h2 class="mb-4 text-center"><i class="bi bi-box-seam"></i> Mis Pedidos</h2>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <?php if (empty($pedidos)): ?>
            <div class="alert alert-info text-center shadow-sm">
                Aún no has realizado ningún pedido.<br>
                <a href="products.php" class="btn btn-primary mt-3"><i class="bi bi-shop"></i> Ir a comprar</a>
            </div>
        <?php else: ?>
            <div class="table-responsive shadow-sm">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Pedido</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Total (S/)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($pedido['id_pedido']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></td>
                                <td>
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
                                </td>
                                <td><strong>S/ <?= number_format($pedido['total_pedido'], 2) ?></strong></td>
                                <td>
                                    <a href="ver_pedido.php?id=<?= $pedido['id_pedido'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver Detalles
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'assets/component/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
