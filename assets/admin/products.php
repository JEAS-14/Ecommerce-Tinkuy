<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Obtener productos
$resultado = $conn->query("SELECT * FROM productos ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos | Admin Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        td img {
            max-width: 50px;
            height: auto;
        }
        .descripcion-larga {
            max-height: 100px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h2 class="mb-4">Gestión de Productos</h2>

        <a href="agregar_producto.php" class="btn btn-success mb-3">+ Agregar Producto</a>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Descripción Larga</th>
                        <th>Precio</th>
                        <th>Imagen</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Material</th>
                        <th>Color</th>
                        <th>Origen</th>
                        <th>Estilo</th>
                        <th>Garantía</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                            <td><?= htmlspecialchars($row['descripcion']) ?></td>
                            <td class="descripcion-larga"><?= nl2br(htmlspecialchars($row['descripcion_larga'])) ?></td>
                            <td>S/ <?= number_format($row['precio'], 2) ?></td>
                            <td><img src="../../assets/img/<?= $row['imagen'] ?>" alt="img"></td>
                            <td><?= htmlspecialchars($row['categoria']) ?></td>
                            <td><?= $row['stock'] ?></td>
                            <td><?= htmlspecialchars($row['material']) ?></td>
                            <td><?= htmlspecialchars($row['color']) ?></td>
                            <td><?= htmlspecialchars($row['origen']) ?></td>
                            <td><?= htmlspecialchars($row['estilo']) ?></td>
                            <td><?= htmlspecialchars($row['garantia']) ?></td>
                            <td>
                                <a href="editar_producto.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Editar</a>
                                <a href="eliminar_producto.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <a href="dashboard.php" class="btn btn-secondary mt-3">← Volver al panel</a>
    </div>
</body>
</html>
