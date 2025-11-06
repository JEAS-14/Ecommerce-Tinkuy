<?php
session_start();
include 'db.php';

// Roles
define('ROL_ADMIN', 1);
define('ROL_VENDEDOR', 2);
define('ROL_CLIENTE', 3);

// Seguridad
if (!isset($_SESSION['usuario_id'])) {
    session_destroy();
    header('Location: ../../login.php');
    exit;
}

$nombre_usuario = $_SESSION['usuario'];

// Mensajes flash
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito']);

// Eliminar usuario (solo cliente)
if (isset($_GET['eliminar_id'])) {
    $id_usuario_a_eliminar = (int) $_GET['eliminar_id'];

    if ($id_usuario_a_eliminar === $_SESSION['usuario_id']) {
        $_SESSION['mensaje_error'] = "No puedes eliminar tu propia cuenta.";
        header('Location: usuarios.php');
        exit;
    }

    try {
        $stmt_check = $conn->prepare("SELECT u.id_rol, r.nombre_rol FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol WHERE u.id_usuario = ?");
        $stmt_check->bind_param("i", $id_usuario_a_eliminar);
        $stmt_check->execute();
        $usuario_check = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$usuario_check || (int) $usuario_check['id_rol'] !== ROL_CLIENTE) {
            $rol_detectado = $usuario_check['nombre_rol'] ?? 'no encontrado';
            $_SESSION['mensaje_error'] = "Solo se puede eliminar clientes. Este usuario es '{$rol_detectado}'.";
            header('Location: usuarios.php');
            exit;
        }

        $stmt_del = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt_del->bind_param("i", $id_usuario_a_eliminar);
        $stmt_del->execute();

        if ($stmt_del->affected_rows > 0) {
            $_SESSION['mensaje_exito'] = "Usuario cliente eliminado correctamente.";
        }
        $stmt_del->close();

    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = "Error: " . $e->getMessage();
    }
    header('Location: usuarios.php');
    exit;
}

// Obtener usuarios
$query_usuarios = "SELECT u.id_usuario, u.usuario, u.email, u.fecha_registro, u.id_rol, r.nombre_rol, p.nombres, p.apellidos FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol LEFT JOIN perfiles p ON u.id_usuario=p.id_usuario ORDER BY u.fecha_registro DESC";
$resultado_usuarios = $conn->query($query_usuarios);
$usuarios = $resultado_usuarios->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .sidebar {
            width: 260px;
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
            background: #212529;
            padding-top: 1rem;
            color: white;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            margin-bottom: 0.5rem;
        }

        .sidebar .nav-link i {
            margin-right: 0.8rem;
        }

        .sidebar .nav-link.active {
            background: #dc3545;
            color: #fff;
        }

        .sidebar .nav-link:hover {
            background: #343a40;
            color: #fff;
        }

        .main-content {
            margin-left: 260px;
            padding: 2.5rem;
            width: calc(100% - 260px);
        }

        .user-dropdown .dropdown-toggle {
            color: #fff;
        }

        .user-dropdown .dropdown-menu {
            border-radius: 0.5rem;
        }

        .stat-card-icon {
            font-size: 3rem;
        }

        .badge {
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

    <div class="sidebar d-flex flex-column p-3 text-white">
        <a href="dashboard.php" class="d-flex align-items-center mb-3 text-white text-decoration-none">
            <i class="bi bi-shop-window fs-4 me-2"></i>
            <span class="fs-4">Admin Tinkuy</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="bi bi-grid-fill"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="pedidos.php" class="nav-link">
                    <i class="bi bi-list-check"></i> Pedidos
                </a>
            </li>
            <li>
                <a href="productos_admin.php" class="nav-link">
                    <i class="bi bi-box-seam-fill"></i> Productos
                </a>
            </li>
            <li>
                <a href="usuarios.php" class="nav-link active" aria-current="page">
                    <i class="bi bi-people-fill"></i> Usuarios
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown user-dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle fs-4 me-2"></i><strong><?= htmlspecialchars($nombre_usuario) ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="../../logout.php">Cerrar Sesión</a></li>
            </ul>
        </div>
    </div>

   <main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Usuarios</h1>
        <!-- Botón para agregar usuario -->
        <a href="crear_usuario.php" class="btn btn-primary">
            <i class="bi bi-person-plus-fill"></i> Agregar Usuario
        </a>
    </div>

    <?php if($mensaje_error): ?><div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div><?php endif; ?>
    <?php if($mensaje_exito): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div><?php endif; ?>

    <div class="card p-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th><th>Usuario</th><th>Nombre Completo</th><th>Email</th><th>Fecha Registro</th><th>Rol</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($usuarios)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No se han encontrado usuarios.</td></tr>
                    <?php else: ?>
                        <?php foreach($usuarios as $usuario):
                            $clase_rol='bg-secondary';
                            if($usuario['nombre_rol']==='admin') $clase_rol='bg-danger';
                            if($usuario['nombre_rol']==='vendedor') $clase_rol='bg-warning text-dark';
                            if($usuario['nombre_rol']==='cliente') $clase_rol='bg-info text-dark';
                            $id=$usuario['id_usuario'];
                            $rol_id=(int)$usuario['id_rol'];
                        ?>
                        <tr>
                            <td><strong><?= $id ?></strong></td>
                            <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                            <td><?= htmlspecialchars($usuario['nombres'].' '.$usuario['apellidos']) ?></td>
                            <td><?= htmlspecialchars($usuario['email']) ?></td>
                            <td><?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></td>
                            <td><span class="badge <?= $clase_rol ?>"><?= htmlspecialchars($usuario['nombre_rol']) ?></span></td>
                            <td>
                                <?php if($_SESSION['usuario_id']!==$id && $rol_id===ROL_CLIENTE): ?>
                                    <a href="usuarios.php?eliminar_id=<?= $id ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro de eliminar <?= htmlspecialchars($usuario['usuario']) ?>?');"><i class="bi bi-trash"></i> Eliminar</a>
                                <?php elseif($_SESSION['usuario_id']===$id): ?>
                                    <button class="btn btn-sm btn-secondary" disabled><i class="bi bi-person-fill-lock"></i> Yo</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled><i class="bi bi-person-x"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>