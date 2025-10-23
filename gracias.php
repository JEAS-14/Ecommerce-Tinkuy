<?php
session_start();
include 'assets/admin/db.php'; //

// Calidad (Seguridad): Verificamos que vengan de un pedido exitoso
// (El ID se guardó en la sesión en pago.php)
if (!isset($_SESSION['pedido_exitoso_id'])) {
    header('Location: index.php'); // Si no hay ID, los mandamos al inicio
    exit;
}

$id_pedido = $_SESSION['pedido_exitoso_id'];

// Limpiamos la variable de sesión para que no puedan recargar esta página
unset($_SESSION['pedido_exitoso_id']);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>¡Gracias por tu compra! | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>
    <?php include 'assets/component/navbar.php'; // ?>

    <div class="container my-5">
        <div class="text-center p-5 border rounded shadow-sm" style="background-color: #f8f9fa;">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
            <h1 class="mt-3">¡Gracias por tu compra!</h1>
            <p class="lead text-muted">Tu pedido ha sido procesado exitosamente.</p>
            <h4 class="fw-normal">Tu número de pedido es: <strong class="text-primary">#<?= htmlspecialchars($id_pedido) ?></strong></h4>
            <p>Hemos enviado una confirmación a tu correo electrónico (simulado).</p>
            <a href="products.php" class="btn btn-primary mt-3"> <i class="bi bi-arrow-left"></i> Seguir comprando
            </a>
        </div>
    </div>

    <?php include 'assets/component/footer.php'; // ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>