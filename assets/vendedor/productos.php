<?php
session_start();
include '../admin/db.php'; // Subimos un nivel

// --- Seguridad ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}
if ($_SESSION['rol'] !== 'vendedor') {
    session_destroy();
    header('Location: ../../login.php');
    exit;
}
// --- Fin Seguridad ---

$id_vendedor = $_SESSION['usuario_id'];
$nombre_vendedor = $_SESSION['usuario'];

// --- Lógica GET ---
// MODIFICADO: Quitamos el filtro "AND p.estado = 'activo'" para obtener TODOS los productos
$query = "
    SELECT
        p.id_producto,
        p.nombre_producto,
        p.imagen_principal,
        p.estado, -- Necesitamos el estado para mostrarlo y decidir botones
        c.nombre_categoria,
        CONCAT(
            '[',
            IFNULL(GROUP_CONCAT(
                JSON_OBJECT(
                    'id_variante', vp.id_variante,
                    'talla', vp.talla,
                    'color', vp.color,
                    'precio', vp.precio,
                    'stock', vp.stock,
                    'estado_variante', vp.estado -- Incluimos estado de variante
                ) ORDER BY vp.id_variante
            ), '') ,
            ']'
        ) AS variantes_json
    FROM
        productos AS p
    JOIN
        categorias AS c ON p.id_categoria = c.id_categoria
    LEFT JOIN
        variantes_producto AS vp ON p.id_producto = vp.id_producto
    WHERE
        p.id_vendedor = ?
        -- YA NO filtramos por p.estado = 'activo' aquí
    GROUP BY
        p.id_producto
    ORDER BY
        p.estado ASC, -- Opcional: Mostrar activos primero
        p.nombre_producto ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_vendedor);
$stmt->execute();
$resultado = $stmt->get_result();
$productos = $resultado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Productos - Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        .producto-inactivo {
            opacity: 0.6;
            background-color: #f8f9fa; /* Gris claro */
        }
        .producto-inactivo:hover { /* Evitar que se aclare al pasar el mouse */
             opacity: 0.7;
        }
        .variante-list small { /* Mejorar legibilidad de variantes */
             display: block;
             margin-bottom: 2px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Vendedor</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vendedorNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="vendedorNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="productos.php">Mis Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="envios.php">Envíos Pendientes</a></li>
                    <li class="nav-item"><a class="nav-link" href="ventas.php">Mis Ventas</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión (<?= htmlspecialchars($nombre_vendedor) ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Mis Productos (Activos e Inactivos)</h2>
            <a href="agregar_producto.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Agregar Nuevo Producto
            </a>
            </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Variantes Activas</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Aún no has agregado ningún producto.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($productos as $producto): ?>
                                    <tr class="<?= ($producto['estado'] === 'inactivo') ? 'producto-inactivo' : '' ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../../assets/img/productos/<?= htmlspecialchars($producto['imagen_principal'] ?: 'default.png') ?>"
                                                     alt="Imagen de <?= htmlspecialchars($producto['nombre_producto']) ?>"
                                                     style="width: 60px; height: 60px; object-fit: cover;" class="rounded me-3">
                                                <strong><?= htmlspecialchars($producto['nombre_producto']) ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($producto['estado'] === 'activo'): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($producto['nombre_categoria']) ?></td>
                                        <td class="variante-list">
                                            <?php
                                            $variantes = json_decode($producto['variantes_json'], true);
                                            $has_active_variants = false;
                                            if (!empty($variantes[0])) {
                                                // No usamos <ul> para ahorrar espacio vertical
                                                foreach ($variantes as $v) {
                                                    if (isset($v['estado_variante']) && $v['estado_variante'] === 'activo') {
                                                        $has_active_variants = true;
                                                        echo sprintf(
                                                            '<small>%s / %s | <strong>S/ %.2f</strong> | Stock: %d</small>', // Formato más compacto
                                                            htmlspecialchars($v['talla'] ?? '-'),
                                                            htmlspecialchars($v['color'] ?? '-'),
                                                            $v['precio'] ?? 0.00,
                                                            $v['stock'] ?? 0
                                                        );
                                                    }
                                                }
                                                if (!$has_active_variants && $producto['estado'] === 'activo') { // Solo mostrar si el producto está activo
                                                     echo '<small class="text-danger">Ninguna variante activa.</small>';
                                                } elseif (empty($variantes)) { // Manejar el caso []
                                                     echo '<small class="text-muted">Sin variantes.</small>';
                                                }
                                            } else {
                                                echo '<small class="text-muted">Sin variantes creadas.</small>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="editar_producto.php?id=<?= $producto['id_producto'] ?>" class="btn btn-sm btn-outline-primary" title="Editar Producto y Variantes">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if ($producto['estado'] === 'activo'): ?>
                                                <a href="cambiar_estado_producto.php?id=<?= $producto['id_producto'] ?>&estado=inactivo"
                                                   class="btn btn-sm btn-outline-warning"
                                                   title="Desactivar Producto"
                                                   onclick="return confirm('¿Desactivar este producto? No será visible para clientes.');">
                                                    <i class="bi bi-eye-slash"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="cambiar_estado_producto.php?id=<?= $producto['id_producto'] ?>&estado=activo"
                                                   class="btn btn-sm btn-outline-success"
                                                   title="Activar Producto"
                                                   onclick="return confirm('¿Reactivar este producto?');">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>