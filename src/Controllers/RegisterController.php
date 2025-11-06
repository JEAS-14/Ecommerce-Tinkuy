<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Core/db.php';
require_once __DIR__ . '/../Core/validaciones.php';

$mensaje_error = "";
$mensaje_exito = "";

// Definimos el ID del rol de cliente (según nuestro script SQL, "cliente" es el 3)
const ID_ROL_CLIENTE = 3;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolección de datos del formulario
    $usuario = trim($_POST['usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $clave_repetida = $_POST['clave_repetida'] ?? '';
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    // --- VALIDACIONES (idénticas a las que había en la versión previa) ---
    if (empty($usuario) || empty($email) || empty($clave) || empty($nombres) || empty($apellidos)) {
        $mensaje_error = "Por favor, completa todos los campos obligatorios.";
    } elseif (strlen($usuario) < 4) {
        $mensaje_error = "Error (ID 17/96): El nombre de usuario debe tener mínimo 4 caracteres.";
    } elseif (strlen($usuario) > 20) {
        $mensaje_error = "Error (ID 18/97): El nombre de usuario debe tener máximo 20 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $usuario)) {
        $mensaje_error = "Error (ID 15): El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.";
    } elseif (strlen($nombres) < 2) {
        $mensaje_error = "Error (ID 66/126): El nombre debe tener mínimo 2 caracteres.";
    } elseif (strlen($nombres) > 50) {
        $mensaje_error = "Error (ID 67/127): El nombre debe tener máximo 50 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $nombres)) {
        $mensaje_error = "Error (ID 68): El nombre solo puede contener letras y espacios.";
    } elseif (strlen($apellidos) < 2) {
        $mensaje_error = "Error (ID 66/126): El apellido debe tener mínimo 2 caracteres.";
    } elseif (strlen($apellidos) > 50) {
        $mensaje_error = "Error (ID 67/127): El apellido debe tener máximo 50 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $apellidos)) {
        $mensaje_error = "Error (ID 68): El apellido solo puede contener letras y espacios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "Error (ID 28/31): El formato del email no es válido.";
    } elseif ($clave !== $clave_repetida) {
        $mensaje_error = "Error (ID 20): Las contraseñas no coinciden.";
    } elseif (strlen($clave) < 7) {
        $mensaje_error = "Error (ID 21/99): La contraseña debe tener mínimo 7 caracteres.";
    } elseif (strlen($clave) > 30) {
        $mensaje_error = "Error (ID 22/100): La contraseña debe tener máximo 30 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $clave)) {
        $mensaje_error = "Error (ID 23): La contraseña debe contener al menos una mayúscula.";
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $clave)) {
        $mensaje_error = "Error (ID 24): La contraseña debe contener al menos un carácter especial.";
    } elseif (trim($clave) === "") {
        $mensaje_error = "Error (ID 26): La contraseña no puede estar vacía o ser solo espacios.";
    } elseif (!empty($telefono) && !preg_match('/^[0-9]{9}$/', $telefono)) {
        $mensaje_error = "Error (ID 36-39): El teléfono debe tener 9 dígitos y contener solo números.";
    } else {
        // Si pasa validaciones, insertar en BD dentro de transacción
        $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

        $conn->begin_transaction();

        try {
            $id_rol_var = ID_ROL_CLIENTE;

            $stmt_usuario = $conn->prepare(
                "INSERT INTO usuarios (id_rol, usuario, email, clave_hash) VALUES (?, ?, ?, ?)"
            );
            $stmt_usuario->bind_param("isss", $id_rol_var, $usuario, $email, $clave_hash);
            $stmt_usuario->execute();

            $nuevo_usuario_id = $conn->insert_id;

            $stmt_perfil = $conn->prepare(
                "INSERT INTO perfiles (id_usuario, nombres, apellidos, telefono) VALUES (?, ?, ?, ?)"
            );
            $telefono_a_insertar = !empty($telefono) ? $telefono : null;
            $stmt_perfil->bind_param("isss", $nuevo_usuario_id, $nombres, $apellidos, $telefono_a_insertar);
            $stmt_perfil->execute();

            $conn->commit();

            // Iniciar sesión automáticamente para mejorar UX: el usuario queda logueado al registrarse
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $nuevo_usuario_id;
            $_SESSION['usuario'] = $usuario;
            // Asumimos que el rol por defecto para registros públicos es 'cliente'
            $_SESSION['rol'] = 'cliente';
            $_SESSION['nombre_usuario'] = $nombres;
            $_SESSION['apellido_usuario'] = $apellidos;
            $_SESSION['mensaje_exito'] = "¡Registro exitoso! Bienvenido, $nombres.";
            // Redirigir al inicio con sesión activa
            header("Location: /Ecommerce-Tinkuy/public/index.php?page=index");
            exit;

        } catch (Exception $e) {
            $conn->rollback();

            // Si hay código de error por duplicado (1062) mostrar mensaje útil
            $codigo_error = $conn->errno;
            if ($codigo_error == 1062) {
                $mensaje_error = "El nombre de usuario o el email ya están registrados.";
            } else {
                $mensaje_error = "Ocurrió un error inesperado al registrarte. Inténtalo de nuevo.";
            }
        }
    }
}

?>
