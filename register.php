<?php
session_start();
include 'assets/admin/db.php';

$mensaje_error = "";
$mensaje_exito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];
    $confirmar = $_POST['confirmar'];

    // Validaciones
    if (empty($usuario) || empty($clave) || empty($confirmar)) {
        $mensaje_error = "Todos los campos son obligatorios.";
    } elseif ($clave !== $confirmar) {
        $mensaje_error = "Las contraseñas no coinciden.";
    } else {
        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $mensaje_error = "El nombre de usuario ya está en uso.";
        } else {
            // Insertar nuevo usuario con rol 'cliente'
            $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
            $rol = "cliente";

            $stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave, rol) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $usuario, $clave_hash, $rol);

            if ($stmt->execute()) {
                $mensaje_exito = "Cuenta creada con éxito. Ahora puedes iniciar sesión.";
            } else {
                $mensaje_error = "Error al registrar usuario: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registrarse | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>
    <?php include 'assets/component/navbar.php'; ?>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center" style="min-height: 100vh; background: linear-gradient(to bottom right, #f5f7fa, #c3cfe2);">
        <div class="login-container" style="max-width: 450px; width: 100%;">
            <div class="card shadow-lg rounded-3">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-plus login-icon" style="font-size: 3rem; color:#0d6efd;"></i>
                        <h3 class="mt-2">Crear cuenta</h3>
                        <p class="text-muted">Regístrate para comprar en Tinkuy</p>
                    </div>

                    <?php if (!empty($mensaje_error)): ?>
                        <div class="alert alert-danger"><?= $mensaje_error ?></div>
                    <?php elseif (!empty($mensaje_exito)): ?>
                        <div class="alert alert-success"><?= $mensaje_exito ?></div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Nombre de usuario</label>
                            <input type="text" name="usuario" class="form-control" placeholder="Elige un usuario" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="clave" class="form-control" placeholder="********" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirmar contraseña</label>
                            <input type="password" name="confirmar" class="form-control" placeholder="********" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Registrarme
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">
                    <p class="text-center mb-0">
                        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'assets/component/footer.php'; ?>
</body>
</html>
