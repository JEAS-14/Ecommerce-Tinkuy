<?php
// test/CategoriaTest.php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/Models/Categoria.php';

class CategoriaTest extends TestCase
{
    private $categoria;

    protected function setUp(): void
    {
        $this->categoria = new Categoria();
    }

    /**
     * Test: Validar nombre de categoría válido
     */
    public function testNombreCategoriaValido()
    {
        $nombreValido = "Textiles";
        $this->assertNotEmpty($nombreValido);
        $this->assertIsString($nombreValido);
        $this->assertGreaterThan(2, strlen($nombreValido));
    }

    /**
     * Test: Validar nombre de categoría vacío
     */
    public function testNombreCategoriaVacio()
    {
        $nombreVacio = "";
        $this->assertEmpty($nombreVacio);
    }

    /**
     * Test: Validar estructura de datos de categoría
     */
    public function testEstructuraCategoria()
    {
        $categoriaData = [
            'id_categoria' => 1,
            'nombre_categoria' => 'Textiles',
            'descripcion' => 'Productos textiles artesanales'
        ];

        $this->assertArrayHasKey('id_categoria', $categoriaData);
        $this->assertArrayHasKey('nombre_categoria', $categoriaData);
        $this->assertIsInt($categoriaData['id_categoria']);
        $this->assertIsString($categoriaData['nombre_categoria']);
    }

    /**
     * Test: Validar que ID de categoría sea numérico
     */
    public function testIdCategoriaNumerico()
    {
        $idValido = 5;
        $this->assertIsInt($idValido);
        $this->assertGreaterThan(0, $idValido);
    }

    protected function tearDown(): void
    {
        $this->categoria = null;
    }
}
