<?php
session_start();
include 'db.php'; // Estamos en la carpeta 'admin', db.php está aquí

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); //
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    session_destroy();
    header('Location: ../../login.php'); //
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

// (Manejo de mensajes de éxito/error de eliminar_producto_admin.php)
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito']);


// --- LÓGICA GET (Calidad de Funcionalidad y Rendimiento) ---
// Consulta similar a la del vendedor, pero SIN filtrar por id_vendedor
// y agregando el nombre del vendedor (u.usuario)

$query = "
    SELECT 
        p.id_producto,
        p.nombre_producto,
        p.imagen_principal,
        c.nombre_categoria,
        u.usuario AS nombre_vendedor, -- Agregamos el nombre del vendedor
        -- Agrupamos todas las variantes de este producto en un solo campo JSON
        CONCAT(
            '[', 
            IFNULL(GROUP_CONCAT(
                JSON_OBJECT(
                    'id_variante', vp.id_variante,
                    'talla', vp.talla,
                    'color', vp.color,
                    'precio', vp.precio,
                    'stock', vp.stock
                ) ORDER BY vp.id_variante
            ), '') ,
            ']'
        ) AS variantes_json
    FROM 
        productos AS p
    JOIN 
        categorias AS c ON p.id_categoria = c.id_categoria
    JOIN 
        usuarios AS u ON p.id_vendedor = u.id_usuario -- Join para obtener el vendedor
    LEFT JOIN 
        variantes_producto AS vp ON p.id_producto = vp.id_producto
    -- WHERE -- ¡No filtramos por vendedor! El admin ve todo.
    GROUP BY
        p.id_producto
    ORDER BY
        p.id_producto DESC -- Ordenamos por ID para ver los más nuevos primero
";

$resultado = $conn->query($query);
$productos = $resultado->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Productos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Admin</a> <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="pedidos.php">Pedidos</a></li> <li class="nav-item"><a class="nav-link active" href="productos_admin.php">Productos</a></li> <li class="nav-item"><a class="nav-link" href="usuarios.php">Usuarios</a></li> </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión</a></li> </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Gestión de Productos (Todos)</h2>
            </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger alert-error-animated"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">Vendedor</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Variantes (Talla / Color / Precio / Stock)</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No hay productos en la tienda.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($productos as $producto): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../../assets/img/productos/<?= htmlspecialchars($producto['imagen_principal']) ?>" alt="" style="width: 60px; height: 60px; object-fit: cover;" class="rounded me-3">
                                                <strong><?= htmlspecialchars($producto['nombre_producto']) ?></strong>
                                            </div>
                                        </td>
                                        <td><small><?= htmlspecialchars($producto['nombre_vendedor']) ?></small></td>
                                        <td><small><?= htmlspecialchars($producto['nombre_categoria']) ?></small></td>
                                        <td>
                                            <?php 
                                            // Decodificamos el JSON de variantes
                                            $variantes = json_decode($producto['variantes_json'], true);
                                            
                                            if (empty($variantes[0])) { // [0] es por el IFNULL de SQL
                                                echo '<small class="text-muted">Sin variantes.</small>';
                                            } else {
                                                echo '<ul class="list-unstyled mb-0">';
                                                foreach ($variantes as $v) {
                                                    echo sprintf(
                                                        '<li><small>%s / %s / <strong>S/ %.2f</strong> / (Stock: %d)</small></li>',
                                                        htmlspecialchars($v['talla']),
                                                        htmlspecialchars($v['color']),
                                                        $v['precio'],
                                                        $v['stock']
                                                    );
                                                }
                                                echo '</ul>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="editar_producto_admin.php?id=<?= $producto['id_producto'] ?>" class="btn btn-sm btn-outline-primary" title="Editar Producto"> <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="eliminar_producto_admin.php?id=<?= $producto['id_producto'] ?>" class="btn btn-sm btn-outline-danger" title="Eliminar Producto" onclick="return confirm('¿Estás seguro? Se eliminará el producto y TODAS sus variantes.');"> <i class="bi bi-trash"></i>
                                            </a>
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