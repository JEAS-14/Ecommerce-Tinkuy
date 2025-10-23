<?php
// Corrección de Fiabilidad: session_start() es lo primero para evitar el error de encabezados (Headers Already Sent)
session_start();
include 'assets/admin/db.php'; // Incluimos la conexión

// --- INICIO: LÓGICA DE OBTENCIÓN DE PRODUCTOS DEL CARRITO ---

$carrito_items = [];
$total_general = 0;

// Verificamos si el carrito existe y no está vacío
if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
    $ids_variantes = array_keys($_SESSION['carrito']);
    
    // 2. Creamos una cadena de placeholders (?,?,?) para el IN()
    $placeholders = implode(',', array_fill(0, count($ids_variantes), '?'));
    // Creamos la cadena de tipos ("iii..." para bind_param)
    $tipos = str_repeat('i', count($ids_variantes));

    // 3. Escribimos la consulta de CALIDAD y SEGURIDAD (consulta preparada)
    // Traemos todos los datos de los productos en el carrito en UNA sola consulta
    $query = "
        SELECT 
            v.id_variante, v.talla, v.color, v.precio,
            p.nombre_producto, p.imagen_principal
        FROM 
            variantes_producto AS v
        JOIN 
            productos AS p ON v.id_producto = p.id_producto
        WHERE 
            v.id_variante IN ($placeholders)
    ";
    
    $stmt = $conn->prepare($query);
    // 4. Hacemos el bind_param dinámico
    // El operador '...' (splat) es fundamental aquí.
    $stmt->bind_param($tipos, ...$ids_variantes);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // 5. Mapeamos los resultados para un acceso fácil
    $detalles_productos = [];
    while ($fila = $resultado->fetch_assoc()) {
        // Usamos el precio de la BD como fuente de la verdad para prevenir manipulaciones
        $detalles_productos[$fila['id_variante']] = $fila;
    }

    // 6. Preparamos el array final para el HTML
    foreach ($_SESSION['carrito'] as $id_variante => $item) {
        if (isset($detalles_productos[$id_variante])) {
            $detalles = $detalles_productos[$id_variante];
            $cantidad = $item['cantidad'];
            // Usamos el precio del producto de la BD (más seguro)
            $precio = $detalles['precio']; 
            $subtotal = $precio * $cantidad;
            
            $total_general += $subtotal;
            
            $carrito_items[] = [
                'id_variante' => $id_variante,
                'nombre' => $detalles['nombre_producto'],
                'imagen' => $detalles['imagen_principal'],
                'talla' => $detalles['talla'],
                'color' => $detalles['color'],
                'cantidad' => $cantidad,
                'precio' => $precio,
                'subtotal' => $subtotal
            ];
        }
    }
}
// Cerramos la conexión a la BD
$conn->close();
// --- FIN: LÓGICA DE OBTENCIÓN DE PRODUCTOS DEL CARRITO ---
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mi Carrito | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css"> 
</head>

<body class="d-flex flex-column min-vh-100"> 
    <?php 
        $pagina_actual = 'cart'; // Marcamos la página actual para el navbar
        include 'assets/component/navbar.php'; 
    ?>

    <main class="flex-grow-1"> 
        <div class="container my-5">
            <h1 class="text-center mb-4">Mi Carrito de Compras</h1>

            <?php if (isset($_SESSION['mensaje_exito'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['mensaje_exito']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensaje_exito']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['mensaje_error'])): ?>
                <div class="alert alert-danger alert-error-animated alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['mensaje_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensaje_error']); ?>
            <?php endif; ?>


            <?php if (empty($carrito_items)): ?>
                <div class="text-center p-5 border rounded shadow-sm">
                    <i class="bi bi-cart-x" style="font-size: 4rem; color: #6c757d;"></i>
                    <h3 class="mt-3">Tu carrito está vacío</h3>
                    <p class="text-muted">Aún no has agregado productos a tu carrito.</p>
                    <a href="products.php" class="btn btn-primary mt-2">
                        <i class="bi bi-arrow-left"></i> Volver a la tienda
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="table-responsive shadow-sm">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Producto</th>
                                        <th scope="col">Detalle</th>
                                        <th scope="col">Precio</th>
                                        <th scope="col">Cantidad</th>
                                        <th scope="col">Subtotal</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($carrito_items as $item): ?>
                                        <tr>
                                            <td>
                                                <img src="assets/img/productos/<?= htmlspecialchars($item['imagen']) ?>" alt="<?= htmlspecialchars($item['nombre']) ?>" class="img-fluid rounded" style="width: 80px; height: 80px; object-fit: cover;">
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    Talla: <?= htmlspecialchars($item['talla']) ?> | 
                                                    Color: <?= htmlspecialchars($item['color']) ?>
                                                </small>
                                            </td>
                                            <td>S/ <?= number_format($item['precio'], 2) ?></td>
                                            <td>
                                                <?= htmlspecialchars($item['cantidad']) ?>
                                            </td>
                                            <td><strong>S/ <?= number_format($item['subtotal'], 2) ?></strong></td>
                                            <td>
                                                <a href="eliminar_carrito.php?id=<?= $item['id_variante'] ?>" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="card-title">Resumen del Pedido</h5>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Subtotal</span>
                                    <strong>S/ <?= number_format($total_general, 2) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Envío</span>
                                    <span class="text-success">GRATIS</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between h4">
                                    <strong>Total</strong>
                                    <strong>S/ <?= number_format($total_general, 2) ?></strong>
                                </div>
                                <div class="d-grid mt-4">
                                    <a href="pago.php" class="btn btn-success btn-lg">
                                        <i class="bi bi-shield-check"></i> Proceder al Pago
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main> 
    <?php include 'assets/component/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>