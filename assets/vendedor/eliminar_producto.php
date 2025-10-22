<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'vendedor') {
  header("Location: ../../login.php");
  exit();
}

include '../admin/db.php';

if (!isset($_GET['id'])) {
  header("Location: productos.php");
  exit();
}

$id = $_GET['id'];

// Eliminar imagen del servidor (opcional, si deseas borrar también el archivo de imagen)
$stmt = $conn->prepare("SELECT imagen FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
  $imagen = $row['imagen'];
  $ruta = "../../assets/img/" . $imagen;
  if (file_exists($ruta)) {
    unlink($ruta); // Borra la imagen del servidor
  }
}

// Eliminar producto de la base de datos
$stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: productos.php");
exit();
?>