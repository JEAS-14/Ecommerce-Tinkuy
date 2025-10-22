<?php
session_start();

// Permitir admin (admín local o admin desde login raiz)
if (!(isset($_SESSION['admin']) || (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'))) {
    // Si no está logueado como admin, redirige al login admin
    header("Location: login.php");
    exit();
}

include 'db.php'; // db.php está en assets/admin/

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitizer básico
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $descripcion_larga = $_POST['descripcion_larga'] ?? '';
    $precio = $_POST['precio'] ?? 0;
    $categoria = $_POST['categoria'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $material = $_POST['material'] ?? '';
    $color = $_POST['color'] ?? '';
    $origen = $_POST['origen'] ?? '';
    $estilo = $_POST['estilo'] ?? '';
    $garantia = $_POST['garantia'] ?? '';
    $id_vendedor = isset($_POST['id_vendedor']) ? intval($_POST['id_vendedor']) : 1; // por defecto 1

    // Opcional: validar que el id_vendedor existe y es rol 'vendedor'
    $chk = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'vendedor'");
    $chk->bind_param("i", $id_vendedor);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        // si no existe vendedor, usar admin como vendedor_id = 1 (o lanzar error)
        $id_vendedor = 1;
    }
    $chk->close();

    // Subida de imagen
    $imagen = '';
    if (!empty($_FILES['imagen']['name'])) {
        $nombreImg = basename($_FILES['imagen']['name']);
        $rutaDestino = __DIR__ . '/../../assets/img/' . $nombreImg; // ruta absoluta desde este archivo
        if (!is_dir(dirname($rutaDestino))) {
            mkdir(dirname($rutaDestino), 0755, true);
        }
        move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino);
        $imagen = $nombreImg;
    }

    // Preparar INSERT (incluyendo id_vendedor)
    $sql = "INSERT INTO productos (id_vendedor, nombre, descripcion, descripcion_larga, precio, imagen, categoria, stock, material, color, origen, estilo, garantia)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error prepare: " . $conn->error);
    }

    // Tipos: i (id_vendedor), s,nombre, s, s, d(precio), s(imagen), s(categoria), i(stock), s(material), s(color), s(origen), s(estilo), s(garantia)
    $stmt->bind_param(
        "isssdssisssss",
        $id_vendedor,
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
        $garantia
    );

    if (!$stmt->execute()) {
        die("Error al agregar producto: " . $stmt->error);
    }

    header("Location: productos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Producto | Admin Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4">Agregar Producto (Admin)</h2>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Vendedor</label>
            <select name="id_vendedor" class="form-control" required>
                <option value="">-- Seleccione un vendedor --</option>
                <?php
                // Traer vendedores (mostrar 'usuario' porque tu tabla no tiene 'nombre')
                $res = $conn->query("SELECT id, usuario FROM usuarios WHERE rol='vendedor' ORDER BY usuario");
                while ($r = $res->fetch_assoc()) {
                    echo "<option value=\"{$r['id']}\">" . htmlspecialchars($r['usuario']) . " (id: {$r['id']})</option>";
                }
                ?>
            </select>
        </div>

        <!-- resto de campos -->
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Descripción breve</label>
            <textarea name="descripcion" class="form-control" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Descripción larga</label>
            <textarea name="descripcion_larga" class="form-control" rows="5" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Precio</label>
            <input type="number" step="0.01" name="precio" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Categoría</label>
            <input type="text" name="categoria" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Stock</label>
            <input type="number" name="stock" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Material</label>
            <input type="text" name="material" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Color</label>
            <input type="text" name="color" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Origen</label>
            <input type="text" name="origen" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Estilo</label>
            <input type="text" name="estilo" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Garantía</label>
            <input type="text" name="garantia" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Imagen</label>
            <input type="file" name="imagen" class="form-control" accept="image/*" required>
        </div>

        <button type="submit" class="btn btn-success">Guardar</button>
        <a href="productos.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>
