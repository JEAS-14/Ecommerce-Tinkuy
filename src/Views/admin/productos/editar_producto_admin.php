<?php

session_start();

include 'db.php'; // Estamos en 'admin', db.php está aquí

// Asumo que 'db.php' ahora define BASE_URL como en el paso anterior

define('BASE_URL', 'http://localhost/ECOMMERCE-TINKUY');



$mensaje_error = "";

$mensaje_exito = "";



// --- Seguridad de ADMIN ---

if (!isset($_SESSION['usuario_id'])) { header('Location: ../../login.php'); exit; }

if ($_SESSION['rol'] !== 'admin') { session_destroy(); header('Location: ../../login.php'); exit; }

// --- Fin Seguridad ---



if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) { header('Location: productos_admin.php'); exit; }

$id_producto = (int)$_GET['id'];

$nombre_admin = $_SESSION['usuario']; // Para el sidebar



// --- Lógica de Reactivación (GET) ---

// (Lógica sin id_vendedor)

if (isset($_GET['reactivar_variante_id'])) {

    try {

        $id_variante_reactivar = (int)$_GET['reactivar_variante_id'];

       

        // Admin no necesita chequear propiedad, solo que exista

        $stmt_check_var = $conn->prepare("SELECT 1 FROM variantes_producto WHERE id_variante = ?");

        $stmt_check_var->bind_param("i", $id_variante_reactivar);

        $stmt_check_var->execute();

        if ($stmt_check_var->get_result()->num_rows === 0) { throw new Exception("Variante no encontrada."); }

        $stmt_check_var->close();



        $stmt_reactivar = $conn->prepare("UPDATE variantes_producto SET estado = 'activo' WHERE id_variante = ? AND id_producto = ?");

        $stmt_reactivar->bind_param("ii", $id_variante_reactivar, $id_producto);

        if($stmt_reactivar->execute()) { $_SESSION['mensaje_exito_temp'] = "Variante reactivada."; }

        else { throw new Exception("Error al reactivar."); }

        $stmt_reactivar->close();

    } catch (Exception $e) { $_SESSION['mensaje_error_temp'] = "Error: " . $e->getMessage(); }

   

    header("Location: editar_producto_admin.php?id=$id_producto"); exit; // Apuntamos al nuevo nombre de archivo

}

if (isset($_SESSION['mensaje_exito_temp'])) { $mensaje_exito = $_SESSION['mensaje_exito_temp']; unset($_SESSION['mensaje_exito_temp']); }

if (isset($_SESSION['mensaje_error_temp'])) { $mensaje_error = $_SESSION['mensaje_error_temp']; unset($_SESSION['mensaje_error_temp']); }

// --- Fin Lógica Reactivación ---





