<?php
session_start();
include 'assets/admin/db.php';

// 1. CONTROL DE ACCESO
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['usuario_id'];
$mensaje_error = "";
$mensaje_exito = "";
$seccion_activa = $_GET['seccion'] ?? 'dashboard';
$accion = $_POST['accion'] ?? null;

// 2. LÓGICA DE GESTIÓN DE DATOS

// --- A. Lógica de ACTUALIZAR PERFIL ---
if ($seccion_activa === 'perfil' && $accion === 'actualizar_perfil') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    // VALIDACIÓN (IDs 65-70, 34-39)
    if (empty($nombre) || empty($apellido)) {
        $mensaje_error = "Error (ID 69): El nombre y apellido son obligatorios.";
    } elseif (strlen($nombre) < 2 || strlen($apellido) < 2) {
        $mensaje_error = "Error (ID 66/126): El nombre y apellido deben tener al menos 2 caracteres.";
    } elseif (strlen($nombre) > 50 || strlen($apellido) > 50) {
        $mensaje_error = "Error (ID 67/127): El nombre y apellido no deben exceder los 50 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $nombre) || !preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $apellido)) {
        $mensaje_error = "Error (ID 68): El nombre y apellido solo pueden contener letras y espacios.";
    } elseif (!empty($telefono) && !preg_match('/^[0-9]{9}$/', $telefono)) {
        $mensaje_error = "Error (ID 36-39): El teléfono debe tener 9 dígitos y contener solo números.";
    } else {
        try {
            $stmt_update = $conn->prepare("
                INSERT INTO perfiles (id_usuario, nombres, apellidos, telefono)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE nombres = VALUES(nombres), apellidos = VALUES(apellidos), telefono = VALUES(telefono)
            ");
            $telefono_a_insertar = !empty($telefono) ? $telefono : NULL;
            $stmt_update->bind_param("isss", $id_usuario, $nombre, $apellido, $telefono_a_insertar);
            $stmt_update->execute();
            $mensaje_exito = "Perfil actualizado con éxito.";
            $_SESSION['nombre_usuario'] = $nombre; 
            $_SESSION['apellido_usuario'] = $apellido;
        } catch (mysqli_sql_exception $e) {
            $mensaje_error = "Error al actualizar el perfil: " . $e->getMessage();
        }
    }
}

// --- B. Lógica de CAMBIAR CONTRASEÑA ---
if ($seccion_activa === 'perfil' && $accion === 'cambiar_clave') {
    $clave_actual = $_POST['clave_actual'];
    $clave_nueva = $_POST['clave_nueva'];
    $clave_repetida = $_POST['clave_repetida'];

    // VALIDACIÓN (IDs 19, 21-26, 85, 87)
    if (empty($clave_actual) || empty($clave_nueva) || empty($clave_repetida)) {
        $mensaje_error = "Error (ID 25/87): Todos los campos de contraseña son obligatorios.";
    } elseif ($clave_nueva !== $clave_repetida) {
        $mensaje_error = "Error (ID 85): La nueva contraseña y su repetición no coinciden.";
    } elseif (strlen($clave_nueva) < 7) {
        $mensaje_error = "Error (ID 21/99): La nueva contraseña debe tener mínimo 7 caracteres.";
    } elseif (strlen($clave_nueva) > 30) {
        $mensaje_error = "Error (ID 22/100): La nueva contraseña debe tener máximo 30 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $clave_nueva)) {
        $mensaje_error = "Error (ID 23): La nueva contraseña debe contener al menos una mayúscula.";
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $clave_nueva)) { 
        $mensaje_error = "Error (ID 24): La nueva contraseña debe contener al menos un carácter especial.";
    } else {
        try {
            $stmt_check = $conn->prepare("SELECT clave_hash FROM usuarios WHERE id_usuario = ?");
            $stmt_check->bind_param("i", $id_usuario);
            $stmt_check->execute();
            $stmt_check->bind_result($clave_hash_db);
            
            if ($stmt_check->fetch() && password_verify($clave_actual, $clave_hash_db)) {
                $stmt_check->close();
                $nueva_clave_hash = password_hash($clave_nueva, PASSWORD_DEFAULT);
                $stmt_update = $conn->prepare("UPDATE usuarios SET clave_hash = ? WHERE id_usuario = ?");
                $stmt_update->bind_param("si", $nueva_clave_hash, $id_usuario);
                $stmt_update->execute();
                $mensaje_exito = "Contraseña actualizada con éxito.";
            } else {
                $mensaje_error = "La contraseña actual es incorrecta.";
            }
        } catch (mysqli_sql_exception $e) {
            $mensaje_error = "Error al cambiar la contraseña: " . $e->getMessage();
        }
    }
}

