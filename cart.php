<?php
session_start();
require_once 'assets/component/navbar.php';

// Inicializar carrito si no existe
$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Carrito de Compras | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>

<body class="d-flex flex-column min-vh-100">
    <div class="flex-fill">
    <main class="container my-5">
        <h1 class="mb-4 text-center text-dark">Carrito de Compras</h1>

        <div class="cart-items">
            <?php if (empty($carrito)): ?>
                <div class="alert alert-info text-center">Tu carrito está vacío 😢</div>
            <?php else: ?>
                <?php foreach ($carrito as $id => $producto):
                    $subtotal = $producto['precio'] * $producto['cantidad'];
                    $total += $subtotal;
                    ?>
                    <div class="row align-items-center mb-3 p-3 bg-light rounded shadow-sm">
                        <div class="col-md-2 text-center">
                            <img src="assets/img/<?= htmlspecialchars($producto['imagen']) ?>"
                                alt="<?= htmlspecialchars($producto['nombre']) ?>" class="img-fluid rounded" />
                        </div>
                        <div class="col-md-4">
                            <h5><?= htmlspecialchars($producto['nombre']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($producto['descripcion']) ?></p>
                        </div>
                        <div class="col-md-2 text-center fw-bold fs-5 text-dark">S/ <?= number_format($producto['precio'], 2) ?>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Cantidad:</label>
                            <input type="number" class="form-control" value="<?= $producto['cantidad'] ?>" min="1" readonly>
                        </div>
                        <div class="col-md-2 text-center">
                            <form action="eliminar_carrito.php" method="POST">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button type="submit" class="btn btn-danger mt-3 mt-md-0">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($carrito)): ?>
            <!-- Total -->
            <div class="text-end mt-4 border-top pt-3">
                <h4>Total: <span class="text-success">S/ <?= number_format($total, 2) ?></span></h4>
            </div>

            <!-- Botones de acción -->
            <div class="d-flex justify-content-between mt-4">
                <a href="products.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Seguir
                    comprando</a>
                <a href="pago.php" class="btn btn-success"><i class="bi bi-cash-stack"></i> Proceder al pago</a>
            </div>
        <?php endif; ?>
    </main>
    </div>
    <?php include 'assets/component/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
</body>

</html>