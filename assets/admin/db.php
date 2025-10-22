<?php
$host = "localhost:3307";
$usuario = "root";
$password = ""; // SIN contraseña
$basedatos = "tinkuy_db";

$conn = new mysqli($host, $usuario, $password, $basedatos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
