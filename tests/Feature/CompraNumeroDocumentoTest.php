<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Compra;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CompraNumeroDocumentoTest extends TestCase
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
            ['ruc' => '20123456789'],
            ['razon_social' => 'PROVEEDOR TEST SAC', 'direccion' => 'CALLE TEST 123', 'activo' => 1]
        );

        DB::table('producto')->updateOrInsert(
            ['codigo' => 'MAT-TEST-001'],
            ['descripcion' => 'MATERIA PRIMA TEST', 'codigo_tipo_producto' => 'MTP', 'codigo_unidad_medida' => 'UND', 'estado' => 1]
        );

        DB::table('almacen')->updateOrInsert(
            ['codigo_almacen' => 'ALM-MAT'],
            ['descripcion' => 'ALMACEN MATERIA PRIMA', 'tipo_almacen' => 'MATERIA_PRIMA', 'activo' => 1]
        );
    }

    public function test_compra_numero_documento_is_padded_to_8_digits()
    {
        $payload = [
            'tipo_documento' => 'Factura',
            'serie_documento' => 'f001',
            'numero_documento' => '33351',
            'fecha_compra' => date('Y-m-d'),
            'ruc_proveedor' => '20123456789',
            'moneda' => 'PEN',
            'total_subtotal' => 100,
            'total_impuestos' => 18,
            'total_general' => 118,
            'productos' => [
                [
                    'codigo' => 'MAT-TEST-001',
                    'cantidad' => 10,
                    'precio' => 10,
                    'codigo_almacen' => 'ALM-MAT',
                    'codigo_unidad_medida' => 'UND',
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

        $this->assertDatabaseHas('compras', [
            'ruc_proveedor' => '20123456789',
            'serie_documento' => 'F001',
            'numero_documento' => '00033351',
        ]);
    }
}
