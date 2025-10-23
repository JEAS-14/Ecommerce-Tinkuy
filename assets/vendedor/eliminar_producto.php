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
    header('Location: ../../login.php'); //
    exit;
}

// 3. Validamos el ID del producto (Calidad de Seguridad)
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['mensaje_error'] = "ID de producto no válido.";
    header('Location: productos.php'); //
    exit;
}

$id_producto = (int)$_GET['id'];
$id_vendedor = $_SESSION['usuario_id'];
$ruta_base_imagenes = "../../assets/img/productos/"; //

// --- INICIO DE CALIDAD (FIABILIDAD ISO 25010) ---

$conn->begin_transaction();

try {
    // 4. Verificamos Propiedad (Calidad de Seguridad) Y Obtenemos nombre de imagen
    // Un vendedor SÓLO puede borrar sus propios productos. Un Admin puede borrar cualquiera.
    
    $sql_permiso = "SELECT imagen_principal FROM productos WHERE id_producto = ?";
    $params = [$id_producto];
    $tipos = "i";

    if ($_SESSION['rol'] === 'vendedor') {
        $sql_permiso .= " AND id_vendedor = ?"; // El vendedor DEBE ser el dueño
        $params[] = $id_vendedor;
        $tipos .= "i";
    }

    $stmt_check = $conn->prepare($sql_permiso);
    $stmt_check->bind_param($tipos, ...$params);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();

    if ($resultado->num_rows === 0) {
        // Si no hay filas, es porque (1) el producto no existe o (2) no tienes permiso
        throw new Exception("Producto no encontrado o no tienes permiso para eliminarlo.");
    }
    
    $fila = $resultado->fetch_assoc();
    $nombre_imagen = $fila['imagen_principal'];

    // 5. Borramos el producto de la Base de Datos
    // (Gracias a ON DELETE CASCADE, esto borrará variantes y calificaciones)
    $stmt_delete = $conn->prepare("DELETE FROM productos WHERE id_producto = ?");
    $stmt_delete->bind_param("i", $id_producto);
    $stmt_delete->execute();

    if ($stmt_delete->affected_rows === 0) {
        throw new Exception("No se pudo eliminar el producto de la base de datos.");
    }
    
    // 6. Borramos el archivo de imagen del servidor (Calidad de Mantenibilidad)
    if (!empty($nombre_imagen) && file_exists($ruta_base_imagenes . $nombre_imagen)) {
        if (!unlink($ruta_base_imagenes . $nombre_imagen)) {
            // Si no se puede borrar el archivo, lanzamos una advertencia pero continuamos (la BD es más importante)
            // En un sistema más complejo, loguearíamos este error.
            error_log("Advertencia: No se pudo eliminar el archivo de imagen: " . $nombre_imagen);
        }
    }

    // 7. COMMIT: Todo salió bien
    $conn->commit();
    $_SESSION['mensaje_exito'] = "Producto eliminado correctamente (junto con sus variantes e imagen).";

} catch (Exception $e) {
    // 8. ROLLBACK: Algo falló
    $conn->rollback();
    $_SESSION['mensaje_error'] = "Error al eliminar el producto: " . $e->getMessage();
}

// 9. Redirigimos de vuelta a la lista de productos
header('Location: productos.php'); //
exit;

?>