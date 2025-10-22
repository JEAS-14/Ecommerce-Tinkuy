<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $imagen = $_POST['imagen'];
    $descripcion = $_POST['descripcion'];
    $cantidad = max(1, intval($_POST['cantidad']));

    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    if (isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id]['cantidad'] += $cantidad;
    } else {
        $_SESSION['carrito'][$id] = [
            'nombre' => $nombre,
            'precio' => $precio,
            'imagen' => $imagen,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad
        ];
    }

    // Mostrar página de confirmación
} else {
    header('Location: products.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Producto agregado | Tinkuy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
    <?php include 'assets/component/navbar.php'; ?>

    <main class="container my-5 text-center" style="min-height: 75vh;">

        <div class="alert alert-success p-4 shadow-sm">
            <h4 class="alert-heading"><i class="bi bi-check-circle-fill"></i> Producto agregado al carrito</h4>
            <p class="mt-3">Has agregado <strong><?= htmlspecialchars($nombre) ?></strong> (x<?= $cantidad ?>) al
                carrito de compras.</p>
        </div>

        <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="cart.php" class="btn btn-success btn-lg"><i class="bi bi-cart"></i> Ver carrito</a>
            <a href="products.php" class="btn btn-outline-secondary btn-lg"><i class="bi bi-arrow-left"></i> Seguir
                comprando</a>
        </div>
        </main>

        <?php include 'assets/component/footer.php'; ?>
</body>

</html>