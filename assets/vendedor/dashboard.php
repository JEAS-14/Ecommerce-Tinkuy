<?php
session_start();
include '../admin/db.php'; // Subimos un nivel

// --- Seguridad y Lógica PHP (SIN CAMBIOS) ---
// (Es la misma lógica que ya teníamos para obtener KPIs, gráfico, top productos)
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}
if ($_SESSION['rol'] !== 'vendedor') {
    session_destroy();
    header('Location: ../../login.php');
    exit;
}
$id_vendedor = $_SESSION['usuario_id'];
$nombre_vendedor = $_SESSION['usuario'];

// --- Consultas SQL (SIN CAMBIOS) ---
// 1. Envíos pendientes
$stmt_pendientes = $conn->prepare("SELECT COUNT(dp.id_detalle) AS total FROM detalle_pedido dp JOIN variantes_producto vp ON dp.id_variante = vp.id_variante JOIN productos p ON vp.id_producto = p.id_producto WHERE p.id_vendedor = ? AND dp.id_estado_detalle = 2");
$stmt_pendientes->bind_param("i", $id_vendedor);
$stmt_pendientes->execute();
$envios_pendientes = $stmt_pendientes->get_result()->fetch_assoc()['total'];
$stmt_pendientes->close();

// 2. Total productos
$stmt_productos = $conn->prepare("SELECT COUNT(*) AS total FROM productos WHERE id_vendedor = ? AND estado = 'activo'"); // Solo activos
$stmt_productos->bind_param("i", $id_vendedor);
$stmt_productos->execute();
$total_productos = $stmt_productos->get_result()->fetch_assoc()['total'];
$stmt_productos->close();

// 3. Stock total
$stmt_stock = $conn->prepare("SELECT SUM(vp.stock) AS total FROM variantes_producto vp JOIN productos p ON vp.id_producto = p.id_producto WHERE p.id_vendedor = ? AND p.estado = 'activo' AND vp.estado = 'activo'"); // Solo activos
$stmt_stock->bind_param("i", $id_vendedor);
$stmt_stock->execute();
$total_stock = $stmt_stock->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_stock->close();

// 4. Ventas totales (Artículos)
$stmt_ventas = $conn->prepare("SELECT COUNT(dp.id_detalle) AS total FROM detalle_pedido dp JOIN variantes_producto vp ON dp.id_variante = vp.id_variante JOIN productos p ON vp.id_producto = p.id_producto WHERE p.id_vendedor = ? AND dp.id_estado_detalle IN (3, 4)"); // Enviados o Entregados
$stmt_ventas->bind_param("i", $id_vendedor);
$stmt_ventas->execute();
$total_ventas = $stmt_ventas->get_result()->fetch_assoc()['total'];
$stmt_ventas->close();

// --- Lógica Gráfico (SIN CAMBIOS) ---
$query_grafico = $conn->prepare("SELECT DATE(pe.fecha_pedido) AS dia, SUM(dp.cantidad * dp.precio_historico) AS total_dia FROM detalle_pedido dp JOIN pedidos pe ON dp.id_pedido = pe.id_pedido JOIN variantes_producto vp ON dp.id_variante = vp.id_variante JOIN productos p ON vp.id_producto = p.id_producto WHERE p.id_vendedor = ? AND dp.id_estado_detalle IN (2, 3, 4) AND pe.fecha_pedido >= CURDATE() - INTERVAL 6 DAY GROUP BY dia ORDER BY dia ASC");
$query_grafico->bind_param("i", $id_vendedor);
$query_grafico->execute();
$resultado_grafico = $query_grafico->get_result();
$ventas_raw = [];
while ($fila = $resultado_grafico->fetch_assoc()) { $ventas_raw[$fila['dia']] = $fila['total_dia']; }
$labels_grafico = []; $data_grafico = [];
for ($i = 6; $i >= 0; $i--) {
    $dia_actual = date('Y-m-d', strtotime("-$i days"));
    $labels_grafico[] = date('d/m', strtotime($dia_actual));
    $data_grafico[] = $ventas_raw[$dia_actual] ?? 0;
}
$json_labels = json_encode($labels_grafico);
$json_data = json_encode($data_grafico);
$query_grafico->close();

