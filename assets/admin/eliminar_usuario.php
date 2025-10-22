<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// ID del usuario a eliminar
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener datos del usuario a eliminar
$stmt = $conn->prepare("SELECT usuario FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $usuario = $resultado->fetch_assoc()['usuario'];

    // Verificamos que no sea el admin logueado
    if ($usuario === $_SESSION['admin']) {
        // No puedes eliminarte a ti mismo
        $_SESSION['mensaje_error'] = "No puedes eliminar tu propia cuenta.";
    } else {
        // Proceder a eliminar
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Usuario eliminado correctamente.";
        } else {
            $_SESSION['mensaje_error'] = "Ocurrió un error al eliminar.";
        }
    }
} else {
    $_SESSION['mensaje_error'] = "Usuario no encontrado.";
}

header("Location: usuarios.php");
exit();
