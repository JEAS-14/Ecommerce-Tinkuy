<?php
session_start();
include 'db.php'; // Estamos en la carpeta 'admin', db.php está aquí

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
// --- FIN DE CALIDAD (SEGURIDAD) ---

$mensaje_error = "";
$mensaje_exito = "";

// --- LÓGICA POST (Calidad de Fiabilidad - Transacción) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- INICIO DE VALIDACIÓN MEJORADA ---
    $usuario_raw = $_POST['usuario'] ?? null;
    $email_raw = $_POST['email'] ?? null;
    $clave = $_POST['clave'] ?? null; 
    $id_rol = isset($_POST['id_rol']) ? (int)$_POST['id_rol'] : 0;
    $nombres_raw = $_POST['nombres'] ?? null;
    $apellidos_raw = $_POST['apellidos'] ?? null;
    $telefono_raw = $_POST['telefono'] ?? ''; 

    $usuario = is_string($usuario_raw) ? trim($usuario_raw) : null;
    $email = is_string($email_raw) ? trim($email_raw) : null;
    $nombres = is_string($nombres_raw) ? trim($nombres_raw) : null;
    $apellidos = is_string($apellidos_raw) ? trim($apellidos_raw) : null;
    $telefono = is_string($telefono_raw) ? trim($telefono_raw) : '';
    
    // Validaciones de Calidad
    if ($usuario === null || $email === null || $clave === null || $id_rol === 0 || $nombres === null || $apellidos === null || 
        $usuario === '' || $email === '' || $clave === '' || $nombres === '' || $apellidos === '') {
        $mensaje_error = "Todos los campos (excepto teléfono) son obligatorios.";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "El formato del email no es válido.";
    } elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $nombres) || !preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $apellidos)) {
        $mensaje_error = "El nombre y apellido solo pueden contener letras y espacios.";
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $usuario)) {
        $mensaje_error = "El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.";
    } elseif (!empty($telefono) && !preg_match('/^[\d\s\+]+$/', $telefono)) {
        $mensaje_error = "El teléfono solo puede contener números, espacios y el signo +.";
    }
    // --- FIN DE VALIDACIÓN MEJORADA ---
    
    else {
        // --- Lógica de Base de Datos (Transacción) ---
        $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
        
        $conn->begin_transaction();
        try {
            // INSERT 1: Crear el usuario en 'usuarios'
            $stmt_usuario = $conn->prepare(
                "INSERT INTO usuarios (id_rol, usuario, email, clave_hash) VALUES (?, ?, ?, ?)"
            );

            // --- INICIO DE CORRECCIÓN (DEPURACIÓN) ---
            // Verificamos si prepare() falló y mostramos el error de MySQL
            if ($stmt_usuario === false) {
                 // Usamos $conn->error para ver el mensaje de MySQL
                throw new mysqli_sql_exception("Error al preparar la consulta de usuarios: " . $conn->error);
            }
            // --- FIN DE CORRECCIÓN (DEPURACIÓN) ---

            // Aseguramos pasar el $email validado y no nulo
            $stmt_usuario->bind_param("isss", $id_rol, $usuario, $email, $clave_hash); // <-- Esta es la línea 57
            $stmt_usuario->execute(); 
            
            $nuevo_usuario_id = $conn->insert_id;

            // INSERT 2: Crear el perfil en 'perfiles'
            $stmt_perfil = $conn->prepare(
                "INSERT INTO perfiles (id_usuario, nombres, apellidos, telefono) VALUES (?, ?, ?, ?)"
            );
             if ($stmt_perfil === false) { // Verificación adicional
                 throw new mysqli_sql_exception("Error al preparar la consulta de perfiles: " . $conn->error);
            }
            $telefono_a_insertar = !empty($telefono) ? $telefono : NULL;
            $stmt_perfil->bind_param("isss", $nuevo_usuario_id, $nombres, $apellidos, $telefono_a_insertar);
            $stmt_perfil->execute();

            // COMMIT: Todo salió bien
            $conn->commit();
            $mensaje_exito = "¡Usuario ($usuario) creado exitosamente!";
            
        } catch (mysqli_sql_exception $e) {
            $conn->rollback(); // Deshacemos todo
            if ($e->getCode() == 1062) { // Error de entrada duplicada
                $mensaje_error = "El nombre de usuario o el email ya están registrados.";
            } else {
                // Mostramos el código de error para depuración
                $mensaje_error = "Error al crear el usuario (Code: {$e->getCode()}): " . $e->getMessage(); 
            }
        }
    }
}

// --- LÓGICA GET (Calidad de Funcionalidad) ---
// (Cargamos los roles para el <select> - esto queda igual)
$resultado_roles = $conn->query("SELECT * FROM roles ORDER BY nombre_rol");
$roles = $resultado_roles->fetch_all(MYSQLI_ASSOC);
?>

<?php
// --- El resto del archivo (<!DOCTYPE html>...) sigue igual ---
?>