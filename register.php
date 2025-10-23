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

    // --- Validaciones de Calidad ---
    if (empty($usuario) || empty($email) || empty($clave) || empty($nombres) || empty($apellidos)) {
        $mensaje_error = "Por favor, completa todos los campos obligatorios.";
    } elseif ($clave !== $clave_repetida) {
        $mensaje_error = "Las contraseñas no coinciden.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "El formato del email no es válido.";
    } 
    
    // --- INICIO DE VALIDACIONES DE CALIDAD (NUEVO) ---

    // preg_match devuelve 1 si coincide, 0 si no.
    // Esta regex permite letras (incluyendo acentos y ñ) y espacios.
    elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $nombres)) {
        $mensaje_error = "El nombre solo puede contener letras y espacios.";
    } 
    elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $apellidos)) {
        $mensaje_error = "Los apellidos solo pueden contener letras y espacios.";
    }
    // Esta regex permite letras, números, guiones y guiones bajos.
    elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $usuario)) {
        $mensaje_error = "El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.";
    } 
    // Esta regex permite solo números, espacios y el signo +. (Para teléfonos opcionales)
    elseif (!empty($telefono) && !preg_match('/^[\d\s\+]+$/', $telefono)) {
        $mensaje_error = "El teléfono solo puede contener números, espacios y el signo +.";
    }
    // --- FIN DE VALIDACIONES DE CALIDAD (NUEVO) ---

    else {
        // --- Lógica de Base de Datos (Transacción) ---
        
        $clave_hash = password_hash($clave, PASSWORD_DEFAULT);

        $conn->begin_transaction();

        try {
            // INSERT 1: Crear el usuario en la tabla 'usuarios'
            $stmt_usuario = $conn->prepare(
                "INSERT INTO usuarios (id_rol, usuario, email, clave_hash) VALUES (?, ?, ?, ?)"
            );
            $stmt_usuario->bind_param("isss", ID_ROL_CLIENTE, $usuario, $email, $clave_hash);
            $stmt_usuario->execute();

            // Obtener el ID del usuario que acabamos de crear
            $nuevo_usuario_id = $conn->insert_id;

            // INSERT 2: Crear el perfil en la tabla 'perfiles'
            $stmt_perfil = $conn->prepare(
                "INSERT INTO perfiles (id_usuario, nombres, apellidos, telefono) VALUES (?, ?, ?, ?)"
            );
            // Si el teléfono está vacío, insertamos NULL
            $telefono_a_insertar = !empty($telefono) ? $telefono : NULL;
            $stmt_perfil->bind_param("isss", $nuevo_usuario_id, $nombres, $apellidos, $telefono_a_insertar);
            $stmt_perfil->execute();

            // Si todo salió bien, confirmamos la transacción
            $conn->commit();
            $mensaje_exito = "¡Registro exitoso! Ahora puedes iniciar sesión.";

        } catch (mysqli_sql_exception $e) {
            // Si algo falló (ej: usuario o email duplicado), deshacemos todo
            $conn->rollback();
            
            if ($e->getCode() == 1062) { // 1062 = Error de entrada duplicada
                $mensaje_error = "El nombre de usuario o el email ya están registrados.";
            } else {
                $mensaje_error = "Ocurrió un error en el registro: " . $e->getMessage();
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
                        <div class="alert alert-danger alert-error-animated"><?= htmlspecialchars($mensaje_error) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($mensaje_exito)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombres" class="form-label">Nombres</label>
                                <input type="text" class="form-control" id="nombres" name="nombres" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="apellidos" class="form-label">Apellidos</label>
                                <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="usuario" class="form-label">Nombre de usuario</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono (Opcional)</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="clave" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="clave" name="clave" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clave_repetida" class="form-label">Repetir Contraseña</label>
                                <input type="password" class="form-control" id="clave_repetida" name="clave_repetida" required>
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
</body>
</html>