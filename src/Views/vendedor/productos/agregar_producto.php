<?php
// Vista de agregar producto (MVC)
// Espera que el controlador provea: $categorias, $base_url, $mensaje_error, $mensaje_exito

$categorias = $categorias ?? [];
$base_url = $base_url ?? '/Ecommerce-Tinkuy/public/index.php';
$mensaje_error = $mensaje_error ?? '';
$mensaje_exito = $mensaje_exito ?? '';

?>

<!DOCTYPE html>
<h lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto - Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php 
    $pagina_actual = 'productos';
    require BASE_PATH . '/src/Views/components/navbar_vendedor.php';
    ?>
            <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Agregar Nuevo Producto</h2>
            <a href="<?= $base_url ?>?page=vendedor_productos" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Productos
            </a>
        </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje_error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['mensaje_error']) ?></div>
            <?php unset($_SESSION['mensaje_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['mensaje_exito']) ?></div>
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>

        <form action="<?= $base_url ?>?page=vendedor_agregar_producto" method="POST" enctype="multipart/form-data" class="row g-4">
            <!-- Información General -->
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Información del Producto</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="nombre_producto" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre_producto" name="nombre_producto" minlength="10" maxlength="60" title="El nombre debe tener entre 10 y 100 caracteres" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" minlength="10" title="La descripción es muy corta (mínimo 10 caracteres)"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="id_categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_categoria" name="id_categoria" required>
                                <option value="">-- Selecciona una categoría --</option>
                                <?php
                                $current_group = null;
                                foreach ($categorias as $cat) {
                                    if ($cat['id_categoria_padre'] === null) {
                                        if ($current_group !== null) echo '</optgroup>';
                                        echo '<optgroup label="' . htmlspecialchars($cat['nombre_categoria']) . '">';
                                        $current_group = $cat['id_categoria'];
                                    }
                                    elseif ($cat['id_categoria_padre'] === $current_group) {
                                        echo '<option value="' . $cat['id_categoria'] . '">&nbsp;&nbsp;&nbsp;' . 
                                             htmlspecialchars($cat['nombre_categoria']) . '</option>';
                                    }
                                    elseif ($cat['id_categoria_padre'] !== null && $cat['id_categoria_padre'] !== $current_group) {
                                        if ($current_group !== null) {
                                            echo '</optgroup>';
                                            $current_group = null;
                                        }
                                        echo '<option value="' . $cat['id_categoria'] . '">' . 
                                             htmlspecialchars($cat['nombre_padre'] ?? '??') . ' / ' . 
                                             htmlspecialchars($cat['nombre_categoria']) .' (Sub)</option>';
                                    }
                                }
                                if ($current_group !== null) echo '</optgroup>';
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="imagen_principal" class="form-label">Imagen Principal <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="imagen_principal" name="imagen_principal" accept="image/*" required>
                            <small class="text-muted">Formatos: JPG, PNG, GIF, WebP. Máximo 2MB.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variantes -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Variantes</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarVariante" onclick="agregarVariante()">
                            <i class="bi bi-plus-circle"></i> Agregar
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="checkProductoSinVariantes">
                                <label class="form-check-label" for="checkProductoSinVariantes">
                                    <strong>🎨 Sin variantes</strong>
                                </label>
                                <small class="text-muted d-block">Automáticamente usa Talla "Única" y Color "Estándar"</small>
                            </div>
                        </div>
                        <hr>
                        <div id="variantes-container">
                            <!-- Las variantes se agregarán aquí dinámicamente -->
                        </div>
                        <p class="text-muted small mb-0" id="no-variantes">No hay variantes agregadas</p>
                    </div>
                </div>
            </div>

            <div class="col-12 text-end">
                <hr>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Crear Producto
                </button>
            </div>
        </form>
    </div>

    <script>
        let varianteCount = 0;

        function agregarVariante() {
            varianteCount++;
            document.getElementById('no-variantes').style.display = 'none';
            
            const varianteHtml = `
                <div class="border rounded p-3 mb-3" id="variante-${varianteCount}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Variante #${varianteCount}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarVariante(${varianteCount})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm talla-input" name="variantes[${varianteCount}][talla]" 
                                   placeholder="Talla" pattern="^[a-zA-Z\s]+$" title="Formato inválido: Solo se permiten letras y espacios" required>
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm color-input" name="variantes[${varianteCount}][color]" 
                                   placeholder="Color" pattern="^[a-zA-Z\s]+$" title="Formato inválido: Solo se permiten letras y espacios" required>
                        </div>
                        <div class="col-6">
                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm precio-input" name="variantes[${varianteCount}][precio]" 
                                   placeholder="Precio" title="El precio debe ser mayor a 0" required>
                        </div>
                        <div class="col-6">
                            <input type="number" step="1" min="1" class="form-control form-control-sm stock-input" name="variantes[${varianteCount}][stock]" 
                                   placeholder="Stock" title="El stock debe ser un número entero" required>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('variantes-container').insertAdjacentHTML('beforeend', varianteHtml);
        }

        function eliminarVariante(id) {
            const elemento = document.getElementById(`variante-${id}`);
            if (elemento) {
                elemento.remove();
                if (document.getElementById('variantes-container').children.length === 0) {
                    document.getElementById('no-variantes').style.display = 'block';
                }
            }
        }

        // Agregar una variante por defecto
        window.addEventListener('load', () => {
            agregarVariante();
        });

        // Auto-rellenar talla y color cuando se marca el checkbox
        document.getElementById('checkProductoSinVariantes').addEventListener('change', function() {
            const tallaInputs = document.querySelectorAll('.talla-input');
            const colorInputs = document.querySelectorAll('.color-input');
            const btnAgregar = document.getElementById('btnAgregarVariante');
            
            if (this.checked) {
                // Checkbox marcado: auto-rellenar y quitar validación required
                tallaInputs.forEach(input => {
                    input.value = 'Única';
                    input.readOnly = true;
                    input.removeAttribute('required');
                    input.removeAttribute('pattern');
                    input.classList.add('bg-light');
                });
                colorInputs.forEach(input => {
                    input.value = 'Estándar';
                    input.readOnly = true;
                    input.removeAttribute('required');
                    input.removeAttribute('pattern');
                    input.classList.add('bg-light');
                });
                // Desactivar botón agregar variantes
                btnAgregar.disabled = true;
                btnAgregar.classList.add('disabled');
            } else {
                // Checkbox desmarcado: limpiar y restaurar validación required
                tallaInputs.forEach(input => {
                    input.value = '';
                    input.readOnly = false;
                    input.setAttribute('required', 'required');
                    input.setAttribute('pattern', '^[a-zA-Z\\s]+$');
                    input.classList.remove('bg-light');
                });
                colorInputs.forEach(input => {
                    input.value = '';
                    input.readOnly = false;
                    input.setAttribute('required', 'required');
                    input.setAttribute('pattern', '^[a-zA-Z\\s]+$');
                    input.classList.remove('bg-light');
                });
                // Reactivar botón agregar variantes
                btnAgregar.disabled = false;
                btnAgregar.classList.remove('disabled');
            }
        });

        // Validaciones en vivo
        const variantesContainer = document.getElementById('variantes-container');

        variantesContainer.addEventListener('input', (e) => {
            const target = e.target;

            if (target.classList.contains('talla-input') || target.classList.contains('color-input')) {
                const regex = /^[a-zA-Z\s]*$/;
                if (!regex.test(target.value)) {
                    alert('Formato inválido: Solo se permiten letras y espacios');
                    target.value = target.value.replace(/[^a-zA-Z\s]/g, '');
                }
            }

            if (target.classList.contains('precio-input')) {
                const val = parseFloat(target.value);
                if (target.value !== '' && (isNaN(val) || val <= 0)) {
                    alert('El precio debe ser mayor a 0');
                    target.value = '';
                }
            }

            if (target.classList.contains('stock-input')) {
                if (target.value !== '' && !Number.isInteger(Number(target.value))) {
                    alert('El stock debe ser un número entero');
                    target.value = Math.max(1, Math.floor(Number(target.value)));
                }
                if (target.value !== '' && Number(target.value) < 1) {
                    alert('El stock debe ser un número entero');
                    target.value = 1;
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
