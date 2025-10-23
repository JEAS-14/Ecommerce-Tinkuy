<?php
session_start();
include 'assets/admin/db.php'; // Incluimos la conexión

// --- INICIO DE CALIDAD (SEGURIDAD) ---
// 1. Si no hay usuario logueado, fuera.
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); //
    exit;
}

// 2. Si el carrito está vacío, no hay nada que pagar.
if (empty($_SESSION['carrito'])) {
    header('Location: cart.php'); //
    exit;
}
// --- FIN DE CALIDAD (SEGURIDAD) ---

$id_usuario = $_SESSION['usuario_id'];
$mensaje_error = "";
$carrito_items = [];
$total_general = 0;


// --- LÓGICA DE PROCESAMIENTO (POST) ---
// (Esto se ejecuta cuando el usuario envía el formulario de pago)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Validar la dirección seleccionada (Calidad de Usabilidad)
    if (empty($_POST['id_direccion']) || !filter_var($_POST['id_direccion'], FILTER_VALIDATE_INT)) {
        $mensaje_error = "Por favor, selecciona una dirección de envío válida.";
    } else {
        $id_direccion_seleccionada = (int)$_POST['id_direccion'];
        // (Aquí iría la validación del método de pago, ej: $_POST['metodo_pago'])

        // --- INICIO DE CALIDAD (FIABILIDAD - TRANSACCIÓN ISO 25010) ---
        $conn->begin_transaction();
        
        try {
            // 2. Recalculamos el total 100% en el servidor (CALIDAD DE SEGURIDAD)
            // (Nunca confiamos en el precio que viene del HTML)
            $ids_variantes = array_keys($_SESSION['carrito']);
            $placeholders = implode(',', array_fill(0, count($ids_variantes), '?'));
            $tipos = str_repeat('i', count($ids_variantes));

            $stmt_precios = $conn->prepare("SELECT id_variante, precio FROM variantes_producto WHERE id_variante IN ($placeholders)");
            $stmt_precios->bind_param($tipos, ...$ids_variantes);
            $stmt_precios->execute();
            $resultado_precios = $stmt_precios->get_result();

            $precios_reales = [];
            while ($fila = $resultado_precios->fetch_assoc()) {
                $precios_reales[$fila['id_variante']] = (float)$fila['precio'];
            }
            
            $total_seguro = 0;
            foreach ($_SESSION['carrito'] as $id_variante => $item) {
                if (!isset($precios_reales[$id_variante])) {
                    // El producto fue eliminado mientras el usuario pagaba
                    throw new Exception("El producto ID $id_variante ya no existe.");
                }
                $total_seguro += $precios_reales[$id_variante] * $item['cantidad'];
            }
            // --- Fin del recálculo seguro del total ---

            // 3. INSERT 1: Crear el Pedido (Estado 2 = 'Pagado', según nuestros datos iniciales)
            $stmt_pedido = $conn->prepare(
                "INSERT INTO pedidos (id_usuario, id_direccion_envio, id_estado_pedido, total_pedido) VALUES (?, ?, 2, ?)"
            );
            $stmt_pedido->bind_param("iid", $id_usuario, $id_direccion_seleccionada, $total_seguro);
            $stmt_pedido->execute();
            $nuevo_pedido_id = $conn->insert_id; // Obtenemos el ID del pedido creado

            // 4. INSERT 2 (Loop): Guardar los Detalles del Pedido
            $stmt_detalle = $conn->prepare(
                "INSERT INTO detalle_pedido (id_pedido, id_variante, cantidad, precio_historico) VALUES (?, ?, ?, ?)"
            );
            
            // 5. UPDATE (Loop): Descontar el Stock (CALIDAD DE FIABILIDAD)
            $stmt_stock = $conn->prepare(
                "UPDATE variantes_producto SET stock = stock - ? WHERE id_variante = ? AND stock >= ?"
            );

            foreach ($_SESSION['carrito'] as $id_variante => $item) {
                $cantidad = $item['cantidad'];
                $precio_historico = $precios_reales[$id_variante];
                
                // Insertamos el detalle
                $stmt_detalle->bind_param("iiid", $nuevo_pedido_id, $id_variante, $cantidad, $precio_historico);
                $stmt_detalle->execute();

                // Actualizamos el stock
                $stmt_stock->bind_param("iii", $cantidad, $id_variante, $cantidad);
                $stmt_stock->execute();
                
                // Verificamos si el stock se pudo actualizar (calidad de concurrencia)
                if ($stmt_stock->affected_rows === 0) {
                    throw new Exception("¡Ups! Stock insuficiente para el producto con ID $id_variante.");
                }
            }
            
            // 6. INSERT 3: Registrar la Transacción (Simulada)
            $stmt_transaccion = $conn->prepare(
                "INSERT INTO transacciones (id_pedido, metodo_pago, monto, estado_pago, id_externo_gateway) VALUES (?, 'Tarjeta (Simulada)', ?, 'exitoso', ?)"
            );
            $id_gateway_simulado = "txn_simulado_" . uniqid();
            $stmt_transaccion->bind_param("ids", $nuevo_pedido_id, $total_seguro, $id_gateway_simulado);
            $stmt_transaccion->execute();

            // 7. COMMIT: ¡Todo salió bien! Confirmamos los cambios en la BD.
            $conn->commit();

            // 8. Limpiamos el carrito y redirigimos a la pág. de gracias
            unset($_SESSION['carrito']);
            $_SESSION['pedido_exitoso_id'] = $nuevo_pedido_id; // Guardamos el ID para 'gracias.php'
            header("Location: gracias.php"); //
            exit;

        } catch (Exception $e) {
            // 7. ROLLBACK: ¡Algo falló! Deshacemos todos los cambios.
            $conn->rollback();
            $mensaje_error = "Error al procesar el pedido: " . $e->getMessage();
        }
        // --- FIN DE CALIDAD (FIABILIDAD - TRANSACCIÓN) ---
    }
}

