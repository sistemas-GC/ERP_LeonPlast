<?php

namespace Tests\Feature;

use App\Http\Controllers\OrdenProcesoController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrdenProcesoHelperTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('producto')->insertOrIgnore([
            ['codigo' => 'PEP-001', 'descripcion' => 'Producto Resultante', 'codigo_tipo_producto' => 'PEP', 'estado' => 1],
            ['codigo' => 'MZ07-001', 'descripcion' => 'Mezclado 001', 'codigo_tipo_producto' => 'MTP', 'estado' => 1],
            ['codigo' => 'CA07-001', 'descripcion' => 'Inyectado 001', 'codigo_tipo_producto' => 'PEP', 'estado' => 1],
            ['codigo' => 'X', 'descripcion' => 'No PEP', 'codigo_tipo_producto' => 'MTP', 'estado' => 1],
        ]);

        DB::table('formula_produccion')->insertOrIgnore([
            'codigo' => 'F001',
            'descripcion' => 'Formula Test',
            'codigo_producto_resultante' => 'PEP-001',
            'estado' => 1,
        ]);

        DB::table('formula_produccion')->insertOrIgnore([
            'codigo' => 'F002',
            'descripcion' => 'Formula Sin Resultante',
            'codigo_producto_resultante' => null,
            'estado' => 1,
        ]);
    }

    public function test_determinar_codigo_pep_desde_formula(): void
    {
        $controller = new OrdenProcesoController();
        $ref = new \ReflectionMethod($controller, 'determinarCodigoPEP');
        $this->assertEquals('PEP-001', $ref->invoke($controller, 'F001'));
    }

    public function test_determinar_codigo_pep_formula_sin_resultante(): void
    {
        $controller = new OrdenProcesoController();
        $ref = new \ReflectionMethod($controller, 'determinarCodigoPEP');
        $this->assertNull($ref->invoke($controller, 'F002'));
    }

    public function test_determinar_codigo_pep_formula_inexistente(): void
    {
        $controller = new OrdenProcesoController();
        $ref = new \ReflectionMethod($controller, 'determinarCodigoPEP');
        $this->assertNull($ref->invoke($controller, 'FORMULA_XYZ'));
    }

    public function test_determinar_codigo_pep_formula_vacia(): void
    {
        $controller = new OrdenProcesoController();
        $ref = new \ReflectionMethod($controller, 'determinarCodigoPEP');
        $this->assertNull($ref->invoke($controller, ''));
    }

    public function test_determinar_pep_desde_producto_mz07_a_ca07(): void
    {
        $controller = new OrdenProcesoController();
        $ref = new \ReflectionMethod($controller, 'determinarPEPdesdeProducto');
        $this->assertEquals('CA07-001', $ref->invoke($controller, 'MZ07-001'));
    }

    public function test_determinar_pep_desde_producto_sin_ca07(): void
    {
        $controller = new OrdenProcesoController();
        $ref = new \ReflectionMethod($controller, 'determinarPEPdesdeProducto');
        $this->assertNull($ref->invoke($controller, 'MZ07-999'));
    }

    public function test_determinar_pep_desde_producto_no_mz07(): void
    {
        $controller = new OrdenProcesoController();
        $ref = new \ReflectionMethod($controller, 'determinarPEPdesdeProducto');
        $this->assertNull($ref->invoke($controller, 'MTP-001'));
    }

    public function test_generar_codigo_pep_formato(): void
    {
        $controller = new OrdenProcesoController();
        $ref = new \ReflectionMethod($controller, 'generarCodigoPEP');
        $codigo = $ref->invoke($controller, 'PEP-001', 'ROJO', 42);
        $this->assertStringStartsWith('PEP-001-ROJO-P42-', $codigo);
        $this->assertLessThanOrEqual(45, strlen($codigo));
    }

    public function test_generar_codigo_pep_sin_color(): void
    {
        $controller = new OrdenProcesoController();
        $ref = new \ReflectionMethod($controller, 'generarCodigoPEP');
        $codigo = $ref->invoke($controller, 'PEP-001', null, 42);
        $this->assertStringStartsWith('PEP-001-SC-P42-', $codigo);
    }
}
