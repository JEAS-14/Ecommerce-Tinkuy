<?php
// src/Views/cart.php
// Esta Vista espera que $carrito_items y $total_general ya existan
// (porque el Controlador 'public/index.php' ya los creó).

// --- DEFINICIÓN DE RUTAS ---
$project_root = "/Ecommerce-Tinkuy";
$base_url = $project_root . "/public";
$controller_url = $base_url . "/index.php"; // El "Cerebro"
$pagina_actual = 'carrito'; // Para el navbar
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mi Carrito | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="<?= $base_url ?>/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">
    
    <?php 
    include BASE_PATH . '/src/Views/components/navbar.php'; 
    ?>

    <main class="flex-grow-1">
        <div class="container my-5">
            <h1 class="text-center mb-4">Mi Carrito de Compras</h1>

            <?php if (isset($_SESSION['mensaje_exito'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['mensaje_exito']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensaje_exito']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['mensaje_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['mensaje_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensaje_error']); ?>
            <?php endif; ?>

            <?php if (empty($carrito_items)): ?>
                <div class="text-center p-5 border rounded shadow-sm bg-white">
                    <i class="bi bi-cart-x" style="font-size: 4rem; color: #6c757d;"></i>
                    <h3 class="mt-3">Tu carrito está vacío</h3>
                    <p class="text-muted">Parece que aún no has agregado nada.</p>
                    <a href="<?= $controller_url ?>?page=products" class="btn btn-primary mt-2">
                        <i class="bi bi-shop me-1"></i> Ir a la tienda
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="table-responsive shadow-sm bg-white rounded">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" colspan="2">Producto</th>
                                        <th scope="col">Precio</th>
                                        <th scope="col">Cantidad</th>
                                        <th scope="col">Subtotal</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($carrito_items as $item): ?>
                                        <tr>
                                            <td style="width: 100px;">
                                                <img src="<?= $project_root ?>/public/img/productos/<?= htmlspecialchars($item['imagen_final']) ?>"
                                                     alt="<?= htmlspecialchars($item['nombre']) ?>"
                                                     class="img-fluid rounded"
                                                     style="width: 80px; height: 80px; object-fit: cover;">
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    Talla: <?= htmlspecialchars($item['talla']) ?> |
                                                    Color: <?= htmlspecialchars($item['color']) ?>
                                                </small>
                                            </td>
                                            <td>S/ <?= number_format($item['precio'], 2) ?></td>
                                            <td><?= htmlspecialchars($item['cantidad']) ?></td>
                                            <td><strong>S/ <?= number_format($item['subtotal'], 2) ?></strong></td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger btn-eliminar" 
                                                        title="Eliminar"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#confirmDeleteModal"
                                                        data-id-variante="<?= $item['id_variante'] ?>"
                                                        data-nombre-producto="<?= htmlspecialchars($item['nombre']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 sticky-top" style="top: 80px;">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Resumen del Pedido</h5>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <strong>S/ <?= number_format($total_general, 2) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Envío</span>
                                    <span class="text-success">GRATIS</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between h4 mb-4">
                                    <strong>Total</strong>
                                    <strong>S/ <?= number_format($total_general, 2) ?></strong>
                                </div>
                                <div class="d-grid">
                                    <a href="<?= $controller_url ?>?page=pago" class="btn btn-success btn-lg">
                                        <i class="bi bi-shield-check me-2"></i> Proceder al Pago
                                    </a>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="<?= $controller_url ?>?page=products" class="link-secondary text-decoration-none">
                                        <i class="bi bi-arrow-left-short"></i> Seguir comprando
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php 
    include BASE_PATH . '/src/Views/components/footer.php'; 
    ?>

    <!-- 🗑 Modal de confirmación de eliminación -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalLabel">Confirmar Eliminación</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            ¿Seguro que deseas eliminar <strong id="modalProductName">...</strong> del carrito?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <a id="btnConfirmDelete" href="#" class="btn btn-danger">Eliminar</a>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var deleteModal = document.getElementById('confirmDeleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var idVariante = button.getAttribute('data-id-variante');
                var nombreProducto = button.getAttribute('data-nombre-producto');

                var deleteUrl = "<?= $controller_url ?>?page=eliminar_carrito&id=" + idVariante;

                var modalProductName = deleteModal.querySelector('#modalProductName');
                var modalConfirmButton = deleteModal.querySelector('#btnConfirmDelete');

                modalProductName.textContent = nombreProducto;
                modalConfirmButton.setAttribute('href', deleteUrl);
            });
        }
    });
    </script>
</body>
</html>
