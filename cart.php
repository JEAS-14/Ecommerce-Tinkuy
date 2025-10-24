<?php
// session_start() debe estar en la página principal antes de incluir navbar
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'assets/admin/db.php'; // Incluimos la conexión

// --- LÓGICA DE OBTENCIÓN DE PRODUCTOS DEL CARRITO ---

$carrito_items = [];
$total_general = 0;
// Rutas base para imágenes
$ruta_base_principal = "assets/img/productos/";
$ruta_base_variantes = "assets/img/productos/variantes/";

if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
    $ids_variantes = array_keys($_SESSION['carrito']);

    if (!empty($ids_variantes)) { // Asegurarse de que hay IDs antes de consultar
        $placeholders = implode(',', array_fill(0, count($ids_variantes), '?'));
        $tipos = str_repeat('i', count($ids_variantes));

        // MODIFICADO: Añadimos v.imagen_variante al SELECT
        $query = "
            SELECT
                v.id_variante, v.talla, v.color, v.precio, v.imagen_variante, -- <<< Campo añadido
                p.nombre_producto, p.imagen_principal
            FROM
                variantes_producto AS v
            JOIN
                productos AS p ON v.id_producto = p.id_producto
            WHERE
                v.id_variante IN ($placeholders)
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($tipos, ...$ids_variantes);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $detalles_productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $detalles_productos[$fila['id_variante']] = $fila;
        }
        $stmt->close(); // Cerrar statement aquí

        // Preparamos el array final para el HTML
        foreach ($_SESSION['carrito'] as $id_variante => $item) {
            if (isset($detalles_productos[$id_variante])) {
                $detalles = $detalles_productos[$id_variante];
                $cantidad = $item['cantidad'];
                $precio = $detalles['precio']; // Precio de la BD
                $subtotal = $precio * $cantidad;
                $total_general += $subtotal;

                // --- NUEVO: Determinar qué imagen mostrar ---
                $imagen_a_usar = $ruta_base_principal . ($detalles['imagen_principal'] ?: 'default.png'); // Fallback a principal o default
                // Si existe imagen_variante y no está vacía, usarla
                if (!empty($detalles['imagen_variante'])) {
                     // Verificar si el archivo existe físicamente (opcional pero recomendado)
                     if (file_exists($ruta_base_variantes . $detalles['imagen_variante'])) {
                          $imagen_a_usar = $ruta_base_variantes . $detalles['imagen_variante'];
                     }
                     // Si no existe físicamente, se queda con la principal (ya asignada)
                }
                // --- FIN NUEVO ---


                $carrito_items[] = [
                    'id_variante' => $id_variante,
                    'nombre' => $detalles['nombre_producto'],
                    // 'imagen' => $detalles['imagen_principal'], // Ya no usamos esta directamente
                    'imagen_a_mostrar' => $imagen_a_usar, // <<< Nueva clave con la ruta correcta
                    'talla' => $detalles['talla'],
                    'color' => $detalles['color'],
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $subtotal
                ];
            } else {
                 // El producto/variante ya no existe, opcionalmente eliminarlo del carrito
                 unset($_SESSION['carrito'][$id_variante]);
                 // Podrías añadir un mensaje de advertencia aquí
                 // $_SESSION['mensaje_error'] = "Algunos productos de tu carrito ya no están disponibles y fueron eliminados.";
            }
        }
    } else {
        // El array de IDs estaba vacío, aunque $_SESSION['carrito'] no. Limpiar por si acaso.
        $_SESSION['carrito'] = [];
    }
}
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
        $pagina_actual = 'carrito'; // Corregido para marcar 'carrito' como activo
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
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['mensaje_error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensaje_error']); ?>
            <?php endif; ?>


            <?php if (empty($carrito_items)): ?>
                <div class="text-center p-5 border rounded shadow-sm bg-white">
                    <i class="bi bi-cart-x" style="font-size: 4rem; color: #6c757d;"></i>
                    <h3 class="mt-3">Tu carrito está vacío</h3>
                    <p class="text-muted">Parece que aún no has agregado nada.</p>
                    <a href="products.php" class="btn btn-primary mt-2">
                        <i class="bi bi-shop me-1"></i> Ir a la tienda
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="table-responsive shadow-sm bg-white rounded">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" colspan="2">Producto</th>
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
                                            <td style="width: 100px;">
                                                <img src="<?= htmlspecialchars($item['imagen_a_mostrar']) ?>"
                                                     alt="<?= htmlspecialchars($item['nombre']) ?>"
                                                     class="img-fluid rounded"
                                                     style="width: 80px; height: 80px; object-fit: cover;">
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
                                                <a href="eliminar_carrito.php?id=<?= $item['id_variante'] ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Quitar este producto del carrito?');">
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
                        <div class="card shadow-sm border-0 sticky-top" style="top: 80px;">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Resumen del Pedido</h5>
                                <hr>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <strong>S/ <?= number_format($total_general, 2) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Envío</span>
                                    <span class="text-success">GRATIS</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between h4 mb-4">
                                    <strong>Total</strong>
                                    <strong>S/ <?= number_format($total_general, 2) ?></strong>
                                </div>
                                <div class="d-grid">
                                    <a href="pago.php" class="btn btn-success btn-lg">
                                        <i class="bi bi-shield-check me-2"></i> Proceder al Pago
                                    </a>
                                </div>
                                <div class="text-center mt-3">
                                     <a href="products.php" class="link-secondary text-decoration-none">
                                        <i class="bi bi-arrow-left-short"></i> Seguir comprando
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