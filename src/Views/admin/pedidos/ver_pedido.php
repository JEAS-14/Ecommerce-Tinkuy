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
$id_pedido = $_GET['id'] ?? 0;
$mensaje_alerta = '';
$tipo_alerta = '';

// Verificamos si es un ID de pedido válido
if (!is_numeric($id_pedido) || $id_pedido <= 0) {
    header('Location: pedidos.php');
    exit;
}

// --- LÓGICA DE ACCIÓN (POST) - (Fiabilidad ISO 25010) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    
    if ($_POST['accion'] == 'cancelar_pedido' && $_POST['id_pedido'] == $id_pedido) {
        
        // Antes de cancelar, RE-VERIFICAMOS el estado actual (Tolerancia a fallos)
        $stmt_check_pedido = $conn->prepare("SELECT id_estado_pedido FROM pedidos WHERE id_pedido = ?");
        $stmt_check_pedido->bind_param("i", $id_pedido);
        $stmt_check_pedido->execute();
        $estado_pedido = $stmt_check_pedido->get_result()->fetch_assoc()['id_estado_pedido'];
        
        // 2. Verificar estado de los ítems
        $stmt_check_items = $conn->prepare("SELECT id_estado_detalle, id_variante, cantidad FROM detalle_pedido WHERE id_pedido = ?");
        $stmt_check_items->bind_param("i", $id_pedido);
        $stmt_check_items->execute();
        $items_a_reponer = $stmt_check_items->get_result();

        $puede_cancelar = ($estado_pedido == 2); // Solo si está 'Pagado'
        $items_para_stock = [];

        foreach ($items_a_reponer as $item) {
            if ($item['id_estado_detalle'] == 3 || $item['id_estado_detalle'] == 4) { // 3=Enviado, 4=Entregado
                $puede_cancelar = false; // ¡Demasiado tarde!
                break;
            }
            $items_para_stock[] = $item; // Guardamos para reponer stock
        }

        if ($puede_cancelar) {
            // ¡Procedemos! Usamos una transacción para Fiabilidad
            $conn->begin_transaction();
            try {
                // 1. Cambiar estado del pedido
                $stmt_cancel = $conn->prepare("UPDATE pedidos SET id_estado_pedido = 5 WHERE id_pedido = ?");
                $stmt_cancel->bind_param("i", $id_pedido);
                $stmt_cancel->execute();

                // 2. Reponer Stock (Crítico para la Fiabilidad)
                $stmt_reponer = $conn->prepare("UPDATE variantes_producto SET stock = stock + ? WHERE id_variante = ?");
                foreach ($items_para_stock as $item) {
                    $stmt_reponer->bind_param("ii", $item['cantidad'], $item['id_variante']);
                    $stmt_reponer->execute();
                }

                $conn->commit();
                $mensaje_alerta = "¡Pedido #" . $id_pedido . " cancelado con éxito! El stock ha sido repuesto.";
                $tipo_alerta = 'success';

            } catch (Exception $e) {
                $conn->rollback();
                $mensaje_alerta = "Error al cancelar el pedido: " . $e->getMessage();
                $tipo_alerta = 'danger';
            }
        } else {
            $mensaje_alerta = "No se puede cancelar el pedido. Uno o más productos ya fueron enviados por el vendedor.";
            $tipo_alerta = 'danger';
        }
    }
}
// --- FIN LÓGICA DE ACCIÓN ---


// --- LÓGICA DE VISUALIZACIÓN (GET) ---

// 1. Consulta Maestra: Info del Pedido, Cliente y Dirección
$sql_pedido = "SELECT 
                    p.id_pedido, p.fecha_pedido, p.total_pedido,
                    e.nombre_estado, e.id_estado,
                    CONCAT(pr.nombres, ' ', pr.apellidos) AS nombre_cliente,
                    pr.telefono,
                    u.email,
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
                    p.id_pedido = ?";

$stmt_pedido = $conn->prepare($sql_pedido);
$stmt_pedido->bind_param("i", $id_pedido);
$stmt_pedido->execute();
$pedido = $stmt_pedido->get_result()->fetch_assoc();

if (!$pedido) {
    echo "Pedido no encontrado.";
    exit;
}

