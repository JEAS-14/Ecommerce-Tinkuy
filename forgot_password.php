<?php
session_start();
include 'assets/admin/db.php';
// Asegúrate que BASE_URL está definida, por ejemplo: define('BASE_URL', 'http://tu-sitio.com');
include 'assets/admin/mailer_config.php'; 

$mensaje = '';
$tipo_mensaje = 'info'; // Para cambiar el color del alert

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // --- CORRECCIÓN: Añadir validación de campo vacío (ID 32) ---
    if (empty($email)) {
        $mensaje = "Error (ID 32): El campo correo es requerido.";
        $tipo_mensaje = 'danger';
    // --- FIN CORRECCIÓN ---

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Error (ID 28): Por favor, ingresa un formato de correo válido.";
        $tipo_mensaje = 'danger';
    } else {
        // (El resto de tu lógica PHP es excelente y se mantiene igual)
        $query = "SELECT id_usuario FROM usuarios WHERE email = ? LIMIT 1"; // No necesitas traer el email de nuevo
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            // Generar token seguro
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expiración en 1 hora

            try {
                $conn->begin_transaction();

                // Borrar tokens anteriores para ese email
                $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $del->bind_param("s", $email);
                $del->execute();

                // Guardar nuevo token hash en BD
                $insert = $conn->prepare("INSERT INTO password_resets (email, token_hash, expiracion) VALUES (?, ?, ?)");
                $insert->bind_param("sss", $email, $token_hash, $expiracion);
                $insert->execute();

                $conn->commit(); // Confirmar transacción solo si todo va bien

                // Enlace de recuperación (Usa el token original, no el hash)
                // Asegúrate que BASE_URL está definida correctamente en algún config
                if (!defined('BASE_URL')) define('BASE_URL', 'http://localhost/tu_proyecto'); // Ejemplo, ajusta esto
                
                $reset_link = BASE_URL . "/reset_password.php?token=" . $token;
                $asunto = "Restablece tu contraseña | Tinkuy";
                
                // (Tu código HTML del correo es muy bueno)
                $body_html = '... (tu HTML del correo aquí) ...'; // Lo omito por brevedad

                // Intentar enviar el correo
                if (send_mail($email, $asunto, $body_html)) {
                    $mensaje = "Se ha enviado un enlace de recuperación a tu correo (si está registrado).";
                    $tipo_mensaje = 'success'; // Cambiamos a success para el mensaje principal
                } else {
                    // Error de envío (puede ser configuración SMTP, etc.)
                    $mensaje = "Hubo un problema al enviar el correo. Inténtalo más tarde.";
                    // Aquí podrías loggear el error real para ti: error_log("Mailer Error: " . $mail->ErrorInfo);
                    $tipo_mensaje = 'danger';
                }

            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $mensaje = "Ocurrió un error al procesar tu solicitud. Inténtalo de nuevo.";
                // Loggear el error real: error_log("DB Error en forgot_password: " . $e->getMessage());
                $tipo_mensaje = 'danger';
            }

        } else {
            // Email no encontrado - Mensaje genérico por seguridad (ID 89)
            $mensaje = "Si existe una cuenta asociada a ese correo, recibirás un enlace.";
            $tipo_mensaje = 'info'; 
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card p-4 shadow-sm" style="max-width: 400px; width: 90%;">
        <div class="text-center mb-4">
             <i class="bi bi-key-fill" style="font-size: 3rem; color: #0d6efd;"></i>
             <h3 class="mt-2">¿Olvidaste tu contraseña?</h3>
             <p class="text-muted">Ingresa tu correo y te enviaremos un enlace para restablecerla.</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensaje) ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <div class="input-group">
                     <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                     <input type="email" class="form-control" name="email" id="email" 
                            placeholder="tu.correo@ejemplo.com" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send"></i> Enviar enlace de recuperación
            </button>
        </form>
        <hr>
        <div class="text-center">
             <a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Volver al inicio de sesión</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>