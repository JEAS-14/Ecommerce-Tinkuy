<?php
session_start();

// Validación básica
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'vendedor') {
  header("Location: ../../login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Panel del Vendedor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
  <div class="container mt-5">
    <h1 class="mb-4">Bienvenido, <?= htmlspecialchars($_SESSION['usuario']) ?>👋</h1>
    <div class="alert alert-info">Desde este panel puedes gestionar tus productos.</div>

    <a href="productos.php" class="btn btn-primary">Gestionar Productos</a>
    <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>
  </div>
</body>

</html>