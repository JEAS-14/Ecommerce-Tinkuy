<?php
session_start();
include 'assets/admin/db.php';

// --- Lógica para obtener productos (Filtros Corregidos) ---
$productos_por_pagina = 12;
$pagina_actual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Variables para filtros y búsqueda (asegurarse de que sean null si están vacíos)
$id_categoria_filtro = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
// Si el valor es 0 o inválido, lo tratamos como null (sin filtro)
if ($id_categoria_filtro === 0 || $id_categoria_filtro === false) {
    $id_categoria_filtro = null;
}
$termino_busqueda = trim(filter_input(INPUT_GET, 'buscar', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$orden = $_GET['orden'] ?? 'nombre_asc';

// Construcción de la consulta base
$sql_base = "
    FROM productos p
    JOIN categorias c ON p.id_categoria = c.id_categoria
    INNER JOIN (
        SELECT id_producto, MIN(precio) as min_precio, MAX(precio) as max_precio, SUM(stock) as total_stock
        FROM variantes_producto
        WHERE estado = 'activo' AND stock > 0
        GROUP BY id_producto
        HAVING SUM(stock) > 0
    ) vp ON p.id_producto = vp.id_producto
    WHERE p.estado = 'activo'
";
$params = []; // Parámetros para bind_param
$types = "";  // Tipos para bind_param

// --- Lógica de Filtros (Corregida) ---

// Añadir filtro de categoría SOLO si se seleccionó una categoría válida
if ($id_categoria_filtro !== null) {
    // Verificar si es padre o hija (misma lógica que antes)
    $stmt_cat_check = $conn->prepare("SELECT id_categoria_padre FROM categorias WHERE id_categoria = ?");
    $stmt_cat_check->bind_param("i", $id_categoria_filtro);
    $stmt_cat_check->execute();
    $cat_info = $stmt_cat_check->get_result()->fetch_assoc();
    $stmt_cat_check->close();

    if ($cat_info) {
        if ($cat_info['id_categoria_padre'] === null) { // Es Padre
            $sql_base .= " AND (p.id_categoria = ? OR c.id_categoria_padre = ?)";
            $params[] = $id_categoria_filtro; $params[] = $id_categoria_filtro; $types .= "ii";
        } else { // Es Hija
            $sql_base .= " AND p.id_categoria = ?";
            $params[] = $id_categoria_filtro; $types .= "i";
        }
    }
    // Si $cat_info es false (categoría no válida), no se añade el filtro
}

// Añadir filtro de búsqueda si existe
if (!empty($termino_busqueda)) {
    $sql_base .= " AND p.nombre_producto LIKE ?";
    $params[] = "%" . $termino_busqueda . "%";
    $types .= "s";
}

// --- Fin Lógica de Filtros ---

// Contar total de productos (para paginación) con filtros
$sql_count = "SELECT COUNT(DISTINCT p.id_producto) as total " . $sql_base;
$stmt_count = $conn->prepare($sql_count);
if (!empty($types)) { // Solo bindear si hay parámetros
    $bind_params_count = [$types];
    foreach ($params as $key => $value) { $bind_params_count[] = &$params[$key]; }
    call_user_func_array([$stmt_count, 'bind_param'], $bind_params_count);
}
$stmt_count->execute();
$total_productos = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);
$stmt_count->close();

// Construir consulta principal
$sql_select = "SELECT DISTINCT p.id_producto, p.nombre_producto, p.imagen_principal, c.nombre_categoria, vp.min_precio, vp.max_precio";
$sql_order_limit = "";
switch ($orden) { // Ordenamiento
    case 'precio_asc': $sql_order_limit .= " ORDER BY vp.min_precio ASC, p.nombre_producto ASC"; break;
    case 'precio_desc': $sql_order_limit .= " ORDER BY vp.min_precio DESC, p.nombre_producto ASC"; break;
    default: $sql_order_limit .= " ORDER BY p.nombre_producto ASC";
}
// Añadir paginación a parámetros y tipos
$params_paginacion = $params; // Copiar parámetros de filtros
$types_paginacion = $types;
$params_paginacion[] = $productos_por_pagina; $params_paginacion[] = $offset; $types_paginacion .= "ii";
$sql_order_limit .= " LIMIT ? OFFSET ?";

// Ejecutar consulta principal
$sql_final = $sql_select . $sql_base . $sql_order_limit;
$stmt_productos = $conn->prepare($sql_final);
// bind_param dinámico (siempre necesario por LIMIT/OFFSET)
$bind_params_final = [$types_paginacion];
foreach ($params_paginacion as $key => $value) { $bind_params_final[] = &$params_paginacion[$key]; }
call_user_func_array([$stmt_productos, 'bind_param'], $bind_params_final);
$stmt_productos->execute();
$resultado_productos = $stmt_productos->get_result();
$productos_listados = $resultado_productos->fetch_all(MYSQLI_ASSOC);
$stmt_productos->close();

// Obtener categorías para filtro
$query_cats_filtro = "SELECT c.id_categoria, c.nombre_categoria, cp.nombre_categoria AS nombre_padre FROM categorias c LEFT JOIN categorias cp ON c.id_categoria_padre = cp.id_categoria ORDER BY COALESCE(cp.nombre_categoria, c.nombre_categoria), c.id_categoria_padre, c.nombre_categoria";
$categorias_filtro = $conn->query($query_cats_filtro)->fetch_all(MYSQLI_ASSOC);
$conn->close();

// Variable para saber si hay filtros activos
$filtros_activos = ($id_categoria_filtro !== null || !empty($termino_busqueda));

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Productos | Tinkuy</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
     <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
     <link rel="stylesheet" href="assets/css/style.css">
     <style>
         .product-card-img { height: 200px; object-fit: cover; }
         .product-card-title a { color: inherit; text-decoration: none; }
         .product-card-title a:hover { color: var(--bs-primary); }
         .pagination .page-link { color: var(--bs-primary); }
         .pagination .active .page-link { background-color: var(--bs-primary); border-color: var(--bs-primary); }
     </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <?php $pagina_actual = 'productos'; include 'assets/component/navbar.php'; ?>

    <main class="container my-4 flex-grow-1">
        <h1 class="mb-3">Nuestros Productos</h1>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="products.php" method="GET" id="filter-form">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <label for="buscar" class="form-label small">Buscar por nombre</label>
                            <input type="text" class="form-control form-control-sm" id="buscar" name="buscar" placeholder="Ej: Chompa Alpaca" value="<?= htmlspecialchars($termino_busqueda) ?>">
                        </div>
                        <div class="col-lg-3 col-md-6">
                             <label for="categoria" class="form-label small">Filtrar por categoría</label>
                             <select name="categoria" id="categoria" class="form-select form-select-sm">
                                 <option value="" <?= ($id_categoria_filtro === null) ? 'selected' : '' ?>>-- Todas las categorías --</option>
                                 <?php
                                    $current_group_label = null;
                                    foreach ($categorias_filtro as $cat) {
                                        if ($cat['id_categoria_padre'] === null) {
                                            if ($current_group_label !== null) echo '</optgroup>';
                                            echo '<optgroup label="' . htmlspecialchars($cat['nombre_categoria']) . '">';
                                            $current_group_label = $cat['nombre_categoria'];
                                            $selected = ($id_categoria_filtro == $cat['id_categoria']) ? 'selected' : '';
                                            // La opción 'Todo' ahora tiene value vacío si queremos que muestre todo al seleccionarla
                                            // O mantenemos el ID si queremos filtrar por la categoría padre + hijas
                                             echo '<option value="' . $cat['id_categoria'] . '" ' . $selected . '>' . htmlspecialchars($cat['nombre_categoria']) . ' (Todo)</option>';
                                        } elseif ($cat['nombre_padre'] === $current_group_label) {
                                            $selected = ($id_categoria_filtro == $cat['id_categoria']) ? 'selected' : '';
                                            echo '<option value="' . $cat['id_categoria'] . '" ' . $selected . '>&nbsp;&nbsp;&nbsp;' . htmlspecialchars($cat['nombre_categoria']) . '</option>';
                                        }
                                    }
                                    if ($current_group_label !== null) echo '</optgroup>';
                                 ?>
                             </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                             <label for="orden" class="form-label small">Ordenar por</label>
                             <select name="orden" id="orden" class="form-select form-select-sm">
                                 <option value="nombre_asc" <?= ($orden == 'nombre_asc') ? 'selected' : '' ?>>Nombre (A-Z)</option>
                                 <option value="precio_asc" <?= ($orden == 'precio_asc') ? 'selected' : '' ?>>Precio (Menor)</option>
                                 <option value="precio_desc" <?= ($orden == 'precio_desc') ? 'selected' : '' ?>>Precio (Mayor)</option>
                             </select>
                        </div>
                        <div class="col-lg-2 col-md-6 d-flex align-items-end">
                             <button class="btn btn-primary btn-sm w-100 me-1" type="submit">
                                 <i class="bi bi-funnel-fill"></i> Filtrar
                             </button>
                             <?php if ($filtros_activos): // Mostrar solo si hay filtros activos ?>
                                <a href="products.php" class="btn btn-outline-secondary btn-sm w-auto" title="Quitar filtros">
                                     <i class="bi bi-x-lg"></i>
                                </a>
                             <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($productos_listados)): ?>
            <div class="alert alert-info text-center shadow-sm">
                 <i class="bi bi-info-circle me-2"></i>No se encontraron productos activos que coincidan.
                 <?php if ($filtros_activos): ?>
                     Intenta <a href="products.php" class="alert-link">restablecer los filtros</a>.
                 <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-muted text-end mb-2"><small>Mostrando <?= count($productos_listados) ?> de <?= $total_productos ?> productos activos</small></p>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($productos_listados as $prod): ?>
                    <div class="col d-flex align-items-stretch">
                        <div class="card h-100 shadow-sm product-card border-0 overflow-hidden">
                            <a href="producto.php?id=<?= $prod['id_producto'] ?>">
                                <img src="assets/img/productos/<?= htmlspecialchars($prod['imagen_principal'] ?: 'default.png') ?>" class="card-img-top product-card-img" alt="<?= htmlspecialchars($prod['nombre_producto']) ?>">
                            </a>
                            <div class="card-body d-flex flex-column pb-2">
                                <h5 class="card-title product-card-title mb-1 fs-6">
                                     <a href="producto.php?id=<?= $prod['id_producto'] ?>" class="text-decoration-none text-dark stretched-link">
                                          <?= htmlspecialchars(mb_strimwidth($prod['nombre_producto'], 0, 50, "...")) ?>
                                     </a>
                                </h5>
                                <p class="card-text small text-muted mb-2"><?= htmlspecialchars($prod['nombre_categoria']) ?></p>
                                <p class="card-text fw-bold text-primary mt-auto mb-0 product-card-price fs-5">
                                     <?php /* Lógica de precio */
                                         if (!is_null($prod['min_precio']) && !is_null($prod['max_precio']) && $prod['min_precio'] != $prod['max_precio']) { echo 'S/ ' . number_format($prod['min_precio'], 2) . ' - S/ ' . number_format($prod['max_precio'], 2); }
                                         elseif (!is_null($prod['min_precio'])) { echo 'S/ ' . number_format($prod['min_precio'], 2); }
                                         else { echo '<span class="text-muted small">Consultar</span>'; }
                                     ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($total_paginas > 1): ?>
                <nav aria-label="Navegación de productos" class="mt-4 d-flex justify-content-center">
                    <ul class="pagination shadow-sm">
                        <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $pagina_actual - 1 ?>&categoria=<?= $id_categoria_filtro ?? '' ?>&buscar=<?= urlencode($termino_busqueda) ?>&orden=<?= $orden ?>">&laquo;</a>
                        </li>
                        <?php /* Lógica de rango de páginas */
                            $rango = 2; $inicio = max(1, $pagina_actual - $rango); $fin = min($total_paginas, $pagina_actual + $rango);
                            if ($inicio > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            for ($i = $inicio; $i <= $fin; $i++): ?><li class="page-item <?= ($i == $pagina_actual) ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&categoria=<?= $id_categoria_filtro ?? '' ?>&buscar=<?= urlencode($termino_busqueda) ?>&orden=<?= $orden ?>"><?= $i ?></a></li><?php endfor;
                            if ($fin < $total_paginas) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        ?>
                        <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $pagina_actual + 1 ?>&categoria=<?= $id_categoria_filtro ?? '' ?>&buscar=<?= urlencode($termino_busqueda) ?>&orden=<?= $orden ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        <?php endif; ?>
    </main>

    <?php include 'assets/component/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('buscar');
            const filterForm = document.getElementById('filter-form');

            if (searchInput && filterForm) {
                searchInput.addEventListener('keypress', function(event) {
                    // Verificar si la tecla presionada es Enter (código 13)
                    if (event.key === 'Enter' || event.keyCode === 13) {
                        event.preventDefault(); // Prevenir el comportamiento por defecto (si lo hubiera)
                        console.log('Enter presionado en búsqueda. Enviando formulario...'); // Mensaje de depuración
                        filterForm.submit(); // Enviar el formulario de filtros
                    }
                });
            }
        });
    </script>
</body>
</html>