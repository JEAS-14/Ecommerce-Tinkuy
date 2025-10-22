<?php
include 'assets/admin/db.php';

if (!isset($_GET['id'])) {
  echo "Producto no especificado.";
  exit();
}

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$producto = $resultado->fetch_assoc();

if (!$producto) {
  echo "Producto no encontrado.";
  exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($producto['nombre']) ?> | Tinkuy</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/estilos.css" />
</head>

<body>
  <?php include 'assets/component/navbar.php'; ?>

  <main class="container my-5">
    <div class="row g-4">
      <!-- Columna izquierda -->
      <div class="col-md-5 text-center">
        <img src="assets/img/<?= $producto['imagen'] ?>" alt="<?= $producto['nombre'] ?>"
          class="img-fluid rounded shadow-sm mb-3" id="imagen-principal" />

        <div class="mt-4 text-start">
          <p><strong>Stock:</strong> <?= $producto['stock'] > 0 ? "Disponible" : "Agotado" ?></p>
          <p><strong>Envío:</strong> Gratis en Lima Metropolitana</p>
          <p><strong>Garantía:</strong> <?= htmlspecialchars($producto['garantia']) ?></p>
          <p><strong>Categoría:</strong> <?= htmlspecialchars($producto['categoria']) ?></p>
        </div>

        <!-- Descripción larga -->
        <section class="mt-4 text-start">
          <h4>Descripción detallada</h4>
          <p><?= nl2br(htmlspecialchars($producto['descripcion_larga'])) ?></p>
        </section>
      </div>

      <!-- Columna derecha -->
      <div class="col-md-7">
        <h1 class="mb-3"><?= htmlspecialchars($producto['nombre']) ?></h1>

        <div class="mb-3">
          <span class="text-warning fs-5">
            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            <i class="bi bi-star-half"></i><i class="bi bi-star"></i>
          </span>
          <span class="ms-2">(48 opiniones)</span>
        </div>

        <h3 class="text-primary mb-4">S/ <?= number_format($producto['precio'], 2) ?></h3>

        <p class="lead"><?= htmlspecialchars($producto['descripcion']) ?></p>

        <ul class="list-group list-group-flush mb-4">
          <li class="list-group-item"><strong>Material:</strong> <?= htmlspecialchars($producto['material']) ?></li>
          <li class="list-group-item"><strong>Color:</strong> <?= htmlspecialchars($producto['color']) ?></li>
          <li class="list-group-item"><strong>Origen:</strong> <?= htmlspecialchars($producto['origen']) ?></li>
          <li class="list-group-item"><strong>Estilo:</strong> <?= htmlspecialchars($producto['estilo']) ?></li>
          <li class="list-group-item"><strong>Garantía:</strong> <?= htmlspecialchars($producto['garantia']) ?></li>
        </ul>

        <form action="agregar_carrito.php" method="POST" class="d-flex gap-3">
          <input type="hidden" name="id" value="<?= $producto['id'] ?>">
          <input type="hidden" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>">
          <input type="hidden" name="precio" value="<?= $producto['precio'] ?>">
          <input type="hidden" name="imagen" value="<?= $producto['imagen'] ?>">
          <input type="hidden" name="descripcion" value="<?= htmlspecialchars($producto['descripcion']) ?>">

          <input type="number" name="cantidad" id="cantidad" class="form-control w-25" value="1" min="1"
            max="<?= $producto['stock'] ?>">

          <button type="submit" class="btn btn-success btn-lg">
            <i class="bi bi-cart-plus"></i> Agregar al carrito
          </button>

          <a href="products.php" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left"></i> Volver a productos
          </a>
        </form>

      </div>
    </div>
  </main>

  <?php include 'assets/component/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function cambiarImagen(src) {
      document.getElementById('imagen-principal').src = src;
    }
  </script>
</body>

</html>