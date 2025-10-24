<?php
session_start();
// Asegúrate que la ruta a db.php sea correcta
// Estando en /assets/vendedor/, subimos un nivel a /assets/ y entramos a /admin/
include '../admin/db.php';

// --- CONTROL DE ACCESO (Seguridad ISO 25010) ---
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php"); // Redirigir al login principal
    exit;
}
// SOLO vendedores pueden acceder aquí
if ($_SESSION['rol'] !== 'vendedor') {
    // Si no es vendedor, destruir sesión y redirigir
    session_destroy();
    header("Location: ../../login.php");
    exit;
}

$id_vendedor = $_SESSION['usuario_id'];
$nombre_vendedor_sesion = $_SESSION['usuario']; // Para la navbar
$mensaje_error = "";
$mensaje_exito = "";

// --- LÓGICA DE ACTUALIZAR PERFIL (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_perfil_vendedor') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? ''); // Opcional

    // Validación simple (puedes añadir regex más específicas si quieres)
    if (empty($nombre) || empty($apellido)) {
        $mensaje_error = "El nombre y apellido son obligatorios.";
    } else {
        try {
            // Usamos INSERT ... ON DUPLICATE KEY UPDATE para crear o actualizar el perfil
            // Asegura que siempre haya una fila en 'perfiles' para cada 'usuario' vendedor
            $stmt_update = $conn->prepare("
                INSERT INTO perfiles (id_usuario, nombres, apellidos, telefono)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE nombres = VALUES(nombres), apellidos = VALUES(apellidos), telefono = VALUES(telefono)
            ");
            $stmt_update->bind_param("isss", $id_vendedor, $nombre, $apellido, $telefono);
            $stmt_update->execute();

            if ($stmt_update->affected_rows >= 0) { // affected_rows puede ser 0 si no hubo cambios, 1 si insertó, 1 o 2 si actualizó
                 $mensaje_exito = "Perfil actualizado con éxito.";
                 // Actualizar nombre en sesión si cambió (opcional, para navbar)
                 // $_SESSION['usuario'] = $nombre; // O como prefieras mostrarlo
            } else {
                 throw new Exception("No se pudo actualizar el perfil.");
            }
             $stmt_update->close();

        } catch (mysqli_sql_exception $e) {
            $mensaje_error = "Error al actualizar el perfil: " . $e->getMessage();
        }
    }
}

// --- OBTENER DATOS ACTUALES PARA MOSTRAR (GET) ---
$datos_perfil = [
    'nombre' => '',
    'apellido' => '',
    'email' => '', // Email viene de la tabla usuarios
    'telefono' => ''
];
$stmt_data = $conn->prepare("
    SELECT u.email, p.nombres, p.apellidos, p.telefono
    FROM usuarios u
    LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
    WHERE u.id_usuario = ? AND u.id_rol = 2 -- Asegurarnos que es vendedor
");
$stmt_data->bind_param("i", $id_vendedor);
$stmt_data->execute();
$resultado_data = $stmt_data->get_result();
if ($fila = $resultado_data->fetch_assoc()) {
    $datos_perfil['nombre'] = $fila['nombres'] ?? '';
    $datos_perfil['apellido'] = $fila['apellidos'] ?? '';
    $datos_perfil['email'] = $fila['email'];
    $datos_perfil['telefono'] = $fila['telefono'] ?? '';
} else {
     // Si no se encuentra el usuario vendedor (raro, pero posible), redirigir
     session_destroy();
     header("Location: ../../login.php");
     exit;
}
$stmt_data->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mi Perfil Vendedor | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body class="bg-light">

    <!-- Navbar del Vendedor -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <i class="bi bi-shop me-2"></i><span style="font-weight: bold; letter-spacing: 1px;">Tinkuy Vendedor</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vendedorNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="vendedorNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="productos.php"><i class="bi bi-box-seam-fill me-1"></i>Mis Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="envios.php"><i class="bi bi-truck me-1"></i>Envíos Pendientes</a></li>
                    <li class="nav-item"><a class="nav-link" href="ventas.php"><i class="bi bi-bar-chart-line-fill me-1"></i>Mis Ventas</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                         <a class="nav-link dropdown-toggle active" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                             <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($nombre_vendedor_sesion) ?>
                         </a>
                         <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                             <li><a class="dropdown-item active" href="mi_perfil_vendedor.php"><i class="bi bi-person-badge me-2"></i>Mi Perfil Vendedor</a></li>
                             <li><hr class="dropdown-divider"></li>
                             <li><a class="dropdown-item text-danger" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                         </ul>
                     </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <h2 class="mb-4">Mi Perfil de Vendedor</h2>

        <div class="row">
            <!-- Menú Lateral Simple -->
            <div class="col-md-3">
                <div class="list-group shadow-sm mb-4">
                    <a href="mi_perfil_vendedor.php" class="list-group-item list-group-item-action active" aria-current="true">
                        <i class="bi bi-person-circle me-2"></i> Datos Personales
                    </a>
                    <a href="?seccion=pagos_recibidos" class="list-group-item list-group-item-action"> <i class="bi bi-cash-coin me-2"></i> Configuración de Pagos </a>
                </div>
            </div>

            <!-- Contenido Principal -->
            <div class="col-md-9">
                <?php if (!empty($mensaje_error)): ?>
                    <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($mensaje_error) ?></div>
                <?php endif; ?>
                <?php if (!empty($mensaje_exito)): ?>
                    <div class="alert alert-success shadow-sm"><?= htmlspecialchars($mensaje_exito) ?></div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header">
                        Editar Datos Personales
                    </div>
                    <div class="card-body">
                        <form method="POST" action="mi_perfil_vendedor.php">
                            <input type="hidden" name="accion" value="actualizar_perfil_vendedor">

                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico (Usuario)</label>
                                <input type="email" class="form-control" id="email"
                                       value="<?= htmlspecialchars($datos_perfil['email']) ?>" disabled readonly>
                                <div class="form-text">Este es tu correo de inicio de sesión y no se puede cambiar aquí.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                           value="<?= htmlspecialchars($datos_perfil['nombre']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellido" class="form-label">Apellido(s) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="apellido" name="apellido"
                                           value="<?= htmlspecialchars($datos_perfil['apellido']) ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono de Contacto (Opcional)</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono"
                                       value="<?= htmlspecialchars($datos_perfil['telefono']) ?>"
                                       placeholder="+51 987654321" pattern="^\+?[0-9\s-]{7,15}$"
                                       title="Formato de teléfono no válido. Use solo números y opcionalmente '+', espacios o guiones.">
                                <div class="form-text">Ej: +51 987654321 o 987654321 (Máx 15 caracteres)</div>
                            </div>

                            <hr>
                            <h5 class="mt-4">Datos de tu Tienda (Opcional)</h5>
                            <div class="mb-3">
                                <label for="nombre_tienda" class="form-label">Nombre de tu Tienda</label>
                                <input type="text" class="form-control" id="nombre_tienda" name="nombre_tienda">
                            </div>
                             <div class="mb-3">
                                <label for="descripcion_tienda" class="form-label">Descripción Breve</label>
                                <textarea class="form-control" id="descripcion_tienda" name="descripcion_tienda" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i> Guardar Cambios</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php
        // Incluir footer si lo tienes estandarizado
        // include '../../assets/component/footer.php';
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>