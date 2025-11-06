<?php
// src/Controllers/VendedorController.php
// Convertimos el controlador procedimental en una clase para usar en MVC
if (session_status() === PHP_SESSION_NONE) session_start();

class VendedorController {
    private $conn;
    private $base_url;

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
        $this->base_url = '/Ecommerce-Tinkuy/public/index.php';

        // Validaciones de sesión y rol
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . $this->base_url . '?page=login');
            exit;
        }
        if ($_SESSION['rol'] !== 'vendedor') {
            session_destroy();
            header('Location: ' . $this->base_url . '?page=login');
            exit;
        }
    }

    /**
     * Listar productos del vendedor (migrado desde la vista procedural)
     * @return array Datos para la vista
     */
    public function listarProductos()
    {
        $id_vendedor = $_SESSION['usuario_id'];
        $nombre_vendedor = $_SESSION['usuario'];

        $query = "
            SELECT
                p.id_producto,
                p.nombre_producto,
                p.imagen_principal,
                p.estado,
                c.nombre_categoria,
                CONCAT(
                    '[',
                    IFNULL(GROUP_CONCAT(
                        JSON_OBJECT(
                            'id_variante', vp.id_variante,
                            'talla', vp.talla,
                            'color', vp.color,
                            'precio', vp.precio,
                            'stock', vp.stock,
                            'estado_variante', vp.estado
                        ) ORDER BY vp.id_variante
                    ), '') ,
                    ']' 
                ) AS variantes_json
            FROM productos AS p
            JOIN categorias AS c ON p.id_categoria = c.id_categoria
            LEFT JOIN variantes_producto AS vp ON p.id_producto = vp.id_producto
            WHERE p.id_vendedor = ?
            GROUP BY p.id_producto
            ORDER BY p.estado ASC, p.nombre_producto ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id_vendedor);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $productos = $resultado->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return [
            'productos' => $productos,
            'nombre_vendedor' => $nombre_vendedor,
            'id_vendedor' => $id_vendedor,
            'base_url' => $this->base_url
        ];
    }

    /**
     * Cambiar estado de un producto (activar/inactivar)
     * @param int $id_producto
     * @param string $nuevo_estado
     * @return array
     */
    public function cambiarEstado($id_producto, $nuevo_estado)
    {
        $id_vendedor = $_SESSION['usuario_id'];
        // Validar entrada
        $nuevo_estado = ($nuevo_estado === 'activo') ? 'activo' : 'inactivo';

        // Verificar propiedad
        $stmt_check = $this->conn->prepare("SELECT id_producto FROM productos WHERE id_producto = ? AND id_vendedor = ?");
        $stmt_check->bind_param('ii', $id_producto, $id_vendedor);
        $stmt_check->execute();
        $res = $stmt_check->get_result();
        if ($res->num_rows === 0) {
            $stmt_check->close();
            return ['success' => false, 'mensaje' => 'No tienes permiso para modificar este producto.'];
        }
        $stmt_check->close();

        $stmt_update = $this->conn->prepare("UPDATE productos SET estado = ? WHERE id_producto = ?");
        $stmt_update->bind_param('si', $nuevo_estado, $id_producto);
        $ok = $stmt_update->execute();
        $stmt_update->close();

        if ($ok) {
            return ['success' => true, 'mensaje' => "Estado del producto #$id_producto actualizado a '$nuevo_estado'."];
        }
        return ['success' => false, 'mensaje' => 'Error al actualizar el estado del producto.'];
    }

    // Métodos stub para futuras implementaciones (agregar/editar/eliminar/listarVentas/listarEnvios)
    public function agregarProducto()
    {
        $id_vendedor = $_SESSION['usuario_id'];
        $mensaje_error = '';
        $mensaje_exito = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->conn->begin_transaction();

                // Validar datos básicos del producto
                $nombre = trim($_POST['nombre_producto'] ?? '');
                $descripcion = trim($_POST['descripcion'] ?? '');
                $id_categoria = filter_var($_POST['id_categoria'] ?? 0, FILTER_VALIDATE_INT);

                if (empty($nombre) || !$id_categoria) {
                    throw new Exception("Nombre y categoría son obligatorios");
                }

                // Validar imagen principal
                if (!isset($_FILES['imagen_principal']) || $_FILES['imagen_principal']['error'] != UPLOAD_ERR_OK) {
                    throw new Exception("La imagen principal es obligatoria");
                }

                // Procesar imagen principal
                $imagen = $_FILES['imagen_principal'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($imagen['type'], $allowedTypes)) {
                    throw new Exception("Tipo de archivo no permitido. Use JPG, PNG, GIF o WebP.");
                }

                $maxFileSize = 2 * 1024 * 1024; // 2MB
                if ($imagen['size'] > $maxFileSize) {
                    throw new Exception("La imagen es demasiado grande (máximo 2MB)");
                }

                $extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
                $imagen_nombre = 'producto_' . time() . '.' . $extension;
                $ruta_destino = BASE_PATH . '/public/img/productos/' . $imagen_nombre;

                if (!move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
                    throw new Exception("Error al guardar la imagen");
                }

                // Insertar producto
                $stmt = $this->conn->prepare("INSERT INTO productos (nombre_producto, descripcion, imagen_principal, id_categoria, id_vendedor, estado) VALUES (?, ?, ?, ?, ?, 'activo')");
                $stmt->bind_param("sssis", $nombre, $descripcion, $imagen_nombre, $id_categoria, $id_vendedor);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al crear el producto: " . $this->conn->error);
                }
                
                $id_producto = $this->conn->insert_id;
                $stmt->close();

                // Procesar variantes iniciales
                if (isset($_POST['variantes']) && is_array($_POST['variantes'])) {
                    $stmt_variante = $this->conn->prepare("INSERT INTO variantes_producto (id_producto, talla, color, sku, precio, stock) VALUES (?, ?, ?, ?, ?, ?)");
                    
                    foreach ($_POST['variantes'] as $v) {
                        if (empty($v['talla']) || empty($v['color']) || empty($v['precio']) || !isset($v['stock'])) {
                            continue; // Saltamos variantes incompletas
                        }

                        $talla = trim($v['talla']);
                        $color = trim($v['color']);
                        $precio = filter_var($v['precio'], FILTER_VALIDATE_FLOAT);
                        $stock = filter_var($v['stock'], FILTER_VALIDATE_INT);
                        
                        if ($precio === false || $stock === false || $precio <= 0 || $stock < 0) {
                            continue; // Saltamos variantes con datos inválidos
                        }

                        $sku = strtoupper(substr($nombre, 0, 3)) . '-' . $id_producto . '-' . $talla . '-' . $color;
                        $stmt_variante->bind_param("isssdi", $id_producto, $talla, $color, $sku, $precio, $stock);
                        $stmt_variante->execute();
                    }
                    $stmt_variante->close();
                }

                $this->conn->commit();
                $_SESSION['mensaje_exito'] = "Producto creado exitosamente.";
                header("Location: " . $this->base_url . "?page=vendedor_productos");
                exit;

            } catch (Exception $e) {
                $this->conn->rollback();
                $mensaje_error = $e->getMessage();
            }
        }

        // Obtener categorías para el formulario
        $query_categorias = "SELECT c.id_categoria, c.nombre_categoria, c.id_categoria_padre, cp.nombre_categoria AS nombre_padre 
                           FROM categorias c 
                           LEFT JOIN categorias cp ON c.id_categoria_padre = cp.id_categoria 
                           ORDER BY COALESCE(cp.nombre_categoria, c.nombre_categoria), c.id_categoria_padre, c.nombre_categoria";
        
        $resultado_categorias = $this->conn->query($query_categorias);
        $categorias = [];
        while ($row = $resultado_categorias->fetch_assoc()) {
            $categorias[] = $row;
        }

        return [
            'categorias' => $categorias,
            'mensaje_error' => $mensaje_error,
            'mensaje_exito' => $mensaje_exito,
            'base_url' => $this->base_url
        ];
    }

    /**
     * Cambia el estado de una variante de producto (activo/inactivo)
     */
    public function cambiarEstadoVariante($id_producto, $id_variante, $nuevo_estado)
    {
        $id_vendedor = $_SESSION['usuario_id'];
        
        try {
            // Verificar propiedad del producto y la variante
            $stmt = $this->conn->prepare("
                SELECT v.id_variante 
                FROM variantes_producto v 
                JOIN productos p ON v.id_producto = p.id_producto 
                WHERE v.id_variante = ? AND v.id_producto = ? AND p.id_vendedor = ?
            ");
            $stmt->bind_param("iii", $id_variante, $id_producto, $id_vendedor);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                return [
                    'success' => false,
                    'mensaje' => 'No tienes permiso para modificar esta variante.'
                ];
            }
            $stmt->close();

            // Actualizar estado
            $stmt = $this->conn->prepare("
                UPDATE variantes_producto 
                SET estado = ? 
                WHERE id_variante = ? AND id_producto = ?
            ");
            $stmt->bind_param("sii", $nuevo_estado, $id_variante, $id_producto);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'mensaje' => $nuevo_estado === 'activo' ? 
                                "Variante reactivada correctamente." : 
                                "Variante desactivada correctamente."
                ];
            } else {
                throw new Exception("Error al actualizar el estado de la variante.");
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'mensaje' => "Error: " . $e->getMessage()
            ];
        }
    }

    public function editarProducto($id_producto)
    {
        $id_vendedor = $_SESSION['usuario_id'];
        $mensaje_error = '';
        $mensaje_exito = '';

        // --- Lógica POST ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->conn->begin_transaction();

                // --- ACCIÓN: ACTUALIZAR PRODUCTO GENERAL ---
                if (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_producto') {
                    $nombre = trim($_POST['nombre_producto']);
                    $descripcion = trim($_POST['descripcion']);
                    $id_categoria = (int)$_POST['id_categoria'];
                    if(empty($nombre) || $id_categoria === 0) { throw new Exception("Nombre y categoría obligatorios."); }

                    $stmt = $this->conn->prepare("UPDATE productos SET nombre_producto = ?, descripcion = ?, id_categoria = ? WHERE id_producto = ? AND id_vendedor = ?");
                    $stmt->bind_param("ssiii", $nombre, $descripcion, $id_categoria, $id_producto, $id_vendedor);
                    if ($stmt->execute()) { $mensaje_exito = "Producto actualizado."; }
                    else { throw new Exception("Error al actualizar producto."); }
                    $stmt->close();
                }

                // --- ACCIÓN: AGREGAR NUEVA VARIANTE (CON IMAGEN) ---
                elseif (isset($_POST['accion']) && $_POST['accion'] === 'agregar_variante') {
                    $talla = trim($_POST['talla']);
                    $color = trim($_POST['color']);
                    $precio = filter_var(trim($_POST['precio']), FILTER_VALIDATE_FLOAT);
                    $stock = filter_var(trim($_POST['stock']), FILTER_VALIDATE_INT);
                    $imagen_variante_nombre = null;

                    if ($precio===false || $stock===false || $precio<=0 || $stock<0) { throw new Exception("Precio/Stock inválidos."); }
                    if (empty($talla) || empty($color)) { throw new Exception("Talla y color obligatorios."); }

                    // Lógica de Subida de Imagen
                    if (isset($_FILES['imagen_variante']) && $_FILES['imagen_variante']['error'] == UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES['imagen_variante']['tmp_name'];
                        $fileName = $_FILES['imagen_variante']['name'];
                        $fileSize = $_FILES['imagen_variante']['size'];
                        $fileType = $_FILES['imagen_variante']['type'];
                        $fileNameCmps = explode(".", $fileName);
                        $fileExtension = strtolower(end($fileNameCmps));
                        $allowedfileExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                        if (!in_array($fileExtension, $allowedfileExtensions)) { throw new Exception("Tipo de archivo no permitido."); }
                        $maxFileSize = 2 * 1024 * 1024;
                        if ($fileSize > $maxFileSize) { throw new Exception("Archivo demasiado grande (Max 2MB)."); }
                        $imagen_variante_nombre = 'variante_' . $id_producto . '_' . time() . '.' . $fileExtension;
                        $dest_path = BASE_PATH . '/public/img/productos/variantes/' . $imagen_variante_nombre;
                        if(!move_uploaded_file($fileTmpPath, $dest_path)) { throw new Exception('Error al mover el archivo subido.'); }
                    }

                    // Verificar propiedad
                    $stmt_check_prop = $this->conn->prepare("SELECT nombre_producto FROM productos WHERE id_producto = ? AND id_vendedor = ?");
                    $stmt_check_prop->bind_param("ii", $id_producto, $id_vendedor);
                    $stmt_check_prop->execute();
                    $res_check = $stmt_check_prop->get_result();
                    if ($res_check->num_rows === 0) { throw new Exception("Permiso denegado."); }
                    $nombre_prod_temp = $res_check->fetch_assoc()['nombre_producto'];
                    $stmt_check_prop->close();

                    $sku_simulado = strtoupper(substr($nombre_prod_temp, 0, 3)) . '-' . $id_producto . '-' . $talla . '-' . $color;

                    $stmt = $this->conn->prepare("INSERT INTO variantes_producto (id_producto, talla, color, sku, precio, stock, imagen_variante) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("isssdis", $id_producto, $talla, $color, $sku_simulado, $precio, $stock, $imagen_variante_nombre);
                    if ($stmt->execute()) { $mensaje_exito = "Nueva variante agregada."; }
                    else { throw new Exception("Error al agregar variante: " . $this->conn->error); }
                    $stmt->close();
                }

                // --- ACCIÓN: ACTUALIZAR / DESACTIVAR VARIANTES EXISTENTES ---
                elseif (isset($_POST['accion']) && $_POST['accion'] === 'actualizar_variantes') {
                    $stmt_update = $this->conn->prepare("UPDATE variantes_producto SET precio = ?, stock = ? WHERE id_variante = ? AND id_producto = ?");
                    $stmt_desactivar = $this->conn->prepare("UPDATE variantes_producto SET estado = 'inactivo' WHERE id_variante = ? AND id_producto = ?");

                    if (isset($_POST['variantes']) && is_array($_POST['variantes'])) {
                        foreach ($_POST['variantes'] as $id_variante => $datos) {
                            $id_variante = (int)$id_variante;
                            // Verificar propiedad de la variante
                            $stmt_check_var = $this->conn->prepare("SELECT 1 FROM variantes_producto vp JOIN productos p ON vp.id_producto = p.id_producto WHERE vp.id_variante = ? AND p.id_vendedor = ?");
                            $stmt_check_var->bind_param("ii", $id_variante, $id_vendedor);
                            $stmt_check_var->execute();
                            if ($stmt_check_var->get_result()->num_rows === 0) { 
                                $stmt_check_var->close(); 
                                throw new Exception("Permiso denegado variante $id_variante."); 
                            }
                            $stmt_check_var->close();

                            if (isset($datos['desactivar'])) {
                                $stmt_desactivar->bind_param("ii", $id_variante, $id_producto);
                                $stmt_desactivar->execute();
                            } else {
                                $precio = filter_var($datos['precio'], FILTER_VALIDATE_FLOAT);
                                $stock = filter_var($datos['stock'], FILTER_VALIDATE_INT);
                                if ($precio===false || $stock===false || $precio<=0 || $stock<0) { 
                                    throw new Exception("Datos inválidos variante $id_variante."); 
                                }
                                $stmt_update->bind_param("diii", $precio, $stock, $id_variante, $id_producto);
                                $stmt_update->execute();
                            }
                        }
                    }
                    $mensaje_exito = "Lista de variantes actualizada.";
                    $stmt_update->close();
                    $stmt_desactivar->close();
                }

                // --- REACTIVAR VARIANTE ---
                if (isset($_GET['reactivar_variante_id'])) {
                    $id_variante_reactivar = (int)$_GET['reactivar_variante_id'];
                    $resultado = $this->cambiarEstadoVariante($id_producto, $id_variante_reactivar, 'activo');
                    if ($resultado['success']) {
                        $mensaje_exito = $resultado['mensaje'];
                    } else {
                        throw new Exception($resultado['mensaje']);
                    }
                }

                $this->conn->commit();

            } catch (Exception $e) {
                $this->conn->rollback();
                $mensaje_error = "Error: " . $e->getMessage();
            }
        }

        // --- Lógica GET (Visualización) ---
        // Verificamos Propiedad y Obtenemos datos del Producto
        $sql_producto = "SELECT p.*, c.nombre_categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id_categoria WHERE p.id_producto = ? AND p.id_vendedor = ?";
        $stmt = $this->conn->prepare($sql_producto);
        $stmt->bind_param("ii", $id_producto, $id_vendedor);
        $stmt->execute();
        $resultado_producto = $stmt->get_result();
        if ($resultado_producto->num_rows === 0) { 
            $_SESSION['mensaje_error'] = "Producto no encontrado o permiso denegado."; 
            header('Location: ' . $this->base_url . '?page=vendedor_productos'); 
            exit; 
        }
        $producto = $resultado_producto->fetch_assoc();
        $stmt->close();

        // Obtenemos categorías con jerarquía
        $query_categorias_jerarquia = "SELECT c.id_categoria, c.nombre_categoria, c.id_categoria_padre, cp.nombre_categoria AS nombre_padre FROM categorias c LEFT JOIN categorias cp ON c.id_categoria_padre = cp.id_categoria ORDER BY COALESCE(cp.nombre_categoria, c.nombre_categoria), c.id_categoria_padre, c.nombre_categoria";
        $resultado_categorias = $this->conn->query($query_categorias_jerarquia);
        $categorias_jerarquia = []; 
        while ($row = $resultado_categorias->fetch_assoc()) { 
            $categorias_jerarquia[] = $row; 
        }

        // Obtenemos TODAS las variantes (incluyendo estado e imagen)
        $stmt_variantes = $this->conn->prepare("SELECT *, estado, imagen_variante FROM variantes_producto WHERE id_producto = ? ORDER BY talla, color");
        $stmt_variantes->bind_param("i", $id_producto);
        $stmt_variantes->execute();
        $resultado_variantes = $stmt_variantes->get_result();
        $variantes = $resultado_variantes->fetch_all(MYSQLI_ASSOC);
        $stmt_variantes->close();

        return [
            'producto' => $producto,
            'categorias_jerarquia' => $categorias_jerarquia,
            'variantes' => $variantes,
            'mensaje_error' => $mensaje_error,
            'mensaje_exito' => $mensaje_exito,
            'base_url' => $this->base_url
        ];
    }

    public function eliminarProducto($id_producto)
    {
        return [];
    }

    public function actualizarPerfil()
    {
        $id_vendedor = $_SESSION['usuario_id'];
        $mensaje_error = '';
        $mensaje_exito = '';

        // Obtener datos de perfil y usuario
        $stmt = $this->conn->prepare(
            "SELECT u.email, p.nombres, p.apellidos, p.telefono
             FROM usuarios u
             LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
             WHERE u.id_usuario = ? AND u.id_rol = 2"
        );
        $stmt->bind_param('i', $id_vendedor);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($fila = $res->fetch_assoc()) {
            $datos_perfil = [
                'nombre' => $fila['nombres'] ?? '',
                'apellido' => $fila['apellidos'] ?? '',
                'email' => $fila['email'] ?? '',
                'telefono' => $fila['telefono'] ?? ''
            ];
        } else {
            session_destroy();
            header('Location: ' . $this->base_url . '?page=login');
            exit;
        }
        $stmt->close();

        return [
            'datos_perfil' => $datos_perfil,
            'mensaje_error' => $mensaje_error,
            'mensaje_exito' => $mensaje_exito,
            'base_url' => $this->base_url
        ];
    }

    public function listarVentas()
    {
        // De momento devolvemos estructura vacía para la vista
        return [
            'ventas' => [],
            'base_url' => $this->base_url,
            'nombre_vendedor' => $_SESSION['usuario'] ?? ''
        ];
    }

    public function listarEnvios()
    {
        return [
            'envios' => [],
            'base_url' => $this->base_url,
            'nombre_vendedor' => $_SESSION['usuario'] ?? ''
        ];
    }
}

?>