// --- LÓGICA DE VISUALIZACIÓN (GET) ---
// (Si no es un POST, o si el POST falló y mostró un error, mostramos la página)

// 1. Buscamos las direcciones del usuario (CALIDAD DE USABILIDAD)
$stmt_direcciones = $conn->prepare("SELECT id_direccion, direccion, ciudad, pais FROM direcciones WHERE id_usuario = ?");
$stmt_direcciones->bind_param("i", $id_usuario);
$stmt_direcciones->execute();
$resultado_direcciones = $stmt_direcciones->get_result();
$direcciones = $resultado_direcciones->fetch_all(MYSQLI_ASSOC);

// 2. Buscamos los detalles del carrito para el resumen (Usamos la misma lógica de cart.php)
$ids_variantes = array_keys($_SESSION['carrito']);
$placeholders = implode(',', array_fill(0, count($ids_variantes), '?'));
$tipos = str_repeat('i', count($ids_variantes));
$query_carrito = "
    SELECT v.id_variante, v.talla, v.color, p.nombre_producto, p.imagen_principal
    FROM variantes_producto AS v JOIN productos AS p ON v.id_producto = p.id_producto
    WHERE v.id_variante IN ($placeholders)
";
$stmt_carrito = $conn->prepare($query_carrito);
$stmt_carrito->bind_param($tipos, ...$ids_variantes);
$stmt_carrito->execute();
$resultado_carrito = $stmt_carrito->get_result();
$detalles_productos = [];
while ($fila = $resultado_carrito->fetch_assoc()) {
    $detalles_productos[$fila['id_variante']] = $fila;
}

