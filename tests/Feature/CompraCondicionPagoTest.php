<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Compra;
use App\Models\CompraCuota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CompraCondicionPagoTest extends TestCase
{
    use DatabaseTransactions;

    protected Usuario $user;

    protected function setUp(): void
    {
        parent::setUp();

        $user = Usuario::where('rol', 'Administrador')->where('activo', 1)->first();
        if (!$user) {
            $user = Usuario::create([
                'codigo_trabajador' => 'T-TEST',
                'nombre_usuario' => 'admin_test_' . uniqid(),
                'contrasena_hash' => bcrypt('password'),
                'email' => 'admin_test_' . uniqid() . '@example.com',
                'rol' => 'Administrador',
                'activo' => 1
            ]);
        }
        $this->user = $user;

        DB::table('proveedores')->updateOrInsert(
            ['ruc' => '20999888777'],
            ['razon_social' => 'PROVEEDOR CUOTAS SAC', 'direccion' => 'AV PRINCIPAL 456', 'activo' => 1]
        );

        DB::table('producto')->updateOrInsert(
            ['codigo' => 'MAT-CUOTA-001'],
            ['descripcion' => 'POLIPROPILENO VIRGEN TEST', 'codigo_tipo_producto' => 'MTP', 'codigo_unidad_medida' => 'KG', 'estado' => 1]
        );

        DB::table('almacen')->updateOrInsert(
            ['codigo_almacen' => 'ALM-CUOTA'],
            ['descripcion' => 'ALMACEN PRUEBAS CUOTAS', 'tipo_almacen' => 'MATERIA_PRIMA', 'activo' => 1]
        );
    }

    public function test_compra_contado_registers_with_same_due_date()
    {
        $payload = [
            'tipo_documento' => 'Factura',
            'serie_documento' => 'F001',
            'numero_documento' => '10001',
            'fecha_compra' => '2026-09-14',
            'condicion_pago' => 'CONTADO',
            'ruc_proveedor' => '20999888777',
            'moneda' => 'PEN',
            'total_subtotal' => 1000,
            'total_impuestos' => 180,
            'total_general' => 1180,
            'productos' => [
                [
                    'codigo' => 'MAT-CUOTA-001',
                    'cantidad' => 100,
                    'precio' => 10,
                    'codigo_almacen' => 'ALM-CUOTA',
                    'codigo_unidad_medida' => 'KG',
                ]
            ]
        ];

        Auth::guard('web')->setUser($this->user);

        $response = $this->actingAs($this->user, 'web')
            ->withoutMiddleware()
            ->from(route('compras.create'))
            ->post(route('compras.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('compras.index'));

        $compra = Compra::where('ruc_proveedor', '20999888777')
            ->where('serie_documento', 'F001')
            ->where('numero_documento', '00010001')
            ->first();

        $this->assertNotNull($compra);
        $this->assertEquals('CONTADO', $compra->condicion_pago);
        $this->assertEquals('2026-09-14', \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('Y-m-d'));
        $this->assertCount(0, $compra->cuotas);
    }

    public function test_compra_credito_registers_cuotas_and_matches_balance()
    {
        $payload = [
            'tipo_documento' => 'Factura',
            'serie_documento' => 'F001',
            'numero_documento' => '10002',
            'fecha_compra' => '2026-09-14',
            'condicion_pago' => 'CREDITO',
            'ruc_proveedor' => '20999888777',
            'moneda' => 'PEN',
            'total_subtotal' => 1000,
            'total_impuestos' => 180,
            'total_general' => 1180,
            'cuotas' => [
                [
                    'numero_cuota' => 1,
                    'fecha_vencimiento' => '2026-10-14',
                    'monto' => 590.00,
                ],
                [
                    'numero_cuota' => 2,
                    'fecha_vencimiento' => '2026-11-14',
                    'monto' => 590.00,
                ],
            ],
            'productos' => [
                [
                    'codigo' => 'MAT-CUOTA-001',
                    'cantidad' => 100,
                    'precio' => 10,
                    'codigo_almacen' => 'ALM-CUOTA',
                    'codigo_unidad_medida' => 'KG',
                ]
            ]
        ];

        Auth::guard('web')->setUser($this->user);

        $response = $this->actingAs($this->user, 'web')
            ->withoutMiddleware()
            ->from(route('compras.create'))
            ->post(route('compras.store'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('compras.index'));

        $compra = Compra::with('cuotas')
            ->where('ruc_proveedor', '20999888777')
            ->where('serie_documento', 'F001')
            ->where('numero_documento', '00010002')
            ->first();

        $this->assertNotNull($compra);
        $this->assertEquals('CREDITO', $compra->condicion_pago);
        $this->assertEquals('2026-11-14', \Carbon\Carbon::parse($compra->fecha_vencimiento)->format('Y-m-d'));
        $this->assertCount(2, $compra->cuotas);
        $this->assertEquals(590.00, $compra->cuotas[0]->monto);
        $this->assertEquals(590.00, $compra->cuotas[1]->monto);
        $this->assertEquals(1180.00, $compra->cuotas->sum('monto'));
    }

    public function test_compra_credito_fails_when_cuotas_sum_does_not_match_total()
    {
        $payload = [
            'tipo_documento' => 'Factura',
            'serie_documento' => 'F001',
            'numero_documento' => '10003',
            'fecha_compra' => '2026-09-14',
            'condicion_pago' => 'CREDITO',
            'ruc_proveedor' => '20999888777',
            'moneda' => 'PEN',
            'total_subtotal' => 1000,
            'total_impuestos' => 180,
            'total_general' => 1180,
            'cuotas' => [
                [
                    'numero_cuota' => 1,
                    'fecha_vencimiento' => '2026-10-14',
                    'monto' => 500.00,
                ],
                [
                    'numero_cuota' => 2,
                    'fecha_vencimiento' => '2026-11-14',
                    'monto' => 500.00, // Sum is 1000 vs 1180
                ],
            ],
            'productos' => [
                [
                    'codigo' => 'MAT-CUOTA-001',
                    'cantidad' => 100,
                    'precio' => 10,
                    'codigo_almacen' => 'ALM-CUOTA',
                    'codigo_unidad_medida' => 'KG',
                ]
            ]
        ];

        Auth::guard('web')->setUser($this->user);

        $response = $this->actingAs($this->user, 'web')
            ->withoutMiddleware()
            ->from(route('compras.create'))
            ->post(route('compras.store'), $payload);

        $response->assertSessionHas('error');

        $compra = Compra::where('ruc_proveedor', '20999888777')
            ->where('serie_documento', 'F001')
            ->where('numero_documento', '00010003')
            ->first();

        $this->assertNull($compra);
    }

    public function test_compra_credito_update_synchronizes_cuotas()
    {
        // 1. Create a compra
        $compra = Compra::create([
            'tipo_documento' => 'Factura',
            'serie_documento' => 'F001',
            'numero_documento' => '00010004',
            'proveedor' => 'PROVEEDOR CUOTAS SAC',
            'ruc_proveedor' => '20999888777',
            'fecha_compra' => '2026-09-14',
            'condicion_pago' => 'CREDITO',
            'fecha_vencimiento' => '2026-10-14',
            'subtotal' => 1000,
            'igv' => 180,
            'total' => 1180,
            'moneda' => 'PEN',
            'tipo_cambio' => 1.000,
            'estado' => 'PENDIENTE',
            'usuario_creacion' => $this->user->id_usuario ?? 1,
        ]);

        CompraCuota::create([
            'id_compra' => $compra->id_compra,
            'numero_cuota' => 1,
            'fecha_vencimiento' => '2026-10-14',
            'monto' => 1180.00,
        ]);

        // 2. Update to 3 cuotas
        $updatePayload = [
            'tipo_documento' => 'Factura',
            'serie_documento' => 'F001',
            'numero_documento' => '00010004',
            'fecha_compra' => '2026-09-14',
            'condicion_pago' => 'CREDITO',
            'ruc_proveedor' => '20999888777',
            'moneda' => 'PEN',
            'total_subtotal' => 1000,
            'total_impuestos' => 180,
            'total_general' => 1180,
            'cuotas' => [
                ['numero_cuota' => 1, 'fecha_vencimiento' => '2026-10-14', 'monto' => 400.00],
                ['numero_cuota' => 2, 'fecha_vencimiento' => '2026-11-14', 'monto' => 400.00],
                ['numero_cuota' => 3, 'fecha_vencimiento' => '2026-12-14', 'monto' => 380.00],
            ],
            'productos' => [
                [
                    'codigo' => 'MAT-CUOTA-001',
                    'cantidad' => 100,
                    'precio' => 10,
                    'codigo_almacen' => 'ALM-CUOTA',
                    'codigo_unidad_medida' => 'KG',
                ]
            ]
        ];

        Auth::guard('web')->setUser($this->user);

        $response = $this->actingAs($this->user, 'web')
            ->withoutMiddleware()
            ->from(route('compras.edit', $compra->id_compra))
            ->put(route('compras.update', $compra->id_compra), $updatePayload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('compras.index'));

        $cuotas = CompraCuota::where('id_compra', $compra->id_compra)->orderBy('numero_cuota')->get();
        $this->assertCount(3, $cuotas);
        $this->assertEquals(400.00, $cuotas[0]->monto);
        $this->assertEquals(400.00, $cuotas[1]->monto);
        $this->assertEquals(380.00, $cuotas[2]->monto);
        $this->assertEquals('2026-12-14', \Carbon\Carbon::parse($compra->fresh()->fecha_vencimiento)->format('Y-m-d'));
    }
}
