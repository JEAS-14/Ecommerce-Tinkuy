<?php
// tests/ValidacionLoginTest.php

use PHPUnit\Framework\TestCase;

// Importamos la función que queremos probar
// __DIR__ se asegura de encontrar el archivo desde la ubicación actual
require_once __DIR__ . '/../assets/admin/validaciones.php';

class ValidacionLoginTest extends TestCase
{
    // Prueba 1: Un caso válido debe devolver null (sin error)
    public function testCasoValido()
    {
        $this->assertNull(
            validarDatosLogin("usuarioValido", "claveValida123")
        );
    }

    // Prueba 2: Un usuario vacío debe devolver el mensaje de error correcto
    public function testUsuarioVacio()
    {
        // assertStringContainsString() verifica que el mensaje de error exista
        $this->assertStringContainsString(
            "Por favor, ingresa",
            validarDatosLogin("", "claveValida123")
        );
    }

    // Prueba 3: Un usuario demasiado corto
    public function testUsuarioMuyCorto()
    {
        $this->assertStringContainsString(
            "al menos 4 caracteres",
            validarDatosLogin("abc", "claveValida123")
        );
    }

    // Prueba 4: Un usuario con caracteres inválidos
    public function testUsuarioCaracteresInvalidos()
    {
        $this->assertStringContainsString(
            "Formato de usuario no válido",
            validarDatosLogin("usuario!!", "claveValida123")
        );
    }

    // Prueba 5: Una clave demasiado corta
    public function testClaveMuyCorta()
    {
        $this->assertStringContainsString(
            "al menos 7 caracteres",
            validarDatosLogin("usuarioValido", "123")
        );
    }
}