// 3. Preparamos el array final para el HTML
foreach ($_SESSION['carrito'] as $id_variante => $item) {
    if (isset($detalles_productos[$id_variante])) {
        $detalles = $detalles_productos[$id_variante];
        $precio = $item['precio']; // Precio seguro de la sesión
        $cantidad = $item['cantidad'];
        $subtotal = $precio * $cantidad;
        $total_general += $subtotal;
        
        $carrito_items[] = [
            'nombre' => $detalles['nombre_producto'],
            'imagen' => $detalles['imagen_principal'],
            'talla' => $detalles['talla'],
            'color' => $detalles['color'],
            'cantidad' => $cantidad,
            'subtotal' => $subtotal
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Finalizar Pago | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
</head>
<body>
    <?php include 'assets/component/navbar.php'; // ?>

    <div class="container my-5">
        <h1 class="text-center mb-4">Finalizar Compra</h1>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-danger alert-error-animated"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-5">
                <div class="col-lg-7">
                    <h4 class="mb-3">Dirección de Envío</h4>
                    <?php if (empty($direcciones)): ?>
                        <div class="alert alert-warning">
                            No tienes direcciones guardadas. 
                            <a href="mi_perfil.php?seccion=direcciones" class="alert-link">Agrega una dirección</a> antes de continuar.
                        </div>
                    <?php else: ?>
                        <div class="list-group mb-3">
                            <?php foreach ($direcciones as $dir): ?>
                                <label class="list-group-item list-group-item-action">
                                    <input class="form-check-input" type="radio" name="id_direccion" value="<?= $dir['id_direccion'] ?>" required>
                                    <strong><?= htmlspecialchars($dir['direccion']) ?></strong>
                                    <small class="d-block text-muted"><?= htmlspecialchars($dir['ciudad']) ?>, <?= htmlspecialchars($dir['pais']) ?></small>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <hr class="my-4">

                    <h4 class="mb-3">Método de Pago</h4>
                    <div class="my-3">
                        <div class="form-check">
                            <input id="tarjeta" name="metodo_pago" type="radio" class="form-check-input" value="tarjeta" checked required>
                            <label class="form-check-label" for="tarjeta">Tarjeta de Crédito/Débito (Simulado)</label>
                        </div>
                    </div>
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label for="cc-name" class="form-label">Nombre en la tarjeta</label>
                            <input type="text" class="form-control" id="cc-name" placeholder="Juan Perez" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cc-number" class="form-label">Número de tarjeta</label>
                            <input type="text" class="form-control" id="cc-number" placeholder="4444 4444 4444 4444" required>
                        </div>
                        <div class="col-md-3">
                            <label for="cc-expiration" class="form-label">Expiración</label>
                            <input type="text" class="form-control" id="cc-expiration" placeholder="MM/YY" required>
                        </div>
                        <div class="col-md-3">
                            <label for="cc-cvv" class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cc-cvv" placeholder="123" required>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <button class="w-100 btn btn-success btn-lg" type="submit" <?php if (empty($direcciones)) echo 'disabled'; ?>>
                        <i class="bi bi-lock-fill"></i> Pagar S/ <?= number_format($total_general, 2) ?>
                    </button>
                </div>

                <div class="col-lg-5">
                    <h4 class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-primary">Tu Carrito</span>
                        <span class="badge bg-primary rounded-pill"><?= count($carrito_items) ?></span>
                    </h4>
                    <ul class="list-group mb-3">
                        <?php foreach ($carrito_items as $item): ?>
                        <li class="list-group-item d-flex justify-content-between lh-sm">
                            <div>
                                <h6 class="my-0"><?= htmlspecialchars($item['nombre']) ?></h6>
                                <small class="text-muted">
                                    <?= htmlspecialchars($item['talla']) ?> / <?= htmlspecialchars($item['color']) ?> 
                                    (Cant: <?= $item['cantidad'] ?>)
                                </small>
                            </div>
                            <span class="text-muted">S/ <?= number_format($item['subtotal'], 2) ?></span>
                        </li>
                        <?php endforeach; ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total (S/)</span>
                            <strong>S/ <?= number_format($total_general, 2) ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </form>
    </div>

    <?php include 'assets/component/footer.php'; // ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
document.addEventListener('DOMContentLoaded', () => {
  const inputNumero = document.getElementById('cc-number');
  const inputCVV = document.getElementById('cc-cvv');
  const inputExp = document.getElementById('cc-expiration');
  const botonPagar = document.querySelector('button[type="submit"]');

  // Crear/insertar icono y feedback si no existen
  const labelNumero = document.querySelector('label[for="cc-number"]');
  const icono = document.createElement('i');
  icono.className = 'bi bi-credit-card text-secondary ms-2';
  labelNumero.appendChild(icono);

  const feedback = document.createElement('div');
  feedback.className = 'form-text mt-1';
  inputNumero.parentNode.appendChild(feedback);

  // Formatea, detecta tipo, valida longitud y Luhn
  inputNumero.addEventListener('input', () => {
    let raw = inputNumero.value.replace(/\D/g, ''); // solo dígitos puros
    // Formateo visual: espacios cada 4 (pero Amex tiene 4-6-5, manejamos solo visual 4s para simplicidad)
    let formatted = raw.match(/.{1,4}/g);
    inputNumero.value = formatted ? formatted.join(' ') : '';

    const tipo = detectarTipoTarjeta(raw);
    const validoLongitud = validarLongitudPorTipo(raw, tipo);
    const pasaLuhn = raw.length >= 12 ? luhnCheck(raw) : false; // Luhn solo si hay suficientes dígitos
    actualizarIcono(tipo);

    // Mensajes de feedback
    if (raw.length === 0) {
      feedback.textContent = '';
      feedback.className = 'form-text mt-1 text-muted';
      inputNumero.classList.remove('is-invalid', 'is-valid');
    } else if (!tipo) {
      feedback.textContent = 'Tipo de tarjeta no reconocido todavía.';
      feedback.className = 'form-text mt-1 text-warning';
      inputNumero.classList.remove('is-valid');
      inputNumero.classList.add('is-invalid');
    } else if (!validoLongitud) {
      const expect = longitudEsperadaTexto(tipo);
      feedback.textContent = `Formato incorrecto para ${tipo}. ${expect}.`;
      feedback.className = 'form-text mt-1 text-danger';
      inputNumero.classList.remove('is-valid');
      inputNumero.classList.add('is-invalid');
    } else if (!pasaLuhn) {
      feedback.textContent = `El número parece inválido (falló la comprobación Luhn). Revisa los dígitos.`;
      feedback.className = 'form-text mt-1 text-danger';
      inputNumero.classList.remove('is-valid');
      inputNumero.classList.add('is-invalid');
    } else {
      feedback.textContent = `Tarjeta detectada: ${tipo}. Número válido.`;
      feedback.className = 'form-text mt-1 text-success';
      inputNumero.classList.remove('is-invalid');
      inputNumero.classList.add('is-valid');
    }
  });

  // Limitar y validar CVV (Amex 4, otros 3)
  inputCVV.addEventListener('input', () => {
    inputCVV.value = inputCVV.value.replace(/\D/g, '');
    const raw = document.getElementById('cc-number').value.replace(/\D/g, '');
    const tipo = detectarTipoTarjeta(raw);
    const max = (tipo === 'Amex') ? 4 : 3;
    inputCVV.value = inputCVV.value.slice(0, max);
  });

  // Expiración MM/YY con máscara simple
  inputExp.addEventListener('input', () => {
    let v = inputExp.value.replace(/\D/g, '').slice(0,4);
    if (v.length >= 3) {
      inputExp.value = v.slice(0,2) + '/' + v.slice(2);
    } else {
      inputExp.value = v;
    }
  });

  // Evitar envío si la tarjeta no pasa las validaciones cliente-side
  document.querySelector('form').addEventListener('submit', (e) => {
    const raw = inputNumero.value.replace(/\D/g, '');
    const tipo = detectarTipoTarjeta(raw);
    const validoLongitud = validarLongitudPorTipo(raw, tipo);
    const pasaLuhn = luhnCheck(raw);

    if (!tipo || !validoLongitud || !pasaLuhn) {
      e.preventDefault();
      inputNumero.focus();
      feedback.textContent = 'Revisa el número de tarjeta. No se puede procesar hasta que sea válido.';
      feedback.className = 'form-text mt-1 text-danger';
      return false;
    }

    // Además aquí podrías deshabilitar el botón y mostrar "procesando..."
    botonPagar.disabled = true;
    botonPagar.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';
  });

  // --- Funciones auxiliares ---

  function detectarTipoTarjeta(num) {
    if (!num) return null;
    // MasterCard: 51-55, 2221-2720
    const reMaster1 = /^(5[1-5][0-9]{0,})$/;
    const reMaster2 = /^(22[2-9][0-9]{0,}|2[3-6][0-9]{0,}|27[01][0-9]{0,}|2720[0-9]{0,})$/;
    if (/^4[0-9]{0,}$/.test(num)) return 'Visa';
    if (reMaster1.test(num) || reMaster2.test(num)) return 'MasterCard';
    if (/^3[47][0-9]{0,}$/.test(num)) return 'Amex';
    if (/^6(?:011|5[0-9]{2}|4[4-9][0-9]|22[1-9])[0-9]{0,}$/.test(num)) return 'Discover';
    return null;
  }

  function validarLongitudPorTipo(num, tipo) {
    const len = num.length;
    if (!tipo) return false;
    switch(tipo) {
      case 'Visa': return (len === 13 || len === 16 || len === 19);
      case 'MasterCard': return (len === 16);
      case 'Amex': return (len === 15);
      case 'Discover': return (len === 16);
      default: return false;
    }
  }

  function longitudEsperadaTexto(tipo) {
    switch(tipo) {
      case 'Visa': return 'Visa puede tener 13, 16 o 19 dígitos.';
      case 'MasterCard': return 'MasterCard debe tener 16 dígitos.';
      case 'Amex': return 'American Express debe tener 15 dígitos.';
      case 'Discover': return 'Discover debe tener 16 dígitos.';
      default: return '';
    }
  }

  function actualizarIcono(tipo) {
    icono.className = 'bi ms-2';
    switch(tipo) {
      case 'Visa':
        icono.classList.add('bi-credit-card-2-front-fill', 'text-primary');
        break;
      case 'MasterCard':
        icono.classList.add('bi-credit-card-2-back-fill', 'text-warning');
        break;
      case 'Amex':
        icono.classList.add('bi-credit-card', 'text-info');
        break;
      case 'Discover':
        icono.classList.add('bi-credit-card', 'text-success');
        break;
      default:
        icono.classList.add('bi-credit-card', 'text-secondary');
    }
  }

  // Luhn algorithm (returns true if number passes check)
  function luhnCheck(val) {
    if (!val || val.length < 12) return false;
    let sum = 0;
    let shouldDouble = false;
    for (let i = val.length - 1; i >= 0; i--) {
      let digit = parseInt(val.charAt(i), 10);
      if (shouldDouble) {
        digit *= 2;
        if (digit > 9) digit -= 9;
      }
      sum += digit;
      shouldDouble = !shouldDouble;
    }
    return (sum % 10) === 0;
  }
});
</script>

</body>
</html>