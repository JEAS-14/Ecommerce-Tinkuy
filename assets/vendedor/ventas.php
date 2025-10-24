<?php
session_start();
include '../admin/db.php'; 

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}
if ($_SESSION['rol'] !== 'vendedor') {
    session_destroy();
    header('Location: ../../login.php');
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

$id_vendedor = $_SESSION['usuario_id'];
$nombre_vendedor = $_SESSION['usuario'];

// --- LÓGICA GET (Funcionalidad de Reporte) ---

// 1. Obtenemos TODOS los ítems COMPLETADOS (Enviados o Entregados)
// Esta es la consulta principal para tu "historial"
$query_items = "
    SELECT 
        dp.id_detalle,
        pe.id_pedido,
        pe.fecha_pedido,
        p.nombre_producto,
        vp.talla,
        vp.color,
        dp.cantidad,
        dp.precio_historico,
        (dp.cantidad * dp.precio_historico) AS subtotal,
        dp.id_estado_detalle, -- 3 = Enviado, 4 = Entregado
        dp.numero_seguimiento,
        emp.nombre_empresa
    FROM 
        detalle_pedido AS dp
    JOIN 
        variantes_producto AS vp ON dp.id_variante = vp.id_variante
    JOIN 
        productos AS p ON vp.id_producto = p.id_producto
    JOIN 
        pedidos AS pe ON dp.id_pedido = pe.id_pedido
    LEFT JOIN
        empresas_envio AS emp ON dp.id_empresa_envio = emp.id_empresa_envio
    WHERE 
        p.id_vendedor = ?            -- Solo tus ventas
        AND dp.id_estado_detalle IN (3, 4) -- Solo ítems 'Enviados' o 'Entregados'
    ORDER BY
        pe.fecha_pedido DESC
";
$stmt_items = $conn->prepare($query_items);
$stmt_items->bind_param("i", $id_vendedor);
$stmt_items->execute();
$resultado_items = $stmt_items->get_result();
$items_vendidos = $resultado_items->fetch_all(MYSQLI_ASSOC);

// 2. Calculamos el total de ingresos de estos ítems
$total_ingresos = 0;
foreach ($items_vendidos as $item) {
    $total_ingresos += $item['subtotal'];
}

$conn->close();

// Función simple para mostrar el nombre del estado
function obtenerNombreEstado($id) {
    if ($id == 3) return '<span class="badge bg-info">Enviado</span>';
    if ($id == 4) return '<span class="badge bg-success">Entregado</span>';
    return '<span class="badge bg-secondary">Desconocido</span>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial de Ventas - Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-success"> 
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Vendedor</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vendedorNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="vendedorNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="productos.php">Mis Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="envios.php">Envíos Pendientes</a></li>
                    <li class="nav-item"><a class="nav-link active" href="ventas.php">Mis Ventas</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión (<?= htmlspecialchars($nombre_vendedor) ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Mi Historial de Ventas</h2>
                <p class="text-muted">Aquí están todos los artículos que has enviado o han sido entregados.</p>
            </div>
            <button class="btn btn-primary" disabled>
                <i class="bi bi-download me-2"></i> Exportar CSV (Próximamente)
            </button>
        </div>

        <div class="card bg-success text-white mb-4 shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title">Ingresos Totales (Enviados/Entregados)</h5>
                <h1 class="display-4">S/ <?= number_format($total_ingresos, 2) ?></h1>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Pedido #</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Producto Vendido</th>
                                <th scope="col">Cant.</th>
                                <th scope="col">Total Ítem</th>
                                <th scope="col">Seguimiento</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items_vendidos)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted p-4">
                                        <i class="bi bi-box-seam" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">Sin ventas completadas</h5>
                                        Aún no has enviado ningún producto.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items_vendidos as $item): ?>
                                    <tr>
                                        <td><strong>#<?= $item['id_pedido'] ?></strong></td>
                                        <td><?= date('d/m/Y', strtotime($item['fecha_pedido'])) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($item['nombre_producto']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                (<?= htmlspecialchars($item['talla']) ?> / <?= htmlspecialchars($item['color']) ?>)
                                            </Ssmall>
                                        </td>
                                        <td><?= $item['cantidad'] ?></td>
                                        <td><strong>S/ <?= number_format($item['subtotal'], 2) ?></strong></td>
                                        <td>
                                            <small>
                                                <?= htmlspecialchars($item['nombre_empresa'] ?? 'N/A') ?><br>
                                                <?= htmlspecialchars($item['numero_seguimiento'] ?? 'N/A') ?>
                                            </small>
                                        </td>
                                        <td><?= obtenerNombreEstado($item['id_estado_detalle']) ?></td>
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