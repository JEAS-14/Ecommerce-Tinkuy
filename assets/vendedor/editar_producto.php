<?php
session_start();
include '../admin/db.php'; // Subimos un nivel

$mensaje_error = "";
$mensaje_exito = "";

// --- Seguridad ---
if (!isset($_SESSION['usuario_id'])) { header('Location: ../../login.php'); exit; }
if ($_SESSION['rol'] !== 'vendedor') { session_destroy(); header('Location: ../../login.php'); exit; }
// --- Fin Seguridad ---

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) { header('Location: productos.php'); exit; }
$id_producto = (int)$_GET['id'];
$id_vendedor = $_SESSION['usuario_id'];

// --- Lógica de Reactivación (GET) ---
if (isset($_GET['reactivar_variante_id'])) {
    try {
        $id_variante_reactivar = (int)$_GET['reactivar_variante_id'];
        $stmt_check_var = $conn->prepare("SELECT 1 FROM variantes_producto vp JOIN productos p ON vp.id_producto = p.id_producto WHERE vp.id_variante = ? AND p.id_vendedor = ?");
        $stmt_check_var->bind_param("ii", $id_variante_reactivar, $id_vendedor);
        $stmt_check_var->execute();
        if ($stmt_check_var->get_result()->num_rows === 0) { throw new Exception("Permiso denegado."); }
        $stmt_check_var->close();

        $stmt_reactivar = $conn->prepare("UPDATE variantes_producto SET estado = 'activo' WHERE id_variante = ? AND id_producto = ?");
        $stmt_reactivar->bind_param("ii", $id_variante_reactivar, $id_producto);
        if($stmt_reactivar->execute()) { $_SESSION['mensaje_exito_temp'] = "Variante reactivada."; }
        else { throw new Exception("Error al reactivar."); }
        $stmt_reactivar->close();
    } catch (Exception $e) { $_SESSION['mensaje_error_temp'] = "Error: " . $e->getMessage(); }
    header("Location: editar_producto.php?id=$id_producto"); exit;
}
if (isset($_SESSION['mensaje_exito_temp'])) { $mensaje_exito = $_SESSION['mensaje_exito_temp']; unset($_SESSION['mensaje_exito_temp']); }
if (isset($_SESSION['mensaje_error_temp'])) { $mensaje_error = $_SESSION['mensaje_error_temp']; unset($_SESSION['mensaje_error_temp']); }
// --- Fin Lógica Reactivación ---


