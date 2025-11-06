<?php
session_start();
include 'db.php'; // Estamos en la carpeta 'admin', db.php está aquí

$mensaje_error = "";
$mensaje_exito = "";

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); //
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    session_destroy();
    header('Location: ../../login.php'); //
    exit;
}

// 1. Validamos el ID del producto (Seguridad)
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['mensaje_error'] = "ID de producto no válido.";
    header('Location: productos_admin.php'); //
    exit;
}
$id_producto = (int)$_GET['id'];
$ruta_base_imagenes = "../../assets/img/productos/"; //

// --- INICIO DE CALIDAD (FIABILIDAD ISO 25010) ---

$conn->begin_transaction();

try {
    // 2. Obtenemos el nombre de la imagen para borrarla después
    $stmt_check = $conn->prepare("SELECT imagen_principal FROM productos WHERE id_producto = ?");
    $stmt_check->bind_param("i", $id_producto);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception("Producto no encontrado.");
    }
    
    $fila = $resultado->fetch_assoc();
    $nombre_imagen = $fila['imagen_principal'];

    // 3. Borramos el producto de la Base de Datos (Admin puede borrar CUALQUIERA)
    // (ON DELETE CASCADE borrará variantes y calificaciones)
    $stmt_delete = $conn->prepare("DELETE FROM productos WHERE id_producto = ?");
    $stmt_delete->bind_param("i", $id_producto);
    $stmt_delete->execute();

    if ($stmt_delete->affected_rows === 0) {
        throw new Exception("No se pudo eliminar el producto de la base de datos.");
    }
    
    // 4. Borramos el archivo de imagen del servidor (Calidad de Mantenibilidad)
    if (!empty($nombre_imagen) && file_exists($ruta_base_imagenes . $nombre_imagen)) {
        if (!unlink($ruta_base_imagenes . $nombre_imagen)) {
             error_log("Advertencia: No se pudo eliminar el archivo de imagen: " . $nombre_imagen);
        }
    }

    // 5. COMMIT: Todo salió bien
    $conn->commit();
    $_SESSION['mensaje_exito'] = "Producto ID #$id_producto eliminado correctamente por el administrador.";

} catch (Exception $e) {
    // 6. ROLLBACK: Algo falló
    $conn->rollback();
     // Código 1451: Error de llave foránea (si el producto está en un pedido)
    if ($e->getCode() == 1451) {
         $_SESSION['mensaje_error'] = "Error: No se puede eliminar el producto ID #$id_producto porque está asociado a pedidos existentes.";
    } else {
        $_SESSION['mensaje_error'] = "Error al eliminar el producto: " . $e->getMessage();
    }
}

// 7. Redirigimos de vuelta a la lista de productos del admin
header('Location: productos_admin.php'); //
exit;

?>