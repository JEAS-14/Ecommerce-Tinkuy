<?php
session_start();
include 'db.php'; // Estamos en la carpeta 'admin', db.php está aquí

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); // Redirigimos al login
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    session_destroy();
    header('Location: ../../login.php'); //
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

$mensaje_error = "";
$mensaje_exito = "";

// --- LÓGICA POST (Calidad de Funcionalidad) ---

// ACCIÓN 1: Cambiar estado manualmente (como antes)
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

// NUEVO - ACCIÓN 2: Registrar envío (ISO 25010 - Adecuación Funcional)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar_envio') {
    try {
        $id_pedido_envio = (int)$_POST['id_pedido_envio'];
        $proveedor_envio = trim($_POST['proveedor_envio']);
        $numero_seguimiento = trim($_POST['numero_seguimiento']);
        
        // El estado '3' es 'Enviado' según tu BBDD
        $id_estado_enviado = 3; 

        if ($id_pedido_envio === 0 || empty($proveedor_envio) || empty($numero_seguimiento)) {
            throw new Exception("Debe completar todos los campos del envío.");
        }
        
        // Actualizamos el pedido con la info de envío y cambiamos su estado a 'Enviado'
        $stmt_envio = $conn->prepare("UPDATE pedidos SET proveedor_envio = ?, numero_seguimiento = ?, id_estado_pedido = ? WHERE id_pedido = ?");
        $stmt_envio->bind_param("ssii", $proveedor_envio, $numero_seguimiento, $id_estado_enviado, $id_pedido_envio);
        $stmt_envio->execute();
        
        if ($stmt_envio->affected_rows > 0) {
            $mensaje_exito = "Envío del pedido #$id_pedido_envio registrado. Estado actualizado a 'Enviado'.";
            
            // --- IMPLEMENTACIÓN FUTURA ---
            // AQUÍ: Implementar la lógica de envío de correo al cliente
            // (Usando la configuración de PHPMailer que tienes)
            // 
            // 1. Obtener el email del cliente (requiere un JOIN extra o una consulta nueva)
            // 2. Enviar correo: "Tu pedido #$id_pedido_envio ha sido enviado vía $proveedor_envio con el Nro. $numero_seguimiento."
            // --- FIN IMPLEMENTACIÓN FUTURA ---

        } else {
            throw new Exception("No se pudo actualizar la información de envío.");
        }
    } catch (Exception $e) {
        $mensaje_error = "Error al registrar envío: " . $e->getMessage();
    }
}


// --- LÓGICA GET (Calidad de Rendimiento) ---

// 1. Obtenemos la lista de TODOS los estados (para el <select>)
$resultado_estados = $conn->query("SELECT * FROM estados_pedido");
$estados_posibles = $resultado_estados->fetch_all(MYSQLI_ASSOC);

// 2. Obtenemos TODOS los pedidos (Consulta MODIFICADA para incluir info de envío)
$query_pedidos = "
    SELECT 
        p.id_pedido,
        p.fecha_pedido,
        p.total_pedido,
        p.proveedor_envio,       -- NUEVO
        p.numero_seguimiento,    -- NUEVO
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
            <a class="navbar-brand" href="dashboard.php">Panel Admin</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="pedidos.php">Pedidos</a></li>
                    <li class="nav-item"><a class="nav-link" href="productos_admin.php">Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="usuarios.php">Usuarios</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4">Gestión de Pedidos</h2>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
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
                                <th scope="col">Total</th>
                                <th scope="col">Seguimiento</th> <th scope="col">Estado</th>
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
                                        <td>
                                            <?= htmlspecialchars($pedido['cliente_nombres'] . ' ' . $pedido['cliente_apellidos']) ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($pedido['direccion'] . ', ' . $pedido['ciudad']) ?></small>
                                        </td>
                                        <td><strong>S/ <?= number_format($pedido['total_pedido'], 2) ?></strong></td>
                                        
                                        <td>
                                            <?php if (!empty($pedido['numero_seguimiento'])): ?>
                                                <small>
                                                    <strong><?= htmlspecialchars($pedido['proveedor_envio']) ?>:</strong>
                                                    <br>
                                                    <?= htmlspecialchars($pedido['numero_seguimiento']) ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">Sin enviar</small>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <form action="pedidos.php" method="POST" class="d-flex">
                                                <input type="hidden" name="accion" value="cambiar_estado">
                                                <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">
                                                <select name="id_estado_pedido" class="form-select form-select-sm" style="min-width: 140px;">
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
                                            <?php if ($pedido['id_estado'] == 2): ?>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-sm btn-success btn-registrar-envio" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEnvio" 
                                                    data-id="<?= $pedido['id_pedido'] ?>"
                                                    title="Registrar Envío">
                                                    <i class="bi bi-truck"></i> Gestionar
                                                </button>
                                            <?php endif; ?>

                                            <a href="detalle_pedido.php?id=<?= $pedido['id_pedido'] ?>" class="btn btn-sm btn-info" title="Ver Detalle">
                                                <i class="bi bi-eye"></i>
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


    <div class="modal fade" id="modalEnvio" tabindex="-1" aria-labelledby="modalEnvioLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEnvioLabel">Registrar Información de Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="pedidos.php" method="POST">
                    <div class="modal-body">
                        <p>Se marcará el pedido como <strong>'Enviado'</strong> y se guardará la información de seguimiento.</p>
                        
                        <input type="hidden" name="accion" value="registrar_envio">
                        <input type="hidden" name="id_pedido_envio" id="id_pedido_envio" value="">

                        <div class="mb-3">
                            <label for="proveedor_envio" class="form-label">Empresa de Envío (Ej: Olva, DHL, Urbano)</label>
                            <input type="text" class="form-control" id="proveedor_envio" name="proveedor_envio" required>
                        </div>
                        <div class="mb-3">
                            <label for="numero_seguimiento" class="form-label">Número de Seguimiento (Tracking)</label>
                            <input type="text" class="form-control" id="numero_seguimiento" name="numero_seguimiento" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar y Marcar como Enviado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalEnvio = document.getElementById('modalEnvio');
            
            modalEnvio.addEventListener('show.bs.modal', function (event) {
                // Botón que activó el modal
                var button = event.relatedTarget;
                
                // Extraer el ID del pedido del atributo data-id
                var idPedido = button.getAttribute('data-id');
                
                // Actualizar el input oculto en el formulario del modal
                var inputIdPedido = modalEnvio.querySelector('#id_pedido_envio');
                inputIdPedido.value = idPedido;

                // Opcional: Actualizar el título del modal
                var modalTitle = modalEnvio.querySelector('.modal-title');
                modalTitle.textContent = 'Registrar Envío para Pedido #' + idPedido;
            });
        });
    </script>
</body>
</html>