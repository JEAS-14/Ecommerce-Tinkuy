<?php
session_start();
include 'assets/admin/db.php'; // Incluimos la conexión

$mensaje_error = "";
$mensaje_exito = "";

// Variables para "recordar" los datos del formulario (Usabilidad)
$nombre = "";
$email = "";
$asunto = "";
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recolectar y limpiar datos
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $asunto = trim($_POST['asunto']);
    $mensaje = trim($_POST['mensaje']);

    // --- 2. Validaciones de Calidad (Seguridad) ---
    if (empty($nombre) || empty($email) || empty($asunto) || empty($mensaje)) {
        $mensaje_error = "Por favor, completa todos los campos del formulario.";
    } 
    // Valida que el email sea un email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "El formato del email no es válido.";
    } 
    // Valida que el nombre solo tenga letras y espacios
    elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $nombre)) {
        $mensaje_error = "El nombre solo puede contener letras y espacios.";
    }
    // Valida que el asunto no tenga caracteres extraños (prevención simple)
    elseif (!preg_match('/^[a-zA-Z0-9\sñáéíóúÁÉÍÓÚ\.,¿?¡!]+$/u', $asunto)) {
        $mensaje_error = "El asunto contiene caracteres no permitidos.";
    } 
    // Limita la longitud del mensaje (Fiabilidad)
    elseif (strlen($mensaje) < 10) {
        $mensaje_error = "El mensaje es demasiado corto.";
    }
    // --- Fin de Validaciones ---

    else {
        // 3. Procesar el formulario (Guardar en la BD)
        try {
            $stmt = $conn->prepare(
                "INSERT INTO mensajes_contacto (nombre, email, asunto, mensaje) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssss", $nombre, $email, $asunto, $mensaje);
            $stmt->execute();

            // Si se guarda, mostramos éxito y limpiamos el formulario
            $mensaje_exito = "¡Gracias por tu mensaje! Te responderemos pronto.";
            $nombre = "";
            $email = "";
            $asunto = "";
            $mensaje = "";

        } catch (mysqli_sql_exception $e) {
            $mensaje_error = "Ocurrió un error al enviar tu mensaje. Inténtalo de nuevo.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Contacto | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        /* (Reutilizamos la animación de "sacudida" que ya debes tener en tu CSS) */
        @keyframes shake-horizontal { 0%, 100% { transform: translateX(0); } 10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); } 20%, 40%, 60%, 80% { transform: translateX(5px); } }
        .alert-error-animated { animation: shake-horizontal 0.5s ease-in-out; }
    </style>
</head>
<body>
    <?php include 'assets/component/navbar.php'; // ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5">
                    <i class="bi bi-envelope-heart-fill" style="font-size: 4rem; color: #0d6efd;"></i>
                    <h1 class="mt-2">Contáctanos</h1>
                    <p class="lead text-muted">¿Tienes preguntas? Estamos aquí para ayudarte.</p>
                </div>

                <div class="card shadow-lg border-0">
                    <div class="card-body p-4 p-md-5">

                        <?php if (!empty($mensaje_error)): ?>
                            <div class="alert alert-danger alert-error-animated"><?= htmlspecialchars($mensaje_error) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($mensaje_exito)): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
                        <?php endif; ?>

                        <form action="contact.php" method="POST" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Tu Nombre</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Tu Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="asunto" class="form-label">Asunto</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lightbulb-fill"></i></span>
                                    <input type="text" class="form-control" id="asunto" name="asunto" value="<?= htmlspecialchars($asunto) ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="mensaje" class="form-label">Mensaje</label>
                                <textarea class="form-control" id="mensaje" name="mensaje" rows="6" required><?= htmlspecialchars($mensaje) ?></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send-fill"></i> Enviar Mensaje
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'assets/component/footer.php'; // ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>