// --- Lógica POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

       

        // --- NUEVA ACCIÓN: CAMBIAR ESTADO DEL PRODUCTO (Activar/Desactivar) ---

        if (isset($_POST['accion']) && $_POST['accion'] === 'cambiar_estado_producto') {

            $nuevo_estado = $_POST['estado'] === 'activo' ? 'activo' : 'inactivo';

            $stmt_estado = $conn->prepare("UPDATE productos SET estado = ? WHERE id_producto = ?");

            $stmt_estado->bind_param("si", $nuevo_estado, $id_producto);

           

            if ($stmt_estado->execute()) {

                $mensaje_exito = "Estado del producto actualizado a '" . $nuevo_estado . "'.";

            } else {

                throw new Exception("Error al cambiar estado del producto.");

            }

            $stmt_estado->close();

        }



        // --- ACCIÓN: ACTUALIZAR PRODUCTO GENERAL ---

        // (Lógica sin id_vendedor)

        elseif (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_producto') {

            $nombre = trim($_POST['nombre_producto']);

            $descripcion = trim($_POST['descripcion']);

            $id_categoria = (int)$_POST['id_categoria'];

            if(empty($nombre) || $id_categoria === 0) { throw new Exception("Nombre y categoría obligatorios."); }



            $stmt = $conn->prepare("UPDATE productos SET nombre_producto = ?, descripcion = ?, id_categoria = ? WHERE id_producto = ?");

            $stmt->bind_param("ssii", $nombre, $descripcion, $id_categoria, $id_producto);

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



            // Lógica de Subida de Imagen (Ruta corregida)

            if (isset($_FILES['imagen_variante']) && $_FILES['imagen_variante']['error'] == UPLOAD_ERR_OK) {

                // ... (Validaciones de tamaño y tipo) ...

                $fileTmpPath = $_FILES['imagen_variante']['tmp_name'];

                $fileName = $_FILES['imagen_variante']['name'];

                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($fileExtension, $allowedfileExtensions)) { throw new Exception("Tipo de archivo no permitido."); }



                $imagen_variante_nombre = 'variante_' . $id_producto . '_' . time() . '.' . $fileExtension;

                // Ruta de FS: desde /admin/ subimos a / e-commerce-tinkuy/ y bajamos a /img/variantes/

                $dest_path = '../img/variantes/' . $imagen_variante_nombre;

               

                if(!move_uploaded_file($fileTmpPath, $dest_path)) { throw new Exception('Error al mover el archivo subido.'); }

            }



            // Obtener nombre para SKU (Admin no necesita chequeo de propiedad)

            $stmt_check_prop = $conn->prepare("SELECT nombre_producto FROM productos WHERE id_producto = ?");

            $stmt_check_prop->bind_param("i", $id_producto);

            $stmt_check_prop->execute();

            $nombre_prod_temp = $stmt_check_prop->get_result()->fetch_assoc()['nombre_producto'];

            $stmt_check_prop->close();



            $sku_simulado = strtoupper(substr($nombre_prod_temp, 0, 3)) . '-' . $id_producto . '-' . $talla . '-' . $color;



            $stmt = $conn->prepare("INSERT INTO variantes_producto (id_producto, talla, color, sku, precio, stock, imagen_variante) VALUES (?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("isssdis", $id_producto, $talla, $color, $sku_simulado, $precio, $stock, $imagen_variante_nombre);

            if ($stmt->execute()) { $mensaje_exito = "Nueva variante agregada."; }

            else { throw new Exception("Error al agregar variante: " . $conn->error); }

            $stmt->close();

        }



        // --- ACCIÓN: ACTUALIZAR / DESACTIVAR VARIANTES EXISTENTES ---

        // (Lógica sin id_vendedor)

        elseif (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_variantes') {

            $conn->begin_transaction();

            $stmt_update = $conn->prepare("UPDATE variantes_producto SET precio = ?, stock = ? WHERE id_variante = ? AND id_producto = ?");

            $stmt_desactivar = $conn->prepare("UPDATE variantes_producto SET estado = 'inactivo' WHERE id_variante = ? AND id_producto = ?");



            if (isset($_POST['variantes']) && is_array($_POST['variantes'])) {

                foreach ($_POST['variantes'] as $id_variante => $datos) {

                    $id_variante = (int)$id_variante;

                   

                    // Admin no necesita chequear propiedad de la variante

                   

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

        if ($conn->inTransaction()) {

             $conn->rollback();

        }

        $mensaje_error = "Error: " . $e->getMessage();

    }

}

// --- FIN Lógica POST ---





// --- Lógica GET (Visualización) ---

// (Lógica sin id_vendedor)

$sql_producto = "SELECT p.*, c.nombre_categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id_categoria WHERE p.id_producto = ?";

$stmt = $conn->prepare($sql_producto);

$stmt->bind_param("i", $id_producto);

$stmt->execute();

$resultado_producto = $stmt->get_result();

if ($resultado_producto->num_rows === 0) { $_SESSION['mensaje_error'] = "Producto no encontrado."; header('Location: productos_admin.php'); exit; }

$producto = $resultado_producto->fetch_assoc();

$stmt->close();



// Obtenemos categorías con jerarquía (sin cambios)

$query_categorias_jerarquia = "SELECT c.id_categoria, c.nombre_categoria, c.id_categoria_padre, cp.nombre_categoria AS nombre_padre FROM categorias c LEFT JOIN categorias cp ON c.id_categoria_padre = cp.id_categoria ORDER BY COALESCE(cp.nombre_categoria, c.nombre_categoria), c.id_categoria_padre, c.nombre_categoria";

$resultado_categorias = $conn->query($query_categorias_jerarquia);

$categorias_jerarquia = []; while ($row = $resultado_categorias->fetch_assoc()) { $categorias_jerarquia[] = $row; }



// Obtenemos TODAS las variantes (sin cambios)

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

    <title>Editar Producto #<?= $id_producto ?> - Admin</title>

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

       

        /* Estilo del script original */

        .variante-inactiva { opacity: 0.6; background-color: #f8f9fa; }

        .img-variante-thumb { width: 40px; height: 40px; object-fit: cover; }

        .img-variante-placeholder { width: 40px; height: 40px; background-color: #eee; display: flex; align-items: center; justify-content: center; border-radius: .25rem; }

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

            <li><a href="pedidos.php" class="nav-link"><i class="bi bi-list-check"></i> Pedidos</a></li>

            <li><a href="productos_admin.php" class="nav-link active" aria-current="page"><i class="bi bi-box-seam-fill"></i> Productos</a></li>

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

        <h2>Editando: <?= htmlspecialchars($producto['nombre_producto']) ?></h2>

       

        <a href="productos_admin.php" class="btn btn-sm btn-outline-secondary mb-3">

            <i class="bi bi-arrow-left"></i> Volver a Productos

        </a>

       

        <?php if (!empty($mensaje_error)): ?> <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div> <?php endif; ?>

        <?php if (!empty($mensaje_exito)): ?> <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div> <?php endif; ?>



        <div class="row g-4">

            <div class="col-lg-7">

                <div class="card shadow-sm mb-4">

                    <div class="card-header">Información General</div>

                    <div class="card-body">

                       

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div>

                                <strong>Estado Actual:</strong>

                                <span class="badge fs-6 bg-<?= $producto['estado'] === 'activo' ? 'success' : 'danger' ?>">

                                    <?= ucfirst($producto['estado']) ?>

                                </span>

                            </div>

                            <form action="editar_producto_admin.php?id=<?= $id_producto ?>" method="POST" class="d-inline">

                                <input type="hidden" name="accion" value="cambiar_estado_producto">

                                <?php if ($producto['estado'] === 'activo'): ?>

                                    <input type="hidden" name="estado" value="inactivo">

                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('¿Desactivar este producto? No se mostrará en la tienda.')">

                                        <i class="bi bi-eye-slash-fill"></i> Desactivar Producto

                                    </button>

                                <?php else: ?>

                                    <input type="hidden" name="estado" value="activo">

                                    <button type="submit" class="btn btn-sm btn-success">

                                        <i class="bi bi-eye-fill"></i> Activar Producto

                                    </button>

                                <?php endif; ?>

                            </form>

                        </div>

                        <hr>

                        <form action="editar_producto_admin.php?id=<?= $id_producto ?>" method="POST">

                            <input type="hidden" name="accion" value="actualizar_producto">

                            <div class="mb-3"><label for="nombre_producto" class="form-label">Nombre</label><input type="text" class="form-control" id="nombre_producto" name="nombre_producto" value="<?= htmlspecialchars($producto['nombre_producto']) ?>" required></div>

                            <div class="mb-3"><label for="descripcion" class="form-label">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?= htmlspecialchars($producto['descripcion']) ?></textarea></div>

                            <div class="mb-3">

                                <label for="id_categoria" class="form-label">Categoría</label>

                                <select class="form-select" id="id_categoria" name="id_categoria" required>

                                    <option value="" disabled>-- Selecciona --</option>

                                    <?php // Lógica de categorías (sin cambios, sigue funcional)

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

                         <form action="editar_producto_admin.php?id=<?= $id_producto ?>" method="POST" enctype="multipart/form-data">

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

                        <form action="editar_producto_admin.php?id=<?= $id_producto ?>" method="POST">

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

                                            <a href="editar_producto_admin.php?id=<?= $id_producto ?>&reactivar_variante_id=<?= $v['id_variante'] ?>" class="btn btn-sm btn-outline-success mt-2" onclick="return confirm('¿Reactivar esta variante?')"> <i class="bi bi-eye"></i> Reactivar </a>

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



    </main>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>