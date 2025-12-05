<?php
// public/index.php
// Punto de entrada principal del sistema Ecommerce-Tinkuy

session_start();
define('BASE_PATH', dirname(__DIR__));

// 🧠 CORE: conexión y validaciones
require_once BASE_PATH . '/src/Core/db.php';
require_once BASE_PATH . '/src/Core/validaciones.php';

// 🧩 MODELOS
require_once BASE_PATH . '/src/Models/Producto.php';
require_once BASE_PATH . '/src/Models/Categoria.php';
require_once BASE_PATH . '/src/Models/Mensaje.php';

// 🎮 CONTROLADORES
// Controladores de vendedor se requieren solo en sus rutas para evitar ejecución
// inmediata al incluir el archivo (los controladores actuales ejecutan lógica al require).
// Controlador de Mensajes (solo definición de clase, sin efectos secundarios)
require_once BASE_PATH . '/src/Controllers/MensajesController.php';

$base_url = "/Ecommerce-Tinkuy/public/index.php";
$page = $_GET['page'] ?? 'index';

// 🧭 RUTEO PRINCIPAL
switch ($page) {

    /* =======================
     * 🏠 PÁGINA DE INICIO
     * ======================= */
    case 'index':
        $modeloProducto = new Producto();
        $productos_destacados = $modeloProducto->getProductosDestacados($conn);
        require BASE_PATH . '/src/Views/index.php';
        break;

    /* =======================
     * 🔑 AUTENTICACIÓN
     * ======================= */
    case 'login':
        require BASE_PATH . '/src/Views/auth/login.php';
        break;

    case 'register':
        require BASE_PATH . '/src/Views/auth/register.php';
        break;

    case 'logout':
        session_destroy();
        header("Location: " . $base_url . "?page=index");
        exit;
        break;

    case 'forgot_password':
        require BASE_PATH . '/src/Views/auth/forgot_password.php';
        break;

    case 'reset_password':
        require BASE_PATH . '/src/Views/auth/reset_password.php';
        break;

    /* =======================
     * 🛍️ PRODUCTOS
     * ======================= */
    case 'products':
    case 'products.php':
        $modeloProducto = new Producto();
        $modeloCategoria = new Categoria();

        $filtros = [
            'categoria' => filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT) ?: null,
            'buscar' => trim(filter_input(INPUT_GET, 'buscar', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'orden' => $_GET['orden'] ?? 'nombre_asc'
        ];
        if ($filtros['categoria'] === 0) $filtros['categoria'] = null;

        $productos_listados = $modeloProducto->getProductosFiltrados($conn, $filtros);
        $categorias = $modeloCategoria->getTodasCategorias($conn);
        $total_productos = count($productos_listados);

        $id_categoria_filtro = $filtros['categoria'];
        $termino_busqueda = $filtros['buscar'];
        $orden = $filtros['orden'];
        $filtros_activos = ($id_categoria_filtro !== null || !empty($termino_busqueda));

        require BASE_PATH . '/src/Views/producto/products.php';
        break;

    case 'producto':
        if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
            header("Location: index.php?page=products");
            exit;
        }

        $id_producto = $_GET['id'];
        $modeloProducto = new Producto();
        $producto = $modeloProducto->getProductoActivoPorId($conn, $id_producto);

        if (is_null($producto)) {
            header("Location: index.php?page=products&error=notfound");
            exit;
        }

        $variantes = $modeloProducto->getVariantesActivasPorId($conn, $id_producto);
        $variantes_json = json_encode($variantes);

        $ruta_base_principal = "/Ecommerce-Tinkuy/public/img/productos/";
        $ruta_base_variantes = "/Ecommerce-Tinkuy/public/img/productos/variantes/";
        $imagen_mostrada_inicial = htmlspecialchars($producto['imagen_principal']);

        require BASE_PATH . '/src/Views/producto/producto.php';
        break;

    /* =======================
     * 🛒 CARRITO Y PEDIDOS
     * ======================= */
    case 'cart':
        $carrito_items = [];
        $total_general = 0;

        if (isset($_SESSION['carrito']) && !empty($_SESSION['carrito'])) {
            $ids_variantes = array_keys($_SESSION['carrito']);
            $modeloProducto = new Producto();
            $detalles_productos = $modeloProducto->getProductosDelCarrito($conn, $ids_variantes);

            foreach ($_SESSION['carrito'] as $id_variante => $item_sesion) {
                if (isset($detalles_productos[$id_variante])) {
                    $detalles_bd = $detalles_productos[$id_variante];
                    $cantidad = $item_sesion['cantidad'];
                    $precio = $detalles_bd['precio'];
                    $subtotal = $precio * $cantidad;
                    $total_general += $subtotal;

                    $imagen_final = $detalles_bd['imagen_principal'] ?: 'default.png';
                    if (!empty($detalles_bd['imagen_variante'])) {
                        $imagen_final = 'variantes/' . $detalles_bd['imagen_variante'];
                    }

                    $carrito_items[] = [
                        'id_variante' => $id_variante,
                        'nombre' => $detalles_bd['nombre_producto'],
                        'imagen_final' => $imagen_final,
                        'talla' => $detalles_bd['talla'],
                        'color' => $detalles_bd['color'],
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'subtotal' => $subtotal
                    ];
                } else {
                    unset($_SESSION['carrito'][$id_variante]);
                }
            }
        }

        require BASE_PATH . '/src/Views/pedido/cart.php';
        break;

    case 'agregar_carrito':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (
                !isset($_POST['id_variante']) || !filter_var($_POST['id_variante'], FILTER_VALIDATE_INT) ||
                !isset($_POST['cantidad']) || !filter_var($_POST['cantidad'], FILTER_VALIDATE_INT) ||
                $_POST['cantidad'] <= 0
            ) {
                $_SESSION['mensaje_error'] = "Datos inválidos para agregar al carrito.";
                header("Location: $base_url?page=products");
                exit;
            }

            $id_variante = (int)$_POST['id_variante'];
            $cantidad_solicitada = (int)$_POST['cantidad'];

            $stmt = $conn->prepare("SELECT precio, stock, id_producto FROM variantes_producto WHERE id_variante = ?");
            $stmt->bind_param("i", $id_variante);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 1) {
                $variante = $resultado->fetch_assoc();
                $stock_real = $variante['stock'];
                $id_producto_padre = $variante['id_producto'];

                if ($cantidad_solicitada > $stock_real) {
                    $_SESSION['mensaje_error'] = "No hay suficiente stock.";
                    header("Location: $base_url?page=producto&id=" . $id_producto_padre);
                    exit;
                }

                if (isset($_SESSION['carrito'][$id_variante])) {
                    $nueva_cantidad = $_SESSION['carrito'][$id_variante]['cantidad'] + $cantidad_solicitada;
                    if ($nueva_cantidad > $stock_real) {
                        $_SESSION['mensaje_error'] = "No puedes agregar más de $stock_real unidades.";
                        header("Location: $base_url?page=cart");
                        exit;
                    } else {
                        $_SESSION['carrito'][$id_variante]['cantidad'] = $nueva_cantidad;
                    }
                } else {
                    $_SESSION['carrito'][$id_variante] = ['cantidad' => $cantidad_solicitada];
                }

                $_SESSION['mensaje_exito'] = "Producto agregado al carrito.";
                header("Location: $base_url?page=cart");
                exit;

            } else {
                $_SESSION['mensaje_error'] = "El producto no existe.";
                header("Location: $base_url?page=products");
                exit;
            }
        } else {
            header("Location: $base_url?page=index");
            exit;
        }
        break;

    case 'eliminar_carrito':
        if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
            $id_variante = (int)$_GET['id'];
            if (isset($_SESSION['carrito'][$id_variante])) {
                unset($_SESSION['carrito'][$id_variante]);
                $_SESSION['mensaje_exito'] = "Producto eliminado del carrito.";
            } else {
                $_SESSION['mensaje_error'] = "El producto no se pudo encontrar.";
            }
        } else {
            $_SESSION['mensaje_error'] = "ID de producto no válido.";
        }
        header("Location: $base_url?page=cart");
        exit;
        break;

    /* =======================
     * 🧾 PEDIDOS
     * ======================= */
    case 'pedidos':
        require BASE_PATH . '/src/Views/pedido/pedidos.php';
        break;

    case 'pago':
        require BASE_PATH . '/src/Views/pedido/pago.php';
        break;

    case 'gracias':
        require BASE_PATH . '/src/Views/pedido/gracias.php';
        break;

    /* =======================
     * 👤 USUARIO
     * ======================= */
    case 'ver_pedido':
        require BASE_PATH . '/src/Views/pedido/ver_pedido.php';
        break;

    case 'mi_perfil':
        require BASE_PATH . '/src/Views/user/mi_perfil.php';
        break;

    case 'mi_perfil_vendedor':
        require_once BASE_PATH . '/src/Controllers/VendedorController.php';
        $vendedorController = new VendedorController();
        $datos = $vendedorController->actualizarPerfil();
        extract($datos);
        require BASE_PATH . '/src/Views/vendedor/perfil/mi_perfil_vendedor.php';
        break;

    case 'vendedor_dashboard':
        // El controlador prepara las variables necesarias para la vista
        require_once BASE_PATH . '/src/Controllers/VendedorDashboardController.php';
        require BASE_PATH . '/src/Views/vendedor/dashboard.php';
        break;

    /* =======================
     * 👨‍💼 VENDEDOR - rutas MVC
     * ======================= */
    case 'vendedor_productos':
        require_once BASE_PATH . '/src/Controllers/VendedorController.php';
        $vendedorController = new VendedorController();
        $datos = $vendedorController->listarProductos();
        extract($datos);
        require BASE_PATH . '/src/Views/vendedor/productos/productos.php';
        break;

    case 'vendedor_agregar_producto':
        require_once BASE_PATH . '/src/Controllers/VendedorController.php';
        $vendedorController = new VendedorController();
        $datos = $vendedorController->agregarProducto();
        extract($datos);
        require BASE_PATH . '/src/Views/vendedor/productos/agregar_producto.php';
        break;

    case 'vendedor_editar_producto':
        $id_producto = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id_producto) {
            $_SESSION['mensaje_error'] = "ID de producto inválido";
            header("Location: $base_url?page=vendedor_productos");
            exit;
        }
    require_once BASE_PATH . '/src/Controllers/VendedorController.php';
    $vendedorController = new VendedorController();
    $datos = $vendedorController->editarProducto($id_producto);
    extract($datos);
    require BASE_PATH . '/src/Views/vendedor/productos/editar_producto.php';
        break;

    case 'vendedor_eliminar_producto':
        $id_producto = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id_producto) {
            $_SESSION['mensaje_error'] = "ID de producto inválido";
            header("Location: $base_url?page=vendedor_productos");
            exit;
        }
        require_once BASE_PATH . '/src/Controllers/VendedorController.php';
        $vendedorController = new VendedorController();
        $datos = $vendedorController->eliminarProducto($id_producto);
        extract($datos);
        if (!empty($mensaje_exito)) {
            $_SESSION['mensaje_exito'] = $mensaje_exito;
            header("Location: $base_url?page=vendedor_productos");
            exit;
        }
        header("Location: $base_url?page=vendedor_productos");
        break;

    case 'vendedor_cambiar_estado':
        $id_producto = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
        $nuevo_estado = $_GET['estado'] ?? '';
        if (!$id_producto || !in_array($nuevo_estado, ['activo', 'inactivo'])) {
            $_SESSION['mensaje_error'] = "Parámetros inválidos";
            header("Location: $base_url?page=vendedor_productos");
            exit;
        }
        require_once BASE_PATH . '/src/Controllers/VendedorController.php';
        $vendedorController = new VendedorController();
        $res = $vendedorController->cambiarEstado($id_producto, $nuevo_estado);
        if ($res['success']) $_SESSION['mensaje_exito'] = $res['mensaje']; else $_SESSION['mensaje_error'] = $res['mensaje'];
        header("Location: $base_url?page=vendedor_productos");
        break;

    case 'vendedor_cambiar_estado_variante':
        $id_producto = filter_var($_GET['id_producto'] ?? 0, FILTER_VALIDATE_INT);
        $id_variante = filter_var($_GET['id_variante'] ?? 0, FILTER_VALIDATE_INT);
        $nuevo_estado = $_GET['estado'] ?? '';
        
        if (!$id_producto || !$id_variante || !in_array($nuevo_estado, ['activo', 'inactivo'])) {
            $_SESSION['mensaje_error'] = "Parámetros inválidos para la variante";
            header("Location: $base_url?page=vendedor_editar_producto&id=$id_producto");
            exit;
        }
        
        require_once BASE_PATH . '/src/Controllers/VendedorController.php';
        $vendedorController = new VendedorController();
        $res = $vendedorController->cambiarEstadoVariante($id_producto, $id_variante, $nuevo_estado);
        
        if ($res['success']) {
            $_SESSION['mensaje_exito'] = $res['mensaje'];
        } else {
            $_SESSION['mensaje_error'] = $res['mensaje'];
        }
        
        header("Location: $base_url?page=vendedor_editar_producto&id=$id_producto");
        break;

    case 'vendedor_ventas':
        require_once BASE_PATH . '/src/Controllers/VendedorController.php';
        $vendedorController = new VendedorController();
        $datos = $vendedorController->listarVentas();
        extract($datos);
        require_once BASE_PATH . '/src/Controllers/VentasController.php';
        
        $ventasController = new VentasController($conn);
        $items_vendidos = $ventasController->listarVentasCompletadas($_SESSION['usuario_id']);
        $total_ingresos = $ventasController->calcularTotalIngresos($items_vendidos);
        
        require BASE_PATH . '/src/Views/vendedor/ventas/ventas.php';
        break;

    case 'vendedor_envios':
        require_once BASE_PATH . '/src/Controllers/VendedorController.php';
        $vendedorController = new VendedorController();
        $datos = $vendedorController->listarEnvios();
        extract($datos);
        require_once BASE_PATH . '/src/Controllers/EnviosController.php';
        
        $enviosController = new EnviosController($conn);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar_envio') {
            $resultado = $enviosController->registrarEnvio(
                (int)$_POST['id_detalle_envio'],
                (int)$_POST['id_empresa_envio'],
                trim($_POST['numero_seguimiento']),
                $_SESSION['usuario_id']
            );
            
            if ($resultado['success']) {
                $_SESSION['mensaje_exito'] = $resultado['message'];
            } else {
                $_SESSION['mensaje_error'] = $resultado['message'];
            }
            header('Location: ' . $base_url . '?page=vendedor_envios');
            exit;
        }
        
        $items_pendientes = $enviosController->listarEnviosPendientes($_SESSION['usuario_id']);
        $empresas_envio = $enviosController->listarEmpresasEnvio();
        
        require BASE_PATH . '/src/Views/vendedor/envios/envios.php';
        break;

    /* =======================
     * 🛡 ADMIN - rutas MVC (placeholders para vistas en src/Views/admin)
     * ======================= */
    case 'admin_login': // <-- RUTA NUEVA
        require_once BASE_PATH . '/src/Controllers/AdminController.php';
        $adminController = new AdminController($conn);
        $adminController->login(); // Llama al método 'login'
        break;
        
    case 'admin_procesar_login': // <-- RUTA NUEVA
        require_once BASE_PATH . '/src/Controllers/AdminController.php';
        $adminController = new AdminController($conn);
         $adminController->procesarLogin(); // Llama al método 'procesarLogin'
         //  break;
    case 'admin_dashboard': 
        require_once BASE_PATH . '/src/Controllers/AdminController.php';
        $adminController = new AdminController($conn);
        $datos = $adminController->dashboard();
        extract($datos);
        require BASE_PATH . '/src/Views/admin/dashboard.php';
        break;
     case 'admin_pedidos':
        require_once BASE_PATH . '/src/Controllers/AdminController.php'; // 1. Carga el controlador
        $adminController = new AdminController($conn);                   // 2. Pasa la BBDD
        $datos = $adminController->pedidos();                        // 3. Obtiene los datos
        extract($datos);
        require BASE_PATH . '/src/Views/admin/pedidos/pedidos.php';
        break;
        case 'admin_ver_pedido':
        // --- INICIO DE CALIDAD (SEGURIDAD) ---
        // 1. Validamos el ID del pedido aquí, antes de llamar al controlador
        if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
            $_SESSION['mensaje_error'] = "ID de pedido no válido.";
            header('Location: ?page=admin_pedidos'); // Redirigimos a la lista
            exit;
        }
        $id_pedido_actual = (int)$_GET['id'];
        // --- FIN DE CALIDAD (SEGURIDAD) ---

        require_once BASE_PATH . '/src/Controllers/AdminController.php'; // 1. Carga el controlador
        $adminController = new AdminController($conn);                   // 2. Pasa la BBDD
        $datos = $adminController->verPedido($id_pedido_actual);       // 3. Obtiene los datos (pasando el ID)
        extract($datos);                                               // 4. Prepara las variables
        require BASE_PATH . '/src/Views/admin/pedidos/ver_pedido.php';   // 5. Carga la vista LIMPIA
        break;

    case 'admin_productos':
        require_once BASE_PATH . '/src/Controllers/AdminProductosController.php'; // 1. Carga el NUEVO controlador
        $controller = new AdminProductosController($conn);                     // 2. Pasa la BBDD
        $datos = $controller->listarProductos();                           // 3. Obtiene los datos (maneja POST y GET)
        extract($datos);                                                     // 4. Prepara las variables
        require BASE_PATH . '/src/Views/admin/productos/productos_admin.php';  // 5. Carga la vista LIMPIA
        break;
    case 'admin_agregar_producto':
        require BASE_PATH . '/src/Views/admin/productos/agregar_producto.php';
        break;

    case 'admin_editar_producto':
        // 1. Validamos el ID del producto
        if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
            $_SESSION['mensaje_error'] = "ID de producto no válido.";
            header('Location: ?page=admin_productos');
            exit;
        }
        $id_producto_actual = (int)$_GET['id'];
        
        // 2. Cargamos el controlador
        require_once BASE_PATH . '/src/Controllers/AdminProductosController.php';
        $controller = new AdminProductosController($conn);
        
        // 3. Llamamos al método (maneja GET, POST y acciones GET como 'reactivar')
        $datos = $controller->editarProducto($id_producto_actual); 
        
        // 4. Preparamos datos y mostramos la vista
        extract($datos);
        require BASE_PATH . '/src/Views/admin/productos/editar_producto_admin.php';
        break;

        case 'admin_usuarios';
            require_once BASE_PATH . '/src/Controllers/AdminUsuariosController.php'; // 1. Carga el NUEVO controlado
             $controller = new AdminUsuariosController($conn);                     // 2. Pasa la BBDD
             $datos = $controller->listarUsuarios();                            // 3. Obtiene los datos (maneja GET y acciones)
             extract($datos);                                                     // 4. Prepara las variables
             require BASE_PATH . '/src/Views/admin/usuarios/usuarios.php';          // 5. Carga la vista LIMPIA
        break;
        case 'admin_crear_usuario':
            require_once BASE_PATH . '/src/Controllers/AdminUsuariosController.php'; // 1. Carga el NUEVO controlador
            $controller = new AdminUsuariosController($conn);                     // 2. Pasa la BBDD
            $datos = $controller->crearUsuario();                              // 3. Obtiene los datos (maneja POST y GET)e
            extract($datos);                                                     // 4. Prepara las variables
            require BASE_PATH . '/src/Views/admin/usuarios/crear_usuario.php';   // 5. Carga la vista LIMPIA
            break;

    /* =======================
     * 📊 REPORTES ADMIN
     * ======================= */
    case 'admin_reportes':
        require_once BASE_PATH . '/src/Controllers/ReportesController.php';
        $reportesController = new ReportesController($conn);
        $reportesController->index();
        break;

    case 'admin_reportes_generar':
        require_once BASE_PATH . '/src/Controllers/ReportesController.php';
        $reportesController = new ReportesController($conn);
        $reportesController->generar();
        break;

    /* =======================
     * 🧩 MISCELÁNEO
     * ======================= */
    case 'deepseek_search':
        require BASE_PATH . '/src/Views/misc/deepseek_search.php';
        break;

    case 'about':
        require BASE_PATH . '/src/Views/misc/about.php';
        break;

    case 'contact':
    case '/contact.php':
        // Lógica de contacto
        $mensaje_error = "";
        $mensaje_exito = "";
        $nombre = $email = $asunto = $mensaje = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre']);
            $email = trim($_POST['email']);
            $asunto = trim($_POST['asunto']);
            $mensaje = trim($_POST['mensaje']);

            if (empty($nombre) || empty($email) || empty($asunto) || empty($mensaje)) {
                $mensaje_error = "Por favor, completa todos los campos.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mensaje_error = "Formato de email no válido.";
            } elseif (!preg_match('/^[a-zA-Z\sñáéíóúÁÉÍÓÚ]+$/u', $nombre)) {
                $mensaje_error = "El nombre solo puede contener letras y espacios.";
            } elseif (strlen($mensaje) < 10) {
                $mensaje_error = "El mensaje es demasiado corto.";
            } else {
                $modeloMensaje = new Mensaje();
                if ($modeloMensaje->guardarMensaje($conn, $nombre, $email, $asunto, $mensaje)) {
                    $mensaje_exito = "¡Gracias por tu mensaje! Te responderemos pronto.";
                    $nombre = $email = $asunto = $mensaje = "";
                } else {
                    $mensaje_error = "Error al enviar el mensaje. Intenta de nuevo.";
                }
            }
        }

        require BASE_PATH . '/src/Views/misc/contact.php';
        break;

    /* =======================
     * 📧 MENSAJES - ADMIN
     * ======================= */
    case 'admin_mensajes':
        $controlador = new MensajesController($conn);
        $datos = $controlador->listar();
        extract($datos);
        require BASE_PATH . '/src/Views/admin/mensajes/mensajes.php';
        break;

    case 'admin_ver_mensaje':
        $id_mensaje = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id_mensaje) {
            $_SESSION['mensaje_error'] = "ID de mensaje no válido.";
            header('Location: ?page=admin_mensajes');
            exit;
        }
        $controlador = new MensajesController($conn);
        $datos = $controlador->ver($id_mensaje);
        extract($datos);
        require BASE_PATH . '/src/Views/admin/mensajes/ver.php';
        break;

    /* =======================
     * 🚫 DEFAULT / ERROR 404
     * ======================= */
    default:
        http_response_code(404);
        echo "<h1>Error 404: Página no encontrada</h1>";
        echo "<p>Página solicitada: " . htmlspecialchars($page) . "</p>";
        break;
}

// 🔚 Cierre de conexión
if (isset($conn) && $conn instanceof mysqli) {
    try {
        @$conn->close();
    } catch (Error $e) {
        // Conexión ya cerrada, ignorar
    }
}
?>
