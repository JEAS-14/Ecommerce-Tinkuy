<?php
session_start();
include 'db.php'; // Conexión a la base de datos

// --- SEGURIDAD ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    session_destroy();
    header('Location: ../../login.php');
    exit;
}

// --- VARIABLES DE MENSAJE ---
$mensaje_error = "";
$mensaje_exito = "";

// =====================================================
// CREAR ADMIN O VENDEDOR
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $id_rol = (int)($_POST['id_rol'] ?? 0);

    $roles_permitidos = [1, 2]; 
    $nombre_rol = match($id_rol) {
        1 => 'Admin',
        2 => 'Vendedor',
        default => 'Inválido',
    };

    // Expresiones regulares
    $regex_letras_espacios = '/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u';
    $regex_usuario = '/^[a-zA-Z0-9_-]+$/';
    $regex_telefono = '/^\d{9}$/';

    // VALIDACIONES PHP
    if ($usuario === '' || $email === '' || $clave === '' || $nombres === '' || $apellidos === '' || !in_array($id_rol, $roles_permitidos)) {
        $mensaje_error = "Todos los campos obligatorios deben ser completados.";
    } elseif (strlen($usuario) < 4 || strlen($usuario) > 15 || !preg_match($regex_usuario, $usuario)) {
        $mensaje_error = "Usuario inválido. 4-15 caracteres. Letras, números, guiones o guiones bajos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "Formato de email inválido.";
    } elseif (strlen($nombres) < 3 || strlen($nombres) > 50 || !preg_match($regex_letras_espacios, $nombres)) {
        $mensaje_error = "Nombre inválido. Solo letras y espacios, 3-50 caracteres.";
    } elseif (strlen($apellidos) < 3 || strlen($apellidos) > 50 || !preg_match($regex_letras_espacios, $apellidos)) {
        $mensaje_error = "Apellido inválido. Solo letras y espacios, 3-50 caracteres.";
    } elseif (!empty($telefono) && !preg_match($regex_telefono, $telefono)) {
        $mensaje_error = "Teléfono inválido. Debe contener exactamente 9 dígitos.";
    } else {
        $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
        $conn->begin_transaction();
        try {
            $stmt_usuario = $conn->prepare("INSERT INTO usuarios (id_rol, usuario, email, clave_hash) VALUES (?, ?, ?, ?)");
            $stmt_usuario->bind_param("isss", $id_rol, $usuario, $email, $clave_hash);
            $stmt_usuario->execute();
            $nuevo_usuario_id = $conn->insert_id;

            $stmt_perfil = $conn->prepare("INSERT INTO perfiles (id_usuario, nombres, apellidos, telefono) VALUES (?, ?, ?, ?)");
            $telefono_a_insertar = $telefono ?: NULL;
            $stmt_perfil->bind_param("isss", $nuevo_usuario_id, $nombres, $apellidos, $telefono_a_insertar);
            $stmt_perfil->execute();

            $conn->commit();
            $mensaje_exito = "✅ Usuario '$usuario' creado exitosamente con rol $nombre_rol!";
            $_POST = [];
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            if ($e->getCode() == 1062) {
                $mensaje_error = "El usuario o email ya existe.";
            } else {
                $mensaje_error = "Error al crear usuario: " . $e->getMessage();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear Usuario - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f8f9fa; }
.sidebar { width:260px; position:fixed; height:100vh; top:0; left:0; background:#212529; padding-top:1rem; color:white; }
.sidebar .nav-link { color:#adb5bd; }
.sidebar .nav-link.active { background:#dc3545; color:#fff; }
.main-content { margin-left:260px; padding:2.5rem; }
</style>
</head>
<body>

<div class="sidebar d-flex flex-column p-3 text-white">
    <a href="dashboard.php" class="d-flex align-items-center mb-3 text-white text-decoration-none">
        <i class="bi bi-shop-window fs-4 me-2"></i><span class="fs-4">Admin Tinkuy</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li><a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
        <li><a href="pedidos.php" class="nav-link"><i class="bi bi-list-check"></i> Pedidos</a></li>
        <li><a href="productos_admin.php" class="nav-link"><i class="bi bi-box-seam-fill"></i> Productos</a></li>
        <li><a href="usuarios.php" class="nav-link"><i class="bi bi-people-fill"></i> Usuarios</a></li>
        <li><a href="crear_usuario.php" class="nav-link active"><i class="bi bi-person-plus"></i> Crear Usuario</a></li>
    </ul>
    <hr>
    <div class="dropdown user-dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle fs-4 me-2"></i><strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark shadow">
            <li><a class="dropdown-item" href="../../logout.php">Cerrar Sesión</a></li>
        </ul>
    </div>
</div>

<main class="main-content">
    <h2 class="mb-4">Crear Nuevo Usuario</h2>

    <?php if($mensaje_error): ?><div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div><?php endif; ?>
    <?php if($mensaje_exito): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div><?php endif; ?>

    <div class="card shadow-sm p-4">
        <form method="POST" novalidate>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="usuario" class="form-control" required
                        pattern="^[a-zA-Z0-9_-]{4,15}$"
                        title="4-15 caracteres. Letras, números, guiones o guiones bajos."
                        value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="clave" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rol</label>
                    <select name="id_rol" class="form-select" required>
                        <option value="" disabled selected>Seleccione el Rol</option>
                        <option value="1" <?= (($_POST['id_rol'] ?? 0) == 1) ? 'selected' : '' ?>>Admin</option>
                        <option value="2" <?= (($_POST['id_rol'] ?? 0) == 2) ? 'selected' : '' ?>>Vendedor</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
    <label class="form-label">Nombres</label>
    <input type="text" name="nombres" class="form-control" required
        pattern="^[a-zA-Z\sñáéíóúÁÉÍÓÚ]{3,50}$"
        title="3-50 letras y espacios."
        value="<?= htmlspecialchars($_POST['nombres'] ?? '') ?>"
        oninput="this.value=this.value.replace(/[^a-zA-Z\sñáéíóúÁÉÍÓÚ]/g,'');">
</div>
<div class="col-md-6">
    <label class="form-label">Apellidos</label>
    <input type="text" name="apellidos" class="form-control" required
        pattern="^[a-zA-Z\sñáéíóúÁÉÍÓÚ]{3,50}$"
        title="3-50 letras y espacios."
        value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>"
        oninput="this.value=this.value.replace(/[^a-zA-Z\sñáéíóúÁÉÍÓÚ]/g,'');">
</div>

            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Teléfono (Opcional)</label>
                    <input type="tel" name="telefono" class="form-control"
                        pattern="^\d{9}$" maxlength="9" inputmode="numeric"
                        title="Exactamente 9 dígitos numéricos."
                        value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'');">
                </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-person-check"></i> Crear Usuario</button>
            <a href="usuarios.php" class="btn btn-secondary">Volver</a>
        </form>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
