<?php
$host = "localhost";
$port = 3306; // Puerto por defecto de MySQL
$usuario = "root";
$password = ""; // Tu contraseña (está bien si es vacía en XAMPP)
$database = "tinkuy_db";

// Pasamos el puerto $port como el quinto parámetro
$conn = new mysqli($host, $usuario, $password, $database);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>