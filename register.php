<?php
session_start();
include 'assets/admin/db.php'; 

$mensaje_error = "";
$mensaje_exito = "";

// Definimos el ID del rol de cliente (según nuestro script SQL, "cliente" es el 3)
const ID_ROL_CLIENTE = 3; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Recolección de datos del formulario (usamos trim para limpiar espacios) ---
    $usuario = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $clave = $_POST['clave']; // La clave no se "trimea" por si el usuario quiere espacios
    $clave_repetida = $_POST['clave_repetida'];
    $nombres = trim($_POST['nombres']);
    $apellidos = trim($_POST['apellidos']);
    $telefono = trim($_POST['telefono']); // Opcional

    // --- 1. VALIDACIÓN DE BACKEND (SEGURIDAD) ---
    // Este bloque comprueba todo, incluso si el usuario deshabilita el HTML/JS.

    // 1. Campos Vacíos (IDs: 16, 25, 32, 69)
    if (empty($usuario) || empty($email) || empty($clave) || empty($nombres) || empty($apellidos)) {
        $mensaje_error = "Por favor, completa todos los campos obligatorios.";
    
    // 2. Nombre de usuario (Alias) (IDs: 15, 17, 18, 96, 97)
    } elseif (strlen($usuario) < 4) {
        $mensaje_error = "Error (ID 17/96): El nombre de usuario debe tener mínimo 4 caracteres.";
    } elseif (strlen($usuario) > 20) {
        $mensaje_error = "Error (ID 18/97): El nombre de usuario debe tener máximo 20 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $usuario)) {
        $mensaje_error = "Error (ID 15): El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.";
    
    // 3. Nombres (Persona) (IDs: 66, 67, 68, 126, 127)
    } elseif (strlen($nombres) < 2) {
        $mensaje_error = "Error (ID 66/126): El nombre debe tener mínimo 2 caracteres.";
    } elseif (strlen($nombres) > 50) {
        $mensaje_error = "Error (ID 67/127): El nombre debe tener máximo 50 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $nombres)) {
        $mensaje_error = "Error (ID 68): El nombre solo puede contener letras y espacios.";
        
    // 4. Apellidos (Persona) (IDs: 66, 67, 68, 126, 127)
    } elseif (strlen($apellidos) < 2) {
        $mensaje_error = "Error (ID 66/126): El apellido debe tener mínimo 2 caracteres.";
    } elseif (strlen($apellidos) > 50) {
        $mensaje_error = "Error (ID 67/127): El apellido debe tener máximo 50 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $apellidos)) {
        $mensaje_error = "Error (ID 68): El apellido solo puede contener letras y espacios.";

    // 5. Correo (IDs: 28, 29, 30)
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "Error (ID 28/31): El formato del email no es válido.";
    
    // 6. Contraseña (IDs: 20, 21, 22, 23, 24, 26, 99, 100)
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

    // 7. Teléfono (Opcional) (IDs: 35, 36, 37, 38, 39)
    } elseif (!empty($telefono) && !preg_match('/^[0-9]{9}$/', $telefono)) {
        $mensaje_error = "Error (ID 36-39): El teléfono debe tener 9 dígitos y contener solo números.";
    }
    
    // --- FIN DE VALIDACIONES ---
    
    else {
        // --- Lógica de Base de Datos (Transacción) ---
        // Si todas las validaciones pasan, se ejecuta esto.
        
        $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

        $conn->begin_transaction();

        try {
            $id_rol_var = ID_ROL_CLIENTE;

            // INSERT 1: Crear el usuario en la tabla 'usuarios'
            $stmt_usuario = $conn->prepare(
                "INSERT INTO usuarios (id_rol, usuario, email, clave_hash) VALUES (?, ?, ?, ?)"
            );
            $stmt_usuario->bind_param("isss", $id_rol_var, $usuario, $email, $clave_hash); 
            $stmt_usuario->execute();

            $nuevo_usuario_id = $conn->insert_id;

            // INSERT 2: Crear el perfil en la tabla 'perfiles'
            $stmt_perfil = $conn->prepare(
                "INSERT INTO perfiles (id_usuario, nombres, apellidos, telefono) VALUES (?, ?, ?, ?)"
            );
            $telefono_a_insertar = !empty($telefono) ? $telefono : NULL;
            $stmt_perfil->bind_param("isss", $nuevo_usuario_id, $nombres, $apellidos, $telefono_a_insertar);
            $stmt_perfil->execute();

            $conn->commit();
            
            $_SESSION['mensaje_exito'] = "¡Registro exitoso! Ahora puedes iniciar sesión.";
            header("Location: login.php");
            exit;

        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            
            if ($e->getCode() == 1062) { // Error de entrada duplicada
                $mensaje_error = "El nombre de usuario o el email ya están registrados.";
            } else {
                $mensaje_error = "Ocurrió un error inesperado al registrarte. Inténtalo de nuevo.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body { background: linear-gradient(to bottom right, #f5f7fa, #c3cfe2); min-height: 100vh; display: flex; flex-direction: column; }
        .register-container { max-width: 500px; margin: 3rem auto; }
        .card { border-radius: 15px; }
    </style>
</head>

<body>
    <?php include 'assets/component/navbar.php'; ?>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="register-container">
            <div class="card shadow-lg">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus-fill" style="font-size: 3rem; color: #0d6efd;"></i>
                        <h3 class="mt-2">Crear Cuenta</h3>
                        <p class="text-muted">Regístrate para empezar a comprar.</p>
                    </div>

                    <?php if (!empty($mensaje_error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($mensaje_exito)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
                    <?php endif; ?>

                    <form method="POST"> <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombres" class="form-label">Nombres</label>
                                <input type="text" class="form-control" id="nombres" name="nombres" 
                                       value="<?= htmlspecialchars($_POST['nombres'] ?? '') ?>" 
                                       required 
                                       minlength="2" 
                                       maxlength="50"
                                       pattern="[a-zA-Z\sñáéíóúÁÉÍÓÚ]+"
                                       title="Solo letras y espacios, mín. 2 caracteres. (IDs 66, 68)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="apellidos" class="form-label">Apellidos</label>
                                <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                       value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>" 
                                       required
                                       minlength="2" 
                                       maxlength="50"
                                       pattern="[a-zA-Z\sñáéíóúÁÉÍÓÚ]+"
                                       title="Solo letras y espacios, mín. 2 caracteres. (IDs 66, 68)">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="usuario" class="form-label">Nombre de usuario</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" 
                                   value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>" 
                                   required
                                   minlength="4"
                                   maxlength="20"
                                   pattern="[a-zA-Z0-9_-]+"
                                   title="De 4 a 20 caracteres. Solo letras, números, guión y guión bajo. (IDs 15, 17, 18)">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                   required
                                   title="Ingresa un correo válido. (ID 28)">
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono (Opcional)</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                                   pattern="[0-9]{9}"
                                   maxlength="9"
                                   title="Debe tener 9 dígitos (solo números). (IDs 36-39)">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="clave" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="clave" name="clave" 
                                       required
                                       minlength="7"
                                       maxlength="30"
                                       title="De 7 a 30 caracteres. (IDs 21, 22)">
                                </div>
                            <div class="col-md-6 mb-3">
                                <label for="clave_repetida" class="form-label">Repetir Contraseña</label>
                                <input type="password" class="form-control" id="clave_repetida" name="clave_repetida" 
                                       required
                                       minlength="7"
                                       maxlength="30"
                                       title="Debe coincidir con la contraseña. (ID 20)">
                            </div>
                        </div>

                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-lg"></i> Registrarme
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">
                    <p class="text-center mb-0">
                        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'assets/component/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Función genérica para prevenir teclas no permitidas
        function limitarEntrada(elemento, regex) {
            elemento.addEventListener('keydown', function(event) {
                const teclasPermitidas = [
                    'Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'
                ];
                
                // Si la tecla es una tecla de control, permitirla
                if (teclasPermitidas.includes(event.key)) {
                    return;
                }
                
                // Si la tecla es de un solo caracter (ej. "a", "1", "#")
                // y NO cumple con la regex, prevenir la acción.
                if (event.key.length === 1 && !regex.test(event.key)) {
                    event.preventDefault(); // <-- "No permitir la acción"
                }
            });
        }

        // Aplicar filtros (Usabilidad)
        
        // IDs 68 (Nombres y Apellidos): Solo letras y espacios
        const regexLetrasEspacios = /^[a-zA-Z\sñáéíóúÁÉÍÓÚ]$/u;
        limitarEntrada(document.getElementById('nombres'), regexLetrasEspacios);
        limitarEntrada(document.getElementById('apellidos'), regexLetrasEspacios);

        // IDs 38, 39 (Teléfono): Solo números
        const regexNumeros = /^[0-9]$/;
        limitarEntrada(document.getElementById('telefono'), regexNumeros);

        // ID 15 (Usuario): Letras, números, guión y guión bajo
        const regexUsuario = /^[a-zA-Z0-9_-]$/;
        limitarEntrada(document.getElementById('usuario'), regexUsuario);

    </script>
</body>
</html>