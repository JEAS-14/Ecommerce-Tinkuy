<?php
// --- Controlador que maneja la lógica del login ---
require_once __DIR__ . '/../../Controllers/AuthController.php';
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
        body { background: linear-gradient(to bottom right, #f5f7fa, #c3cfe2); min-height: 100vh; display: flex; flex-direction: column; }
        .login-container { max-width: 400px; margin: auto; animation: fadeIn 0.6s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .card { border-radius: 15px; }
        .form-control:focus { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25); }
        .login-icon { font-size: 3rem; color: #0d6efd; }
    </style>
</head>

<body>
 <?php 
    // Ruta Navbar Corregida
    include BASE_PATH . '/src/Views/components/navbar.php'; 
    ?>

    <main class="flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="login-container">
            <div class="card shadow-lg">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-circle login-icon"></i>
                        <h3 class="mt-2">Iniciar Sesión</h3>
                        <p class="text-muted">Bienvenido de nuevo.</p>
                    </div>

                    <?php if (!empty($mensaje_error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($mensaje_exito)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Nombre de usuario</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="usuario" name="usuario"
                                       placeholder="Tu usuario" 
                                       required minlength="4" maxlength="20"
                                       pattern="[a-zA-Z0-9_-]+"
                                       title="De 4 a 20 caracteres (letras, números, guiones). (IDs 2, 4, 5)">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="clave" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="clave" name="clave"
                                       placeholder="********" required minlength="7" maxlength="30"
                                       title="De 7 a 30 caracteres. (IDs 8, 9)">
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

    <?php 
    // Ruta Footer Corregida
    include BASE_PATH . '/src/Views/components/footer.php'; 
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.getElementById('usuario').addEventListener('keydown', function(event) {
            const teclasPermitidas = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
            if (teclasPermitidas.includes(event.key)) return;
            if (event.key.length === 1 && !/^[a-zA-Z0-9_-]$/.test(event.key)) event.preventDefault();
        });
    </script>
</body>
</html>
