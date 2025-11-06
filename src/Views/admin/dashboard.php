<?php
session_start();
include 'db.php'; // Estamos en la carpeta 'admin', db.php está aquí

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
// (Tu código de seguridad está perfecto)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    session_destroy();
    header('Location: ../../login.php');
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

$nombre_admin = $_SESSION['usuario'];

// --- LÓGICA DE CALIDAD (FUNCIONALIDAD) ---
// (Tus consultas están perfectas)
$stmt_pendientes = $conn->query("SELECT COUNT(*) AS total FROM pedidos WHERE id_estado_pedido = 1");
$pedidos_pendientes = $stmt_pendientes->fetch_assoc()['total'];

$stmt_usuarios = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
$total_usuarios = $stmt_usuarios->fetch_assoc()['total'];

$stmt_productos = $conn->query("SELECT COUNT(*) AS total FROM productos");
$total_productos = $stmt_productos->fetch_assoc()['total'];

$stmt_ingresos = $conn->query("SELECT SUM(total_pedido) AS total FROM pedidos WHERE id_estado_pedido IN (2, 3, 4)");
$ingresos_totales = $stmt_ingresos->fetch_assoc()['total'] ?? 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            /* Un gris muy claro */
        }

        .sidebar {
            width: 260px;
            /* Ancho fijo para el sidebar */
            height: 100vh;
            /* Ocupa toda la altura */
            position: fixed;
            /* Fijo en la pantalla */
            top: 0;
            left: 0;
            background-color: #212529;
            /* Un fondo oscuro estándar */
            padding-top: 1rem;
        }

        /* Estilos para los links de navegación */
        .sidebar .nav-link {
            color: #adb5bd;
            /* Color de texto grisáceo */
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .sidebar .nav-link i {
            margin-right: 0.8rem;
            /* Espacio entre icono y texto */
        }

        /* Estilo para el link activo */
        .sidebar .nav-link.active {
            background-color: #dc3545;
            /* Tu color rojo de marca */
            color: #fff;
        }

        .sidebar .nav-link:hover {
            background-color: #343a40;
            /* Un hover sutil */
            color: #fff;
        }

        /* Contenido principal */
        .main-content {
            margin-left: 260px;
            /* Mismo ancho que el sidebar */
            padding: 2.5rem;
            width: calc(100% - 260px);
            /* Ocupa el resto del ancho */
        }

        /* Dropdown de usuario en el sidebar */
        .user-dropdown .dropdown-toggle {
            color: #fff;
        }

        .user-dropdown .dropdown-menu {
            border-radius: 0.5rem;
        }

        /* Estilos para los iconos de las tarjetas */
        .stat-card-icon {
            font-size: 3rem;
            /* Tamaño de 3rem que tenías */
        }
    </style>
</head>

<body>

    <div class="sidebar d-flex flex-column p-3 text-white">
        <a href="dashboard.php"
            class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="bi bi-shop-window fs-4 me-2"></i>
            <span class="fs-4">Admin Tinkuy</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active" aria-current="page">
                    <i class="bi bi-grid-fill"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="pedidos.php" class="nav-link">
                    <i class="bi bi-list-check"></i>
                    Pedidos
                </a>
            </li>
            <li>
                <a href="productos_admin.php" class="nav-link">
                    <i class="bi bi-box-seam-fill"></i>
                    Productos
                </a>
            </li>
            <li>
                <a href="usuarios.php" class="nav-link">
                    <i class="bi bi-people-fill"></i>
                    Usuarios
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown user-dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle fs-4 me-2"></i>
                <strong><?= htmlspecialchars($nombre_admin) ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="../../logout.php">Cerrar Sesión</a></li>
            </ul>
        </div>
    </div>

    <main class="main-content">
        <h1 class="mb-4">Bienvenido, <?= htmlspecialchars($nombre_admin) ?> 👋</h1>
        <p class="lead text-muted mb-4">Resumen general de la tienda Tinkuy.</p>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100 border-warning border-2">
                    <div class="card-body">
                        <i class="bi bi-clock-history stat-card-icon text-warning"></i>
                        <h3 class="card-title mt-3"><?= $pedidos_pendientes ?></h3>
                        <p class="card-text text-muted">Pedidos Pendientes</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="pedidos.php" class="btn btn-sm btn-outline-warning">Gestionar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100 border-primary border-2">
                    <div class="card-body">
                        <i class="bi bi-people-fill stat-card-icon text-primary"></i>
                        <h3 class="card-title mt-3"><?= $total_usuarios ?></h3>
                        <p class="card-text text-muted">Usuarios Registrados</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="usuarios.php" class="btn btn-sm btn-outline-primary">Gestionar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100 border-info border-2">
                    <div class="card-body">
                        <i class="bi bi-box-seam stat-card-icon text-info"></i>
                        <h3 class="card-title mt-3"><?= $total_productos ?></h3>
                        <p class="card-text text-muted">Productos Totales</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="productos_admin.php" class="btn btn-sm btn-outline-info">Gestionar</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm text-center h-100 border-success border-2">
                    <div class="card-body">
                        <i class="bi bi-cash-coin stat-card-icon text-success"></i>
                        <h3 class="card-title mt-3">S/ <?= number_format($ingresos_totales, 2) ?></h3>
                        <p class="card-text text-muted">Ingresos Totales</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-3">
                        <a href="pedidos.php" class="btn btn-sm btn-outline-success">Ver Pedidos</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4 mt-5 shadow-sm">
            <h4>Acciones Rápidas:</h4>
            <div class="list-group list-group-flush mt-2">
                <a href="pedidos.php" class="list-group-item list-group-item-action"><i
                        class="bi bi-list-check me-2"></i>Ver y gestionar pedidos de clientes</a>
                <a href="productos_admin.php" class="list-group-item list-group-item-action"><i
                        class="bi bi-box-seam me-2"></i>Administrar todos los productos de la tienda</a>
                <a href="usuarios.php" class="list-group-item list-group-item-action"><i
                        class="bi bi-people-fill me-2"></i>Gestionar cuentas de usuario (clientes, vendedores,
                    admins)</a>
                <a href="crear_usuario.php" class="list-group-item list-group-item-action"><i
                        class="bi bi-person-plus-fill me-2"></i>Crear una nueva cuenta de usuario (vendedor o admin)</a>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>