<?php
// products.php - Listado de productos con conexión al backend

include 'assets/admin/db.php';

// Captura de filtros
$buscar = $_GET['buscar'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$tipo = $_GET['tipo'] ?? '';

// Consulta dinámica
$sql = "SELECT * FROM productos WHERE 1";

if (!empty($buscar)) {
    $sql .= " AND nombre LIKE '%$buscar%'";
}
if (!empty($categoria)) {
    $sql .= " AND categoria = '$categoria'";
}
if (!empty($tipo)) {
    $sql .= " AND tipo = '$tipo'";
}

$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="d-flex flex-column min-vh-500">
    <?php include 'assets/component/navbar.php'; ?>

    <!-- Buscador con filtros -->
    <div class="page-wrapper d-flex flex-column min-vh-100">
    <section class="container my-5" aria-label="Buscador de productos">
        <div class="row justify-content-center align-items-center">
            <div class="col-md-4 d-flex align-items-center">
                <p class="mb-0">Encuentra fácilmente la prenda ideal: busca por nombre, tipo o categoría.</p>
            </div>
            <div class="col-md-8">
                <form action="products.php" method="GET">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar productos..."
                                aria-label="Buscar productos" value="<?= htmlspecialchars($buscar) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="categoria" class="form-select" aria-label="Seleccionar categoría">
                                <option value="">Todas las categorías</option>
                                <option value="prendas" <?= $categoria == 'prendas' ? 'selected' : '' ?>>Prendas</option>
                                <option value="accesorios" <?= $categoria == 'accesorios' ? 'selected' : '' ?>>Accesorios</option>
                                <option value="ropa-hombre" <?= $categoria == 'ropa-hombre' ? 'selected' : '' ?>>Ropa para Hombre</option>
                                <option value="ropa-mujer" <?= $categoria == 'ropa-mujer' ? 'selected' : '' ?>>Ropa para Mujer</option>
                            </select>
                        </div>
                        
                        <div class="col-md-1 d-grid">
                            <button class="btn btn-outline-primary" type="submit" aria-label="Buscar">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Listado de productos -->
    <section class="container mb-5" aria-label="Listado de productos">
        <div class="row">
            <!-- Panel lateral -->
            <aside class="col-md-3 mb-4">
                <!-- Categorías -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul"></i> Categorías</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><a href="?categoria=prendas" class="text-dark">Prendas</a></li>
                        <li class="list-group-item"><a href="?categoria=accesorios" class="text-dark">Accesorios</a></li>
                        <li class="list-group-item"><a href="?categoria=ropa-hombre" class="text-dark">Ropa para Hombre</a></li>
                        <li class="list-group-item"><a href="?categoria=ropa-mujer" class="text-dark">Ropa para Mujer</a></li>
                    </ul>
                </div>

               
            </aside>

            <!-- Productos dinámicos -->
            <div class="col-md-9">
                <h2 class="mb-4">Catálogo de Productos Artesanales</h2>

                <?php if ($resultado->num_rows > 0): ?>
                    <?php while ($producto = $resultado->fetch_assoc()): ?>
                        <div class="card mb-4 shadow-sm">
                            <div class="row g-0">
                                <div class="col-md-3 text-center">
                                    <img src="assets/img/<?= htmlspecialchars($producto['imagen']) ?>" class="img-fluid p-3"
                                        alt="<?= htmlspecialchars($producto['nombre']) ?>">
                                </div>
                                <div class="col-md-9">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($producto['nombre']) ?></h5>
                                        <p class="card-text"><?= htmlspecialchars($producto['descripcion']) ?></p>
                                        <p class="card-text"><strong>S/ <?= number_format($producto['precio'], 2) ?></strong></p>
                                        <a href="producto.php?id=<?= $producto['id'] ?>" class="btn btn-primary">
                                            <i class="bi bi-box-arrow-in-right"></i> Ver más
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-warning">No se encontraron productos que coincidan con los filtros.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>

    <?php include 'assets/component/footer.php'; ?>
</body>
</html>
