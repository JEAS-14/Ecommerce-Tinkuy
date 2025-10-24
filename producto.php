<?php
session_start();
include 'assets/admin/db.php'; // Incluimos la conexión

// --- LÓGICA PARA BUSCAR EL PRODUCTO Y SUS VARIANTES ---

// 1. Validar el ID del producto de la URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: products.php");
    exit;
}
$id_producto = $_GET['id'];

// 2. Obtener la información general del producto (incluyendo estado)
$stmt_producto = $conn->prepare("
    SELECT p.nombre_producto, p.descripcion, p.imagen_principal, p.estado, c.nombre_categoria
    FROM productos AS p
    JOIN categorias AS c ON p.id_categoria = c.id_categoria
    WHERE p.id_producto = ?
");
$stmt_producto->bind_param("i", $id_producto);
$stmt_producto->execute();
$resultado_producto = $stmt_producto->get_result();

// Si no se encontró el producto O está inactivo, redirigimos
if ($resultado_producto->num_rows === 0) {
    header("Location: products.php?error=notfound"); // Mensaje opcional
    exit;
}
$producto = $resultado_producto->fetch_assoc();

// Redirigir si el producto está inactivo (a menos que seas admin/vendedor?)
// Por ahora, lo ocultamos a todos si está inactivo.
if ($producto['estado'] === 'inactivo') {
     header("Location: products.php?error=inactive"); // Mensaje opcional
     exit;
}


// 3. Obtener TODAS las variantes ACTIVAS y con stock para este producto
// Agregamos 'imagen_variante' y filtramos por estado='activo' y stock > 0
$stmt_variantes = $conn->prepare("
    SELECT id_variante, talla, color, precio, stock, imagen_variante
    FROM variantes_producto
    WHERE id_producto = ?
      AND estado = 'activo'  -- <<< SOLO VARIANTES ACTIVAS
      AND stock > 0          -- <<< SOLO CON STOCK
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
$variantes_json = json_encode($variantes);

$stmt_producto->close();
$stmt_variantes->close();
$conn->close();

// Determinamos la imagen principal inicial (usamos la del producto por defecto)
$imagen_mostrada_inicial = htmlspecialchars($producto['imagen_principal']);
// Ruta base para imágenes principales
$ruta_base_principal = "assets/img/productos/";
// Ruta base para imágenes de variantes (CORREGIDA)
$ruta_base_variantes = "assets/img/productos/variantes/";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($producto['nombre_producto']); ?> | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        /* Estilo opcional para imagen principal */
        #producto-imagen {
            max-height: 500px; /* Evita que sea demasiado grande */
            object-fit: contain; /* Asegura que se vea completa */
            transition: opacity 0.3s ease-in-out; /* Suave transición al cambiar */
        }
        /* Opcional: Estilo para select deshabilitado */
        select:disabled {
            background-color: #e9ecef;
            opacity: 0.7;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php include 'assets/component/navbar.php'; ?>

    <div class="container my-5 flex-grow-1">
        <div class="row g-4">
            <div class="col-md-6 text-center">
                <img id="producto-imagen-principal"
                     src="<?= $ruta_base_principal . $imagen_mostrada_inicial; ?>"
                     class="img-fluid rounded shadow-sm mb-3"
                     alt="Imagen principal de <?= htmlspecialchars($producto['nombre_producto']); ?>">
            </div>

            <div class="col-md-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="products.php">Productos</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($producto['nombre_categoria']); ?></li>
                    </ol>
                </nav>

                <h2 class="mb-3"><?= htmlspecialchars($producto['nombre_producto']); ?></h2>
                <p class="lead text-muted mb-3"><?= nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
                <h3 id="precio-producto" class="my-3 text-primary fw-bold">
                    <?php if (empty($variantes)): ?>
                        <span class="text-danger">Agotado</span>
                    <?php else: ?>
                        Selecciona Talla/Color
                    <?php endif; ?>
                </h3>
                <small id="stock-producto" class="text-muted d-block mb-3"></small>

                <hr>

                <form id="form-carrito" action="agregar_carrito.php" method="POST">
                    <input type="hidden" name="id_producto" value="<?= $id_producto; ?>">

                    <div class="mb-3">
                        <label for="select-variante" class="form-label"><strong>Selecciona Talla y Color:</strong></label>
                        <select class="form-select form-select-lg" id="select-variante" name="id_variante" required <?= empty($variantes) ? 'disabled' : '' ?>>
                            <option value="" selected disabled>
                                <?= empty($variantes) ? 'Producto Agotado' : 'Elige una opción...' ?>
                            </option>
                            <?php foreach ($variantes as $variante): ?>
                                <option value="<?= $variante['id_variante']; ?>"
                                        data-precio="<?= htmlspecialchars($variante['precio']); ?>"
                                        data-stock="<?= htmlspecialchars($variante['stock']); ?>"
                                        data-imagen="<?= htmlspecialchars($variante['imagen_variante'] ?? ''); // Pasar nombre imagen variante ?>">
                                    <?= htmlspecialchars($variante['talla'] . ' - ' . $variante['color']); ?>
                                    (<?= $variante['stock'] ?> disp.)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad" class="form-label"><strong>Cantidad:</strong></label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" value="1" min="1" max="1" required disabled>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="btn-agregar-carrito" disabled>
                            <i class="bi bi-cart-plus me-2"></i> Agregar al Carrito
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'assets/component/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Pasamos las variantes de PHP a JavaScript (como antes)
        const variantes = <?php echo $variantes_json; ?>;

        // Rutas base para las imágenes (desde PHP)
        const rutaBasePrincipal = '<?= $ruta_base_principal ?>';
        const rutaBaseVariantes = '<?= $ruta_base_variantes ?>';
        const imagenProductoBase = '<?= htmlspecialchars($producto['imagen_principal']) ?>'; // Nombre archivo imagen principal

        // Obtenemos los elementos del DOM
        const selectVariante = document.getElementById('select-variante');
        const precioElemento = document.getElementById('precio-producto');
        const stockElemento = document.getElementById('stock-producto');
        const cantidadInput = document.getElementById('cantidad');
        const botonAgregar = document.getElementById('btn-agregar-carrito');
        const imagenPrincipalEl = document.getElementById('producto-imagen-principal'); // ID corregido

        // Escuchamos cambios en el <select>
        selectVariante.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const idSeleccionado = selectedOption.value;

            // Buscamos la variante seleccionada en nuestro array JS
            // (Alternativa: usar los data-* directamente, más eficiente si hay muchas variantes)
            // const varianteElegida = variantes.find(v => v.id_variante == idSeleccionado);
            const precioSeleccionado = selectedOption.getAttribute('data-precio');
            const stockSeleccionado = selectedOption.getAttribute('data-stock');
            const imagenVarianteNombre = selectedOption.getAttribute('data-imagen'); // Nombre archivo imagen variante

            if (idSeleccionado && precioSeleccionado && stockSeleccionado) {
                // 1. Actualizar Precio y Stock
                precioElemento.textContent = 'S/ ' + parseFloat(precioSeleccionado).toFixed(2);
                stockElemento.textContent = 'Stock disponible: ' + stockSeleccionado;

                // 2. Actualizar Imagen Principal
                imagenPrincipalEl.style.opacity = 0; // Iniciar fade out
                setTimeout(() => { // Esperar un poco para cambiar la imagen
                    if (imagenVarianteNombre) {
                        // Si la variante tiene imagen, usarla (construir ruta completa)
                        imagenPrincipalEl.src = rutaBaseVariantes + imagenVarianteNombre;
                    } else {
                        // Si no, usar la imagen principal del producto
                        imagenPrincipalEl.src = rutaBasePrincipal + imagenProductoBase;
                    }
                    imagenPrincipalEl.style.opacity = 1; // Fade in
                }, 150); // Tiempo corto para transición


                // 3. Actualizar y habilitar Cantidad
                cantidadInput.max = stockSeleccionado; // Establecer máximo según stock
                // Resetear cantidad a 1 si el valor actual excede el nuevo stock
                if (parseInt(cantidadInput.value) > parseInt(stockSeleccionado)) {
                     cantidadInput.value = 1;
                }
                cantidadInput.disabled = false; // Habilitar input cantidad

                // 4. Habilitar Botón Agregar
                botonAgregar.disabled = false;

            } else {
                // Reseteamos si la opción seleccionada no es válida (ej. la primera "Elige...")
                precioElemento.textContent = 'Selecciona Talla/Color';
                stockElemento.textContent = '';
                imagenPrincipalEl.src = rutaBasePrincipal + imagenProductoBase; // Volver a la img principal
                cantidadInput.value = 1;
                cantidadInput.max = 1;
                cantidadInput.disabled = true;
                botonAgregar.disabled = true;
            }
        });

        // Opcional: Validar cantidad al cambiarla manualmente
        cantidadInput.addEventListener('change', function() {
            const maxStock = parseInt(this.max);
            if (parseInt(this.value) > maxStock) {
                this.value = maxStock; // No permitir más que el stock
            }
            if (parseInt(this.value) < 1) {
                this.value = 1; // Mínimo 1
            }
        });

    </script>
</body>
</html>