<?php
session_start();
include 'db.php'; // Estamos en la carpeta 'admin', db.php está aquí

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); //
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    header('Location: ../../login.php'); //
    exit;
}

// 1. Validamos el ID del pedido (Seguridad)
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['mensaje_error'] = "ID de pedido no válido.";
    header('Location: pedidos.php'); //
    exit;
}
$id_pedido = (int)$_GET['id'];
// --- FIN DE CALIDAD (SEGURIDAD) ---


// --- LÓGICA GET (Calidad de Funcionalidad y Rendimiento) ---

// 2. Consulta 1: Obtener la información general del pedido (Cliente, Envío, Total)
$query_pedido = "
    SELECT 
        p.id_pedido, p.fecha_pedido, p.total_pedido,
        e.nombre_estado,
        pr.nombres AS cliente_nombres, pr.apellidos AS cliente_apellidos, pr.email AS cliente_email,
        d.direccion, d.ciudad, d.pais, d.codigo_postal
    FROM 
        pedidos AS p
    JOIN 
        estados_pedido AS e ON p.id_estado_pedido = e.id_estado
    JOIN 
        usuarios AS u ON p.id_usuario = u.id_usuario
    JOIN 
        perfiles AS pr ON u.id_usuario = pr.id_usuario
    JOIN 
        direcciones AS d ON p.id_direccion_envio = d.id_direccion
    WHERE 
        p.id_pedido = ?
";
$stmt_pedido = $conn->prepare($query_pedido);
$stmt_pedido->bind_param("i", $id_pedido);
$stmt_pedido->execute();
$resultado_pedido = $stmt_pedido->get_result();

if ($resultado_pedido->num_rows === 0) {
    // Si no se encontró el pedido
    $_SESSION['mensaje_error'] = "Pedido no encontrado.";
    header('Location: pedidos.php'); //
    exit;
}
$pedido = $resultado_pedido->fetch_assoc();


// 3. Consulta 2: Obtener los productos (líneas) de este pedido
$query_detalles = "
    SELECT 
        dp.cantidad,
        dp.precio_historico,
        vp.talla, vp.color, vp.sku,
        prod.nombre_producto, prod.imagen_principal
    FROM 
        detalle_pedido AS dp
    JOIN 
        variantes_producto AS vp ON dp.id_variante = vp.id_variante
    JOIN 
        productos AS prod ON vp.id_producto = prod.id_producto
    WHERE 
        dp.id_pedido = ?
";
$stmt_detalles = $conn->prepare($query_detalles);
$stmt_detalles->bind_param("i", $id_pedido);
$stmt_detalles->execute();
$resultado_detalles = $stmt_detalles->get_result();
$detalles = $resultado_detalles->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Pedido #<?= $pedido['id_pedido'] ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Admin</a> <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="pedidos.php">Pedidos</a></li> <li class="nav-item"><a class="nav-link" href="productos_admin.php">Productos</a></li> <li class="nav-item"><a class="nav-link" href="usuarios.php">Usuarios</a></li> </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión</a></li> </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-3">Detalle del Pedido <span class="text-primary">#<?= $pedido['id_pedido'] ?></span></h2>
        <a href="pedidos.php" class="btn btn-sm btn-outline-secondary mb-4"> <i class="bi bi-arrow-left"></i> Volver a todos los pedidos
        </a>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <i class="bi bi-person-fill"></i> Información del Cliente
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Nombre:</strong> <?= htmlspecialchars($pedido['cliente_nombres'] . ' ' . $pedido['cliente_apellidos']) ?></p>
                        <p class="mb-0"><strong>Email:</strong> <?= htmlspecialchars($pedido['cliente_email']) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <i class="bi bi-truck"></i> Dirección de Envío
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><?= htmlspecialchars($pedido['direccion']) ?></p>
                        <p class="mb-1"><?= htmlspecialchars($pedido['ciudad']) ?>, <?= htmlspecialchars($pedido['pais']) ?></p>
                        <p class="mb-0 text-muted">CP: <?= htmlspecialchars($pedido['codigo_postal']) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header">
                        <i class="bi bi-receipt"></i> Resumen del Pedido
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></p>
                        <p class="mb-1"><strong>Estado:</strong> <span class="badge bg-info text-dark"><?= htmlspecialchars($pedido['nombre_estado']) ?></span></p>
                        <h4 class="mb-0 mt-2"><strong>Total: S/ <?= number_format($pedido['total_pedido'], 2) ?></strong></h4>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mt-5 mb-3">Productos Incluidos</h3>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Producto</th>
                                <th scope="col">SKU</th>
                                <th scope="col">Detalle (Talla/Color)</th>
                                <th scope="col">Precio (Histórico)</th>
                                <th scope="col">Cantidad</th>
                                <th scope="col">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../../assets/img/productos/<?= htmlspecialchars($item['imagen_principal']) ?>" alt="" style="width: 60px; height: 60px; object-fit: cover;" class="rounded me-3">
                                            <strong><?= htmlspecialchars($item['nombre_producto']) ?></strong>
                                        </div>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($item['sku']) ?></small></td>
                                    <td><?= htmlspecialchars($item['talla']) ?> / <?= htmlspecialchars($item['color']) ?></td>
                                    <td>S/ <?= number_format($item['precio_historico'], 2) ?></td>
                                    <td>x <?= $item['cantidad'] ?></td>
                                    <td><strong>S/ <?= number_format($item['precio_historico'] * $item['cantidad'], 2) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>