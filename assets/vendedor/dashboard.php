<?php
session_start();
include '../admin/db.php'; // Subimos un nivel para encontrar db.php

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); //
    exit;
}
// 2. Verificamos que el ROL sea 'vendedor'
// (Un admin puede entrar, pero este es el dashboard de VENDEDOR)
if ($_SESSION['rol'] !== 'vendedor' && $_SESSION['rol'] !== 'admin') {
    header('Location: ../../login.php'); //
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

$id_vendedor = $_SESSION['usuario_id'];
$nombre_vendedor = $_SESSION['usuario']; // Obtenemos el nombre de la sesión

// --- LÓGICA DE CALIDAD (FUNCIONALIDAD) ---
// (Consultas para las tarjetas de estadísticas)

// 1. Contar total de productos publicados por este vendedor
$stmt_productos = $conn->prepare("SELECT COUNT(*) AS total FROM productos WHERE id_vendedor = ?");
$stmt_productos->bind_param("i", $id_vendedor);
$stmt_productos->execute();
$total_productos = $stmt_productos->get_result()->fetch_assoc()['total'];

// 2. Contar total de variantes (SKUs)
$stmt_variantes = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM variantes_producto vp
    JOIN productos p ON vp.id_producto = p.id_producto
    WHERE p.id_vendedor = ?
");
$stmt_variantes->bind_param("i", $id_vendedor);
$stmt_variantes->execute();
$total_variantes = $stmt_variantes->get_result()->fetch_assoc()['total'];

// 3. Sumar el stock total disponible
$stmt_stock = $conn->prepare("
    SELECT SUM(vp.stock) AS total 
    FROM variantes_producto vp
    JOIN productos p ON vp.id_producto = p.id_producto
    WHERE p.id_vendedor = ?
");
$stmt_stock->bind_param("i", $id_vendedor);
$stmt_stock->execute();
$total_stock = $stmt_stock->get_result()->fetch_assoc()['total'] ?? 0; // Usamos ?? 0 por si es NULL

// 4. (Opcional) Contar ventas (más compleja)
// (Contamos cuántos 'detalle_pedido' están en estado 'Pagado' o 'Enviado' o 'Entregado')
$stmt_ventas = $conn->prepare("
    SELECT COUNT(dp.id_detalle) AS total
    FROM detalle_pedido dp
    JOIN variantes_producto vp ON dp.id_variante = vp.id_variante
    JOIN productos p ON vp.id_producto = p.id_producto
    JOIN pedidos pe ON dp.id_pedido = pe.id_pedido
    WHERE p.id_vendedor = ? AND pe.id_estado_pedido IN (2, 3, 4) -- (Pagado, Enviado, Entregado)
");
$stmt_ventas->bind_param("i", $id_vendedor);
$stmt_ventas->execute();
$total_ventas = $stmt_ventas->get_result()->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Vendedor</a> <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="productos.php">Mis Productos</a></li> <li class="nav-item"><a class="nav-link" href="agregar_producto.php">Agregar Producto</a></li> </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión</a></li> </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h1 class="mb-4">Bienvenido, <?= htmlspecialchars($nombre_vendedor) ?></h1>
        <p class="lead text-muted">Aquí tienes un resumen de tu tienda en Tinkuy.</p>

        <div class="row g-4 mt-3">
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-box-seam" style="font-size: 3rem; color: #0d6efd;"></i>
                        <h3 classs="card-title mt-3"><?= $total_productos ?></h3>
                        <p class="card-text text-muted">Productos Publicados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-tags" style="font-size: 3rem; color: #198754;"></i>
                        <h3 classs="card-title mt-3"><?= $total_variantes ?></h3>
                        <p class="card-text text-muted">Variantes (SKUs)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-archive" style="font-size: 3rem; color: #ffc107;"></i>
                        <h3 classs="card-title mt-3"><?= $total_stock ?></h3>
                        <p class="card-text text-muted">Unidades en Stock</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100">
                    <div class="card-body">
                        <i class="bi bi-cart-check" style="font-size: 3rem; color: #dc3545;"></i>
                        <h3 classs="card-title mt-3"><?= $total_ventas ?></h3>
                        <p class="card-text text-muted">Artículos Vendidos</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-5">
            <div class="col-md-6">
                <div class="card card-body text-center">
                    <h5>Gestionar Mis Productos</h5>
                    <p class="text-muted small">Edita, elimina o ve el stock de tus productos.</p>
                    <a href="productos.php" class="btn btn-outline-primary"> <i class="bi bi-list-task"></i> Ir a Mis Productos
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-body text-center">
                    <h5>Agregar un Producto Nuevo</h5>
                    <p class="text-muted small">Sube un nuevo artículo al catálogo.</p>
                    <a href="agregar_producto.php" class="btn btn-primary"> <i class="bi bi-plus-circle"></i> Agregar Nuevo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>