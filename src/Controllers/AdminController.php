<?php
// Controlador base para administración (admin)
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../Core/db.php';
require_once __DIR__ . '/../Core/validaciones.php';

$base_url = '/Ecommerce-Tinkuy/public/index.php';
$mensaje_error = '';
$mensaje_exito = '';

// Control de acceso: solo admin
if (!isset($_SESSION['usuario_id']) || strtolower($_SESSION['rol'] ?? '') !== 'admin') {
    header("Location: /Ecommerce-Tinkuy/public/index.php?page=login");
    exit;
}

$id_admin = $_SESSION['usuario_id'];

// Métodos esqueleto (implementar según migración)
function admin_get_dashboard_stats($conn, $id_admin) {
    $stats = ['total_usuarios' => 0, 'total_productos' => 0, 'total_pedidos' => 0];
    try {
        $r = $conn->query("SELECT COUNT(*) AS c FROM usuarios");
        $stats['total_usuarios'] = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) AS c FROM productos");
        $stats['total_productos'] = (int)$r->fetch_assoc()['c'];
        $r = $conn->query("SELECT COUNT(*) AS c FROM pedidos");
        $stats['total_pedidos'] = (int)$r->fetch_assoc()['c'];
    } catch (Exception $e) {
        // ignore here; caller will show mensaje
    }
    return $stats;
}

// El controlador deja variables para las vistas: $base_url, $mensaje_error, $mensaje_exito, etc.
?>
