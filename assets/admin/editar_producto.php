<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Obtener producto existente
if (!isset($_GET['id'])) {
    header("Location: productos.php");
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

// Procesar edición
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $descripcion_larga = $_POST['descripcion_larga'];
    $precio = $_POST['precio'];
    $categoria = $_POST['categoria'];
    $stock = $_POST['stock'];
    $material = $_POST['material'];
    $color = $_POST['color'];
    $origen = $_POST['origen'];
    $estilo = $_POST['estilo'];
    $garantia = $_POST['garantia'];

    // Imagen
    $imagen = $producto['imagen'];
    if ($_FILES['imagen']['name']) {
        $nombreImg = basename($_FILES['imagen']['name']);
        $rutaDestino = '../../assets/img/' . $nombreImg;
        move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino);
        $imagen = $nombreImg;
    }

    // Actualizar producto
    $stmt = $conn->prepare("UPDATE productos SET 
        nombre = ?, descripcion = ?, descripcion_larga = ?, precio = ?, imagen = ?, 
        categoria = ?, stock = ?, material = ?, color = ?, origen = ?, estilo = ?, garantia = ?
        WHERE id = ?");
    $stmt->bind_param(
        "sssdssisssssi",
        $nombre,
        $descripcion,
        $descripcion_larga,
        $precio,
        $imagen,
        $categoria,
        $stock,
        $material,
        $color,
        $origen,
        $estilo,
        $garantia,
        $id
    );
    $stmt->execute();

    header("Location: productos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Producto | Admin Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-5">
        <h2 class="mb-4">Editar Producto</h2>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control"
                    value="<?= htmlspecialchars($producto['nombre']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción breve</label>
                <textarea name="descripcion" class="form-control"
                    required><?= htmlspecialchars($producto['descripcion']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción larga</label>
                <textarea name="descripcion_larga" class="form-control" rows="5"
                    required><?= htmlspecialchars($producto['descripcion_larga']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Precio</label>
                <input type="number" step="0.01" name="precio" class="form-control" value="<?= $producto['precio'] ?>"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Categoría</label>
                <input type="text" name="categoria" class="form-control"
                    value="<?= htmlspecialchars($producto['categoria']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Stock</label>
                <input type="number" name="stock" class="form-control" value="<?= $producto['stock'] ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Material</label>
                <input type="text" name="material" class="form-control"
                    value="<?= htmlspecialchars($producto['material']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Color</label>
                <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($producto['color']) ?>"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Origen</label>
                <input type="text" name="origen" class="form-control"
                    value="<?= htmlspecialchars($producto['origen']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Estilo</label>
                <input type="text" name="estilo" class="form-control"
                    value="<?= htmlspecialchars($producto['estilo']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Garantía</label>
                <input type="text" name="garantia" class="form-control"
                    value="<?= htmlspecialchars($producto['garantia']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Imagen actual:</label><br>
                <img src="../../assets/img/<?= $producto['imagen'] ?>" alt="Imagen actual" width="150" class="mb-2"><br>
                <input type="file" name="imagen" class="form-control" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="productos.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>

</html>