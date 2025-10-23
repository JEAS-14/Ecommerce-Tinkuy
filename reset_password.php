<?php
session_start();
include 'assets/admin/db.php';
include 'assets/admin/mailer_config.php';

$mensaje = '';
$show_form = false;
$token_raw = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($token_raw)) {
    $token_hash = hash('sha256', $token_raw);
    $stmt = $conn->prepare("SELECT email, expiracion FROM password_resets WHERE token_hash = ? LIMIT 1");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $row = $res->fetch_assoc();
        if (strtotime($row['expiracion']) >= time()) {
            $show_form = true;
        } else {
            $mensaje = "El enlace ha expirado.";
        }
    } else {
        $mensaje = "Enlace inválido.";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_post = $_POST['token'] ?? '';
    $clave_nueva = $_POST['clave_nueva'] ?? '';
    $clave_confirm = $_POST['clave_confirm'] ?? '';

    if ($clave_nueva !== $clave_confirm) {
        $mensaje = "Las contraseñas no coinciden.";
    } elseif (strlen($clave_nueva) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        $token_hash = hash('sha256', $token_post);
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token_hash = ? AND expiracion > NOW() LIMIT 1");
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $email = $row['email'];
            $new_hash = password_hash($clave_nueva, PASSWORD_BCRYPT);

            $upd = $conn->prepare("UPDATE usuarios SET clave_hash = ? WHERE email = ?");
            $upd->bind_param("ss", $new_hash, $email);
            $upd->execute();

            $conn->query("DELETE FROM password_resets WHERE email = '$email'");

            $mensaje = "✅ Contraseña actualizada correctamente.";
        } else {
            $mensaje = "Enlace inválido o expirado.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Restablecer Contraseña | Tinkuy</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh">
<div class="card p-4 shadow" style="max-width:400px;">
    <h3 class="mb-3 text-center">Restablecer Contraseña</h3>
    <?php if ($mensaje): ?><div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($show_form): ?>
    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token_raw) ?>">
        <div class="mb-3">
            <label for="clave_nueva" class="form-label">Nueva contraseña</label>
            <input type="password" name="clave_nueva" id="clave_nueva" class="form-control" required minlength="6">
        </div>
        <div class="mb-3">
            <label for="clave_confirm" class="form-label">Confirmar contraseña</label>
            <input type="password" name="clave_confirm" id="clave_confirm" class="form-control" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary w-100">Guardar nueva contraseña</button>
    </form>
    <?php else: ?>
    <a href="forgot_password.php" class="btn btn-link w-100">Solicitar nuevo enlace</a>
    <?php endif; ?>
</div>
</body>
</html>
