<?php
session_start();
include 'assets/admin/db.php';
include 'assets/admin/mailer_config.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Por favor, ingresa un correo válido.";
    } else {
        $query = "SELECT id_usuario, email FROM usuarios WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            // Generar token único
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Borrar tokens anteriores
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();

            // Guardar nuevo token
            $insert = $conn->prepare("INSERT INTO password_resets (email, token_hash, expiracion) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $email, $token_hash, $expiracion);
            $insert->execute();

            // Enlace de recuperación
            $reset_link = BASE_URL . "/reset_password.php?token=" . $token;
            $asunto = "🔐 Restablece tu contraseña | Tinkuy";

            $body_html = '
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f7f9fc;
      margin: 0;
      padding: 0;
      color: #333;
    }
    .container {
      max-width: 600px;
      margin: 30px auto;
      background: #ffffff;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    .header {
      background-color: #0d6efd;
      padding: 20px;
      text-align: center;
      color: #fff;
    }
    .header h1 {
      margin: 0;
      font-size: 24px;
      letter-spacing: 1px;
    }
    .content {
      padding: 30px;
      text-align: center;
    }
    .content p {
      font-size: 16px;
      line-height: 1.5;
      margin-bottom: 25px;
    }
    .btn {
      display: inline-block;
      background-color: #0d6efd;
      color: #fff !important;
      padding: 12px 25px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      font-size: 16px;
    }
    .footer {
      background-color: #f1f3f6;
      text-align: center;
      padding: 15px;
      font-size: 13px;
      color: #666;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Tinkuy</h1>
    </div>
    <div class="content">
      <p>Hola 👋,</p>
      <p>Recibimos una solicitud para restablecer tu contraseña.</p>
      <p>Haz clic en el siguiente botón para continuar:</p>
      <p>
        <a href="' . $reset_link . '" class="btn">Restablecer Contraseña</a>
      </p>
      <p>Este enlace expirará en 1 hora.<br>Si no solicitaste esto, puedes ignorar este mensaje.</p>
    </div>
    <div class="footer">
      © ' . date('Y') . ' Tinkuy — Todos los derechos reservados.
    </div>
  </div>
</body>
</html>
';


            if (send_mail($email, $asunto, $body_html)) {
                $mensaje = "Se ha enviado un enlace de recuperación a tu correo.";
            } else {
                $mensaje = "No se pudo enviar el correo. Verifica tu configuración SMTP.";
            }
        } else {
            $mensaje = "Si existe una cuenta con ese correo, se enviará un enlace de recuperación.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh">
    <div class="card p-4 shadow" style="max-width:400px;">
        <h3 class="mb-3 text-center">¿Olvidaste tu contraseña?</h3>
        <?php if ($mensaje): ?>
            <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" name="email" id="email" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Enviar enlace</button>
        </form>
        <hr>
        <a href="login.php">Volver al inicio de sesión</a>
    </div>
</body>

</html>