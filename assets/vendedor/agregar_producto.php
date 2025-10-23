<?php
session_start();
include '../admin/db.php'; // Subimos un nivel para encontrar db.php

$mensaje_error = "";
$mensaje_exito = "";

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
// 1. Verificamos si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); // Redirigimos al login
    exit;
}

// 2. Verificamos que el ROL sea 'vendedor' o 'admin'
if ($_SESSION['rol'] !== 'vendedor' && $_SESSION['rol'] !== 'admin') {
    // Si no es vendedor o admin, no tiene nada que hacer aquí
    session_destroy();
    header('Location: ../../login.php'); //
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

$id_vendedor = $_SESSION['usuario_id']; // El producto se asignará a este vendedor

// Lógica para procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- 3. Recolectar datos del producto (Contenedor) ---
    $nombre_producto = trim($_POST['nombre_producto']);
    $descripcion = trim($_POST['descripcion']);
    $id_categoria = (int)$_POST['id_categoria'];
    
    // --- 4. Recolectar datos de la PRIMERA VARIANTE ---
    $talla = trim($_POST['talla']);
    $color = trim($_POST['color']);
    $precio = filter_var(trim($_POST['precio']), FILTER_VALIDATE_FLOAT);
    $stock = filter_var(trim($_POST['stock']), FILTER_VALIDATE_INT);

    // --- 5. Lógica de Subida de Imagen (Calidad de Seguridad) ---
    $nombre_imagen = "";
    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
        
        $directorio_destino = "../../assets/img/productos/"; // Carpeta de destino (SUBIR 2 NIVELES)
        //
        
        // Creamos un nombre de archivo único para evitar sobreescribir (Calidad)
        $extension = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
        $nombre_imagen = uniqid('prod_') . '.' . $extension;
        $ruta_destino = $directorio_destino . $nombre_imagen;

        // Validaciones de Calidad de la imagen
        $mime_type = $_FILES['imagen_principal']['type'];
        $tamano_archivo = $_FILES['imagen_principal']['size']; // en bytes
        
        if (!in_array($mime_type, ['image/jpeg', 'image/png', 'image/webp'])) {
            $mensaje_error = "Error: Solo se permiten imágenes JPG, PNG o WEBP.";
        } elseif ($tamano_archivo > 2 * 1024 * 1024) { // 2MB Límite
            $mensaje_error = "Error: La imagen no puede pesar más de 2MB.";
        } elseif (!move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $ruta_destino)) {
            $mensaje_error = "Error al mover el archivo de imagen.";
        }
        
    } else {
        $mensaje_error = "Error: La imagen principal es obligatoria.";
    }

    // --- 6. Validaciones de Datos ---
    if (empty($mensaje_error)) { // Si no hubo errores con la imagen...
        if (empty($nombre_producto) || $id_categoria === 0 || $precio === false || $stock === false) {
            $mensaje_error = "Por favor, completa todos los campos obligatorios.";
        } elseif ($precio <= 0 || $stock < 0) {
            $mensaje_error = "El precio y el stock deben ser números positivos.";
        }
    }

    // --- 7. Lógica de BD (Fiabilidad ISO 25010 - Transacción) ---
    if (empty($mensaje_error)) {
        
        $conn->begin_transaction();
        
        try {
            // INSERT 1: Crear el Producto (Contenedor)
            $stmt_producto = $conn->prepare(
                "INSERT INTO productos (id_categoria, id_vendedor, nombre_producto, descripcion, imagen_principal) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt_producto->bind_param("iisss", $id_categoria, $id_vendedor, $nombre_producto, $descripcion, $nombre_imagen);
            $stmt_producto->execute();
            
            // Obtenemos el ID del producto que acabamos de crear
            $nuevo_producto_id = $conn->insert_id;

            // INSERT 2: Crear la Primera Variante
            $sku_simulado = strtoupper(substr($nombre_producto, 0, 3)) . '-' . $nuevo_producto_id . '-' . $talla . '-' . $color;
            $stmt_variante = $conn->prepare(
                "INSERT INTO variantes_producto (id_producto, talla, color, sku, precio, stock) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt_variante->bind_param("isssdi", $nuevo_producto_id, $talla, $color, $sku_simulado, $precio, $stock);
            $stmt_variante->execute();

            // COMMIT: Todo salió bien
            $conn->commit();
            $mensaje_exito = "¡Producto y su primera variante creados con éxito!";
            
        } catch (mysqli_sql_exception $e) {
            // ROLLBACK: Algo falló
            $conn->rollback();
            $mensaje_error = "Error al guardar en la base de datos: " . $e->getMessage();
            // (Si falla, borramos la imagen que se subió)
            if (file_exists($ruta_destino)) {
                unlink($ruta_destino);
            }
        }
    }
}

// --- Lógica GET (Calidad): Cargar las categorías para el <select> ---
$resultado_categorias = $conn->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC");
$categorias = $resultado_categorias->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto - Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Vendedor</a> <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="productos.php">Mis Productos</a></li> <li class="nav-item"><a class="nav-link active" href="agregar_producto.php">Agregar Producto</a></li> </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión</a></li> </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h2>Agregar Nuevo Producto</h2>
                <p>Crea el producto general y su primera variante (talla, color, precio y stock).</p>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        
                        <?php if (!empty($mensaje_error)): ?>
                            <div class="alert alert-danger alert-error-animated"><?= htmlspecialchars($mensaje_error) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($mensaje_exito)): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" novalidate>
                            
                            <h5 class="mt-3">1. Información General del Producto</h5>
                            <hr>
                            <div class="mb-3">
                                <label for="nombre_producto" class="form-label">Nombre del Producto</label>
                                <input type="text" class="form-control" id="nombre_producto" name="nombre_producto" required>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_categoria" class="form-label">Categoría</label>
                                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                                        <option value="" disabled selected>Elige una categoría...</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre_categoria']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="imagen_principal" class="form-label">Imagen Principal</label>
                                    <input type="file" class="form-control" id="imagen_principal" name="imagen_principal" accept="image/jpeg, image/png, image/webp" required>
                                </div>
                            </div>

                            <h5 class="mt-4">2. Primera Variante (Inventario)</h5>
                            <p class="text-muted">Crearás el producto con esta primera variante. Luego podrás agregar más.</p>
                            <hr>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="talla" class="form-label">Talla</label>
                                    <input type="text" class="form-control" id="talla" name="talla" placeholder="Ej: M, L, Única" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <input type="text" class="form-control" id="color" name="color" placeholder="Ej: Rojo, Azul, Multicolor" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="precio" class="form-label">Precio (S/)</label>
                                    <input type="number" step="0.01" class="form-control" id="precio" name="precio" placeholder="Ej: 150.00" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="stock" class="form-label">Stock (Cantidad)</label>
                                    <input type="number" class="form-control" id="stock" name="stock" placeholder="Ej: 10" required>
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-plus-circle"></i> Crear Producto y Variante
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>