<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Controllers/PaymentController.php';

class PaymentControllerTest extends TestCase {
    private $conn;
    private $paymentController;

    protected function setUp(): void {
        // Configurar la conexión a la base de datos de prueba
        $this->conn = new mysqli("localhost", "root", "", "tinkuy_db_test");
        $this->paymentController = new PaymentController($this->conn);
    }

    /**
     * @test
     */
    public function testProcesarPagoConCarritoVacio() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("El carrito está vacío");
        
        $this->paymentController->procesarPago(1, 1, []);
    }

    /**
     * @test
     */
    public function testValidarDireccionInvalida() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("La dirección seleccionada no es válida");
        
        $carrito = [1 => ['cantidad' => 1]];
        $this->paymentController->procesarPago(1, 999999, $carrito);
    }

    /**
     * @test
     */
    public function testProcesarPagoExitoso() {
        // Preparar datos de prueba
        $id_usuario = 1;
        $id_direccion = 1;
        $carrito = [
            1 => ['cantidad' => 1],
            2 => ['cantidad' => 2]
        ];

        // Ejecutar el proceso
        $resultado = $this->paymentController->procesarPago($id_usuario, $id_direccion, $carrito);

        // Verificar resultado
        $this->assertTrue($resultado['success']);
        $this->assertNotNull($resultado['order_id']);
        $this->assertEquals('Pedido procesado correctamente', $resultado['message']);
    }

    /**
     * @test
     */
    public function testProcesarPagoConStockInsuficiente() {
        $this->expectException(Exception::class);
        // El controlador devuelve "Stock insuficiente para el producto ID X"
        $this->expectExceptionMessageMatches('/Stock insuficiente/');
        
        $carrito = [1 => ['cantidad' => 999999]];
        $this->paymentController->procesarPago(1, 1, $carrito);
    }

    protected function tearDown(): void {
        // Limpiar datos de prueba
        $this->conn->query("DELETE FROM pedidos WHERE id_usuario = 1");
        $this->conn->close();
    }
}