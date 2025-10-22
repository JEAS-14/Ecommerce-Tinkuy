<?php
session_start();

// Verificar si el usuario está logueado
//if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
//    header("Location: ../../login.php");
//    exit();
//}
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Administración | Tinkuy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <h1 class="mb-4">Bienvenido, <?= htmlspecialchars($_SESSION['admin']) ?> 👋</h1>

        <!-- <h1 class="mb-4">Bienvenido, <//?= htmlspecialchars($_SESSION['usuario']) ?> </h1> -->


        <div class="mb-3">
            <a href="../../index.php" class="btn btn-outline-danger">Cerrar sesión</a>
        </div>

        <div class="card p-4">
            <h4>Acciones disponibles:</h4>
            <ul>
                <li><a href="productos.php">Administrar productos</a></li>
                <li><a href="pedidos.php">Ver pedidos</a></li>
                <li><a href="usuarios.php">Gestionar usuarios</a></li>
            </ul>
        </div>
    </div>
</body>

</html>