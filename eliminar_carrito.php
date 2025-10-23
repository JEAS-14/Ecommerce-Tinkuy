<?php
session_start();

// 1. Verificamos que el ID de la variante a eliminar venga por la URL (GET)
// y que sea un número entero (Validación de Calidad)
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    
    $id_variante = (int)$_GET['id'];
    
    // 2. Verificamos si esa variante SÍ existe en el carrito (sesión)
    if (isset($_SESSION['carrito'][$id_variante])) {
        
        // 3. La eliminamos del array de la sesión
        unset($_SESSION['carrito'][$id_variante]);
        
        // 4. (Opcional) Creamos un mensaje de éxito
        $_SESSION['mensaje_exito'] = "Producto eliminado del carrito.";
    } else {
        // (Opcional) Mensaje de error si intentan borrar algo que no está
        $_SESSION['mensaje_error'] = "El producto no se pudo encontrar en tu carrito.";
    }

} else {
    // (Opcional) Mensaje de error si el ID es inválido
    $_SESSION['mensaje_error'] = "ID de producto no válido.";
}

// 5. Pase lo que pase, redirigimos al usuario de vuelta a la página del carrito
header("Location: cart.php");
exit;
?>