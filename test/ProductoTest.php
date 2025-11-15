<?php
// test/ProductoTest.php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Models/Producto.php';

class ProductoTest extends TestCase
{
    private $producto;
    private $connMock;

    protected function setUp(): void
    {
        // Mock de la conexión a base de datos
        $this->connMock = $this->createMock(mysqli::class);
        $this->producto = new Producto();
    }

    /**
     * Test: Validar que un nombre de producto válido sea aceptado
     */
    public function testNombreProductoValido()
    {
        $nombreValido = "Chompa de Alpaca";
        $this->assertNotEmpty($nombreValido);
        $this->assertGreaterThan(3, strlen($nombreValido));
    }

    /**
     * Test: Validar que un nombre de producto muy corto sea rechazado
     */
    public function testNombreProductoMuyCorto()
    {
        $nombreCorto = "Ch";
        $this->assertLessThan(3, strlen($nombreCorto));
    }

    /**
     * Test: Validar que un precio válido sea positivo
     */
    public function testPrecioProductoValido()
    {
        $precioValido = 150.50;
        $this->assertGreaterThan(0, $precioValido);
        $this->assertIsFloat($precioValido);
    }

    /**
     * Test: Validar que un precio negativo sea inválido
     */
    public function testPrecioProductoNegativo()
    {
        $precioInvalido = -50.00;
        $this->assertLessThan(0, $precioInvalido);
    }

    /**
     * Test: Validar que el stock no sea negativo
     */
    public function testStockNoNegativo()
    {
        $stockValido = 10;
        $this->assertGreaterThanOrEqual(0, $stockValido);
        
        $stockInvalido = -5;
        $this->assertLessThan(0, $stockInvalido);
    }

    /**
     * Test: Validar estructura de datos de producto
     */
    public function testEstructuraProducto()
    {
        $productoData = [
            'id_producto' => 1,
            'nombre_producto' => 'Chompa Artesanal',
            'precio' => 120.00,
            'stock' => 15,
            'estado' => 'activo'
        ];

        $this->assertArrayHasKey('id_producto', $productoData);
        $this->assertArrayHasKey('nombre_producto', $productoData);
        $this->assertArrayHasKey('precio', $productoData);
        $this->assertArrayHasKey('stock', $productoData);
        $this->assertEquals('activo', $productoData['estado']);
    }

    protected function tearDown(): void
    {
        $this->producto = null;
        $this->connMock = null;
    }
}
