<?php
session_start();
include '../admin/db.php'; // Subimos un nivel para encontrar db.php

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); //
    exit;
}
if ($_SESSION['rol'] !== 'vendedor' && $_SESSION['rol'] !== 'admin') {
    header('Location: ../../login.php'); //
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

$id_vendedor = $_SESSION['usuario_id'];

// --- LÓGICA DE CALIDAD (FUNCIONALIDAD Y RENDIMIENTO) ---
// Esta consulta es avanzada.
// 1. Selecciona los productos (p)
// 2. SOLO donde el id_vendedor coincida con el de la sesión.
// 3. Usa JOIN para obtener el nombre de la categoría (c).
// 4. Usa un LEFT JOIN y GROUP_CONCAT para "pegar" todas las variantes (vp)
//    en una sola cadena de texto (JSON), lo cual es MUY eficiente.

$query = "
    SELECT 
        p.id_producto,
        p.nombre_producto,
        p.imagen_principal,
        c.nombre_categoria,
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
    LEFT JOIN 
        variantes_producto AS vp ON p.id_producto = vp.id_producto
    WHERE 
        p.id_vendedor = ?  -- <-- ¡Seguridad! Solo muestra los productos de este vendedor
    GROUP BY
        p.id_producto
    ORDER BY
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
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Vendedor</a> <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="productos.php">Mis Productos</a></li> <li class="nav-item"><a class="nav-link" href="agregar_producto.php">Agregar Producto</a></li> </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión</a></li> </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Mis Productos</h2>
            <a href="agregar_producto.php" class="btn btn-primary"> <i class="bi bi-plus-circle"></i> Agregar Nuevo Producto
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">Categoría</th>
                                <th scope="col">Variantes (Talla / Color / Precio / Stock)</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Aún no has agregado ningún producto.</td>
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
                                        <td><?= htmlspecialchars($producto['nombre_categoria']) ?></td>
                                        <td>
                                            <?php 
                                            // Decodificamos el JSON de variantes que creó el SQL
                                            $variantes = json_decode($producto['variantes_json'], true);
                                            
                                            if (empty($variantes[0])) { // [0] es por el IFNULL de SQL
                                                echo '<small class="text-muted">Sin variantes creadas.</small>';
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
                                            <a href="editar_producto.php?id=<?= $producto['id_producto'] ?>" class="btn btn-sm btn-outline-primary" title="Editar Producto"> <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="eliminar_producto.php?id=<?= $producto['id_producto'] ?>" class="btn btn-sm btn-outline-danger" title="Eliminar Producto" onclick="return confirm('¿Estás seguro de que quieres eliminar este producto y TODAS sus variantes?');"> <i class="bi bi-trash"></i>
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