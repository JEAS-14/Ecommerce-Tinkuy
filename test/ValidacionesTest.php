<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/Core/validaciones.php';
class ValidacionesTest extends TestCase {
    public function testUsuarioValidoYLlaveValida() {
        $this->assertNull(validarDatosLogin('usuario_ok', 'claveSegura7'));
    }
    public function testUsuarioDemasiadoLargo() {
        $error = validarDatosLogin(str_repeat('a', 21), 'claveSegura7');
        $this->assertNotNull($error);
        $this->assertStringContainsString('20 caracteres', $error);
    }
    public function testClaveDemasiadoLarga() {
        $error = validarDatosLogin('usuario_ok', str_repeat('x', 31));
        $this->assertNotNull($error);
        $this->assertStringContainsString('30 caracteres', $error);
    }
    public function testUsuarioConGuionYGuionBajoPermitidos() {
        $this->assertNull(validarDatosLogin('user-name_ok', 'password777'));
    }
    public function testUsuarioConCaracteresNoPermitidos() {
        $error = validarDatosLogin('name!*', 'password777');
        $this->assertNotNull($error);
        $this->assertStringContainsString('Formato', $error);
    }
    public function testClaveMinimaExactaAceptada() {
        $this->assertNull(validarDatosLogin('usuario_ok', '1234567'));
    }
    public function testUsuarioMinimoExactoAceptado() {
        $this->assertNull(validarDatosLogin('abcd', 'claveSegura7'));
    }
}
?>
