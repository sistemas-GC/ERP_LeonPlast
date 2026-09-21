<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Usuario;

class OrdenProcesoIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    private Usuario $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $user = Usuario::where('rol', 'Administrador')->first();
        if (!$user) {
            $user = Usuario::create([
                'nombre_usuario' => 'admin_test',
                'contrasena_hash' => bcrypt('password'),
                'rol' => 'Administrador',
                'activo' => 1
            ]);
        }
        $this->adminUser = $user;

        DB::table('producto')->insertOrIgnore([
            ['codigo' => 'PROD-001', 'descripcion' => 'Producto Test', 'codigo_tipo_producto' => 'MTP', 'estado' => 1],
            ['codigo' => 'ACT-001', 'descripcion' => 'Actividad Test', 'codigo_tipo_producto' => 'ACT', 'estado' => 1],
            ['codigo' => 'MZ07-001', 'descripcion' => 'Mezclado Test', 'codigo_tipo_producto' => 'PEP', 'estado' => 1],
        ]);

        DB::table('proceso_produccion')->insertOrIgnore([
            ['codigo' => 1, 'descripcion' => 'Actividad', 'estado' => 1],
            ['codigo' => 99, 'descripcion' => 'Proceso Produccion', 'estado' => 1],
        ]);

        DB::table('almacen')->insertOrIgnore([
            ['codigo_almacen' => 'ALM-01', 'descripcion' => 'Almacen Test', 'tipo_almacen' => 'MATERIA_PRIMA', 'activo' => 1],
        ]);

        DB::table('inventario')->insertOrIgnore([
            'id_inventario' => 9991,
            'codigo_almacen' => 'ALM-01',
            'codigo_producto' => 'PROD-001',
            'lote' => 'LOTE-001',
            'stock_actual' => 100,
            'costo_promedio' => 10,
            'estado' => 1,
        ]);

        DB::table('orden_produccion_global')->insertOrIgnore([
            'idop' => 9999,
            'codigo_op' => 'OP-2025-999',
            'codigo_producto_proceso' => 1,
            'codigo_centro_trabajo_produccion' => null,
            'fecha' => now()->toDateString(),
            'cantidad' => 100,
            'activo' => 1,
            'estado' => 'PENDIENTE',
        ]);

        DB::table('orden_proceso')->insertOrIgnore([
            'id' => 9991,
            'idop' => 9999,
            'secuencia' => 10,
            'codigo_proceso' => '1',
            'descripcion_proceso' => 'Proceso Test',
            'estado' => 1,
            'estado_avance' => 'PENDIENTE',
            'fecha_inicio' => now()->toDateString(),
        ]);
    }

    public function test_store_componentes_stock_insuficiente_lanza_excepcion(): void
    {
        DB::table('orden_proceso')->insertOrIgnore([
            'id' => 9992,
            'idop' => 9999,
            'secuencia' => 200,
            'codigo_proceso' => '99',
            'descripcion_proceso' => 'Stock Test',
            'estado' => 1,
            'estado_avance' => 'PENDIENTE',
            'fecha_inicio' => now(),
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('ordenes.procesos.componentes.store', ['orden' => 9999, 'proceso' => 9992]), [
            'codigo_almacen_consumo' => 'ALM-01',
            'componentes_json' => json_encode([
                [
                    'codigo_producto' => 'PROD-001',
                    'codigo_tipo_producto' => 'MTP',
                    'cantidad' => 999999,
                    'codigo_unidad_medida' => 'KG',
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin' => now()->toDateString(),
                    'hora_inicio' => '08:00',
                    'hora_fin' => '17:00',
                ],
            ]),
            'merma_kg' => 0,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('STOCK INSUFICIENTE', session('error'));
    }

    public function test_store_componentes_orden_completada_lanza_error(): void
    {
        DB::table('orden_produccion_global')->where('idop', 9999)->update(['estado' => 'COMPLETADO']);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('ordenes.procesos.componentes.store', ['orden' => 9999, 'proceso' => 9991]), [
            'codigo_almacen_consumo' => 'ALM-01',
            'componentes_json' => json_encode([
                [
                    'codigo_producto' => 'ACT-001',
                    'codigo_tipo_producto' => 'ACT',
                    'cantidad' => 1,
                    'codigo_trabajador' => null,
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin' => now()->toDateString(),
                    'hora_inicio' => '08:00',
                    'hora_fin' => '17:00',
                ],
            ]),
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('COMPLETADA', session('error'));
    }

    public function test_store_componentes_troquelado_excede_peso_neto_lanza_error(): void
    {
        DB::table('orden_produccion_global')->where('idop', 9999)->update([
            'cantidad' => 50,
            'tara' => 10, // Peso neto = 40 KG
            'estado' => 'EN_PROCESO'
        ]);

        DB::table('proceso_produccion')->updateOrInsert(
            ['codigo' => 17],
            ['descripcion' => 'TROQUELADO', 'estado' => 1]
        );

        DB::table('orden_proceso')->updateOrInsert(
            ['id' => 9995],
            [
                'idop' => 9999,
                'secuencia' => 20,
                'codigo_proceso' => '17', // Troquelado
                'descripcion_proceso' => 'TROQUELADO',
                'estado' => 1,
                'estado_avance' => 'EN_PROCESO',
                'fecha_inicio' => now()->toDateString(),
            ]
        );

        DB::table('inventario')->where('id_inventario', 9991)->update(['stock_actual' => 9999]);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('ordenes.procesos.componentes.store', ['orden' => 9999, 'proceso' => 9995]), [
            'codigo_almacen_consumo' => 'ALM-01',
            'componentes_json' => json_encode([
                [
                    'codigo_producto' => 'PROD-001',
                    'codigo_tipo_producto' => 'MTP',
                    'cantidad' => 45, // Excede peso neto de 40 KG
                    'codigo_unidad_medida' => 'KG',
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin' => now()->toDateString(),
                    'hora_inicio' => '08:00',
                    'hora_fin' => '17:00',
                ],
            ]),
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('excede el Peso Neto del Rollo', session('error'));
    }

    public function test_destroy_proceso_completado_lanza_excepcion(): void
    {
        DB::table('orden_proceso')->where('id', 9991)->update(['estado_avance' => 'COMPLETADO']);

        $this->actingAs($this->adminUser);

        $response = $this->delete(route('ordenes.procesos.destroy', ['proceso' => 9991]));

        $response->assertSessionHas('error');
        $this->assertStringContainsString('COMPLETADO', session('error'));
    }

    public function test_finalizar_sin_componentes_lanza_error(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('ordenes.procesos.finalizar', ['orden' => 9999, 'proceso' => 9991]));

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Debe registrar al menos', session('error'));
    }

    public function test_actividades_se_registran_sin_inventario(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('ordenes.procesos.componentes.store', ['orden' => 9999, 'proceso' => 9991]), [
            'codigo_almacen_consumo' => 'ALM-01',
            'componentes_json' => json_encode([
                [
                    'codigo_producto' => 'ACT-001',
                    'codigo_tipo_producto' => 'ACT',
                    'cantidad' => 1,
                    'codigo_trabajador' => null,
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin' => now()->toDateString(),
                    'hora_inicio' => '08:00',
                    'hora_fin' => '17:00',
                ],
            ]),
            'merma_kg' => 0,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('componentes_orden_produccion_global', [
            'id_proceso' => 9991,
            'codigo_tipo_producto' => 'ACT',
            'estado' => 1,
        ]);
    }

    public function test_determinar_pep_desde_formula(): void
    {
        DB::table('formula_produccion')->insertOrIgnore([
            'codigo' => 'F-TEST-99',
            'descripcion' => 'Formula de prueba',
            'codigo_producto_resultante' => 'MZ07-001',
            'estado' => 1,
        ]);

        DB::table('color')->insertOrIgnore([
            'codigo' => 'ROJO',
            'descripcion' => 'Rojo',
            'activo' => 1,
        ]);

        DB::table('orden_proceso')->insertOrIgnore([
            'id' => 9994,
            'idop' => 9999,
            'secuencia' => 20,
            'codigo_proceso' => '99',
            'descripcion_proceso' => 'Proceso PEP Test',
            'estado' => 1,
            'estado_avance' => 'PENDIENTE',
            'fecha_inicio' => now(),
        ]);

        DB::table('inventario')->where('id_inventario', 9991)->update(['stock_actual' => 999999]);

        $this->actingAs($this->adminUser);

        $response = $this->post(route('ordenes.procesos.componentes.store', ['orden' => 9999, 'proceso' => 9994]), [
            'codigo_almacen_consumo' => 'ALM-01',
            'componentes_json' => json_encode([
                [
                    'codigo_producto' => 'PROD-001',
                    'codigo_tipo_producto' => 'MTP',
                    'cantidad' => 50,
                    'codigo_unidad_medida' => 'KG',
                    'codigo_formula' => 'F-TEST-99',
                    'codigo_color' => 'ROJO',
                    'codigo_centro_trabajo' => null,
                    'codigo_molde' => null,
                    'codigo_trabajador' => null,
                    'fecha_inicio' => now()->toDateString(),
                    'fecha_fin' => now()->toDateString(),
                    'hora_inicio' => '08:00',
                    'hora_fin' => '17:00',
                ],
            ]),
            'merma_kg' => 0,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('produccion_ingresos_proceso', [
            'id_proceso' => 9994,
            'codigo_producto_proceso' => 'MZ07-001',
            'estado' => 'APROBADO',
        ]);
    }

    public function test_encartonado_view_renders_correctly(): void
    {
        DB::table('orden_produccion_global')->where('idop', 9999)->update(['estado' => 'PENDIENTE']);

        DB::table('proceso_produccion')->insertOrIgnore([
            ['codigo' => 8, 'descripcion' => 'ENCARTONADO', 'estado' => 1],
        ]);

        DB::table('orden_proceso')->insertOrIgnore([
            'id' => 19995,
            'idop' => 9999,
            'secuencia' => 30,
            'codigo_proceso' => '8',
            'descripcion_proceso' => 'ENCARTONADO',
            'estado' => 1,
            'estado_avance' => 'PENDIENTE',
            'fecha_inicio' => now()->toDateString(),
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->get(route('ordenes.procesos.ejecutar', ['orden' => 9999, 'proceso' => 19995]));

        $response->assertStatus(200);
        $response->assertSee('Producción (Encartonado)');
        $response->assertSee('Merma Cartón');
        $response->assertSee('Merma Clip');
        $response->assertSee('Cáscara para molido');
        $response->assertSee('Selección de 4 Colores');
    }

    public function test_encartonado_store_ejecucion_agrupada_validates_4_colors(): void
    {
        DB::table('orden_produccion_global')->where('idop', 9999)->update(['estado' => 'PENDIENTE']);

        DB::table('proceso_produccion')->insertOrIgnore([
            ['codigo' => 8, 'descripcion' => 'ENCARTONADO', 'estado' => 1],
        ]);

        DB::table('orden_proceso')->insertOrIgnore([
            'id' => 9996,
            'idop' => 9999,
            'secuencia' => 40,
            'codigo_proceso' => '8',
            'descripcion_proceso' => 'ENCARTONADO',
            'estado' => 1,
            'estado_avance' => 'PENDIENTE',
            'fecha_inicio' => now()->toDateString(),
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson(route('ordenes.procesos.ejecucion_agrupada.store', ['orden' => 9999, 'proceso' => 9996]), [
            'tipo_operacion' => 'encartonado',
            'codigo_formula' => 'EN07-Z001.A',
            'colores_seleccionados' => ['B004', 'G001'], // only 2 colors
            'cantidad_total' => 100,
            'codigo_centro_trabajo' => 'ENC-001',
            'codigo_trabajador' => 'T-001',
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '17:00',
            'codigo_almacen_consumo' => 'PS5E',
        ]);

        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('4 colores', $response->json('message'));
    }

    public function test_encartonado_store_ejecucion_agrupada_success(): void
    {
        DB::table('orden_produccion_global')->where('idop', 9999)->update(['estado' => 'PENDIENTE']);

        DB::table('almacen')->insertOrIgnore([
            ['codigo_almacen' => 'PS5E', 'descripcion' => 'ALMACEN ENCARTONADO', 'tipo_almacen' => 'PRODUCCION', 'activo' => 1],
        ]);

        DB::table('centro_trabajo_produccion')->insertOrIgnore([
            ['codigo' => 'ENC-001', 'descripcion' => 'ENCARTONADORA 01', 'estado' => 1],
        ]);

        DB::table('trabajador')->insertOrIgnore([
            ['codigo' => 'T-001', 'nombre' => 'TRABAJADOR TEST', 'estado' => 1],
        ]);

        // Insert inventory for 4 hook colors and cardboard strip in PS5E
        $colors = ['B004', 'B009', 'G001', 'G011'];
        foreach ($colors as $c) {
            DB::table('producto')->insertOrIgnore([
                'codigo' => "EN07-{$c}.A",
                'descripcion' => "GANCHO COLOR {$c}",
                'codigo_tipo_producto' => 'PEP',
                'codigo_color' => $c,
                'estado' => 1,
            ]);

            DB::table('inventario')->insertOrIgnore([
                'codigo_almacen' => 'PS5E',
                'codigo_producto' => "EN07-{$c}.A",
                'lote' => "LOTE-{$c}",
                'stock_actual' => 500,
                'costo_promedio' => 1,
                'estado' => 1,
            ]);
        }

        DB::table('producto')->insertOrIgnore([
            'codigo' => '01.20.004.004',
            'descripcion' => 'CARTON EN ROLLO 54 MM',
            'codigo_tipo_producto' => 'AUX',
            'estado' => 1,
        ]);

        DB::table('inventario')->insertOrIgnore([
            'codigo_almacen' => 'PS5E',
            'codigo_producto' => '01.20.004.004',
            'lote' => 'LOTE-CARTON',
            'stock_actual' => 500,
            'costo_promedio' => 0.5,
            'estado' => 1,
        ]);

        DB::table('proceso_produccion')->insertOrIgnore([
            ['codigo' => 8, 'descripcion' => 'ENCARTONADO', 'estado' => 1],
        ]);

        DB::table('orden_proceso')->insertOrIgnore([
            'id' => 9997,
            'idop' => 9999,
            'secuencia' => 50,
            'codigo_proceso' => '8',
            'descripcion_proceso' => 'ENCARTONADO',
            'estado' => 1,
            'estado_avance' => 'PENDIENTE',
            'fecha_inicio' => now()->toDateString(),
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson(route('ordenes.procesos.ejecucion_agrupada.store', ['orden' => 9999, 'proceso' => 9997]), [
            'tipo_operacion' => 'encartonado',
            'codigo_formula' => 'EN07-Z001.A',
            'colores_seleccionados' => $colors,
            'cantidad_total' => 100,
            'codigo_centro_trabajo' => 'ENC-001',
            'codigo_trabajador' => 'T-001',
            'fecha' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fin' => '17:00',
            'codigo_almacen_consumo' => 'PS5E',
        ]);

        $response->assertJson([
            'success' => true,
        ]);

        // Verify 4 hook components recorded with 25% distribution each + 1 cardboard component
        foreach ($colors as $c) {
            $this->assertDatabaseHas('componentes_orden_produccion_global', [
                'id_proceso' => 9997,
                'codigo_producto' => "EN07-{$c}.A",
            ]);
        }

        $this->assertDatabaseHas('componentes_orden_produccion_global', [
            'id_proceso' => 9997,
            'codigo_producto' => '01.20.004.004',
        ]);
    }
}
