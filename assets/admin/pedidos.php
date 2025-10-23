<?php
session_start();
include 'db.php'; // Estamos en la carpeta 'admin', db.php está aquí

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); // Redirigimos al login
    exit;
}
// 2. Verificamos que el ROL sea 'admin'
if ($_SESSION['rol'] !== 'admin') {
    // Si no es admin, no puede estar aquí
    session_destroy();
    header('Location: ../../login.php'); //
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

$mensaje_error = "";
$mensaje_exito = "";

// --- LÓGICA POST (Calidad de Funcionalidad) ---
// (Para actualizar el estado de un pedido)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado') {
    try {
        $id_pedido = (int)$_POST['id_pedido'];
        $id_nuevo_estado = (int)$_POST['id_estado_pedido'];
        
        if ($id_pedido === 0 || $id_nuevo_estado === 0) {
            throw new Exception("Datos inválidos.");
        }
        
        $stmt_update = $conn->prepare("UPDATE pedidos SET id_estado_pedido = ? WHERE id_pedido = ?");
        $stmt_update->bind_param("ii", $id_nuevo_estado, $id_pedido);
        $stmt_update->execute();
        
        if ($stmt_update->affected_rows > 0) {
            $mensaje_exito = "Estado del pedido #$id_pedido actualizado correctamente.";
        } else {
            throw new Exception("No se pudo actualizar el estado o ya estaba en ese estado.");
        }
    } catch (Exception $e) {
        $mensaje_error = "Error: " . $e->getMessage();
    }
}

// --- LÓGICA GET (Calidad de Rendimiento) ---
// (Obtenemos todos los pedidos con sus datos relacionados en una consulta)

// 1. Obtenemos la lista de TODOS los estados (para el <select>)
$resultado_estados = $conn->query("SELECT * FROM estados_pedido");
$estados_posibles = $resultado_estados->fetch_all(MYSQLI_ASSOC);

// 2. Obtenemos TODOS los pedidos con JOINs
$query_pedidos = "
    SELECT 
        p.id_pedido,
        p.fecha_pedido,
        p.total_pedido,
        e.nombre_estado,
        e.id_estado,
        pr.nombres AS cliente_nombres,
        pr.apellidos AS cliente_apellidos,
        d.direccion,
        d.ciudad
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
    ORDER BY
        p.fecha_pedido DESC
";
$resultado_pedidos = $conn->query($query_pedidos);
$pedidos = $resultado_pedidos->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Pedidos - Admin</title>
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
        <h2 class="mb-4">Gestión de Pedidos</h2>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger alert-error-animated"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Pedido #</th>
                                <th scope="col">Fecha</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Dirección Envío</th>
                                <th scope="col">Total</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pedidos)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No se han encontrado pedidos.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <tr>
                                        <td><strong>#<?= $pedido['id_pedido'] ?></strong></td>
                                        <td><?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></td>
                                        <td><?= htmlspecialchars($pedido['cliente_nombres'] . ' ' . $pedido['cliente_apellidos']) ?></td>
                                        <td><small><?= htmlspecialchars($pedido['direccion'] . ', ' . $pedido['ciudad']) ?></small></td>
                                        <td><strong>S/ <?= number_format($pedido['total_pedido'], 2) ?></strong></td>
                                        <td>
                                            <form action="pedidos.php" method="POST" class="d-flex">
                                                <input type="hidden" name="accion" value="cambiar_estado">
                                                <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                                                <select name="id_estado_pedido" class="form-select form-select-sm">
                                                    <?php foreach ($estados_posibles as $estado): ?>
                                                        <option value="<?= $estado['id_estado'] ?>" <?= ($estado['id_estado'] == $pedido['id_estado']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($estado['nombre_estado']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-outline-primary ms-2" title="Guardar Cambio">
                                                    <i class="bi bi-save"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="detalle_pedido.php?id=<?= $pedido['id_pedido'] ?>" class="btn btn-sm btn-info" title="Ver Detalle"> <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
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