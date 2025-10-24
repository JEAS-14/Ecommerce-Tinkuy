<?php
// MUY IMPORTANTE: Asegúrate de llamar session_start() UNA SOLA VEZ
// al INICIO de cada página principal (index.php, products.php, etc.)
// ANTES de incluir esta navbar. Quitamos el session_start() de aquí.
if (session_status() === PHP_SESSION_NONE) {
    // Podrías iniciarla aquí como fallback, pero es mejor hacerlo en la página principal.
    // session_start();
}

// Inicializar $pagina_actual si no existe (buena práctica)
$pagina_actual = $pagina_actual ?? ''; // Usar operador de fusión null

// Variables para el menú de usuario
$is_logged_in = isset($_SESSION['usuario_id']);
$user_role = $_SESSION['rol'] ?? null;
// Usamos 'usuario' que parece más consistente en tu código, con fallback
$display_name = htmlspecialchars($_SESSION['usuario'] ?? 'Mi Cuenta');

?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/img/Logo.png" style="width: 50px; height: auto; margin-right: 10px;" alt="LogoTinkuy" />   
            <span style="font-weight: bold; letter-spacing: 1px;">Tinkuy</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= ($pagina_actual == 'index' ? 'active fw-bold' : ''); ?>" href="index.php">
                        <i class="bi bi-house-door me-1"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($pagina_actual == 'nosotros' ? 'active fw-bold' : ''); ?>" href="about.php">
                        <i class="bi bi-info-circle me-1"></i> Nosotros
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($pagina_actual == 'productos' ? 'active fw-bold' : ''); ?>" href="products.php">
                        <i class="bi bi-shop me-1"></i> Productos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($pagina_actual == 'contacto' ? 'active fw-bold' : ''); ?>" href="contact.php">
                        <i class="bi bi-envelope me-1"></i> Contacto
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative <?= ($pagina_actual == 'carrito' ? 'active fw-bold' : ''); ?>" href="cart.php">
                        <i class="bi bi-cart me-1"></i> Carrito
                        <?php
                            // Mostrar contador de carrito si existe y no está vacío
                            $count = isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0;
                            if ($count > 0):
                        ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $count ?>
                                <span class="visually-hidden">items en carrito</span>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>

                <?php if ($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-warning" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= $display_name ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                            <?php // --- Menú específico por ROL --- ?>
                            <?php if ($user_role === 'cliente'): ?>
                                <li><a class="dropdown-item" href="mi_perfil.php"><i class="bi bi-person-lines-fill me-2"></i>Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="pedidos.php"><i class="bi bi-box-seam me-2"></i>Mis Pedidos</a></li>
                            <?php elseif ($user_role === 'vendedor'): ?>
                                <li><a class="dropdown-item" href="assets/vendedor/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Panel Vendedor</a></li>
                                <li><a class="dropdown-item" href="assets/vendedor/mi_perfil_vendedor.php"><i class="bi bi-person-badge me-2"></i>Mi Perfil Vendedor</a></li>
                            <?php elseif ($user_role === 'admin'): ?>
                                <li><a class="dropdown-item" href="assets/admin/dashboard.php"><i class="bi bi-shield-lock-fill me-2"></i>Panel Admin</a></li>
                            <?php else: ?>
                                 <li><span class="dropdown-item-text text-muted">Rol no definido</span></li>
                            <?php endif; ?>
                            <?php // --- Fin Menú específico --- ?>

                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                <?php else: // Si NO está logueado ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($pagina_actual == 'login' ? 'active fw-bold' : ''); ?>" href="login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($pagina_actual == 'registro' ? 'active fw-bold' : ''); ?>" href="register.php">
                            <i class="bi bi-person-plus-fill me-1"></i> Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav> 