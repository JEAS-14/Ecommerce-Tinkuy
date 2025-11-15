<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Controllers/PaymentController.php';

class PaymentControllerTestExtended extends TestCase {
    private $conn;
    private $paymentController;

    protected function setUp(): void {
        $this->conn = new mysqli("localhost", "root", "", "tinkuy_db_test");
        $this->paymentController = new PaymentController($this->conn);
    }

    /**
     * @test
     */
    public function testProcesarPagoVarianteInexistente() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Uno o más productos ya no están disponibles/');
        $carrito = [9999 => ['cantidad' => 1]];
        $this->paymentController->procesarPago(1, 1, $carrito);
    }

    /**
     * @test
     */
    public function testDireccionNoPerteneceAlUsuario() {
        $this->conn->query("INSERT INTO direcciones (id_usuario, direccion, ciudad, pais) VALUES (2, 'Ajena', 'Ciudad', 'Pais')");
        $idDirAjena = $this->conn->insert_id;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("La dirección seleccionada no es válida");
        $carrito = [1 => ['cantidad' => 1]];
        $this->paymentController->procesarPago(1, $idDirAjena, $carrito);
    }

    /**
     * @test
     */
    public function testRollbackNoCreaPedidoTrasErrorStock() {
        $before = $this->conn->query("SELECT COUNT(*) AS c FROM pedidos")->fetch_assoc()['c'];
        try {
            $this->paymentController->procesarPago(1, 1, [1 => ['cantidad' => 999999]]);
            $this->fail('Se esperaba excepción por stock insuficiente');
        } catch (Exception $e) {
            $this->assertStringContainsString('Stock insuficiente', $e->getMessage());
        }
        $after = $this->conn->query("SELECT COUNT(*) AS c FROM pedidos")->fetch_assoc()['c'];
        $this->assertEquals($before, $after, 'No debe haberse creado pedido tras fallo de stock');
    }

    protected function tearDown(): void {
        $this->conn->query("DELETE FROM pedidos WHERE id_usuario = 1");
        $this->conn->query("DELETE FROM direcciones WHERE id_usuario = 2");
        $this->conn->close();
    }
}
?>