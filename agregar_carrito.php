<?php
session_start();
include 'assets/admin/db.php'; // Incluimos la conexión

// Inicializamos el carrito en la sesión si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// 1. Verificamos que los datos lleguen por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 2. Validamos la entrada (Calidad de Entrada)
    if (
        !isset($_POST['id_variante']) || !filter_var($_POST['id_variante'], FILTER_VALIDATE_INT) ||
        !isset($_POST['cantidad']) || !filter_var($_POST['cantidad'], FILTER_VALIDATE_INT) ||
        $_POST['cantidad'] <= 0
    ) {
        // Datos inválidos o maliciosos
        $_SESSION['mensaje_error'] = "Datos inválidos para agregar al carrito.";
        header("Location: products.php"); // Redirigir a la tienda
        exit;
    }

    $id_variante = (int)$_POST['id_variante'];
    $cantidad_solicitada = (int)$_POST['cantidad'];

    // --- 3. Validación de Calidad (Precio y Stock) ---
    // ¡Nunca confiamos en el cliente! Buscamos los datos reales en la BD.

    $stmt = $conn->prepare("
        SELECT precio, stock, id_producto 
        FROM variantes_producto 
        WHERE id_variante = ?
    ");
    $stmt->bind_param("i", $id_variante);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $variante = $resultado->fetch_assoc();
        
        $precio_real = $variante['precio'];
        $stock_real = $variante['stock'];
        $id_producto_padre = $variante['id_producto'];

        // 4. Verificamos el stock
        if ($cantidad_solicitada > $stock_real) {
            // No hay suficiente stock
            $_SESSION['mensaje_error'] = "No hay suficiente stock para la cantidad solicitada.";
            header("Location: producto.php?id=" . $id_producto_padre); // Devolvemos a la pág. del producto
            exit;
        }

        // --- 5. Lógica para agregar al carrito (Sesión) ---

        // Verificamos si esta variante ya está en el carrito
        if (isset($_SESSION['carrito'][$id_variante])) {
            
            // Si ya está, solo actualizamos la cantidad
            $nueva_cantidad = $_SESSION['carrito'][$id_variante]['cantidad'] + $cantidad_solicitada;
            
            // Verificamos de nuevo el stock total
            if ($nueva_cantidad > $stock_real) {
                $_SESSION['mensaje_error'] = "No puedes agregar más de $stock_real unidades de este producto.";
                header("Location: cart.php"); // Redirigir al carrito
                exit;
            } else {
                $_SESSION['carrito'][$id_variante]['cantidad'] = $nueva_cantidad;
            }

        } else {
            // Si es un producto nuevo, lo agregamos al carrito
            $_SESSION['carrito'][$id_variante] = [
                'id_variante' => $id_variante,
                'cantidad' => $cantidad_solicitada,
                'precio' => $precio_real // <-- Guardamos el precio REAL de la BD
            ];
        }

        $_SESSION['mensaje_exito'] = "Producto agregado al carrito.";
        header("Location: cart.php"); // Redirigimos al carrito para que vea lo que agregó
        exit;

    } else {
        // La variante no existe (ID manipulado)
        $_SESSION['mensaje_error'] = "El producto que intentas agregar no existe.";
        header("Location: products.php");
        exit;
    }

} else {
    // Si no es POST, redirigimos
    header("Location: index.php");
    exit;
}
?>