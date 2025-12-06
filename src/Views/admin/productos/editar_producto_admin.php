<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto #<?= $id_producto ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* (Tu CSS está perfecto) */
        body { background-color: #f8f9fa; }
        .sidebar { width: 260px; height: 100vh; position: fixed; top: 0; left: 0; background-color: #212529; padding-top: 1rem; }
        .sidebar .nav-link { color: #adb5bd; font-size: 1rem; margin-bottom: 0.5rem; }
        .sidebar .nav-link i { margin-right: 0.8rem; }
        .sidebar .nav-link.active { background-color: #dc3545; color: #fff; }
        .sidebar .nav-link:hover { background-color: #343a40; color: #fff; }
        .main-content { margin-left: 260px; padding: 2.5rem; width: calc(100% - 260px); }
        .variante-inactiva { opacity: 0.6; background-color: #f8f9fa; }
        .img-variante-thumb { width: 40px; height: 40px; object-fit: cover; }
        .img-variante-placeholder { width: 40px; height: 40px; background-color: #eee; display: flex; align-items: center; justify-content: center; border-radius: .25rem; }
    </style>
</head>
<body>

    <div class="sidebar d-flex flex-column p-3 text-white">
        <a href="?page=admin_dashboard" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="bi bi-shop-window fs-4 me-2"></i> <span class="fs-4">Admin Tinkuy</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li><a href="?page=admin_dashboard" class="nav-link"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
            <li><a href="?page=admin_pedidos" class="nav-link"><i class="bi bi-list-check"></i> Pedidos</a></li>
            <li><a href="?page=admin_productos" class="nav-link active" aria-current="page"><i class="bi bi-box-seam-fill"></i> Productos</a></li>
            <li><a href="?page=admin_usuarios" class="nav-link"><i class="bi bi-people-fill"></i> Usuarios</a></li>
                    <li><a href="?page=admin_mensajes" class="nav-link"><i class="bi bi-envelope-fill"></i> Mensajes</a></li>
                    <li><a href="?page=admin_reportes" class="nav-link"><i class="bi bi-graph-up"></i> Reportes</a></li>
            
                    <li class="nav-item mt-3 pt-3 border-top">
                        <a href="?page=index" class="nav-link">
                            <i class="bi bi-globe"></i> Ver Tienda
                        </a>
                    </li>
        </ul>
        <hr>
        <div class="dropdown user-dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle fs-4 me-2"></i> <strong><?= htmlspecialchars($nombre_admin) ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="?page=logout">Cerrar Sesión</a></li>
            </ul>
        </div>
    </div>

    <main class="main-content">
        <h2>Editando: <?= htmlspecialchars($producto['nombre_producto']) ?></h2>
        
        <a href="?page=admin_productos" class="btn btn-sm btn-outline-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Volver a Productos
        </a>
        
        <?php if (!empty($mensaje_error)): ?> <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div> <?php endif; ?>
        <?php if (!empty($mensaje_exito)): ?> <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div> <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">Información General</div>
                    <div class="card-body">
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong>Estado Actual:</strong>
                                <span class="badge fs-6 bg-<?= $producto['estado'] === 'activo' ? 'success' : 'danger' ?>">
                                    <?= ucfirst($producto['estado']) ?>
                                </span>
                            </div>
                            <form action="?page=admin_editar_producto&id=<?= $id_producto ?>" method="POST" class="d-inline">
                                <input type="hidden" name="accion" value="cambiar_estado_producto">
                                <?php if ($producto['estado'] === 'activo'): ?>
                                    <input type="hidden" name="estado" value="inactivo">
                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('¿Desactivar este producto? No se mostrará en la tienda.')">
                                        <i class="bi bi-eye-slash-fill"></i> Desactivar Producto
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="estado" value="activo">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-eye-fill"></i> Activar Producto
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                        <hr>
                        
                        <form action="?page=admin_editar_producto&id=<?= $id_producto ?>" method="POST">
                            <input type="hidden" name="accion" value="actualizar_producto">
                            <div class="mb-3"><label for="nombre_producto" class="form-label">Nombre</label><input type="text" class="form-control" id="nombre_producto" name="nombre_producto" value="<?= htmlspecialchars($producto['nombre_producto']) ?>" minlength="10" maxlength="100" title="El nombre debe tener entre 10 y 100 caracteres" required></div>
                            <div class="mb-3"><label for="descripcion" class="form-label">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="4" minlength="10" title="La descripción es muy corta (mínimo 10 caracteres)"><?= htmlspecialchars($producto['descripcion']) ?></textarea></div>
                            <div class="mb-3">
                                <label for="id_categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="id_categoria" name="id_categoria" required>
                                    <option value="" disabled>-- Selecciona --</option>
                                    <?php // (Tu lógica de categorías está perfecta)
                                    $current_group = null;
                                    foreach ($categorias_jerarquia as $cat) {
                                        if ($cat['id_categoria_padre'] === null) { if ($current_group !== null) echo '</optgroup>'; echo '<optgroup label="' . htmlspecialchars($cat['nombre_categoria']) . '">'; $current_group = $cat['id_categoria']; }
                                        elseif ($cat['id_categoria_padre'] === $current_group) { $selected = ($cat['id_categoria'] == $producto['id_categoria']) ? 'selected' : ''; echo '<option value="' . $cat['id_categoria'] . '" ' . $selected . '>&nbsp;&nbsp;&nbsp;' . htmlspecialchars($cat['nombre_categoria']) . '</option>'; }
                                        elseif ($cat['id_categoria_padre'] !== null && $cat['id_categoria_padre'] !== $current_group) { if ($current_group !== null) { echo '</optgroup>'; $current_group = null; } $selected = ($cat['id_categoria'] == $producto['id_categoria']) ? 'selected' : ''; echo '<option value="' . $cat['id_categoria'] . '" ' . $selected . '>' . htmlspecialchars($cat['nombre_padre'] ?? '??') . ' / ' . htmlspecialchars($cat['nombre_categoria']) .' (Sub)</option>'; }
                                    } if ($current_group !== null) echo '</optgroup>';
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar Cambios Generales</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">Agregar Nueva Variante</div>
                    <div class="card-body">
                        <form action="?page=admin_editar_producto&id=<?= $id_producto ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="accion" value="agregar_variante">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="producto_unico_admin">
                                    <label class="form-check-label" for="producto_unico_admin">
                                        <strong>🎨 Sin variantes</strong>
                                    </label>
                                    <small class="text-muted d-block">Automáticamente usa Talla "Única" y Color "Estándar"</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label for="talla" class="form-label">Talla</label><input type="text" class="form-control" id="talla_admin" name="talla" placeholder="Ej: M" pattern="^[a-zA-Z\s]+$" title="Formato inválido: Solo se permiten letras y espacios"></div>
                                <div class="col-md-6 mb-3"><label for="color" class="form-label">Color</label><input type="text" class="form-control" id="color_admin" name="color" placeholder="Ej: Rojo" pattern="^[a-zA-Z\s]+$" title="Formato inválido: Solo se permiten letras y espacios"></div>
                                <div class="col-md-6 mb-3"><label for="precio" class="form-label">Precio (S/) <span class="text-danger">*</span></label><input type="number" step="0.01" min="0.01" class="form-control" id="precio" name="precio" placeholder="150.00" title="El precio debe ser mayor a 0" required></div>
                                <div class="col-md-6 mb-3"><label for="stock" class="form-label">Stock <span class="text-danger">*</span></label><input type="number" step="1" min="1" class="form-control" id="stock" name="stock" placeholder="10" title="El stock debe ser un número entero" required></div>
                                <div class="col-12 mb-3">
                                    <label for="imagen_variante" class="form-label">Imagen Específica (Opcional)</label>
                                    <input class="form-control" type="file" id="imagen_variante" name="imagen_variante" accept="image/jpeg, image/png, image/gif, image/webp">
                                    <small class="text-muted">Si subes una imagen, se mostrará específicamente para esta variante. Max 2MB.</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> Agregar Variante</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header">Variantes Existentes</div>
                    <div class="card-body">
                        <form action="?page=admin_editar_producto&id=<?= $id_producto ?>" method="POST">
                            <input type="hidden" name="accion" value="actualizar_variantes">
                            <?php if (empty($variantes)): ?>
                                <p class="text-muted">Sin variantes.</p>
                            <?php else: ?>
                                <?php foreach ($variantes as $v): ?>
                                    <div class="border rounded p-3 mb-3 <?= ($v['estado'] === 'inactivo') ? 'variante-inactiva' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($v['imagen_variante'])): ?>
                                                    <img src="<?= IMG_VARIANTES_URL ?><?= htmlspecialchars($v['imagen_variante']) ?>" alt="Variante" class="img-variante-thumb rounded me-2">
                                                <?php else: ?>
                                                    <div class="img-variante-placeholder me-2"><i class="bi bi-image text-muted"></i></div>
                                                <?php endif; ?>
                                                <h6 class="mb-0 ms-1"> <?= htmlspecialchars($v['talla']) ?> / <?= htmlspecialchars($v['color']) ?></h6>
                                            </div>
                                            <span class="badge bg-<?= ($v['estado'] === 'inactivo') ? 'secondary' : 'success' ?>"><?= ucfirst($v['estado']) ?></span>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6"><label class="form-label small">Precio</label><input type="number" step="0.01" class="form-control form-control-sm" name="variantes[<?= $v['id_variante'] ?>][precio]" value="<?= htmlspecialchars($v['precio']) ?>" <?= ($v['estado'] === 'inactivo') ? 'disabled' : '' ?>></div>
                                            <div class="col-6"><label class="form-label small">Stock</label><input type="number" class="form-control form-control-sm" name="variantes[<?= $v['id_variante'] ?>][stock]" value="<?= htmlspecialchars($v['stock']) ?>" <?= ($v['estado'] === 'inactivo') ? 'disabled' : '' ?>></div>
                                        </div>
                                        <?php if ($v['estado'] === 'activo'): ?>
                                            <div class="form-check mt-2"> <input class="form-check-input" type="checkbox" name="variantes[<?= $v['id_variante'] ?>][desactivar]" id="desactivar_<?= $v['id_variante'] ?>"> <label class="form-check-label small text-warning" for="desactivar_<?= $v['id_variante'] ?>"> Marcar para Desactivar </label> </div>
                                        <?php else: ?>
                                            <a href="?page=admin_editar_producto&id=<?= $id_producto ?>&reactivar_variante_id=<?= $v['id_variante'] ?>" class="btn btn-sm btn-outline-success mt-2" onclick="return confirm('¿Reactivar esta variante?')"> <i class="bi bi-eye"></i> Reactivar </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <button type="submit" class="btn btn-warning w-100" onclick="return confirm('¿Guardar cambios y desactivar variantes marcadas?')"> <i class="bi bi-save"></i> Actualizar Lista </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-rellenar talla y color cuando se marca el checkbox
        document.getElementById('producto_unico_admin').addEventListener('change', function() {
            const inputTalla = document.getElementById('talla_admin');
            const inputColor = document.getElementById('color_admin');
            
            if (this.checked) {
                inputTalla.value = 'Única';
                inputColor.value = 'Estándar';
                inputTalla.readOnly = true;
                inputColor.readOnly = true;
                inputTalla.removeAttribute('pattern');
                inputColor.removeAttribute('pattern');
                inputTalla.classList.add('bg-light');
                inputColor.classList.add('bg-light');
            } else {
                inputTalla.value = '';
                inputColor.value = '';
                inputTalla.readOnly = false;
                inputColor.readOnly = false;
                inputTalla.setAttribute('pattern', '^[a-zA-Z\\s]+$');
                inputColor.setAttribute('pattern', '^[a-zA-Z\\s]+$');
                inputTalla.classList.remove('bg-light');
                inputColor.classList.remove('bg-light');
            }
        });

        // Validaciones en vivo
        const inputTalla = document.getElementById('talla_admin');
        const inputColor = document.getElementById('color_admin');
        const inputPrecio = document.getElementById('precio');
        const inputStock = document.getElementById('stock');

        [inputTalla, inputColor].forEach(input => {
            input.addEventListener('input', function() {
                const regex = /^[a-zA-Z\s]*$/;
                if (!regex.test(this.value)) {
                    alert('Formato inválido: Solo se permiten letras y espacios');
                    this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                }
            });
        });

        inputPrecio.addEventListener('input', function() {
            const val = parseFloat(this.value);
            if (this.value !== '' && (isNaN(val) || val <= 0)) {
                alert('El precio debe ser mayor a 0');
                this.value = '';
            }
        });

        inputStock.addEventListener('input', function() {
            if (this.value !== '' && !Number.isInteger(Number(this.value))) {
                alert('El stock debe ser un número entero');
                this.value = Math.max(1, Math.floor(Number(this.value)));
            }
            if (this.value !== '' && Number(this.value) < 1) {
                alert('El stock debe ser un número entero');
                this.value = 1;
            }
        });
    </script>
</body>
</html>