// 2. Consulta de Detalle: (Tu consulta ya estaba correcta)
$sql_detalles = "SELECT 
                    dp.id_detalle, dp.cantidad, dp.precio_historico, dp.id_estado_detalle, dp.numero_seguimiento,
                    prod.nombre_producto, 
                    prod.imagen_principal,  -- Imagen principal
                    vp.imagen_variante,   -- Imagen de la variante (LA QUE QUEREMOS)
                    vp.talla, vp.color,
                    vendedor_perfil.nombres AS nombre_vendedor,
                    ee.nombre_empresa
                FROM 
                    detalle_pedido AS dp
                JOIN 
                    variantes_producto AS vp ON dp.id_variante = vp.id_variante
                JOIN 
                    productos AS prod ON vp.id_producto = prod.id_producto
                JOIN 
                    usuarios AS vendedor_user ON prod.id_vendedor = vendedor_user.id_usuario
                JOIN 
                    perfiles AS vendedor_perfil ON vendedor_user.id_usuario = vendedor_perfil.id_usuario
                LEFT JOIN 
                    empresas_envio AS ee ON dp.id_empresa_envio = ee.id_empresa_envio
                WHERE 
                    dp.id_pedido = ?";

$stmt_detalles = $conn->prepare($sql_detalles);
$stmt_detalles->bind_param("i", $id_pedido);
$stmt_detalles->execute();
$detalles_pedido = $stmt_detalles->get_result();

