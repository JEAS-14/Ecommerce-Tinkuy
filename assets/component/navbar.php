<?php
// Aseguramos que la sesión se inicie si no lo está
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Inicializar $pagina_actual si no existe
if (!isset($pagina_actual)) {
    $pagina_actual = '';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/img/Logo.png" style="width: 70px; height: 60px;" alt="LogoTinkuy" />
            Tinkuy
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($pagina_actual == 'index' ? 'active fw-bold' : ''); ?>" href="index.php">
                        <i class="bi bi-house-door-fill"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($pagina_actual == 'nosotros' ? 'active fw-bold' : ''); ?>" href="about.php">
                        <i class="bi bi-people-fill"></i> Nosotros
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($pagina_actual == 'productos' ? 'active fw-bold' : ''); ?>" href="products.php">
                        <i class="bi bi-shop-window"></i> Productos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($pagina_actual == 'contacto' ? 'active fw-bold' : ''); ?>" href="contact.php">
                        <i class="bi bi-envelope-fill"></i> Contacto
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($pagina_actual == 'carrito' ? 'active fw-bold' : ''); ?>" href="cart.php">
                        <i class="bi bi-cart-fill"></i> Carrito
                    </a>
                </li>

                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <?php
                    // Recuperar el nombre y apellido si están en sesión
                    $nombre_usuario = $_SESSION['nombre_usuario'] ?? 'Mi Cuenta';
                    $apellido_usuario = $_SESSION['apellido_usuario'] ?? '';
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-warning" href="#" id="navbarUserDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <?= htmlspecialchars($nombre_usuario . ' ' . $apellido_usuario) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUserDropdown">
                            <li><a class="dropdown-item" href="mi_perfil.php"><i class="bi bi-person-lines-fill"></i> Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="pedidos.php"><i class="bi bi-box-seam"></i> Mis Pedidos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($pagina_actual == 'login' ? 'active fw-bold' : ''); ?>" href="login.php">
                            <i class="bi bi-person-circle"></i> Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($pagina_actual == 'registro' ? 'active fw-bold' : ''); ?>" href="register.php">
                            <i class="bi bi-person-plus"></i> Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
