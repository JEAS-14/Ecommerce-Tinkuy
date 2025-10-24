<?php
session_start();

// Asumimos que la conexión a la BBDD está en la carpeta 'admin'
// Si la tienes en 'vendedor', cambia a: include 'db.php';
include '../admin/db.php';

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
// 1. Verificamos que haya un usuario logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}
// 2. Verificamos que el ROL sea 'vendedor'
if ($_SESSION['rol'] !== 'vendedor') {
    // Si no es vendedor, no puede estar aquí
    session_destroy();
    header('Location: ../../login.php');
    exit;
}
// 3. Obtenemos el ID del vendedor logueado
$id_vendedor = $_SESSION['usuario_id'];
$nombre_vendedor = $_SESSION['usuario'];
// --- FIN DE CALIDAD (SEGURIDAD) ---

$mensaje_error = "";
$mensaje_exito = "";

// --- LÓGICA POST (Calidad de Funcionalidad y Seguridad) ---
// (Para registrar el envío de un ítem)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar_envio') {
    try {
        $id_detalle = (int) $_POST['id_detalle_envio'];
        $id_empresa_envio = (int) $_POST['id_empresa_envio'];
        $numero_seguimiento = trim($_POST['numero_seguimiento']);

        // El estado '3' es 'Enviado' (lo definimos en la BBDD)
        $id_estado_enviado = 3;

        if ($id_detalle === 0 || $id_empresa_envio === 0 || empty($numero_seguimiento)) {
            throw new Exception("Debe seleccionar una empresa y completar el N° de seguimiento.");
        }

        // --- INICIO VERIFICACIÓN DE PERMISO (ISO 25010 - Seguridad) ---
        // Verificamos que el 'id_detalle' que intenta modificar REALMENTE le pertenece a este vendedor

        $stmt_check = $conn->prepare("
            SELECT p.id_vendedor
            FROM detalle_pedido dp
            JOIN variantes_producto vp ON dp.id_variante = vp.id_variante
            JOIN productos p ON vp.id_producto = p.id_producto
            WHERE dp.id_detalle = ?
        ");
        $stmt_check->bind_param("i", $id_detalle);
        $stmt_check->execute();
        $resultado_check = $stmt_check->get_result();
        $fila_check = $resultado_check->fetch_assoc();

        if (!$fila_check || $fila_check['id_vendedor'] != $id_vendedor) {
            // Si no hay resultado O el id_vendedor no coincide, es un intento malicioso o un error.
            throw new Exception("Error de permisos: Usted no puede modificar este envío.");
        }
        // --- FIN VERIFICACIÓN DE PERMISO ---

        // Si la verificación es exitosa, actualizamos el ítem (detalle_pedido)
        $stmt_envio = $conn->prepare(
            "UPDATE detalle_pedido SET id_empresa_envio = ?, numero_seguimiento = ?, id_estado_detalle = ? WHERE id_detalle = ?"
        );
        $stmt_envio->bind_param("isii", $id_empresa_envio, $numero_seguimiento, $id_estado_enviado, $id_detalle);
        $stmt_envio->execute();

        if ($stmt_envio->affected_rows > 0) {
            $mensaje_exito = "Envío para el ítem #$id_detalle registrado correctamente.";
            // (Aquí también iría la lógica de email para el cliente)
        } else {
            throw new Exception("No se pudo actualizar la información de envío.");
        }
    } catch (Exception $e) {
        $mensaje_error = "Error al registrar envío: " . $e->getMessage();
    }
}

// --- LÓGICA GET (Calidad de Rendimiento) ---

// 1. Obtenemos la lista de EMPRESAS DE ENVÍO (para el <select> del modal)
// (Usamos la tabla que creamos en la BBDD)
$resultado_empresas = $conn->query("SELECT * FROM empresas_envio ORDER BY nombre_empresa ASC");
$empresas_envio = $resultado_empresas->fetch_all(MYSQLI_ASSOC);

// 2. Obtenemos TODOS los ítems PENDIENTES DE ENVÍO (estado 2) que pertenecen a ESTE VENDEDOR
$query_items = "
    SELECT 
        dp.id_detalle,
        dp.cantidad,
        dp.precio_historico,
        p.nombre_producto,
        vp.talla,
        vp.color,
        pe.id_pedido,
        pe.fecha_pedido,
        d.direccion,
        d.ciudad,
        d.pais,
        d.codigo_postal,
        comprador_perfil.nombres AS cliente_nombres,
        comprador_perfil.apellidos AS cliente_apellidos
    FROM 
        detalle_pedido AS dp
    JOIN 
        variantes_producto AS vp ON dp.id_variante = vp.id_variante
    JOIN 
        productos AS p ON vp.id_producto = p.id_producto
    JOIN 
        pedidos AS pe ON dp.id_pedido = pe.id_pedido
    JOIN 
        direcciones AS d ON pe.id_direccion_envio = d.id_direccion
    JOIN 
        usuarios AS comprador ON pe.id_usuario = comprador.id_usuario
    JOIN 
        perfiles AS comprador_perfil ON comprador.id_usuario = comprador_perfil.id_usuario
    WHERE 
        p.id_vendedor = ?            -- Solo ítems de ESTE vendedor
        AND dp.id_estado_detalle = 2 -- Solo ítems 'Pagados' (pendientes de envío)
    ORDER BY
        pe.fecha_pedido ASC