// --- Lógica POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- ACCIÓN: ACTUALIZAR PRODUCTO GENERAL ---
        if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_producto') {
            $nombre = trim($_POST['nombre_producto']);
            $descripcion = trim($_POST['descripcion']);
            $id_categoria = (int)$_POST['id_categoria'];
            if(empty($nombre) || $id_categoria === 0) { throw new Exception("Nombre y categoría obligatorios."); }

            $stmt = $conn->prepare("UPDATE productos SET nombre_producto = ?, descripcion = ?, id_categoria = ? WHERE id_producto = ? AND id_vendedor = ?");
            $stmt->bind_param("ssiii", $nombre, $descripcion, $id_categoria, $id_producto, $id_vendedor);
            if ($stmt->execute()) { $mensaje_exito = "Producto actualizado."; }
            else { throw new Exception("Error al actualizar producto."); }
            $stmt->close();
        }

        // --- ACCIÓN: AGREGAR NUEVA VARIANTE (CON IMAGEN) ---
        elseif (isset($_POST['accion']) && $_POST['accion'] === 'agregar_variante') {
            $talla = trim($_POST['talla']);
            $color = trim($_POST['color']);
            $precio = filter_var(trim($_POST['precio']), FILTER_VALIDATE_FLOAT);
            $stock = filter_var(trim($_POST['stock']), FILTER_VALIDATE_INT);
            $imagen_variante_nombre = null;

            if ($precio===false || $stock===false || $precio<=0 || $stock<0) { throw new Exception("Precio/Stock inválidos."); }
            if (empty($talla) || empty($color)) { throw new Exception("Talla y color obligatorios."); }

            // Lógica de Subida de Imagen
            if (isset($_FILES['imagen_variante']) && $_FILES['imagen_variante']['error'] == UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['imagen_variante']['tmp_name'];
                $fileName = $_FILES['imagen_variante']['name'];
                $fileSize = $_FILES['imagen_variante']['size'];
                $fileType = $_FILES['imagen_variante']['type'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                $allowedfileExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                if (!in_array($fileExtension, $allowedfileExtensions)) { throw new Exception("Tipo de archivo no permitido."); }
                $maxFileSize = 2 * 1024 * 1024;
                if ($fileSize > $maxFileSize) { throw new Exception("Archivo demasiado grande (Max 2MB)."); }
                $imagen_variante_nombre = 'variante_' . $id_producto . '_' . time() . '.' . $fileExtension;
                $dest_path = '../../assets/img/productos/variantes/' . $imagen_variante_nombre; // <-- Asegúrate que esta carpeta exista
                if(!move_uploaded_file($fileTmpPath, $dest_path)) { throw new Exception('Error al mover el archivo subido.'); }
            }

            // Verificar propiedad y obtener nombre para SKU
            $stmt_check_prop = $conn->prepare("SELECT nombre_producto FROM productos WHERE id_producto = ? AND id_vendedor = ?");
            $stmt_check_prop->bind_param("ii", $id_producto, $id_vendedor);
            $stmt_check_prop->execute();
            $res_check = $stmt_check_prop->get_result();
            if ($res_check->num_rows === 0) { throw new Exception("Permiso denegado."); }
            $nombre_prod_temp = $res_check->fetch_assoc()['nombre_producto'];
            $stmt_check_prop->close();

            $sku_simulado = strtoupper(substr($nombre_prod_temp, 0, 3)) . '-' . $id_producto . '-' . $talla . '-' . $color;

            $stmt = $conn->prepare("INSERT INTO variantes_producto (id_producto, talla, color, sku, precio, stock, imagen_variante) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssdis", $id_producto, $talla, $color, $sku_simulado, $precio, $stock, $imagen_variante_nombre);
            if ($stmt->execute()) { $mensaje_exito = "Nueva variante agregada."; }
            else { throw new Exception("Error al agregar variante: " . $conn->error); }
            $stmt->close();
        }

        // --- ACCIÓN: ACTUALIZAR / DESACTIVAR VARIANTES EXISTENTES ---
        elseif (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_variantes') {
            $conn->begin_transaction();
            $stmt_update = $conn->prepare("UPDATE variantes_producto SET precio = ?, stock = ? WHERE id_variante = ? AND id_producto = ?");
            $stmt_desactivar = $conn->prepare("UPDATE variantes_producto SET estado = 'inactivo' WHERE id_variante = ? AND id_producto = ?");

            if (isset($_POST['variantes']) && is_array($_POST['variantes'])) {
                foreach ($_POST['variantes'] as $id_variante => $datos) {
                    $id_variante = (int)$id_variante;
                    $stmt_check_var = $conn->prepare("SELECT 1 FROM variantes_producto vp JOIN productos p ON vp.id_producto = p.id_producto WHERE vp.id_variante = ? AND p.id_vendedor = ?");
                    $stmt_check_var->bind_param("ii", $id_variante, $id_vendedor);
                    $stmt_check_var->execute();
                    if ($stmt_check_var->get_result()->num_rows === 0) { $stmt_check_var->close(); throw new Exception("Permiso denegado variante $id_variante."); }
                    $stmt_check_var->close();

                    if (isset($datos['desactivar'])) {
                        $stmt_desactivar->bind_param("ii", $id_variante, $id_producto);
                        $stmt_desactivar->execute();
                    } else {
                        $precio = filter_var($datos['precio'], FILTER_VALIDATE_FLOAT);
                        $stock = filter_var($datos['stock'], FILTER_VALIDATE_INT);
                        if ($precio===false || $stock===false || $precio<=0 || $stock<0) { throw new Exception("Datos inválidos variante $id_variante."); }
                        $stmt_update->bind_param("diii", $precio, $stock, $id_variante, $id_producto);
                        $stmt_update->execute();
                    }
                }
            }
            $conn->commit();
            $stmt_update->close();
            $stmt_desactivar->close();
            $mensaje_exito = "Lista de variantes actualizada.";
        }

    } catch (Exception $e) {
        // CORREGIDO: Eliminamos la comprobación inTransaction()
        $conn->rollback();
        $mensaje_error = "Error: " . $e->getMessage();
    }
}
// --- FIN Lógica POST ---


// --- Lógica GET (Visualización) ---
// Verificamos Propiedad y Obtenemos datos del Producto
$sql_producto = "SELECT p.*, c.nombre_categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id_categoria WHERE p.id_producto = ? AND p.id_vendedor = ?";
$stmt = $conn->prepare($sql_producto);
$stmt->bind_param("ii", $id_producto, $id_vendedor);
$stmt->execute();
$resultado_producto = $stmt->get_result();
if ($resultado_producto->num_rows === 0) { $_SESSION['mensaje_error'] = "Producto no encontrado o permiso denegado."; header('Location: productos.php'); exit; }
$producto = $resultado_producto->fetch_assoc();
$stmt->close();

