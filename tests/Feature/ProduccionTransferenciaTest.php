<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\TransferenciaAlmacen;
use App\Models\TransferenciaAlmacenDetalle;
use Illuminate\Support\Facades\DB;

class ProduccionTransferenciaTest extends TestCase
{
    protected function getAdmin()
    {
        return Usuario::where('rol', 'Administrador')->first() ?? Usuario::first();
    }

    protected function setUpTestWarehousesAndProduct(): array
    {
        $origen = 'TEST_P1';
        $destino = 'TEST_P2';
        $codProd = 'TEST-PROD-TRP';

        DB::table('almacen')->updateOrInsert(
            ['codigo_almacen' => $origen],
            ['descripcion' => 'ALMACEN ORIGEN QA', 'tipo_almacen' => 'PRODUCTO_PROCESO', 'activo' => 1]
        );

        DB::table('almacen')->updateOrInsert(
            ['codigo_almacen' => $destino],
            ['descripcion' => 'ALMACEN DESTINO QA', 'tipo_almacen' => 'PRODUCTO_PROCESO', 'activo' => 1]
        );

        DB::table('producto')->updateOrInsert(
            ['codigo' => $codProd],
            ['descripcion' => 'PRODUCTO TEST TRANSFERENCIA', 'codigo_tipo_producto' => 'PEP', 'codigo_unidad_medida' => 'KG', 'estado' => 1]
        );

        return [$origen, $destino, $codProd];
    }

