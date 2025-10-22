<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house-door-fill"></i> Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php"><i class="bi bi-people-fill"></i> Nosotros</a></li>
                <li class="nav-item"><a class="nav-link" href="products.php"><i class="bi bi-shop-window"></i> Productos</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php"><i class="bi bi-envelope-fill"></i> Contacto</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php"><i class="bi bi-cart-fill"></i> Carrito</a></li>

                <?php if (isset($_SESSION['usuario'])): ?>
                    <!-- Si está logueado -->
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                <?php else: ?>
                    <!-- Si NO está logueado -->
                    <li class="nav-item"><a class="nav-link" href="login.php"><i class="bi bi-person-circle"></i> Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
