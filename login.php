<?php
session_start();
// Incluimos la conexión a la BD
include 'assets/admin/db.php';

$mensaje_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];

    // --- INICIO DE VALIDACIÓN DE CALIDAD ---
    if (empty($usuario) || empty($clave)) {
        $mensaje_error = "Por favor, ingresa tu usuario y contraseña.";
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $usuario)) {
        $mensaje_error = "Formato de usuario no válido.";
    }
    // --- FIN DE VALIDACIÓN DE CALIDAD ---
    else {
        // Consulta con JOIN de roles
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
                // Guardamos en sesión los datos básicos
                $_SESSION['usuario_id'] = $id_db;
                $_SESSION['usuario'] = $usuario_db;
                $_SESSION['rol'] = $nombre_rol_db;

                // --- NUEVO: Recuperar nombre y apellido del perfil ---
                $perfil_stmt = $conn->prepare("SELECT nombres, apellidos FROM perfiles WHERE id_usuario = ?");
                $perfil_stmt->bind_param("i", $id_db);
                $perfil_stmt->execute();
                $perfil_result = $perfil_stmt->get_result();

                if ($perfil = $perfil_result->fetch_assoc()) {
                    $_SESSION['nombre_usuario'] = $perfil['nombres'];
                    $_SESSION['apellido_usuario'] = $perfil['apellidos'];
                } else {
                    // Si no tiene perfil, usar el nombre de usuario como respaldo
                    $_SESSION['nombre_usuario'] = $usuario_db;
                    $_SESSION['apellido_usuario'] = '';
                }

                // --- FIN NUEVO BLOQUE ---

                // Redirección según el rol
                if ($nombre_rol_db === 'admin') {
                    header("Location: assets/admin/dashboard.php");
                } elseif ($nombre_rol_db === 'vendedor') {
                    header("Location: assets/vendedor/dashboard.php");
                } else {
                    header("Location: index.php");
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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Iniciar Sesión | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background: linear-gradient(to bottom right, #f5f7fa, #c3cfe2);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .login-container {
            max-width: 400px;
            margin: auto;
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            border-radius: 15px;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
        }

        .login-icon {
            font-size: 3rem;
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <?php include 'assets/component/navbar.php'; ?>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="login-container">
            <div class="card shadow-lg">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-circle login-icon"></i>
                        <h3 class="mt-2">Iniciar Sesión</h3>
                        <p class="text-muted">Bienvenido de nuevo, por favor ingresa tus datos.</p>
                    </div>

                    <?php if (!empty($mensaje_error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Nombre de usuario</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="usuario" name="usuario"
                                    placeholder="Tu usuario" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="clave" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="clave" name="clave"
                                    placeholder="********" required>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="recordar" name="recordar">
                            <label class="form-check-label" for="recordar">Recordarme</label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Ingresar
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="text-decoration-none">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                    </form>

                    <hr class="my-4">
                    <p class="text-center mb-0">
                        ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'assets/component/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>