// --- Lógica Top 5 Más Vendidos (SIN CAMBIOS) ---
$query_top_5 = $conn->prepare("SELECT p.nombre_producto, SUM(dp.cantidad) AS total_vendido FROM detalle_pedido dp JOIN variantes_producto vp ON dp.id_variante = vp.id_variante JOIN productos p ON vp.id_producto = p.id_producto WHERE p.id_vendedor = ? AND dp.id_estado_detalle IN (3, 4) GROUP BY p.id_producto, p.nombre_producto ORDER BY total_vendido DESC LIMIT 5");
$query_top_5->bind_param("i", $id_vendedor);
$query_top_5->execute();
$top_5_productos = $query_top_5->get_result();
// No cerramos aquí, lo usamos en el HTML

// --- Lógica Productos Sin Ventas (SIN CAMBIOS) ---
$query_sin_ventas = $conn->prepare("SELECT p.nombre_producto FROM productos p LEFT JOIN (SELECT DISTINCT vp.id_producto FROM detalle_pedido dp JOIN variantes_producto vp ON dp.id_variante = vp.id_variante WHERE dp.id_estado_detalle IN (2, 3, 4)) AS vendidos ON p.id_producto = vendidos.id_producto WHERE p.id_vendedor = ? AND p.estado = 'activo' AND vendidos.id_producto IS NULL LIMIT 5"); // Solo activos
$query_sin_ventas->bind_param("i", $id_vendedor);
$query_sin_ventas->execute();
$productos_sin_ventas = $query_sin_ventas->get_result();
// No cerramos aquí, lo usamos en el HTML