";
$stmt_items = $conn->prepare($query_items);
$stmt_items->bind_param("i", $id_vendedor);
$stmt_items->execute();
$resultado_items = $stmt_items->get_result();
$items_pendientes = $resultado_items->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Envíos - Panel Vendedor</title>
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
                    <li class="nav-item"><a class="nav-link" href="ventas.php">Mis Ventas</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión
                            (<?= htmlspecialchars($nombre_vendedor) ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4">Gestión de Envíos Pendientes</h2>
        <p class="text-muted">Aquí aparecen todos los productos que has vendido y que están pagados, listos para que los
            envíes.</p>

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
                                <th scope="col">Producto a Enviar</th>
                                <th scope="col">Cantidad</th>
                                <th scope="col">Cliente</th>
                                <th scope="col">Dirección de Envío</th>
                                <th scope="col">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items_pendientes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">
                                        <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">¡Todo al día!</h5>
                                        No tienes envíos pendientes.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items_pendientes as $item): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?= $item['id_pedido'] ?></strong>
                                            <br>
                                            <small><?= date('d/m/Y', strtotime($item['fecha_pedido'])) ?></small>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($item['nombre_producto']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                (<?= htmlspecialchars($item['talla']) ?> /
                                                <?= htmlspecialchars($item['color']) ?>)
                                                </Ssmall>
                                        </td>
                                        <td><strong><?= $item['cantidad'] ?></strong></td>
                                        <td><?= htmlspecialchars($item['cliente_nombres'] . ' ' . $item['cliente_apellidos']) ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?= htmlspecialchars($item['direccion']) ?><br>
                                                <?= htmlspecialchars($item['ciudad']) ?>, <?= htmlspecialchars($item['pais']) ?>
                                                (CP: <?= htmlspecialchars($item['codigo_postal']) ?>)
                                            </small>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-success btn-registrar-envio"
                                                data-bs-toggle="modal" data-bs-target="#modalEnvio"
                                                data-id-detalle="<?= $item['id_detalle'] ?>"
                                                data-producto-nombre="<?= htmlspecialchars($item['nombre_producto']) ?>"
                                                title="Registrar Envío">
                                                <i class="bi bi-truck"></i> Registrar Envío
                                            </button>
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
                    <h5 class="modal-title" id="modalEnvioLabel">Registrar Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="envios.php" method="POST">
                    <div class="modal-body">
                        <p>Vas a registrar el envío para el ítem: <strong id="modal-producto-nombre"></strong></p>

                        <input type="hidden" name="accion" value="registrar_envio">
                        <input type="hidden" name="id_detalle_envio" id="id_detalle_envio" value="">

                        <div class="mb-3">
                            <label for="id_empresa_envio" class="form-label">Empresa de Envío</label>
                            <select class="form-select" id="id_empresa_envio" name="id_empresa_envio" required>
                                <option value="" disabled selected>-- Seleccione una empresa --</option>
                                <?php foreach ($empresas_envio as $empresa): ?>
                                    <option value="<?= $empresa['id_empresa_envio'] ?>">
                                        <?= htmlspecialchars($empresa['nombre_empresa']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="numero_seguimiento" class="form-label">Número de Seguimiento (Tracking)</label>
                            <input type="text" class="form-control" id="numero_seguimiento" name="numero_seguimiento"
                                required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Envío</button>
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

                // Extraer datos de los atributos data-*
                var idDetalle = button.getAttribute('data-id-detalle');
                var nombreProducto = button.getAttribute('data-producto-nombre');

                // Actualizar el input oculto en el formulario del modal
                var inputIdDetalle = modalEnvio.querySelector('#id_detalle_envio');
                inputIdDetalle.value = idDetalle;

                // Actualizar el título y el texto del modal
                var modalTitle = modalEnvio.querySelector('.modal-title');
                modalTitle.textContent = 'Registrar Envío para Ítem #' + idDetalle;

                var modalProductName = modalEnvio.querySelector('#modal-producto-nombre');
                modalProductName.textContent = nombreProducto;
            });
        });
    </script>
</body>

</html>