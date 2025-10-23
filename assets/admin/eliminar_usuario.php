<?php
session_start();
include 'db.php'; //

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); //
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    header('Location: ../../login.php'); //
    exit;
}

// 1. Validamos el ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['mensaje_error'] = "ID de usuario no válido.";
    header('Location: usuarios.php'); //
    exit;
}
$id_usuario_a_eliminar = (int)$_GET['id'];

// 2. Seguridad Crítica: Un admin NO puede eliminarse a sí mismo.
if ($id_usuario_a_eliminar === $_SESSION['usuario_id']) {
    $_SESSION['mensaje_error'] = "Error: No puedes eliminar tu propia cuenta de administrador.";
    header('Location: usuarios.php'); //
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---


// --- INICIO DE CALIDAD (FIABILIDAD ISO 25010) ---
try {
    // 3. Intentamos eliminar el usuario.
    // NOTA: La BD (ON DELETE CASCADE) borrará 'perfiles' y 'direcciones'.
    // NOTA: La BD (ON DELETE RESTRICT) fallará si el usuario tiene 'pedidos' o 'productos'.
    
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario_a_eliminar);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['mensaje_exito'] = "Usuario ID #$id_usuario_a_eliminar eliminado correctamente.";
    } else {
        throw new Exception("El usuario no se encontró o no se pudo eliminar.");
    }
    
} catch (mysqli_sql_exception $e) {
    // 4. Manejo de Errores (Fiabilidad)
    // Código 1451: Error de llave foránea (RESTRICT)
    if ($e->getCode() == 1451) {
        $_SESSION['mensaje_error'] = "Error: No se puede eliminar el usuario ID #$id_usuario_a_eliminar porque tiene pedidos o productos asociados. Primero debe reasignarlos o eliminarlos.";
    } else {
        $_SESSION['mensaje_error'] = "Error de base de datos: " . $e->getMessage();
    }
}

// 5. Redirigimos de vuelta a la lista
header('Location: usuarios.php'); //
exit;
?>