$conn->close(); // Cerramos la conexión al final
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Vendedor | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script> 

    <style>
        body {
            background-color: #f0f2f5; /* Un gris más suave */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Fuente más moderna */
        }
        .navbar-brand {
            font-weight: bold;
            letter-spacing: 1px;
        }
        .card-kpi {
            border: none; /* Quitamos borde */
            border-radius: 0.8rem; /* Más redondeado */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); /* Sombra suave para efecto flotante */
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; /* Transición suave */
            background-color: #fff; /* Fondo blanco */
        }
        .card-kpi:hover {
            transform: translateY(-5px); /* Elevar al pasar el mouse */
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12); /* Sombra más pronunciada */
        }
        .card-kpi .card-body i {
            /* Colores más vibrantes para los iconos */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 3.5rem; /* Iconos un poco más grandes */
        }
         /* Icono específico para Envíos Pendientes */
        .card-kpi.envios-pendientes .card-body i {
             background: linear-gradient(135deg, #f7b733 0%, #fc4a1a 100%); /* Naranja a rojo */
             -webkit-background-clip: text;
             -webkit-text-fill-color: transparent;
        }
        .card-kpi h3 {
            font-weight: 700;
            margin-top: 1rem;
            color: #333;
        }
        .card-kpi .card-text {
            color: #6c757d;
            font-size: 0.95rem;
        }
        .card-kpi .card-footer {
             background-color: transparent !important; /* Footer transparente */
             border-top: none; /* Sin línea arriba del footer */
             padding-top: 0;
        }
        .chart-container {
            position: relative;
            /* height: 300px; /* Altura fija más pequeña para el gráfico */
            max-height: 350px; /* Máxima altura */
            width: 100%;
            margin: auto;
        }
        .card-lista {
             border: none;
             border-radius: 0.8rem;
             box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
             background-color: #fff;
        }
        .list-group-item {
            border-left: none;
            border-right: none;
            padding: 0.8rem 1rem;
        }
        .list-group-item:first-child { border-top: none; border-top-left-radius: 0.8rem; border-top-right-radius: 0.8rem;}
        .list-group-item:last-child { border-bottom: none; border-bottom-left-radius: 0.8rem; border-bottom-right-radius: 0.8rem;}

        /* Navbar más moderna */
        .navbar {
             box-shadow: 0 2px 4px rgba(0,0,0,.08);
             padding-top: 0.8rem;
             padding-bottom: 0.8rem;
        }
        .navbar .nav-link {
            font-weight: 500;
            color: rgba(255,255,255,0.8);
            transition: color 0.2s;
        }
        .navbar .nav-link:hover, .navbar .nav-link.active {
            color: #fff;
        }
    </style>
    </head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                 <i class="bi bi-shop me-2"></i>Tinkuy Vendedor
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vendedorNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="vendedorNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="productos.php"> <i class="bi bi-box-seam-fill me-1"></i>Mis Productos</a></li>
                    <li class="nav-item"><a class="nav-link position-relative" href="envios.php">
                         <i class="bi bi-truck me-1"></i>Envíos Pendientes
                         <?php if ($envios_pendientes > 0): ?>
                             <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                 <?= $envios_pendientes ?>
                                 <span class="visually-hidden">envíos pendientes</span>
                             </span>
                         <?php endif; ?>
                    </a></li>
                    <li class="nav-item"><a class="nav-link" href="ventas.php"> <i class="bi bi-bar-chart-line-fill me-1"></i>Mis Ventas</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                         <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                             <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($nombre_vendedor) ?>
                         </a>
                         <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                             <li><a class="dropdown-item" href="../../mi_perfil.php"> <i class="bi bi-gear me-2"></i>Mi Perfil</a></li>
                             <li><hr class="dropdown-divider"></li>
                             <li><a class="dropdown-item text-danger" href="../../logout.php"> <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                         </ul>
                     </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <h1 class="mb-4 display-6">Bienvenido, <?= htmlspecialchars($nombre_vendedor) ?> 👋</h1>

        <?php if ($envios_pendientes > 0): ?>
            <div class="alert alert-warning d-flex align-items-center shadow-sm border-0 mb-4" role="alert" style="border-radius: 0.8rem;">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                <div>
                    <strong>¡Acción Requerida!</strong> Tienes <strong><?= $envios_pendientes ?></strong> envío(s) pendiente(s).
                    <a href="envios.php" class="alert-link fw-bold">Gestionar ahora <i class="bi bi-arrow-right-short"></i></a>.
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-success d-flex align-items-center shadow-sm border-0 mb-4" role="alert" style="border-radius: 0.8rem;">
                 <i class="bi bi-check-circle-fill fs-4 me-3 text-success"></i>
                 <div>
                     <strong>¡Todo al día!</strong> No tienes envíos pendientes. ¡Sigue así!
                 </div>
             </div>
        <?php endif; ?>

        <div class="row g-4">
             <div class="col-md-6 col-lg-3">
                 <div class="card card-kpi envios-pendientes h-100">
                     <div class="card-body text-center">
                         <i class="bi bi-clock-history"></i>
                         <h3 class="card-title"><?= $envios_pendientes ?></h3>
                         <p class="card-text text-muted">Envíos Pendientes</p>
                     </div>
                     <div class="card-footer">
                          <a href="envios.php" class="btn btn-sm <?= ($envios_pendientes > 0) ? 'btn-warning' : 'btn-outline-secondary disabled'; ?> w-100">
                             <?= ($envios_pendientes > 0) ? 'Gestionar' : 'Ninguno'; ?>
                          </a>
                     </div>
                 </div>
             </div>
             <div class="col-md-6 col-lg-3">
                 <div class="card card-kpi h-100">
                     <div class="card-body text-center">
                         <i class="bi bi-check2-circle"></i>
                         <h3 class="card-title"><?= $total_ventas ?></h3>
                         <p class="card-text text-muted">Artículos Enviados/Entregados</p>
                     </div>
                      <div class="card-footer">
                           <a href="ventas.php" class="btn btn-sm btn-outline-primary w-100">Ver Historial</a>
                      </div>
                 </div>
             </div>
             <div class="col-md-6 col-lg-3">
                 <div class="card card-kpi h-100">
                     <div class="card-body text-center">
                         <i class="bi bi-box-seam"></i>
                         <h3 class="card-title"><?= $total_productos ?></h3>
                         <p class="card-text text-muted">Productos Activos</p>
                     </div>
                      <div class="card-footer">
                           <a href="productos.php" class="btn btn-sm btn-outline-success w-100">Gestionar Productos</a>
                      </div>
                 </div>
             </div>
             <div class="col-md-6 col-lg-3">
                 <div class="card card-kpi h-100">
                     <div class="card-body text-center">
                         <i class="bi bi-archive"></i>
                         <h3 class="card-title"><?= $total_stock ?></h3>
                         <p class="card-text text-muted">Unidades en Stock</p>
                     </div>
                 </div>
             </div>
        </div>

        <div class="card card-lista mt-4">
            <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                <h5 class="mb-0 text-dark"><i class="bi bi-graph-up me-2 text-success"></i>Ventas (S/) Últimos 7 Días</h5>
            </div>
            <div class="card-body pt-2">
                <div class="chart-container">
                    <canvas id="graficoVentas"></canvas>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="card card-lista h-100">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h5 class="mb-0 text-dark">
                           <i class="bi bi-star-fill me-2 text-warning"></i>Top 5 Productos Vendidos
                        </h5>
                    </div>
                    <div class="card-body pt-2">
                        <ul class="list-group list-group-flush">
                            <?php if ($top_5_productos->num_rows > 0): ?>
                                <?php $rank = 1; while($prod = $top_5_productos->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-bold me-2"><?= $rank++ ?>.</span> <?= htmlspecialchars($prod['nombre_producto']) ?>
                                        </div>
                                        <span class="badge bg-success rounded-pill"><?= $prod['total_vendido'] ?></span>
                                    </li>
                                <?php endwhile; $top_5_productos->close(); ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted text-center py-3">Aún no hay suficientes datos de ventas.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-lista h-100">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                         <h5 class="mb-0 text-dark">
                            <i class="bi bi-search me-2 text-danger"></i>Productos Sin Ventas Recientes
                         </h5>
                    </div>
                    <div class="card-body pt-2">
                        <ul class="list-group list-group-flush">
                             <?php if ($productos_sin_ventas->num_rows > 0): ?>
                                <?php while($prod = $productos_sin_ventas->fetch_assoc()): ?>
                                    <li class="list-group-item">
                                        <?= htmlspecialchars($prod['nombre_producto']) ?>
                                    </li>
                                <?php endwhile; $productos_sin_ventas->close(); ?>
                             <?php else: ?>
                                <li class="list-group-item text-muted text-center py-3">¡Todos tus productos activos se han vendido!</li>
                             <?php endif; ?>
                        </ul>
                    </div>
                     <div class="card-footer text-center">
                         <a href="productos.php" class="btn btn-sm btn-outline-secondary">Revisar mi inventario completo</a>
                     </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const labels = <?php echo $json_labels; ?>;
        const data = <?php echo $json_data; ?>;
        const ctx = document.getElementById('graficoVentas').getContext('2d');
        const graficoVentas = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas (S/)',
                    data: data,
                    backgroundColor: 'rgba(25, 135, 84, 0.1)', // Más suave
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3 // Curva un poco más pronunciada
                }]
            },
            options: {
                 maintainAspectRatio: false, // <-- IMPORTANTE para controlar altura con CSS
                 scales: {
                     y: {
                         beginAtZero: true,
                         ticks: {
                             callback: function(value) { return 'S/ ' + value.toFixed(2); }
                         }
                     }
                 },
                 plugins: {
                     legend: { display: false }, // Ocultar leyenda para más espacio
                     tooltip: {
                         callbacks: {
                             label: function(context) {
                                 let label = context.dataset.label || '';
                                 if (label) { label += ': '; }
                                 if (context.parsed.y !== null) {
                                     label += 'S/ ' + context.parsed.y.toFixed(2);
                                 }
                                 return label;
                             }
                         }
                     }
                 }
            }
        });
    </script>
</body>
</html>