<?php
session_start();
include 'db.php'; 

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

$nombre_admin = $_SESSION['usuario'];

// --- LÓGICA DE CALIDAD (FUNCIONALIDAD) ---

// Filtros (funcionalidad futura, pero los preparamos)
$filtro_estado = $_GET['estado'] ?? ''; // ej: ?estado=2

$sql = "SELECT 
            p.id_pedido,
            p.fecha_pedido,
            p.total_pedido,
            e.nombre_estado,
            e.id_estado,
            CONCAT(pr.nombres, ' ', pr.apellidos) AS nombre_cliente
        FROM 
            pedidos AS p
        JOIN 
            estados_pedido AS e ON p.id_estado_pedido = e.id_estado
        JOIN 
            usuarios AS u ON p.id_usuario = u.id_usuario
        JOIN 
            perfiles AS pr ON u.id_usuario = pr.id_usuario
        ";

// Aplicar filtro si existe
if (!empty($filtro_estado) && is_numeric($filtro_estado)) {
    $sql .= " WHERE p.id_estado_pedido = " . intval($filtro_estado);
}

$sql .= " ORDER BY p.fecha_pedido DESC";

$result_pedidos = $conn->query($sql);
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Admin Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            width: 260px; height: 100vh; position: fixed; top: 0; left: 0;
            background-color: #212529; padding-top: 1rem;
        }
        .sidebar .nav-link { color: #adb5bd; font-size: 1rem; margin-bottom: 0.5rem; }
        .sidebar .nav-link i { margin-right: 0.8rem; }
        .sidebar .nav-link.active { background-color: #dc3545; color: #fff; }
        .sidebar .nav-link:hover { background-color: #343a40; color: #fff; }
        .main-content { margin-left: 260px; padding: 2.5rem; width: calc(100% - 260px); }
        .user-dropdown .dropdown-toggle { color: #fff; }
        .user-dropdown .dropdown-menu { border-radius: 0.5rem; }
        
        /* Estilos para badges de estado (Usabilidad) */
        .badge-estado { font-size: 0.9em; padding: 0.5em 0.75em; }
    </style>
</head>
<body>

    <div class="sidebar d-flex flex-column p-3 text-white">
        <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="bi bi-shop-window fs-4 me-2"></i>
            <span class="fs-4">Admin Tinkuy</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="bi bi-grid-fill"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="pedidos.php" class="nav-link active" aria-current="page">
                    <i class="bi bi-list-check"></i> Pedidos
                </a>
            </li>
            <li>
                <a href="productos_admin.php" class="nav-link">
                    <i class="bi bi-box-seam-fill"></i> Productos
                </a>
            </li>
            <li>
                <a href="usuarios.php" class="nav-link">
                    <i class="bi bi-people-fill"></i> Usuarios
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown user-dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle fs-4 me-2"></i>
                <strong><?= htmlspecialchars($nombre_admin) ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="../../logout.php">Cerrar Sesión</a></li>
            </ul>
        </div>
    </div>

    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestión de Pedidos</h1>
            <div class="btn-group">
                <a href="pedidos.php" class="btn btn-outline-secondary <?php echo empty($filtro_estado) ? 'active' : ''; ?>">Todos</a>
                <a href="pedidos.php?estado=2" class="btn btn-outline-primary <?php echo $filtro_estado == '2' ? 'active' : ''; ?>">Pagados</a>
                <a href="pedidos.php?estado=3" class="btn btn-outline-info <?php echo $filtro_estado == '3' ? 'active' : ''; ?>">Enviados</a>
                <a href="pedidos.php?estado=5" class="btn btn-outline-danger <?php echo $filtro_estado == '5' ? 'active' : ''; ?>">Cancelados</a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>N° Pedido</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_pedidos->num_rows > 0) : ?>
                                <?php while ($pedido = $result_pedidos->fetch_assoc()) : ?>
                                    <tr>
                                        <td class_alias="font-monospace">#<?= $pedido['id_pedido'] ?></td>
                                        <td><?= htmlspecialchars($pedido['nombre_cliente']) ?></td>
                                        <td><?= date("d/m/Y H:i", strtotime($pedido['fecha_pedido'])) ?></td>
                                        <td>
                                            <?php
                                            // Asignamos colores según el estado (Mejora la Usabilidad)
                                            $badge_color = 'secondary'; // Default
                                            if ($pedido['id_estado'] == 1) $badge_color = 'warning text-dark'; // Pendiente
                                            if ($pedido['id_estado'] == 2) $badge_color = 'primary'; // Pagado
                                            if ($pedido['id_estado'] == 3) $badge_color = 'info text-dark'; // Enviado
                                            if ($pedido['id_estado'] == 4) $badge_color = 'success'; // Entregado
                                            if ($pedido['id_estado'] == 5) $badge_color = 'danger'; // Cancelado
                                            ?>
                                            <span class="badge rounded-pill bg-<?= $badge_color ?> badge-estado">
                                                <?= htmlspecialchars($pedido['nombre_estado']) ?>
                                            </span>
                                        </td>
                                        <td>S/ <?= number_format($pedido['total_pedido'], 2) ?></td>
                                        <td>
                                            <a href="ver_pedido.php?id=<?= $pedido['id_pedido'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye-fill me-1"></i> Ver Detalles
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No se encontraron pedidos.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>