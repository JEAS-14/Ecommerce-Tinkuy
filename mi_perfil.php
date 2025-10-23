<?php
session_start();
// Asegúrate de que esta ruta sea correcta para tu proyecto
include 'assets/admin/db.php';

// ----------------------------------------------------
// 1. CONTROL DE ACCESO (Seguridad)
// ----------------------------------------------------
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$mensaje_error = "";
$mensaje_exito = "";
$seccion_activa = $_GET['seccion'] ?? 'dashboard';

// ----------------------------------------------------
// 2. LÓGICA DE GESTIÓN DE DATOS (Múltiples Secciones)
// ----------------------------------------------------

// A. Obtener Datos Personales del Perfil y Usuario
$datos_perfil = [
    'nombre' => '',
    'apellido' => '',
    'email' => '',
    'telefono' => ''
];
$stmt_data = $conn->prepare("
    SELECT u.email, p.nombres, p.apellidos, p.telefono 
    FROM usuarios u 
    LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario 
    WHERE u.id_usuario = ?
");
$stmt_data->bind_param("i", $id_usuario);
$stmt_data->execute();
$resultado_data = $stmt_data->get_result();
if ($fila = $resultado_data->fetch_assoc()) {
    $datos_perfil['nombre'] = $fila['nombres'] ?? '';
    $datos_perfil['apellido'] = $fila['apellidos'] ?? '';
    $datos_perfil['email'] = $fila['email']; // Email viene de la tabla usuarios
    $datos_perfil['telefono'] = $fila['telefono'] ?? '';
}

// B. Lógica de AGREGAR DIRECCIÓN
if ($seccion_activa === 'direcciones' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar_direccion') {
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $pais = trim($_POST['pais'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    $es_principal = isset($_POST['es_principal']) ? 1 : 0;

    if (empty($direccion) || empty($ciudad) || empty($pais) || empty($codigo_postal)) {
        $mensaje_error = "Todos los campos de la dirección son obligatorios.";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO direcciones (id_usuario, direccion, ciudad, pais, codigo_postal, es_principal)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issssi", $id_usuario, $direccion, $ciudad, $pais, $codigo_postal, $es_principal);
            $stmt->execute();
            $mensaje_exito = "Dirección guardada con éxito.";

            if ($es_principal) {
                $last_id = $conn->insert_id;
                $stmt_update = $conn->prepare("UPDATE direcciones SET es_principal = 0 WHERE id_usuario = ? AND id_direccion != ?");
                $stmt_update->bind_param("ii", $id_usuario, $last_id);
                $stmt_update->execute();
            }

        } catch (mysqli_sql_exception $e) {
            $mensaje_error = "Error al guardar la dirección: " . $e->getMessage();
        }
    }
}

// C. Lógica de AGREGAR TARJETA
if ($seccion_activa === 'pagos' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar_tarjeta') {

    $nombre_tarjeta = trim($_POST['nombre_tarjeta'] ?? '');
    $numero_tarjeta_completo = trim($_POST['numero_tarjeta'] ?? '');
    $ultimos_4_digitos = substr($numero_tarjeta_completo, -4);
    $expiracion = trim($_POST['expiracion'] ?? '');
    $tipo = trim($_POST['tipo_tarjeta'] ?? 'Visa');

    if (empty($nombre_tarjeta) || !ctype_digit($ultimos_4_digitos) || strlen($numero_tarjeta_completo) < 4 || empty($expiracion)) {
        $mensaje_error = "Por favor, complete los datos de la tarjeta correctamente.";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO tarjetas_usuario (id_usuario, nombre_tarjeta, ultimos_4_digitos, expiracion, tipo)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issss", $id_usuario, $nombre_tarjeta, $ultimos_4_digitos, $expiracion, $tipo);
            $stmt->execute();
            $mensaje_exito = "Tarjeta simulada agregada con éxito.";
        } catch (mysqli_sql_exception $e) {
            $mensaje_error = "Error al guardar la tarjeta: " . $e->getMessage();
        }
    }
}

// D. Lógica de ACTUALIZAR PERFIL
if ($seccion_activa === 'perfil' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar_perfil') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    $telefono_regex = '/^\+?[0-9\s-]{7,15}$/';

    if (empty($nombre) || empty($apellido)) {
        $mensaje_error = "El nombre y apellido son obligatorios.";
    } elseif (!empty($telefono) && !preg_match($telefono_regex, $telefono)) {
        $mensaje_error = "El formato del teléfono no es válido.";
    } else {
        try {
            $stmt_update = $conn->prepare("
                INSERT INTO perfiles (id_usuario, nombres, apellidos, telefono)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE nombres = VALUES(nombres), apellidos = VALUES(apellidos), telefono = VALUES(telefono)
            ");
            $stmt_update->bind_param("isss", $id_usuario, $nombre, $apellido, $telefono);
            $stmt_update->execute();
            $mensaje_exito = "Perfil actualizado con éxito.";
            $datos_perfil['nombre'] = $nombre;
            $datos_perfil['apellido'] = $apellido;
            $datos_perfil['telefono'] = $telefono;

        } catch (mysqli_sql_exception $e) {
            $mensaje_error = "Error al actualizar el perfil: " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// 3. RECUPERAR DATOS PARA MOSTRAR
// ----------------------------------------------------
$direcciones = [];
if ($seccion_activa === 'direcciones' || $seccion_activa === 'dashboard') {
    $stmt_dir = $conn->prepare("
        SELECT id_direccion, direccion, ciudad, pais, codigo_postal, es_principal
        FROM direcciones
        WHERE id_usuario = ?
        ORDER BY es_principal DESC, id_direccion DESC
    ");
    $stmt_dir->bind_param("i", $id_usuario);
    $stmt_dir->execute();
    $direcciones = $stmt_dir->get_result()->fetch_all(MYSQLI_ASSOC);
}

$tarjetas = [];
if ($seccion_activa === 'pagos' || $seccion_activa === 'dashboard') {
    $stmt_tar = $conn->prepare("
        SELECT id_tarjeta, nombre_tarjeta, ultimos_4_digitos, expiracion, tipo
        FROM tarjetas_usuario
        WHERE id_usuario = ?
    ");
    $stmt_tar->bind_param("i", $id_usuario);
    $stmt_tar->execute();
    $tarjetas = $stmt_tar->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ----------------------------------------------------
// 4. ESTRUCTURA HTML/VISUAL
// ----------------------------------------------------
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mi Perfil | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>

<body>
    <?php include 'assets/component/navbar.php'; ?>


    <main class="container my-5">
        <h2 class="mb-4">Dashboard de Mi Cuenta</h2>
        <!-- El resto de tu HTML se mantiene igual -->


        <div class="row">
            <div class="col-md-3">
                <div class="list-group shadow-sm">
                    <a href="mi_perfil.php?seccion=dashboard"
                        class="list-group-item list-group-item-action <?= ($seccion_activa === 'dashboard') ? 'active' : '' ?>">
                        <i class="bi bi-speedometer"></i> Dashboard
                    </a>
                    <a href="mi_perfil.php?seccion=perfil"
                        class="list-group-item list-group-item-action <?= ($seccion_activa === 'perfil') ? 'active' : '' ?>">
                        <i class="bi bi-person-circle"></i> Datos Personales
                    </a>
                    <a href="mi_perfil.php?seccion=direcciones"
                        class="list-group-item list-group-item-action <?= ($seccion_activa === 'direcciones') ? 'active' : '' ?>">
                        <i class="bi bi-geo-alt-fill"></i> Direcciones
                    </a>
                    <a href="mi_perfil.php?seccion=pagos"
                        class="list-group-item list-group-item-action <?= ($seccion_activa === 'pagos') ? 'active' : '' ?>">
                        <i class="bi bi-credit-card-fill"></i> Métodos de Pago
                    </a>
                    <a href="pedidos.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-box-seam"></i> Mis Pedidos
                    </a>
                </div>
            </div>

            <div class="col-md-9">
                <?php if (!empty($mensaje_error)): ?>
                    <div class="alert alert-danger shadow-sm"><?= htmlspecialchars($mensaje_error) ?></div>
                <?php endif; ?>
                <?php if (!empty($mensaje_exito)): ?>
                    <div class="alert alert-success shadow-sm"><?= htmlspecialchars($mensaje_exito) ?></div>
                <?php endif; ?>

                <?php if ($seccion_activa === 'dashboard'): ?>

                    <h3 class="border-bottom pb-2">Resumen de la Cuenta</h3>
                    <p>Bienvenido, **<?= htmlspecialchars($datos_perfil['nombre'] . " " . $datos_perfil['apellido']) ?>**.
                    </p>

                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <div class="col">
                            <div class="card bg-light shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="bi bi-geo-alt-fill"></i> Direcciones</h5>
                                    <p class="card-text">Tienes **<?= count($direcciones) ?>** direcciones guardadas.</p>
                                    <a href="mi_perfil.php?seccion=direcciones"
                                        class="btn btn-sm btn-outline-primary">Gestionar Direcciones</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card bg-light shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="bi bi-credit-card-fill"></i> Pagos</h5>
                                    <p class="card-text">Tienes **<?= count($tarjetas) ?>** métodos de pago guardados.</p>
                                    <a href="mi_perfil.php?seccion=pagos" class="btn btn-sm btn-outline-primary">Gestionar
                                        Pagos</a>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($seccion_activa === 'perfil'): ?>

                    <h3 class="border-bottom pb-2">Datos Personales</h3>
                    <p>Aquí puedes editar tu información de contacto.</p>

                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="mi_perfil.php?seccion=perfil">
                                <input type="hidden" name="accion" value="actualizar_perfil">

                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email"
                                        value="<?= htmlspecialchars($datos_perfil['email']) ?>" disabled>
                                    <div class="form-text">El correo es tu usuario y no se puede cambiar aquí.</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            value="<?= htmlspecialchars($datos_perfil['nombre']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="apellido" class="form-label">Apellido</label>
                                        <input type="text" class="form-control" id="apellido" name="apellido"
                                            value="<?= htmlspecialchars($datos_perfil['apellido']) ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono (Opcional)</label>
                                    <!-- Validación de teléfono en cliente -->
                                    <input type="tel" class="form-control" id="telefono" name="telefono"
                                        value="<?= htmlspecialchars($datos_perfil['telefono']) ?>"
                                        placeholder="+51 987654321" pattern="^\+?[0-9\s-]{7,15}$"
                                        title="Formato de teléfono no válido. Use solo números y opcionalmente el signo '+'.">
                                    <div class="form-text">Ej: +51 987654321 o 987654321 (Máximo 15 caracteres)</div>
                                </div>

                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar
                                    Cambios</button>
                            </form>
                        </div>
                    </div>

                <?php elseif ($seccion_activa === 'direcciones'): ?>

                    <h3 class="border-bottom pb-2">Mis Direcciones de Envío</h3>
                    <p>Aquí puedes gestionar tus direcciones guardadas.</p>

                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-primary text-white">Agregar Nueva Dirección</div>
                        <div class="card-body">
                            <form method="POST" action="mi_perfil.php?seccion=direcciones">
                                <input type="hidden" name="accion" value="agregar_direccion">

                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección Completa</label>
                                    <input type="text" class="form-control" id="direccion" name="direccion" required
                                        placeholder="Calle, número, apto/interior">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="ciudad" class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" id="ciudad" name="ciudad" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="pais" class="form-label">País</label>
                                        <input type="text" class="form-control" id="pais" name="pais" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="codigo_postal" class="form-label">Código Postal</label>
                                        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal"
                                            required>
                                    </div>
                                    <div class="col-md-6 mb-3 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" id="es_principal"
                                                name="es_principal">
                                            <label class="form-check-label" for="es_principal">
                                                Establecer como principal
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle-fill"></i> Guardar
                                    Dirección</button>
                            </form>
                        </div>
                    </div>

                    <h4 class="mt-5 border-bottom pb-2">Direcciones Guardadas (<?= count($direcciones) ?>)</h4>
                    <?php if (empty($direcciones)): ?>
                        <div class="alert alert-info shadow-sm">Aún no tienes direcciones de envío guardadas.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($direcciones as $dir): ?>
                                <div
                                    class="list-group-item list-group-item-action <?= $dir['es_principal'] ? 'list-group-item-primary' : '' ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <i class="bi bi-house-door-fill"></i>
                                            <?= htmlspecialchars($dir['direccion']) ?>
                                            <?php if ($dir['es_principal']): ?>
                                                <span class="badge bg-primary ms-2">Principal</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted"><?= htmlspecialchars($dir['pais']) ?></small>
                                    </div>
                                    <p class="mb-1"><?= htmlspecialchars($dir['ciudad']) ?>, C.P.
                                        <?= htmlspecialchars($dir['codigo_postal']) ?></p>
                                    <small>
                                        <button class="btn btn-sm btn-outline-danger disabled">Eliminar</button>
                                        <button class="btn btn-sm btn-outline-secondary disabled">Editar</button>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($seccion_activa === 'pagos'): ?>

                    <h3 class="border-bottom pb-2">Métodos de Pago Guardados</h3>
                    <p>Solo guardamos una representación segura de tu tarjeta (los últimos 4 dígitos).</p>

                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-primary text-white">Agregar Nueva Tarjeta (Simulada)</div>
                        <div class="card-body">
                            <form method="POST" action="mi_perfil.php?seccion=pagos">
                                <input type="hidden" name="accion" value="agregar_tarjeta">

                                <div class="mb-3">
                                    <label for="nombre_tarjeta" class="form-label">Nombre en la Tarjeta</label>
                                    <input type="text" class="form-control" id="nombre_tarjeta" name="nombre_tarjeta"
                                        required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="numero_tarjeta" class="form-label">Número de Tarjeta (Completo)</label>
                                        <input type="text" class="form-control" id="numero_tarjeta" name="numero_tarjeta"
                                            required placeholder="Solo se guardarán los últimos 4 dígitos"
                                            pattern="[0-9]{13,16}" title="Ingrese entre 13 y 16 dígitos numéricos.">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="expiracion" class="form-label">Fecha de Expiración (MM/AA)</label>
                                        <input type="text" class="form-control" id="expiracion" name="expiracion" required
                                            placeholder="MM/AA" pattern="^(0[1-9]|1[0-2])\/\d{2}$"
                                            title="Formato MM/AA (Ej: 12/25)">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="tipo_tarjeta" class="form-label">Tipo de Tarjeta</label>
                                    <select class="form-select" id="tipo_tarjeta" name="tipo_tarjeta">
                                        <option value="Visa">Visa</option>
                                        <option value="Mastercard">Mastercard</option>
                                        <option value="Amex">American Express</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle-fill"></i> Guardar
                                    Tarjeta</button>
                            </form>
                        </div>
                    </div>

                    <h4 class="mt-5 border-bottom pb-2">Tarjetas Guardadas (<?= count($tarjetas) ?>)</h4>
                    <?php if (empty($tarjetas)): ?>
                        <div class="alert alert-info shadow-sm">Aún no tienes métodos de pago guardados.</div>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($tarjetas as $tar): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center shadow-sm mb-2">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">
                                            <i class="bi bi-credit-card-fill"></i>
                                            <?= htmlspecialchars($tar['tipo']) ?> terminada en
                                            ****<?= htmlspecialchars($tar['ultimos_4_digitos']) ?>
                                        </div>
                                        <small class="text-muted">Nombre: <?= htmlspecialchars($tar['nombre_tarjeta']) ?> | Expira:
                                            <?= htmlspecialchars($tar['expiracion']) ?></small>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-danger disabled">Eliminar</button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include 'assets/component/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>