$permite_cancelacion_admin = ($pedido['id_estado'] == 2);
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Pedido #<?= $id_pedido ?> - Admin</title>
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
        .badge-estado { font-size: 0.9em; padding: 0.5em 0.75em; }
        .img-producto-tabla { width: 50px; height: 50px; object-fit: cover; border-radius: 0.25rem; }
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
            <li><a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
            <li><a href="pedidos.php" class="nav-link active" aria-current="page"><i class="bi bi-list-check"></i> Pedidos</a></li>
            <li><a href="productos_admin.php" class="nav-link"><i class="bi bi-box-seam-fill"></i> Productos</a></li>
            <li><a href="usuarios.php" class="nav-link"><i class="bi bi-people-fill"></i> Usuarios</a></li>
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
            <h1 class="h2">Detalle del Pedido #<?= $id_pedido ?></h1>
            <div>
                <?php if ($permite_cancelacion_admin): ?>
                    <form method="POST" action="ver_pedido.php?id=<?= $id_pedido ?>" onsubmit="return confirm('¿Estás seguro de que deseas cancelar este pedido? Esta acción repondrá el stock y no se puede deshacer.');" style="display: inline;">
                        <input type="hidden" name="id_pedido" value="<?= $id_pedido ?>">
                        <input type="hidden" name="accion" value="cancelar_pedido">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle-fill me-1"></i> Cancelar Pedido
                        </button>
                    </form>
                <?php endif; ?>
                <a href="pedidos.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Pedidos
                </a>
            </div>
        </div>

        <?php if (!empty($mensaje_alerta)): ?>
            <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensaje_alerta) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><i class="bi bi-person-fill me-2"></i><strong>Cliente</strong></div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Nombre:</strong> <?= htmlspecialchars($pedido['nombre_cliente']) ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($pedido['email']) ?></p>
                        <p class="mb-0"><strong>Teléfono:</strong> <?= htmlspecialchars($pedido['telefono']) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><i class="bi bi-truck me-2"></i><strong>Dirección de Envío</strong></div>
                    <div class="card-body">
                        <p class="mb-1"><?= htmlspecialchars($pedido['direccion']) ?></p>
                        <p class="mb-1"><?= htmlspecialchars($pedido['ciudad']) ?>, <?= htmlspecialchars($pedido['pais']) ?></p>
                        <p class="mb-0"><strong>CP:</strong> <?= htmlspecialchars($pedido['codigo_postal']) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><i class="bi bi-file-earmark-text-fill me-2"></i><strong>Resumen del Pedido</strong></div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Fecha:</strong> <?= date("d/m/Y H:i", strtotime($pedido['fecha_pedido'])) ?></p>
                        <p class="mb-1"><strong>Total:</strong> <span class="fs-5 fw-bold text-success">S/ <?= number_format($pedido['total_pedido'], 2) ?></span></p>
                        <p class="mb-0"><strong>Estado General:</strong> 
                            <?php
                            $badge_color = 'secondary'; // Default
                            if ($pedido['id_estado'] == 1) $badge_color = 'warning text-dark';
                            if ($pedido['id_estado'] == 2) $badge_color = 'primary';
                            if ($pedido['id_estado'] == 3) $badge_color = 'info text-dark';
                            if ($pedido['id_estado'] == 4) $badge_color = 'success';
                            if ($pedido['id_estado'] == 5) $badge_color = 'danger';
                            ?>
                            <span class="badge rounded-pill bg-<?= $badge_color ?> badge-estado">
                                <?= htmlspecialchars($pedido['nombre_estado']) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header">
                <h5><i class="bi bi-box-seam me-2"></i>Productos en este Pedido</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Vendedor</th>
                                <th>Cantidad</th>
                                <th>Precio Histórico</th>
                                <th>Subtotal</th>
                                <th>Estado del Ítem</th>
                                <th>Info de Envío</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php while ($item = $detalles_pedido->fetch_assoc()) : ?>
                                <?php
                                // --- Lógica de Estado de Ítem ---
                                $estado_item_texto = 'Desconocido';
                                $estado_item_color = 'secondary';
                                if ($item['id_estado_detalle'] == 2) {
                                    $estado_item_texto = 'Pagado (Vendedor debe enviar)';
                                    $estado_item_color = 'primary';
                                } else if ($item['id_estado_detalle'] == 3) {
                                    $estado_item_texto = 'Enviado';
                                    $estado_item_color = 'info text-dark';
                                    if ($pedido['id_estado'] == 5) {
                                        $estado_item_texto = 'Enviado (Cancelación Fallida)';
                                        $estado_item_color = 'danger';
                                    }
                                } else if ($item['id_estado_detalle'] == 4) {
                                    $estado_item_texto = 'Entregado';
                                    $estado_item_color = 'success';
                                }
                                if ($pedido['id_estado'] == 5 && $item['id_estado_detalle'] == 2) {
                                     $estado_item_texto = 'Cancelado';
                                     $estado_item_color = 'danger';
                                }

                                // --- LÓGICA DE IMAGEN (LA CORRECCIÓN) ---
                                $imagen_src = '';
                                // 1. Priorizamos la imagen de la variante (tu carpeta img/variantes/)
                                if (!empty($item['../../assets/img/productos/variantes/'])) {
                                    // Subimos un nivel (../) desde 'admin' y entramos a 'img/variantes/'
                                    $imagen_src = '../img/variantes/' . htmlspecialchars($item['imagen_variante']);
                                } 
                                // 2. Si no hay, usamos la imagen principal (tu carpeta img/productos/)
                                else if (!empty($item['imagen_principal'])) {
                                    $imagen_src = '../img/productos/' . htmlspecialchars($item['imagen_principal']);
                                }
                                // 3. (Opcional) Si no hay ninguna, pon una imagen "placeholder"
                                // else {
                                //    $imagen_src = '../img/placeholder.png'; // Asegúrate de tener esta imagen
                                // }

                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $imagen_src ?>" class="img-producto-tabla me-2" alt="Imagen del producto">
                                            <div>
                                                <strong><?= htmlspecialchars($item['nombre_producto']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($item['talla']) ?> / <?= htmlspecialchars($item['color']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="bi bi-person-badge"></i> <?= htmlspecialchars($item['nombre_vendedor']) ?>
                                    </td>
                                    <td>x <?= $item['cantidad'] ?></td>
                                    <td>S/ <?= number_format($item['precio_historico'], 2) ?></td>
                                    <td><strong>S/ <?= number_format($item['precio_historico'] * $item['cantidad'], 2) ?></strong></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?= $estado_item_color ?>">
                                            <?= $estado_item_texto ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['nombre_empresa'])) : ?>
                                            <strong><?= htmlspecialchars($item['nombre_empresa']) ?>:</strong>
                                            <br>
                                            <span class="font-monospace"><?= htmlspecialchars($item['numero_seguimiento']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>