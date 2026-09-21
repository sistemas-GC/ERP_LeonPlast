<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\ProductoProceso;
use App\Models\Molde;
use Illuminate\Support\Facades\DB;

class ProductoProcesoTest extends TestCase
{
    protected function getAdmin()
    {
        return Usuario::where('rol', 'Administrador')->first() ?? Usuario::first();
    }

    public function test_create_view_loads_with_siguiente_codigo(): void
    {
        $admin = $this->getAdmin();
        if (!$admin) {
            $this->markTestSkipped('No user found');
        }

        $max = ProductoProceso::max('codigo') ?? 0;
        $expectedNext = $max + 1;

        $response = $this->actingAs($admin)->get(route('productos_proceso.create'));
        $response->assertStatus(200);
        $response->assertSee('value="' . $expectedNext . '"', false);
    }

    public function test_store_rejects_non_integer_codigo_with_validation_error(): void
    {
        $admin = $this->getAdmin();
        if (!$admin) {
            $this->markTestSkipped('No user found');
        }

        $response = $this->actingAs($admin)->post(route('productos_proceso.store'), [
            'codigo' => 'ENCARTONADO MANUAL',
            'descripcion' => 'ENCARTONADO MANUAL DE GANCHO LEON PLAST',
        ]);

        $response->assertSessionHasErrors('codigo');
    }

    public function test_store_creates_producto_proceso_with_auto_or_explicit_integer_code(): void
    {
        $admin = $this->getAdmin();
        if (!$admin) {
            $this->markTestSkipped('No user found');
        }

        $testCode = 9999;
        // Clean up if existed
        DB::table('producto_molde')->where('codigo_producto_proceso', $testCode)->delete();
        ProductoProceso::where('codigo', $testCode)->delete();

        $molde = Molde::first();

        $response = $this->actingAs($admin)->post(route('productos_proceso.store'), [
            'codigo' => $testCode,
            'descripcion' => 'TEST PRODUCTO PROCESO QA',
            'moldes' => $molde ? [$molde->codigo] : [],
        ]);

        $response->assertRedirect(route('productos_proceso.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('producto_proceso', [
            'codigo' => $testCode,
            'descripcion' => 'TEST PRODUCTO PROCESO QA',
            'estado' => 1,
        ]);

        if ($molde) {
            $this->assertDatabaseHas('producto_molde', [
                'codigo_producto_proceso' => $testCode,
                'codigo_molde' => $molde->codigo,
            ]);
        }

        // Clean up
        DB::table('producto_molde')->where('codigo_producto_proceso', $testCode)->delete();
        ProductoProceso::where('codigo', $testCode)->delete();
    }
}
