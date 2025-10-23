<?php
session_start();
include 'db.php'; // Estamos en admin

$mensaje_error = "";
$mensaje_exito = "";

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); //
    exit;
}
if ($_SESSION['rol'] !== 'admin') { // SOLO admin puede editar CUALQUIER producto
    session_destroy();
    header('Location: ../../login.php'); //
    exit;
}

// 1. Validamos el ID del producto (Seguridad)
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header('Location: productos_admin.php'); //
    exit;
}
$id_producto = (int)$_GET['id'];
// (No necesitamos $id_vendedor porque el admin puede editar todo)

// --- LÓGICA DE PROCESAMIENTO (POST) ---
// (Idéntica a la del vendedor, pero las consultas NO filtran por id_vendedor)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- ACCIÓN: ACTUALIZAR PRODUCTO GENERAL ---
        if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_producto') {
            $nombre = trim($_POST['nombre_producto']);
            $descripcion = trim($_POST['descripcion']);
            $id_categoria = (int)$_POST['id_categoria'];

            if(empty($nombre) || $id_categoria === 0) {
                 throw new Exception("El nombre y la categoría son obligatorios.");
            }
            
            // ADMIN SÍ puede actualizar, NO necesita filtrar por vendedor
            $stmt = $conn->prepare("UPDATE productos SET nombre_producto = ?, descripcion = ?, id_categoria = ? WHERE id_producto = ?");
            $stmt->bind_param("ssii", $nombre, $descripcion, $id_categoria, $id_producto);
            $stmt->execute();
            $mensaje_exito = "Producto actualizado correctamente por admin.";
        }
        
        // --- ACCIÓN: AGREGAR NUEVA VARIANTE ---
        // (Esta lógica es idéntica a la del vendedor)
        elseif (isset($_POST['accion']) && $_POST['accion'] === 'agregar_variante') {
             $talla = trim($_POST['talla']);
            $color = trim($_POST['color']);
            $precio = filter_var(trim($_POST['precio']), FILTER_VALIDATE_FLOAT);
            $stock = filter_var(trim($_POST['stock']), FILTER_VALIDATE_INT);

            if ($precio === false || $stock === false || $precio <= 0 || $stock < 0) {
                throw new Exception("El precio y el stock deben ser números positivos.");
            }
            if (empty($talla) || empty($color)) {
                throw new Exception("La talla y el color son obligatorios para la nueva variante.");
            }
            
            $nombre_prod_temp = $conn->query("SELECT nombre_producto FROM productos WHERE id_producto = $id_producto")->fetch_assoc()['nombre_producto'];
            $sku_simulado = strtoupper(substr($nombre_prod_temp, 0, 3)) . '-' . $id_producto . '-' . $talla . '-' . $color;

            $stmt = $conn->prepare("INSERT INTO variantes_producto (id_producto, talla, color, sku, precio, stock) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssdi", $id_producto, $talla, $color, $sku_simulado, $precio, $stock);
            $stmt->execute();
            $mensaje_exito = "Nueva variante agregada con éxito por admin.";
        }

        // --- ACCIÓN: ACTUALIZAR / ELIMINAR VARIANTES EXISTENTES ---
        // (Esta lógica es idéntica a la del vendedor)
        elseif (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_variantes') {
            $conn->begin_transaction(); 
            $stmt_update = $conn->prepare("UPDATE variantes_producto SET precio = ?, stock = ? WHERE id_variante = ? AND id_producto = ?");
            $stmt_delete = $conn->prepare("DELETE FROM variantes_producto WHERE id_variante = ? AND id_producto = ?");

            foreach ($_POST['variantes'] as $id_variante => $datos) {
                $id_variante = (int)$id_variante; 
                if (isset($datos['eliminar'])) {
                    $stmt_delete->bind_param("ii", $id_variante, $id_producto);
                    $stmt_delete->execute();
                } else {
                    $precio = filter_var($datos['precio'], FILTER_VALIDATE_FLOAT);
                    $stock = filter_var($datos['stock'], FILTER_VALIDATE_INT);
                    if ($precio === false || $stock === false || $precio <= 0 || $stock < 0) {
                        throw new Exception("Datos inválidos para variante ID $id_variante.");
                    }
                    $stmt_update->bind_param("diii", $precio, $stock, $id_variante, $id_producto);
                    $stmt_update->execute();
                }
            }
            $conn->commit(); 
            $mensaje_exito = "Lista de variantes actualizada por admin.";
        }
        
    } catch (Exception $e) {
        $conn->rollback(); 
        $mensaje_error = "Error: " . $e->getMessage();
    }
}
// --- FIN DE LÓGICA (POST) ---


// --- LÓGICA DE VISUALIZACIÓN (GET) ---
// (Cargamos los datos frescos después del POST)