// Obtenemos categorías con jerarquía
$query_categorias_jerarquia = "SELECT c.id_categoria, c.nombre_categoria, c.id_categoria_padre, cp.nombre_categoria AS nombre_padre FROM categorias c LEFT JOIN categorias cp ON c.id_categoria_padre = cp.id_categoria ORDER BY COALESCE(cp.nombre_categoria, c.nombre_categoria), c.id_categoria_padre, c.nombre_categoria";
$resultado_categorias = $conn->query($query_categorias_jerarquia);
$categorias_jerarquia = []; while ($row = $resultado_categorias->fetch_assoc()) { $categorias_jerarquia[] = $row; }

// Obtenemos TODAS las variantes (incluyendo estado e imagen)
$stmt_variantes = $conn->prepare("SELECT *, estado, imagen_variante FROM variantes_producto WHERE id_producto = ? ORDER BY talla, color");
$stmt_variantes->bind_param("i", $id_producto);
$stmt_variantes->execute();
$resultado_variantes = $stmt_variantes->get_result();
$variantes = $resultado_variantes->fetch_all(MYSQLI_ASSOC);
$stmt_variantes->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style> .variante-inactiva { opacity: 0.6; background-color: #f8f9fa; } </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Vendedor</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vendedorNavbar"> <span class="navbar-toggler-icon"></span> </button>
            <div class="collapse navbar-collapse" id="vendedorNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link active" href="productos.php">Mis Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="envios.php">Envíos Pendientes</a></li>
                    <li class="nav-item"><a class="nav-link" href="ventas.php">Mis Ventas</a></li>
                </ul>
                <ul class="navbar-nav"> <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión</a></li> </ul>
            </div>
        </div>
     </nav>

    <div class="container my-5">
        <h2>Editando: <?= htmlspecialchars($producto['nombre_producto']) ?></h2>
        <a href="productos.php" class="btn btn-sm btn-outline-secondary mb-3"> <i class="bi bi-arrow-left"></i> Volver </a>
        <?php if (!empty($mensaje_error)): ?> <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div> <?php endif; ?>
        <?php if (!empty($mensaje_exito)): ?> <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div> <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">Información General</div>
                    <div class="card-body">
                         <form action="editar_producto.php?id=<?= $id_producto ?>" method="POST">
                            <input type="hidden" name="accion" value="actualizar_producto">
                            <div class="mb-3"><label for="nombre_producto" class="form-label">Nombre</label><input type="text" class="form-control" id="nombre_producto" name="nombre_producto" value="<?= htmlspecialchars($producto['nombre_producto']) ?>" required></div>
                            <div class="mb-3"><label for="descripcion" class="form-label">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($producto['descripcion']) ?></textarea></div>
                            <div class="mb-3">
                                <label for="id_categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="id_categoria" name="id_categoria" required>
                                    <option value="" disabled>-- Selecciona --</option>
                                    <?php /* Lógica PHP para optgroup (sin cambios) */
                                    $current_group = null;
                                    foreach ($categorias_jerarquia as $cat) {
                                        if ($cat['id_categoria_padre'] === null) { if ($current_group !== null) echo '</optgroup>'; echo '<optgroup label="' . htmlspecialchars($cat['nombre_categoria']) . '">'; $current_group = $cat['id_categoria']; }
                                        elseif ($cat['id_categoria_padre'] === $current_group) { $selected = ($cat['id_categoria'] == $producto['id_categoria']) ? 'selected' : ''; echo '<option value="' . $cat['id_categoria'] . '" ' . $selected . '>&nbsp;&nbsp;&nbsp;' . htmlspecialchars($cat['nombre_categoria']) . '</option>'; }
                                        elseif ($cat['id_categoria_padre'] !== null && $cat['id_categoria_padre'] !== $current_group) { if ($current_group !== null) { echo '</optgroup>'; $current_group = null; } $selected = ($cat['id_categoria'] == $producto['id_categoria']) ? 'selected' : ''; echo '<option value="' . $cat['id_categoria'] . '" ' . $selected . '>' . htmlspecialchars($cat['nombre_padre'] ?? '??') . ' / ' . htmlspecialchars($cat['nombre_categoria']) .' (Sub)</option>'; }
                                    } if ($current_group !== null) echo '</optgroup>';
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar Cambios Generales</button>
                        </form>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-header">Agregar Nueva Variante</div>
                    <div class="card-body">
                         <form action="editar_producto.php?id=<?= $id_producto ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="accion" value="agregar_variante">
                            <div class="row">
                                <div class="col-md-6 mb-3"><label for="talla" class="form-label">Talla <span class="text-danger">*</span></label><input type="text" class="form-control" id="talla" name="talla" placeholder="Ej: M" required></div>
                                <div class="col-md-6 mb-3"><label for="color" class="form-label">Color <span class="text-danger">*</span></label><input type="text" class="form-control" id="color" name="color" placeholder="Ej: Rojo" required></div>
                                <div class="col-md-6 mb-3"><label for="precio" class="form-label">Precio (S/) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="precio" name="precio" placeholder="150.00" required></div>
                                <div class="col-md-6 mb-3"><label for="stock" class="form-label">Stock <span class="text-danger">*</span></label><input type="number" class="form-control" id="stock" name="stock" placeholder="10" required></div>
                                <div class="col-12 mb-3">
                                     <label for="imagen_variante" class="form-label">Imagen Específica (Opcional)</label>
                                     <input class="form-control" type="file" id="imagen_variante" name="imagen_variante" accept="image/jpeg, image/png, image/gif, image/webp">
                            
                                     <small class="text-muted">Si subes una imagen, se mostrará específicamente para esta variante. Max 2MB.</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> Agregar Variante</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header">Variantes Existentes</div>
                    <div class="card-body">
                         <form action="editar_producto.php?id=<?= $id_producto ?>" method="POST">
                            <input type="hidden" name="accion" value="actualizar_variantes">
                            <?php if (empty($variantes)): ?>
                                <p class="text-muted">Sin variantes.</p>
                            <?php else: ?>
                                <?php foreach ($variantes as $v): ?>
                                    <div class="border rounded p-3 mb-3 <?= ($v['estado'] === 'inactivo') ? 'variante-inactiva' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($v['imagen_variante'])): ?>
                                                    <img src="../../assets/img/productos/variantes/<?= htmlspecialchars($v['imagen_variante']) ?>" alt="Variante" style="width: 40px; height: 40px; object-fit: cover;" class="rounded me-2">
                                                <?php else: ?>
                                                     <div class="me-2" style="width: 40px; height: 40px; background-color: #eee; display: flex; align-items: center; justify-content: center; border-radius: .25rem;"><i class="bi bi-image text-muted"></i></div>
                                                <?php endif; ?>
                                                <h6 class="mb-0 ms-1"> <?= htmlspecialchars($v['talla']) ?> / <?= htmlspecialchars($v['color']) ?></h6>
                                            </div>
                                             <span class="badge bg-<?= ($v['estado'] === 'inactivo') ? 'secondary' : 'success' ?>"><?= ucfirst($v['estado']) ?></span>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6"><label class="form-label small">Precio</label><input type="number" step="0.01" class="form-control form-control-sm" name="variantes[<?= $v['id_variante'] ?>][precio]" value="<?= htmlspecialchars($v['precio']) ?>" <?= ($v['estado'] === 'inactivo') ? 'disabled' : '' ?>></div>
                                            <div class="col-6"><label class="form-label small">Stock</label><input type="number" class="form-control form-control-sm" name="variantes[<?= $v['id_variante'] ?>][stock]" value="<?= htmlspecialchars($v['stock']) ?>" <?= ($v['estado'] === 'inactivo') ? 'disabled' : '' ?>></div>
                                        </div>
                                        <?php if ($v['estado'] === 'activo'): ?>
                                            <div class="form-check mt-2"> <input class="form-check-input" type="checkbox" name="variantes[<?= $v['id_variante'] ?>][desactivar]" id="desactivar_<?= $v['id_variante'] ?>"> <label class="form-check-label small text-warning" for="desactivar_<?= $v['id_variante'] ?>"> Marcar para Desactivar </label> </div>
                                        <?php else: ?>
                                             <a href="editar_producto.php?id=<?= $id_producto ?>&reactivar_variante_id=<?= $v['id_variante'] ?>" class="btn btn-sm btn-outline-success mt-2" onclick="return confirm('¿Reactivar esta variante?')"> <i class="bi bi-eye"></i> Reactivar </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <button type="submit" class="btn btn-warning w-100" onclick="return confirm('¿Guardar cambios y desactivar variantes marcadas?')"> <i class="bi bi-save"></i> Actualizar Lista </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>