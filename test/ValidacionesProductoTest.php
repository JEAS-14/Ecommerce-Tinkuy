<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../src/Core/validaciones.php';

class ValidacionesProductoTest extends TestCase {
    public function testNombreProductoValido() {
        $nombre = 'Producto de Prueba';
        $error = validarNombreProducto($nombre);
        $this->assertNull($error);
    }
    public function testNombreProductoMuyCorto() {
        $nombre = 'abc';
        $error = validarNombreProducto($nombre);
        $this->assertNotNull($error);
        $this->assertStringContainsString('mínimo', $error);
    }
    public function testNombreProductoMuyLargo() {
        $nombre = str_repeat('a', 61);
        $error = validarNombreProducto($nombre);
        $this->assertNotNull($error);
        $this->assertStringContainsString('máximo', $error);
    }
    public function testDescripcionProductoValida() {
        $desc = 'Descripción válida para producto.';
        $error = validarDescripcionProducto($desc);
        $this->assertNull($error);
    }
    public function testDescripcionProductoMuyCorta() {
        $desc = 'abc';
        $error = validarDescripcionProducto($desc);
        $this->assertNotNull($error);
        $this->assertStringContainsString('mínimo', $error);
    }
    public function testPrecioProductoValido() {
        $precio = 10.5;
        $error = validarPrecioProducto($precio);
        $this->assertNull($error);
    }
    public function testPrecioProductoInvalido() {
        $precio = 0;
        $error = validarPrecioProducto($precio);
        $this->assertNotNull($error);
        $this->assertStringContainsString('mayor a 0', $error);
    }
    public function testStockProductoValido() {
        $stock = 5;
        $error = validarStockProducto($stock);
        $this->assertNull($error);
    }
    public function testStockProductoInvalido() {
        $stock = 0;
        $error = validarStockProducto($stock);
        $this->assertNotNull($error);
        $this->assertStringContainsString('mayor o igual a 1', $error);
    }
}
?>