// --- C. Lógica de AGREGAR DIRECCIÓN ---
if ($seccion_activa === 'direcciones' && $accion === 'agregar_direccion') {
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $pais = trim($_POST['pais'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    $es_principal = isset($_POST['es_principal']) ? 1 : 0;

    // VALIDACIÓN (IDs 59-83)
    if (empty($direccion) || empty($ciudad) || empty($pais) || empty($codigo_postal)) {
        $mensaje_error = "Error (ID 63/69/75/81): Todos los campos de la dirección son obligatorios.";
    } elseif (strlen($direccion) < 10) {
        $mensaje_error = "Error (ID 60): La dirección debe tener al menos 10 caracteres.";
    } elseif (strlen($direccion) > 100) {
        $mensaje_error = "Error (ID 61): La dirección no debe exceder los 100 caracteres.";
    } elseif (!preg_match('/[a-zA-Z0-9]/', $direccion)) {
        $mensaje_error = "Error (ID 62): La dirección contiene caracteres inválidos.";
    } elseif (strlen($ciudad) < 2 || strlen($pais) < 2) {
        $mensaje_error = "Error (ID 66/72): Ciudad y País deben tener al menos 2 caracteres.";
    } elseif (strlen($ciudad) > 50 || strlen($pais) > 50) {
        $mensaje_error = "Error (ID 67/73): Ciudad y País no deben exceder los 50 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $ciudad) || !preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $pais)) {
        $mensaje_error = "Error (ID 68/74): Ciudad y País solo pueden contener letras y espacios.";
    
    // --- CORRECCIÓN CÓDIGO POSTAL (IDs 78, 79, 80) ---
    // Aplicando tu regla de "exactamente 4 dígitos"
    } elseif (!preg_match('/^[0-9]{4}$/', $codigo_postal)) {
        $mensaje_error = "Error (ID 78-80): El código postal debe tener 4 dígitos y contener solo números.";
    // --- FIN CORRECCIÓN ---
    
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

// --- D. Lógica de AGREGAR TARJETA ---
if ($seccion_activa === 'pagos' && $accion === 'agregar_tarjeta') {
    $nombre_tarjeta = trim($_POST['nombre_tarjeta'] ?? '');
    $numero_tarjeta = trim($_POST['numero_tarjeta'] ?? '');
    $expiracion = trim($_POST['expiracion'] ?? '');
    $tipo = trim($_POST['tipo_tarjeta'] ?? 'Visa');

    // VALIDACIÓN (IDs 40-54)
    if (empty($nombre_tarjeta) || empty($numero_tarjeta) || empty($expiracion)) {
        $mensaje_error = "Error (ID 45/49): Todos los campos de la tarjeta son obligatorios.";
    } elseif (strlen($nombre_tarjeta) < 3) {
        $mensaje_error = "Error (ID 41/106): El nombre en la tarjeta debe tener al menos 3 caracteres.";
    } elseif (strlen($nombre_tarjeta) > 50) {
        $mensaje_error = "Error (ID 41/107): El nombre en la tarjeta no debe exceder los 50 caracteres.";
    } elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $nombre_tarjeta)) {
        $mensaje_error = "Error (ID 42): El nombre en la tarjeta solo debe contener letras y espacios.";
    } elseif (!preg_match('/^[0-9]{13,16}$/', $numero_tarjeta)) {
        $mensaje_error = "Error (ID 41/42): El número de tarjeta debe tener entre 13 y 16 dígitos numéricos.";
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiracion)) {
        $mensaje_error = "Error (ID 46): El formato de expiración debe ser MM/AA.";
    } else {
        $ultimos_4_digitos = substr($numero_tarjeta, -4);
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


// 3. RECUPERAR DATOS FRESCOS PARA MOSTRAR
// (Se ejecuta siempre para refrescar la vista después de un POST)
$datos_perfil = [ 'nombre' => '', 'apellido' => '', 'email' => '', 'telefono' => '' ];
$stmt_data = $conn->prepare("SELECT u.email, p.nombres, p.apellidos, p.telefono FROM usuarios u LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario WHERE u.id_usuario = ?");
$stmt_data->bind_param("i", $id_usuario);
$stmt_data->execute();
$resultado_data = $stmt_data->get_result();
if ($fila = $resultado_data->fetch_assoc()) {
    $datos_perfil['nombre'] = $fila['nombres'] ?? '';
    $datos_perfil['apellido'] = $fila['apellidos'] ?? '';
    $datos_perfil['email'] = $fila['email'];
    $datos_perfil['telefono'] = $fila['telefono'] ?? '';
}

$direcciones = [];
$stmt_dir = $conn->prepare("SELECT id_direccion, direccion, ciudad, pais, codigo_postal, es_principal FROM direcciones WHERE id_usuario = ? ORDER BY es_principal DESC, id_direccion DESC");
$stmt_dir->bind_param("i", $id_usuario);
$stmt_dir->execute();
$direcciones = $stmt_dir->get_result()->fetch_all(MYSQLI_ASSOC);

$tarjetas = [];
$stmt_tar = $conn->prepare("SELECT id_tarjeta, nombre_tarjeta, ultimos_4_digitos, expiracion, tipo FROM tarjetas_usuario WHERE id_usuario = ?");
$stmt_tar->bind_param("i", $id_usuario);
$stmt_tar->execute();
$tarjetas = $stmt_tar->get_result()->fetch_all(MYSQLI_ASSOC);
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

        <div class="row">
            <div class="col-md-3">
                <div class="list-group shadow-sm">
                    <a href="mi_perfil.php?seccion=dashboard" class="list-group-item list-group-item-action <?= ($seccion_activa === 'dashboard') ? 'active' : '' ?>">
                        <i class="bi bi-speedometer"></i> Dashboard
                    </a>
                    <a href="mi_perfil.php?seccion=perfil" class="list-group-item list-group-item-action <?= ($seccion_activa === 'perfil') ? 'active' : '' ?>">
                        <i class="bi bi-person-circle"></i> Mi Perfil
                    </a>
                    <a href="mi_perfil.php?seccion=direcciones" class="list-group-item list-group-item-action <?= ($seccion_activa === 'direcciones') ? 'active' : '' ?>">
                        <i class="bi bi-geo-alt-fill"></i> Direcciones
                    </a>
                    <a href="mi_perfil.php?seccion=pagos" class="list-group-item list-group-item-action <?= ($seccion_activa === 'pagos') ? 'active' : '' ?>">
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
                    <p>Bienvenido, <strong><?= htmlspecialchars($datos_perfil['nombre'] . " " . $datos_perfil['apellido']) ?></strong>. </p>
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <div class="col">
                            <div class="card bg-light shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="bi bi-geo-alt-fill"></i> Direcciones</h5>
                                    <p class="card-text">Tienes <strong><?= count($direcciones) ?></strong> direcciones guardadas.</p>
                                    <a href="mi_perfil.php?seccion=direcciones" class="btn btn-sm btn-outline-primary">Gestionar Direcciones</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card bg-light shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="bi bi-credit-card-fill"></i> Pagos</h5>
                                    <p class="card-text">Tienes <strong><?= count($tarjetas) ?></strong> métodos de pago guardados.</p>
                                    <a href="mi_perfil.php?seccion=pagos" class="btn btn-sm btn-outline-primary">Gestionar Pagos</a>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($seccion_activa === 'perfil'): ?>

                    <h3 class="border-bottom pb-2">Datos Personales</h3>
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <form method="POST" action="mi_perfil.php?seccion=perfil">
                                <input type="hidden" name="accion" value="actualizar_perfil">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($datos_perfil['email']) ?>" disabled>
                                    <div class="form-text">El correo es tu usuario y no se puede cambiar aquí.</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nombre" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                               value="<?= htmlspecialchars($datos_perfil['nombre']) ?>" 
                                               required 
                                               minlength="2" 
                                               maxlength="50"
                                               pattern="[a-zA-Z\sñáéíóúÁÉÍÓÚ]+"
                                               title="Mín. 2, Máx. 50 caracteres. Solo letras y espacios. (IDs 66, 67, 68)">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="apellido" class="form-label">Apellido</label>
                                        <input type="text" class="form-control" id="apellido" name="apellido"
                                               value="<?= htmlspecialchars($datos_perfil['apellido']) ?>" 
                                               required
                                               minlength="2" 
                                               maxlength="50"
                                               pattern="[a-zA-Z\sñáéíóúÁÉÍÓÚ]+"
                                               title="Mín. 2, Máx. 50 caracteres. Solo letras y espacios. (IDs 66, 67, 68)">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">Teléfono (Opcional)</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono"
                                           value="<?= htmlspecialchars($datos_perfil['telefono']) ?>"
                                           pattern="[0-9]{9}"
                                           maxlength="9"
                                           title="Debe tener 9 dígitos (solo números). (IDs 36-39)">
                                    <div class="form-text">Ej: 987654321 (9 dígitos, solo números)</div>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Cambios</button>
                            </form>
                        </div>
                    </div>

                    <h3 class="border-bottom pb-2 mt-5">Cambiar Contraseña</h3>
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="POST" action="mi_perfil.php?seccion=perfil">
                                <input type="hidden" name="accion" value="cambiar_clave">
                                <div class="mb-3">
                                    <label for="clave_actual" class="form-label">Contraseña Actual</label>
                                    <input type="password" class="form-control" id="clave_actual" name="clave_actual" 
                                           required
                                           minlength="7"
                                           maxlength="30"
                                           title="Tu contraseña actual (mín. 7 caracteres). (ID 87)">
                                </div>
                                <div class="mb-3">
                                    <label for="clave_nueva" class="form-label">Contraseña Nueva</label>
                                    <input type="password" class="form-control" id="clave_nueva" name="clave_nueva" 
                                           required
                                           minlength="7"
                                           maxlength="30"
                                           aria-describedby="passwordHelpBlock">
                                    <div id="passwordHelpBlock" class="form-text">
                                        (IDs 19, 21-24) Debe tener 7-30 caracteres, 1 mayúscula y 1 carácter especial (ej. #, $, !).
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="clave_repetida" class="form-label">Repetir Contraseña Nueva</label>
                                    <input type="password" class="form-control" id="clave_repetida" name="clave_repetida" 
                                           required
                                           minlength="7"
                                           maxlength="30"
                                           title="Repite la nueva contraseña. (ID 85)">
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-key-fill"></i> Cambiar Contraseña</button>
                            </form>
                        </div>
                    </div>

                <?php elseif ($seccion_activa === 'direcciones'): ?>
                    <h3 class="border-bottom pb-2">Mis Direcciones de Envío</h3>
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-primary text-white">Agregar Nueva Dirección</div>
                        <div class="card-body">
                            <form method="POST" action="mi_perfil.php?seccion=direcciones">
                                <input type="hidden" name="accion" value="agregar_direccion">
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">Dirección Completa</label>
                                    <input type="text" class="form-control" id="direccion" name="direccion" required
                                           placeholder="Calle, número, apto/interior"
                                           minlength="10"
                                           maxlength="100"
                                           title="Mín. 10, Máx. 100 caracteres. (IDs 60, 61)">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="ciudad" class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" id="ciudad" name="ciudad" required
                                               minlength="2"
                                               maxlength="50"
                                               pattern="[a-zA-Z\sñáéíóúÁÉÍÓÚ]+"
                                               title="Mín. 2, Máx. 50 caracteres. Solo letras. (IDs 66, 67, 68)">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="pais" class="form-label">País</label>
                                        <input type="text" class="form-control" id="pais" name="pais" required
                                               minlength="2"
                                               maxlength="50"
                                               pattern="[a-zA-Z\sñáéíóúÁÉÍÓÚ]+"
                                               title="Mín. 2, Máx. 50 caracteres. Solo letras. (IDs 72, 73, 74)">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="codigo_postal" class="form-label">Código Postal</label>
                                        <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" required
                                               minlength="4"
                                               maxlength="4"
                                               pattern="[0-9]{4}"
                                               title="Debe tener 4 dígitos (solo números). (IDs 78, 80)">
                                    </div>
                                    <div class="col-md-6 mb-3 d-flex align-items-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" id="es_principal" name="es_principal">
                                            <label class="form-check-label" for="es_principal"> (ID 83) Establecer como principal </label>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle-fill"></i> Guardar Dirección</button>
                            </form>
                        </div>
                    </div>
                    
                    <h4 class="mt-5 border-bottom pb-2">Direcciones Guardadas (<?= count($direcciones) ?>)</h4>
                    <?php if (empty($direcciones)): ?>
                        <div class="alert alert-info shadow-sm">Aún no tienes direcciones de envío guardadas.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($direcciones as $dir): ?>
                                <div class="list-group-item list-group-item-action <?= $dir['es_principal'] ? 'list-group-item-primary' : '' ?>">
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
                                    <p class="mb-1"><?= htmlspecialchars($dir['ciudad']) ?>, C.P. <?= htmlspecialchars($dir['codigo_postal']) ?></p>
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
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header bg-primary text-white">Agregar Nueva Tarjeta (Simulada)</div>
                        <div class="card-body">
                            <form method="POST" action="mi_perfil.php?seccion=pagos">
                                <input type="hidden" name="accion" value="agregar_tarjeta">
                                <div class="mb-3">
                                    <label for="nombre_tarjeta" class="form-label">Nombre en la Tarjeta</label>
                                    <input type="text" class="form-control" id="nombre_tarjeta" name="nombre_tarjeta"
                                           required
                                           minlength="3"
                                           maxlength="50"
                                           pattern="[a-zA-Z\sñáéíóúÁÉÍÓÚ]+"
                                           title="Mín. 3, Máx. 50 caracteres. Solo letras. (IDs 40-45)">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="numero_tarjeta" class="form-label">Número de Tarjeta (Completo)</label>
                                        <input type="text" class="form-control" id="numero_tarjeta" name="numero_tarjeta"
                                               required placeholder="Solo se guardarán los últimos 4 dígitos"
                                               pattern="[0-9]{13,16}"
                                               maxlength="16"
                                               title="Entre 13 y 16 dígitos numéricos. (IDs 41, 42, 43)">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="expiracion" class="form-label">Fecha de Expiración (MM/AA)</label>
                                        <input type="text" class="form-control" id="expiracion" name="expiracion" required
                                               placeholder="MM/AA" 
                                               pattern="^(0[1-9]|1[0-2])\/\d{2}$"
                                               maxlength="5"
                                               title="Formato MM/AA (Ej: 12/25). (IDs 46, 47)">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="tipo_tarjeta" class="form-label">Tipo de Tarjeta</label>
                                    <select class="form-select" id="tipo_tarjeta" name="tipo_tarjeta">
                                        <option value="Visa">Visa</option>
                                        <option value="Mastercard">Mastercard</option>
                                        <option valueD="Amex">American Express</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle-fill"></i> Guardar Tarjeta</button>
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
                                            <?= htmlspecialchars($tar['tipo']) ?> terminada en ****<?= htmlspecialchars($tar['ultimos_4_digitos']) ?>
                                        </div>
                                        <small class="text-muted">Nombre: <?= htmlspecialchars($tar['nombre_tarjeta']) ?> | Expira: <?= htmlspecialchars($tar['expiracion']) ?></small>
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
    
    <script>
        function limitarEntrada(elemento, regex) {
            if (elemento) {
                elemento.addEventListener('keydown', function(event) {
                    const teclasPermitidas = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
                    if (teclasPermitidas.includes(event.key)) return;
                    if (event.key.length === 1 && !regex.test(event.key)) {
                        event.preventDefault(); // "No permitir la acción"
                    }
                });
            }
        }
        
        // Regex
        const regexLetrasEspacios = /^[a-zA-Z\sñáéíóúÁÉÍÓÚ]$/u;
        const regexNumeros = /^[0-9]$/;

        // Aplicar a "Mi Perfil"
        limitarEntrada(document.getElementById('nombre'), regexLetrasEspacios);
        limitarEntrada(document.getElementById('apellido'), regexLetrasEspacios);
        limitarEntrada(document.getElementById('telefono'), regexNumeros);

        // Aplicar a "Direcciones"
        limitarEntrada(document.getElementById('ciudad'), regexLetrasEspacios);
        limitarEntrada(document.getElementById('pais'), regexLetrasEspacios);
        limitarEntrada(document.getElementById('codigo_postal'), regexNumeros);

        // Aplicar a "Métodos de Pago"
        limitarEntrada(document.getElementById('nombre_tarjeta'), regexLetrasEspacios);
        limitarEntrada(document.getElementById('numero_tarjeta'), regexNumeros);
        
        // Un filtro especial para la fecha MM/AA
        const expInput = document.getElementById('expiracion');
        if (expInput) {
            expInput.addEventListener('keydown', function(event) {
                const teclasPermitidas = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
                if (teclasPermitidas.includes(event.key)) return;

                // Permitir solo números y el slash "/"
                if (event.key.length === 1 && !/^[0-9\/]$/.test(event.key)) {
                    event.preventDefault();
                }
            });
        }
    </script>
</body>
</html>