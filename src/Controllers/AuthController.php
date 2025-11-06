<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Core/db.php';
require_once __DIR__ . '/../Core/validaciones.php';

$mensaje_error = "";
$mensaje_exito = "";

// Si ya hay sesión activa
if (isset($_SESSION['usuario_id'])) {
    // usar ruta absoluta dentro del proyecto para evitar ambigüedades con rutas relativas
    header("Location: /Ecommerce-Tinkuy/public/index.php?page=index");
    exit;
}

// Si viene mensaje desde registro
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];

    $mensaje_error = validarDatosLogin($usuario, $clave);

    if (is_null($mensaje_error)) {
        $query = "SELECT u.id_usuario, u.usuario, u.clave_hash, r.nombre_rol 
                  FROM usuarios AS u
                  JOIN roles AS r ON u.id_rol = r.id_rol
                  WHERE u.usuario = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id_db, $usuario_db, $clave_hash_db, $nombre_rol_db);
            $stmt->fetch();

            if (password_verify($clave, $clave_hash_db)) {
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $id_db;
                $_SESSION['usuario'] = $usuario_db;
                $_SESSION['rol'] = $nombre_rol_db;

                $perfil_stmt = $conn->prepare("SELECT nombres, apellidos FROM perfiles WHERE id_usuario = ?");
                $perfil_stmt->bind_param("i", $id_db);
                $perfil_stmt->execute();
                $perfil_result = $perfil_stmt->get_result();

                if ($perfil = $perfil_result->fetch_assoc()) {
                    $_SESSION['nombre_usuario'] = $perfil['nombres'];
                    $_SESSION['apellido_usuario'] = $perfil['apellidos'];
                } else {
                    $_SESSION['nombre_usuario'] = $usuario_db;
                    $_SESSION['apellido_usuario'] = '';
                }
                $perfil_stmt->close();

                // Redirecciones explícitas con rutas absolutas en el host
                if (strtolower($nombre_rol_db) === 'admin') {
                    // Redirigir al nuevo dashboard MVC del admin
                    header("Location: /Ecommerce-Tinkuy/public/index.php?page=admin_dashboard");
                } elseif (strtolower($nombre_rol_db) === 'vendedor') {
                    // Redirigir al nuevo dashboard MVC del vendedor
                    header("Location: /Ecommerce-Tinkuy/public/index.php?page=vendedor_dashboard");
                } else {
                    header("Location: /Ecommerce-Tinkuy/public/index.php?page=index");
                }
                exit;
            } else {
                $mensaje_error = "Usuario o contraseña incorrectos.";
            }
        } else {
            $mensaje_error = "Usuario o contraseña incorrectos.";
        }

        $stmt->close();
    }
}