    public function test_index_and_create_views_load_successfully(): void
    {
        $admin = $this->getAdmin();
        if (!$admin) $this->markTestSkipped('No user found');

        $responseIndex = $this->actingAs($admin)->get(route('produccion.transferencias.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Transferencias de Producción');

        $responseCreate = $this->actingAs($admin)->get(route('produccion.transferencias.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Registrar Transferencia de Producción');
    }

    public function test_buscar_productos_returns_totalized_stock_across_multiple_lots(): void
    {
        $admin = $this->getAdmin();
        if (!$admin) $this->markTestSkipped('No user found');

        [$origen, $destino, $codProd] = $this->setUpTestWarehousesAndProduct();

        // Limpiar inventario de prueba
        DB::table('inventario')->where('codigo_producto', $codProd)->where('codigo_almacen', $origen)->delete();

        // Insertar 2 lotes para el mismo producto en el almacén de origen
        DB::table('inventario')->insert([
            [
                'codigo_almacen' => $origen,
                'codigo_producto' => $codProd,
                'lote' => 'LOTE-TEST-A',
                'stock_actual' => 20.5000,
                'costo_promedio' => 5.0,
                'fecha_vencimiento' => '2026-10-01',
                'estado' => 1
            ],
            [
                'codigo_almacen' => $origen,
                'codigo_producto' => $codProd,
                'lote' => 'LOTE-TEST-B',
                'stock_actual' => 30.5000,
                'costo_promedio' => 5.0,
                'fecha_vencimiento' => '2026-11-01',
                'estado' => 1
            ],
        ]);

        $response = $this->actingAs($admin)->getJson(route('produccion.transferencias.buscar_productos', [
            'codigo_almacen_origen' => $origen,
            'search' => 'TEST-PROD-TRP'
        ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($codProd, $data[0]['codigo_producto']);
        $this->assertEquals(51.0000, floatval($data[0]['stock_total_disponible']));
        $this->assertEquals(2, $data[0]['total_lotes']);
    }

    public function test_store_transfers_stock_using_fifo_across_multiple_lots(): void
    {
        $admin = $this->getAdmin();
        if (!$admin) $this->markTestSkipped('No user found');

        [$origen, $destino, $codProd] = $this->setUpTestWarehousesAndProduct();

        // Limpiar
        DB::table('inventario')->where('codigo_producto', $codProd)->delete();

        // Lote 1 (vence antes): 15 kg
        DB::table('inventario')->insert([
            'codigo_almacen' => $origen,
            'codigo_producto' => $codProd,
            'lote' => 'LOTE-FIFO-1',
            'stock_actual' => 15.0000,
            'costo_promedio' => 4.0,
            'fecha_vencimiento' => '2026-09-20',
            'estado' => 1
        ]);

        // Lote 2 (vence después): 25 kg
        DB::table('inventario')->insert([
            'codigo_almacen' => $origen,
            'codigo_producto' => $codProd,
            'lote' => 'LOTE-FIFO-2',
            'stock_actual' => 25.0000,
            'costo_promedio' => 4.0,
            'fecha_vencimiento' => '2026-12-20',
            'estado' => 1
        ]);

        // Transferir 30 kg en total
        $response = $this->actingAs($admin)->post(route('produccion.transferencias.store'), [
            'fecha_transferencia' => now()->toDateString(),
            'codigo_almacen_origen' => $origen,
            'codigo_almacen_destino' => $destino,
            'observaciones' => 'Prueba de transferencia FIFO',
            'items' => [
                [
                    'codigo_producto' => $codProd,
                    'cantidad' => 30.0000,
                ]
            ]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificación en Origen:
        // Lote 1 consumido completamente (0 kg)
        $stockLote1Origen = DB::table('inventario')->where('codigo_almacen', $origen)->where('lote', 'LOTE-FIFO-1')->value('stock_actual');
        $this->assertEquals(0, floatval($stockLote1Origen));

        // Lote 2 consumió 15 kg (quedan 10 kg)
        $stockLote2Origen = DB::table('inventario')->where('codigo_almacen', $origen)->where('lote', 'LOTE-FIFO-2')->value('stock_actual');
        $this->assertEquals(10.0, floatval($stockLote2Origen));

        // Verificación en Destino:
        // Lote 1 ingresó 15 kg
        $stockLote1Destino = DB::table('inventario')->where('codigo_almacen', $destino)->where('lote', 'LOTE-FIFO-1')->value('stock_actual');
        $this->assertEquals(15.0, floatval($stockLote1Destino));

        // Lote 2 ingresó 15 kg
        $stockLote2Destino = DB::table('inventario')->where('codigo_almacen', $destino)->where('lote', 'LOTE-FIFO-2')->value('stock_actual');
        $this->assertEquals(15.0, floatval($stockLote2Destino));

        // Verificación de Detalles y Kardex
        $trans = TransferenciaAlmacen::where('codigo_almacen_origen', $origen)->where('codigo_almacen_destino', $destino)->latest('id_transferencia')->first();
        $this->assertNotNull($trans);
        $this->assertStringStartsWith('TRP-', $trans->numero_transferencia);

        $detalles = TransferenciaAlmacenDetalle::where('id_transferencia', $trans->id_transferencia)->get();
        $this->assertCount(2, $detalles);

        $this->assertDatabaseHas('kardex', [
            'codigo_almacen' => $origen,
            'codigo_producto' => $codProd,
            'lote' => 'LOTE-FIFO-1',
            'cantidad_salida' => 15.0,
        ]);

        $this->assertDatabaseHas('kardex', [
            'codigo_almacen' => $destino,
            'codigo_producto' => $codProd,
            'lote' => 'LOTE-FIFO-2',
            'cantidad_entrada' => 15.0,
        ]);
    }

    public function test_store_rejects_insufficient_total_stock(): void
    {
        $admin = $this->getAdmin();
        if (!$admin) $this->markTestSkipped('No user found');

        [$origen, $destino, $codProd] = $this->setUpTestWarehousesAndProduct();

        DB::table('inventario')->where('codigo_producto', $codProd)->delete();

        // Solo 10 kg disponibles
        DB::table('inventario')->insert([
            'codigo_almacen' => $origen,
            'codigo_producto' => $codProd,
            'lote' => 'LOTE-MIN-1',
            'stock_actual' => 10.0000,
            'costo_promedio' => 3.0,
            'estado' => 1
        ]);

        $response = $this->actingAs($admin)->post(route('produccion.transferencias.store'), [
            'fecha_transferencia' => now()->toDateString(),
            'codigo_almacen_origen' => $origen,
            'codigo_almacen_destino' => $destino,
            'items' => [
                [
                    'codigo_producto' => $codProd,
                    'cantidad' => 25.0000, // Excede 10kg
                ]
            ]
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stock insuficiente', session('error'));
    }

    public function test_anular_reverts_inventory_and_kardex_safely(): void
    {
        $admin = $this->getAdmin();
        if (!$admin) $this->markTestSkipped('No user found');

        [$origen, $destino, $codProd] = $this->setUpTestWarehousesAndProduct();

        DB::table('inventario')->where('codigo_producto', $codProd)->delete();

        // Origen con 50 kg
        DB::table('inventario')->insert([
            'codigo_almacen' => $origen,
            'codigo_producto' => $codProd,
            'lote' => 'LOTE-ANULAR-1',
            'stock_actual' => 50.0000,
            'costo_promedio' => 6.0,
            'estado' => 1
        ]);

        // Transferir 20 kg
        $this->actingAs($admin)->post(route('produccion.transferencias.store'), [
            'fecha_transferencia' => now()->toDateString(),
            'codigo_almacen_origen' => $origen,
            'codigo_almacen_destino' => $destino,
            'items' => [
                [
                    'codigo_producto' => $codProd,
                    'cantidad' => 20.0000,
                ]
            ]
        ]);

        $trans = TransferenciaAlmacen::where('codigo_almacen_origen', $origen)->latest('id_transferencia')->first();

        // Anular la transferencia
        $responseAnular = $this->actingAs($admin)->post(route('produccion.transferencias.anular', $trans->id_transferencia));
        $responseAnular->assertSessionHas('success');

        // En origen debe volver a tener 50 kg
        $stockOrigen = DB::table('inventario')->where('codigo_almacen', $origen)->where('lote', 'LOTE-ANULAR-1')->value('stock_actual');
        $this->assertEquals(50.0, floatval($stockOrigen));

        // En destino debe quedar en 0 kg
        $stockDestino = DB::table('inventario')->where('codigo_almacen', $destino)->where('lote', 'LOTE-ANULAR-1')->value('stock_actual');
        $this->assertEquals(0, floatval($stockDestino));

        $trans->refresh();
        $this->assertEquals('ANULADO', $trans->estado);
    }
}
