<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionRouteTest extends TestCase
{
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_report_routes_exist(): void
    {
        $this->assertTrue(route('reportes.index') !== null);
        $this->assertTrue(route('reportes.produccion') !== null);
        $this->assertTrue(route('reportes.inventario') !== null);
    }

    public function test_ejecutar_view_loads_successfully(): void
    {
        $admin = \App\Models\Usuario::where('rol', 'Administrador')->first();
        $proceso = \App\Models\OrdenProceso::first();

        if ($admin && $proceso) {
            $response = $this->actingAs($admin)->get(route('ordenes.procesos.ejecutar', [$proceso->idop, $proceso->id]));
            $response->assertStatus(200);
            $response->assertSee('Ejecución');
        }
    }
}
