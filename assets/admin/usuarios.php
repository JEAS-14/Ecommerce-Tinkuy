<?php
session_start();
include 'db.php'; // Estamos en la carpeta 'admin', db.php está aquí

// --- INICIO DE CALIDAD (SEGURIDAD ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php'); //
    exit;
}
if ($_SESSION['rol'] !== 'admin') {
    // Si no es admin, no puede estar aquí
    session_destroy();
    header('Location: ../../login.php'); //
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

// (Manejo de mensajes de éxito/error de eliminar_usuario.php)
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
unset($_SESSION['mensaje_error'], $_SESSION['mensaje_exito']);

// --- LÓGICA GET (Calidad de Rendimiento) ---
// (Obtenemos todos los usuarios con sus roles y perfiles)
$query_usuarios = "
    SELECT 
        u.id_usuario,
        u.usuario,
        u.email,
        u.fecha_registro,
        r.nombre_rol,
        p.nombres,
        p.apellidos
    FROM 
        usuarios AS u
    JOIN 
        roles AS r ON u.id_rol = r.id_rol
    LEFT JOIN 
        perfiles AS p ON u.id_usuario = p.id_usuario
    ORDER BY
        u.fecha_registro DESC
";
$resultado_usuarios = $conn->query($query_usuarios);
$usuarios = $resultado_usuarios->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Panel Admin</a> <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="pedidos.php">Pedidos</a></li> <li class="nav-item"><a class="nav-link" href="productos_admin.php">Productos</a></li> <li class="nav-item"><a class="nav-link active" href="usuarios.php">Usuarios</a></li> </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../../logout.php">Cerrar Sesión</a></li> </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Gestión de Usuarios</h2>
            <a href="crear_usuario.php" class="btn btn-primary"> <i class="bi bi-person-plus"></i> Crear Nuevo Usuario
            </a>
        </div>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger alert-error-animated"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Usuario</th>
                                <th scope="col">Nombre Completo</th>
                                <th scope="col">Email</th>
                                <th scope="col">Rol</th>
                                <th scope="col">Fecha Registro</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No se han encontrado usuarios.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><strong><?= $usuario['id_usuario'] ?></strong></td>
                                        <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                                        <td><?= htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></td>
                                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                                        <td>
                                            <?php 
                                            // Damos estilo al rol (Calidad de Usabilidad)
                                            $clase_rol = 'bg-secondary';
                                            if ($usuario['nombre_rol'] === 'admin') $clase_rol = 'bg-danger';
                                            if ($usuario['nombre_rol'] === 'vendedor') $clase_rol = 'bg-warning text-dark';
                                            if ($usuario['nombre_rol'] === 'cliente') $clase_rol = 'bg-info text-dark';
                                            ?>
                                            <span class="badge <?= $clase_rol ?>"><?= htmlspecialchars($usuario['nombre_rol']) ?></span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></td>
                                        <td>
                                            <?php if ($_SESSION['usuario_id'] !== $usuario['id_usuario']): // No puedes eliminarte a ti mismo ?>
                                                <a href="eliminar_usuario.php?id=<?= $usuario['id_usuario'] ?>" class="btn btn-sm btn-outline-danger" title="Eliminar Usuario" onclick="return confirm('¿Estás seguro de que quieres eliminar a este usuario? Esta acción es irreversible.');"> <i class="bi bi-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled title="No puedes eliminarte a ti mismo"><i class="bi bi-trash"></i></button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>