<?php
// src/Views/producto.php
// Esta Vista espera que todas las variables ($producto, $variantes, etc.)
// ya existan (definidas por el Controlador public/index.php).

// --- DEFINICIÓN DE RUTAS (¡La parte que faltaba!) ---
$project_root = "/Ecommerce-Tinkuy";
$base_url = $project_root . "/public";
$controller_url = $base_url . "/index.php"; // El "Cerebro"
$pagina_actual = 'producto'; // Para el navbar
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
        #producto-imagen-principal { max-height: 500px; object-fit: contain; transition: opacity 0.3s ease-in-out; }
        select:disabled { background-color: #e9ecef; opacity: 0.7; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php include BASE_PATH . '/src/Views/components/navbar.php'; ?>

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
                        <li class="breadcrumb-item"><a href="<?= $controller_url ?>?page=products">Productos</a></li> 
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

                <form id="form-carrito" action="<?= $controller_url ?>?page=agregar_carrito" method="POST"> 
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
                                        data-imagen="<?= htmlspecialchars($variante['imagen_variante'] ?? ''); ?>">
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

    <?php include BASE_PATH . '/src/Views/components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Pasamos las variantes (definidas por el Controlador) a JavaScript
        const variantes = <?php echo $variantes_json; ?>;
        // ... (El resto de tu JavaScript funciona perfecto, no se toca) ...
        const rutaBasePrincipal = '<?= $ruta_base_principal ?>';
        const rutaBaseVariantes = '<?= $ruta_base_variantes ?>';
        const imagenProductoBase = '<?= htmlspecialchars($producto['imagen_principal']) ?>';
        const selectVariante = document.getElementById('select-variante');
        const precioElemento = document.getElementById('precio-producto');
        const stockElemento = document.getElementById('stock-producto');
        const cantidadInput = document.getElementById('cantidad');
        const botonAgregar = document.getElementById('btn-agregar-carrito');
        const imagenPrincipalEl = document.getElementById('producto-imagen-principal');
 
        selectVariante.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const idSeleccionado = selectedOption.value;
            const precioSeleccionado = selectedOption.getAttribute('data-precio');
            const stockSeleccionado = selectedOption.getAttribute('data-stock');
            const imagenVarianteNombre = selectedOption.getAttribute('data-imagen'); 
 
            if (idSeleccionado && precioSeleccionado && stockSeleccionado) {
                precioElemento.textContent = 'S/ ' + parseFloat(precioSeleccionado).toFixed(2);
                stockElemento.textContent = 'Stock disponible: ' + stockSeleccionado;
 
                imagenPrincipalEl.style.opacity = 0; 
                setTimeout(() => { 
                    if (imagenVarianteNombre) {
                        imagenPrincipalEl.src = rutaBaseVariantes + imagenVarianteNombre;
                    } else {
                        imagenPrincipalEl.src = rutaBasePrincipal + imagenProductoBase;
                    }
                    imagenPrincipalEl.style.opacity = 1;
                }, 150); 
 
 
                cantidadInput.max = stockSeleccionado; 
                if (parseInt(cantidadInput.value) > parseInt(stockSeleccionado)) {
                    cantidadInput.value = 1;
                }
                cantidadInput.disabled = false; 
                botonAgregar.disabled = false;
 
            } else {
                precioElemento.textContent = 'Selecciona Talla/Color';
                stockElemento.textContent = '';
                imagenPrincipalEl.src = rutaBasePrincipal + imagenProductoBase; 
                cantidadInput.value = 1;
                cantidadInput.max = 1;
                cantidadInput.disabled = true;
                botonAgregar.disabled = true;
            }
        });
 
        cantidadInput.addEventListener('change', function() {
            const maxStock = parseInt(this.max);
            if (parseInt(this.value) > maxStock) {
                this.value = maxStock;
            }
            if (parseInt(this.value) < 1) {
                this.value = 1;
            }
        });
    </script>
</body>
</html>