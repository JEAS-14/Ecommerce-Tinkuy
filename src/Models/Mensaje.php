<?php
// src/Models/Mensaje.php

class Mensaje
{
    /**
     * Guarda un nuevo mensaje de contacto en la base de datos.
     *
     * @param mysqli $conn La conexión a la BD.
     * @param string $nombre
     * @param string $email
     * @param string $asunto
     * @param string $mensaje
     * @return bool True si se guardó, False si hubo un error.
     */
    public function guardarMensaje($conn, $nombre, $email, $asunto, $mensaje)
    {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO mensajes_contacto (nombre, email, asunto, mensaje) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssss", $nombre, $email, $asunto, $mensaje);
            $stmt->execute();
            $stmt->close();
            return true; // Éxito

        } catch (mysqli_sql_exception $e) {
            // Opcional: registrar el error $e->getMessage()
            return false; // Error
        }
    }
}