// 3. Obtenemos datos del Producto (Admin puede ver CUALQUIERA)
$stmt = $conn->prepare("
    SELECT p.*, c.nombre_categoria, u.usuario AS nombre_vendedor 
    FROM productos p 
    JOIN categorias c ON p.id_categoria = c.id_categoria 
    JOIN usuarios u ON p.id_vendedor = u.id_usuario
    WHERE p.id_producto = ?
");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$resultado_producto = $stmt->get_result();

if ($resultado_producto->num_rows === 0) {
    $_SESSION['mensaje_error'] = "Producto no encontrado.";
    header('Location: productos_admin.php'); //
    exit;
}
$producto = $resultado_producto->fetch_assoc();

// 4. Obtenemos todas las categorías (para el <select>)
$resultado_categorias = $conn->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC");
$categorias = $resultado_categorias->fetch_all(MYSQLI_ASSOC);

// 5. Obtenemos TODAS las variantes de este producto
$stmt_variantes = $conn->prepare("SELECT * FROM variantes_producto WHERE id_producto = ? ORDER BY talla, color");
$stmt_variantes->bind_param("i", $id_producto);
$stmt_variantes->execute();
$resultado_variantes = $stmt_variantes->get_result();
$variantes = $resultado_variantes->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto (Admin) - <?= htmlspecialchars($producto['nombre_producto']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Admin</a> <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="pedidos.php">Pedidos</a></li> <li class="nav-item"><a class="nav-link active" href="productos_admin.php">Productos</a></li> <li class="nav-item"><a class="nav-link" href="usuarios.php">Usuarios</a></li> </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión</a></li> </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2>Editando (Admin): <?= htmlspecialchars($producto['nombre_producto']) ?></h2>
        <p class="text-muted">Producto de: <strong><?= htmlspecialchars($producto['nombre_vendedor']) ?></strong></p>
        <a href="productos_admin.php" class="btn btn-sm btn-outline-secondary mb-3"> <i class="bi bi-arrow-left"></i> Volver a la lista
        </a>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger alert-error-animated"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-lg-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        Información General
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <input type="hidden" name="accion" value="actualizar_producto">
                            <div class="mb-3">
                                <label for="nombre_producto" class="form-label">Nombre del Producto</label>
                                <input type="text" class="form-control" id="nombre_producto" name="nombre_producto" value="<?= htmlspecialchars($producto['nombre_producto']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="id_categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="id_categoria" name="id_categoria" required>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id_categoria'] ?>" <?= ($cat['id_categoria'] == $producto['id_categoria']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nombre_categoria']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar Cambios Generales</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                     <div class="card-header">
                        Agregar Nueva Variante
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <input type="hidden" name="accion" value="agregar_variante">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="talla" class="form-label">Talla</label>
                                    <input type="text" class="form-control" id="talla" name="talla" placeholder="Ej: M" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <input type="text" class="form-control" id="color" name="color" placeholder="Ej: Rojo" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="precio" class="form-label">Precio (S/)</label>
                                    <input type="number" step="0.01" class="form-control" id="precio" name="precio" placeholder="Ej: 150.00" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="stock" name="stock" placeholder="Ej: 10" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> Agregar esta Variante
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header">
                        Variantes Existentes
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <input type="hidden" name="accion" value="actualizar_variantes">
                            <?php if (empty($variantes)): ?>
                                <p class="text-muted">Este producto aún no tiene variantes.</p>
                            <?php else: ?>
                                <?php foreach ($variantes as $v): ?>
                                    <div class="border rounded p-3 mb-3">
                                        <h6 class="mb-3">Variante: <?= htmlspecialchars($v['talla']) ?> / <?= htmlspecialchars($v['color']) ?></h6>
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label small">Precio</label>
                                                <input type="number" step="0.01" class="form-control form-control-sm" name="variantes[<?= $v['id_variante'] ?>][precio]" value="<?= htmlspecialchars($v['precio']) ?>">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small">Stock</label>
                                                <input type="number" class="form-control form-control-sm" name="variantes[<?= $v['id_variante'] ?>][stock]" value="<?= htmlspecialchars($v['stock']) ?>">
                                            </div>
                                        </div>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="variantes[<?= $v['id_variante'] ?>][eliminar]" id="eliminar_<?= $v['id_variante'] ?>">
                                            <label class="form-check-label small text-danger" for="eliminar_<?= $v['id_variante'] ?>">
                                                Marcar para Eliminar
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <button type="submit" class="btn btn-warning w-100" onclick="return confirm('¿Estás seguro? Se guardarán todos los cambios y se eliminarán las variantes marcadas.')">
                                    <i class="bi bi-save"></i> Actualizar Lista de Variantes
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

        </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>