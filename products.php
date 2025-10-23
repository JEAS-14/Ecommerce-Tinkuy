<?php
session_start();
include 'assets/admin/db.php'; // Incluimos la conexión

// --- ESTA ES LA PARTE CORREGIDA ---

// 1. Esta consulta es más compleja.
// Seleccionamos la información del producto (de la tabla 'productos')
// Y usamos un Sub-SELECT (MIN(vp.precio)) para encontrar el precio
// de la variante más barata de ESE producto.
// También contamos cuántas variantes tiene (COUNT) y sumamos el stock total (SUM).

$query = "
    SELECT 
        p.id_producto,
        p.nombre_producto,
        p.descripcion,
        p.imagen_principal,
        c.nombre_categoria,
        (SELECT MIN(vp.precio) FROM variantes_producto vp WHERE vp.id_producto = p.id_producto) AS precio_minimo,
        (SELECT SUM(vp.stock) FROM variantes_producto vp WHERE vp.id_producto = p.id_producto) AS stock_total
    FROM 
        productos AS p
    JOIN 
        categorias AS c ON p.id_categoria = c.id_categoria
    WHERE
        (SELECT SUM(vp.stock) FROM variantes_producto vp WHERE vp.id_producto = p.id_producto) > 0
    ORDER BY 
        p.fecha_creacion DESC
";

// 2. Ejecutar la consulta
$resultado = $conn->query($query);
// --- FIN DE LA PARTE CORREGIDA ---
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Nuestros Productos | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    </head>

<body>
    <?php include 'assets/component/navbar.php'; ?>

    <div class="container my-5">
        <h1 class="text-center mb-4">Catálogo de Productos</h1>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">

            <?php
            // 3. Hacemos el loop con los nuevos datos
            if ($resultado && $resultado->num_rows > 0):
                while ($producto = $resultado->fetch_assoc()):
            ?>
            
            <div class="col">
                <div class="card h-100 shadow-sm border-0">
                    
                    <img src="assets/img/productos/<?php echo htmlspecialchars($producto['imagen_principal']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" style="height: 250px; object-fit: cover;">
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($producto['nombre_producto']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                        
                        <p class="card-text fs-5 fw-bold text-primary">
                            Desde S/ <?php echo htmlspecialchars(number_format($producto['precio_minimo'], 2)); ?>
                        </p>
                        <small class="text-muted">Stock Total: <?php echo htmlspecialchars($producto['stock_total']); ?></small>
                    </div>
                    <div class="card-footer bg-white border-0 p-3">
                        <a href="producto.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-primary w-100">
                            <i class="bi bi-eye"></i> Ver Opciones
                        </a>
                    </div>
                </div>
            </div>

            <?php
                endwhile;
            else:
            ?>
                <div class="col-12">
                    <p class="text-center text-muted fs-4">No hay productos disponibles en este momento.</p>
                </div>
            <?php
            endif;
            $conn->close();
            ?>

        </div>
    </div>

    <?php include 'assets/component/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>