<?php
// --- Controlador que maneja la lógica del registro ---
require_once __DIR__ . '/../../Controllers/RegisterController.php';
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
  <?php 
    // Ruta Navbar Corregida
    include BASE_PATH . '/src/Views/components/navbar.php'; 
    ?>

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

     <?php 
    // Ruta Footer Corregida
    include BASE_PATH . '/src/Views/components/footer.php'; 
    ?>

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