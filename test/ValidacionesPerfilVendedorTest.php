<?php
use PHPUnit\Framework\TestCase;
require_once realpath(__DIR__ . '/../src/Core/validaciones.php');

class ValidacionesPerfilVendedorTest extends TestCase {
    public function testNombreValido() {
        $this->assertNull(validarNombreApellido('Juan Pérez'));
    }
    public function testNombreMuyCorto() {
        $error = validarNombreApellido('J');
        $this->assertNotNull($error);
        $this->assertStringContainsString('mínimo', $error);
    }
    public function testNombreMuyLargo() {
        $error = validarNombreApellido(str_repeat('a', 51));
        $this->assertNotNull($error);
        $this->assertStringContainsString('máximo', $error);
    }
    public function testNombreConNumeros() {
        $error = validarNombreApellido('Juan123');
        $this->assertNotNull($error);
        $this->assertStringContainsString('letras', $error);
    }
    public function testTelefonoValido() {
        $this->assertNull(validarTelefono('987654321'));
    }
    public function testTelefonoInvalido() {
        $error = validarTelefono('12345');
        $this->assertNotNull($error);
        $this->assertStringContainsString('9 dígitos', $error);
    }
    public function testNombreTiendaValido() {
        $this->assertNull(validarNombreTienda("Tienda & Prueba-1'"));
    }
    public function testNombreTiendaMuyCorto() {
        $error = validarNombreTienda('Ti');
        $this->assertNotNull($error);
        $this->assertStringContainsString('mínimo', $error);
    }
    public function testNombreTiendaCaracteresInvalidos() {
        $error = validarNombreTienda('Tienda@Prueba');
        $this->assertNotNull($error);
        $this->assertStringContainsString('no permitidos', $error);
    }
}
?>
