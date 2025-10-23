<?php
session_start();
include 'assets/admin/db.php'; // Incluimos la conexión

// --- LÓGICA PARA BUSCAR EL PRODUCTO Y SUS VARIANTES ---

// 1. Validar el ID del producto de la URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    // Si no hay ID o no es un número, redirigimos
    header("Location: products.php");
    exit;
}
$id_producto = $_GET['id'];

// 2. Obtener la información general del producto
$stmt_producto = $conn->prepare("
    SELECT p.nombre_producto, p.descripcion, p.imagen_principal, c.nombre_categoria
    FROM productos AS p
    JOIN categorias AS c ON p.id_categoria = c.id_categoria
    WHERE p.id_producto = ?
");
$stmt_producto->bind_param("i", $id_producto);
$stmt_producto->execute();
$resultado_producto = $stmt_producto->get_result();

if ($resultado_producto->num_rows === 0) {
    // Si no se encontró el producto, redirigimos
    header("Location: products.php");
    exit;
}
$producto = $resultado_producto->fetch_assoc();


// 3. Obtener TODAS las variantes disponibles para este producto
$stmt_variantes = $conn->prepare("
    SELECT id_variante, talla, color, precio, stock
    FROM variantes_producto
    WHERE id_producto = ? AND stock > 0
    ORDER BY talla, color
");
$stmt_variantes->bind_param("i", $id_producto);
$stmt_variantes->execute();
$resultado_variantes = $stmt_variantes->get_result();

$variantes = [];
while ($fila = $resultado_variantes->fetch_assoc()) {
    $variantes[] = $fila;
}

// 4. Convertir las variantes a JSON para usarlas con JavaScript
// Esto es para la magia de actualizar el precio en tiempo real
$variantes_json = json_encode($variantes);

$stmt_producto->close();
$stmt_variantes->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($producto['nombre_producto']); ?> | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>

<body>
    <?php include 'assets/component/navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-6">
                <img src="assets/img/productos/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" class="img-fluid rounded shadow-sm" alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>">
            </div>

            <div class="col-md-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="products.php">Productos</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($producto['nombre_categoria']); ?></li>
                    </ol>
                </nav>

                <h2><?php echo htmlspecialchars($producto['nombre_producto']); ?></h2>
                <p class="lead text-muted"><?php echo htmlspecialchars($producto['descripcion']); ?></p>

                <h3 id="precio-producto" class="my-3 text-primary fw-bold">Selecciona una opción</h3>
                <small id="stock-producto" class_="text-muted"></small>
                
                <hr>

                <form id="form-carrito" action="agregar_carrito.php" method="POST">
                    
                    <input type="hidden" name="id_producto" value="<?php echo $id_producto; ?>">
                    
                    <div class="mb-3">
                        <label for="select-variante" class="form-label"><strong>Selecciona Talla y Color:</strong></label>
                        <select class="form-select" id="select-variante" name="id_variante" required>
                            <option value="" selected disabled>Elige una opción...</option>
                            <?php foreach ($variantes as $variante): ?>
                                <option value="<?php echo $variante['id_variante']; ?>">
                                    <?php echo htmlspecialchars($variante['talla'] . ' - ' . $variante['color']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad" class="form-label"><strong>Cantidad:</strong></label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" value="1" min="1" max="10" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg" id="btn-agregar-carrito" disabled>
                            <i class="bi bi-cart-plus"></i> Agregar al Carrito
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <?php include 'assets/component/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Pasamos las variantes de PHP a JavaScript
        const variantes = <?php echo $variantes_json; ?>;
        
        // Obtenemos los elementos del DOM
        const selectVariante = document.getElementById('select-variante');
        const precioElemento = document.getElementById('precio-producto');
        const stockElemento = document.getElementById('stock-producto');
        const cantidadInput = document.getElementById('cantidad');
        const botonAgregar = document.getElementById('btn-agregar-carrito');

        // Escuchamos cambios en el <select>
        selectVariante.addEventListener('change', function() {
            // Buscamos la variante seleccionada en nuestro array
            const idSeleccionado = this.value;
            const varianteElegida = variantes.find(v => v.id_variante == idSeleccionado);
            
            if (varianteElegida) {
                // Actualizamos el precio
                precioElemento.textContent = 'S/ ' + parseFloat(varianteElegida.precio).toFixed(2);
                
                // Actualizamos el stock
                stockElemento.textContent = 'Stock disponible: ' + varianteElegida.stock;
                
                // Actualizamos el 'max' del input de cantidad
                cantidadInput.max = varianteElegida.stock;
                if (cantidadInput.value > varianteElegida.stock) {
                    cantidadInput.value = varianteElegida.stock;
                }
                
                // Habilitamos el botón de agregar
                botonAgregar.disabled = false;
            } else {
                // Si no hay nada seleccionado, reseteamos
                precioElemento.textContent = 'Selecciona una opción';
                stockElemento.textContent = '';
                botonAgregar.disabled = true;
            }
        });
    </script>
</body>
</html>