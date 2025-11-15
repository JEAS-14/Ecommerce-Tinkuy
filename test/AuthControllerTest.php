<?php
// test/AuthControllerTest.php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Core/validaciones.php';

class AuthControllerTest extends TestCase
{
    /**
     * Test: Validar credenciales correctas
     */
    public function testValidarCredencialesCorrectas()
    {
        $usuario = "testuser";
        $clave = "password123";
        
        $resultado = validarDatosLogin($usuario, $clave);
        
        // Si las validaciones pasan, debe retornar null
        $this->assertNull($resultado);
    }

    /**
     * Test: Validar credenciales con usuario vacío
     */
    public function testValidarCredencialesUsuarioVacio()
    {
        $usuario = "";
        $clave = "password123";
        
        $resultado = validarDatosLogin($usuario, $clave);
        
        $this->assertNotNull($resultado);
        $this->assertStringContainsString("Por favor", $resultado);
    }

    /**
     * Test: Validar credenciales con clave vacía
     */
    public function testValidarCredencialesClaveVacia()
    {
        $usuario = "testuser";
        $clave = "";
        
        $resultado = validarDatosLogin($usuario, $clave);
        
        $this->assertNotNull($resultado);
        $this->assertStringContainsString("Por favor", $resultado);
    }

    /**
     * Test: Validar formato de usuario
     */
    public function testFormatoUsuarioInvalido()
    {
        $usuarioInvalido = "user@#$";
        $clave = "password123";
        
        $resultado = validarDatosLogin($usuarioInvalido, $clave);
        
        $this->assertNotNull($resultado);
        $this->assertStringContainsString("Formato", $resultado);
    }

    /**
     * Test: Validar longitud mínima de usuario
     */
    public function testUsuarioMuyCorto()
    {
        $usuarioCorto = "ab";
        $clave = "password123";
        
        $resultado = validarDatosLogin($usuarioCorto, $clave);
        
        $this->assertNotNull($resultado);
        $this->assertStringContainsString("4 caracteres", $resultado);
    }

    /**
     * Test: Validar longitud mínima de clave
     */
    public function testClaveMuyCorta()
    {
        $usuario = "testuser";
        $claveCorta = "12345";
        
        $resultado = validarDatosLogin($usuario, $claveCorta);
        
        $this->assertNotNull($resultado);
        $this->assertStringContainsString("7 caracteres", $resultado);
    }
}
