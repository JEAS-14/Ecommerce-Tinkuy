<?php
session_start();
include 'db.php'; // Estamos en la carpeta 'admin', db.php está aquí

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); // Redirigimos al login
    exit;
}
// Verificamos que el ROL sea 'admin'
if ($_SESSION['rol'] !== 'admin') {
    session_destroy();
    header('Location: ../../login.php'); //
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

$nombre_admin = $_SESSION['usuario']; // Obtenemos el nombre de la sesión

// --- LÓGICA DE CALIDAD (FUNCIONALIDAD) ---
// (Consultas para las tarjetas de estadísticas)

// 1. Contar pedidos pendientes (Estado 1 = 'Pendiente de Pago')
$stmt_pendientes = $conn->query("SELECT COUNT(*) AS total FROM pedidos WHERE id_estado_pedido = 1");
$pedidos_pendientes = $stmt_pendientes->fetch_assoc()['total'];

// 2. Contar total de usuarios registrados
$stmt_usuarios = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
$total_usuarios = $stmt_usuarios->fetch_assoc()['total'];

// 3. Contar total de productos listados
$stmt_productos = $conn->query("SELECT COUNT(*) AS total FROM productos");
$total_productos = $stmt_productos->fetch_assoc()['total'];

// 4. Calcular ingresos totales (Suma de pedidos pagados, enviados o entregados)
$stmt_ingresos = $conn->query("SELECT SUM(total_pedido) AS total FROM pedidos WHERE id_estado_pedido IN (2, 3, 4)");
$ingresos_totales = $stmt_ingresos->fetch_assoc()['total'] ?? 0; // Usamos ?? 0 por si es NULL

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Admin</a> <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="pedidos.php">Pedidos</a></li> <li class="nav-item"><a class="nav-link" href="productos_admin.php">Productos</a></li> <li class="nav-item"><a class="nav-link" href="usuarios.php">Usuarios</a></li> </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión</a></li> </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <h1 class="mb-4">Bienvenido, <?= htmlspecialchars($nombre_admin) ?> 👋</h1>
        <p class="lead text-muted mb-4">Resumen general de la tienda Tinkuy.</p>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100 border-warning">
                    <div class="card-body">
                        <i class="bi bi-clock-history" style="font-size: 3rem; color: #ffc107;"></i>
                        <h3 class="card-title mt-3"><?= $pedidos_pendientes ?></h3>
                        <p class="card-text text-muted">Pedidos Pendientes</p>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                         <a href="pedidos.php" class="btn btn-sm btn-outline-warning">Gestionar</a> </div>
                </div>
            </div>
             <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100 border-primary">
                    <div class="card-body">
                        <i class="bi bi-people-fill" style="font-size: 3rem; color: #0d6efd;"></i>
                        <h3 class="card-title mt-3"><?= $total_usuarios ?></h3>
                        <p class="card-text text-muted">Usuarios Registrados</p>
                    </div>
                     <div class="card-footer bg-transparent border-0">
                         <a href="usuarios.php" class="btn btn-sm btn-outline-primary">Gestionar</a> </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100 border-info">
                    <div class="card-body">
                        <i class="bi bi-box-seam" style="font-size: 3rem; color: #0dcaf0;"></i>
                        <h3 class="card-title mt-3"><?= $total_productos ?></h3>
                        <p class="card-text text-muted">Productos Totales</p>
                    </div>
                     <div class="card-footer bg-transparent border-0">
                         <a href="productos_admin.php" class="btn btn-sm btn-outline-info">Gestionar</a> </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100 border-success">
                    <div class="card-body">
                        <i class="bi bi-cash-coin" style="font-size: 3rem; color: #198754;"></i>
                        <h3 class="card-title mt-3">S/ <?= number_format($ingresos_totales, 2) ?></h3>
                        <p class="card-text text-muted">Ingresos Totales</p>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                         <a href="pedidos.php" class="btn btn-sm btn-outline-success">Ver Pedidos</a> </div>
                </div>
            </div>
        </div>

        <div class="card p-4 mt-5 shadow-sm">
             <h4>Acciones Rápidas:</h4>
             <div class="list-group list-group-flush mt-2">
                 <a href="pedidos.php" class="list-group-item list-group-item-action"><i class="bi bi-list-check me-2"></i>Ver y gestionar pedidos de clientes</a> <a href="productos_admin.php" class="list-group-item list-group-item-action"><i class="bi bi-box-seam me-2"></i>Administrar todos los productos de la tienda</a> <a href="usuarios.php" class="list-group-item list-group-item-action"><i class="bi bi-people-fill me-2"></i>Gestionar cuentas de usuario (clientes, vendedores, admins)</a> <a href="crear_usuario.php" class="list-group-item list-group-item-action"><i class="bi bi-person-plus-fill me-2"></i>Crear una nueva cuenta de usuario (vendedor o admin)</a> </div>
         </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>