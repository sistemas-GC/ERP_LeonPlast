<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmbolsadoFormulasSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 1. Asegurar unidades de medida BOL y PAQ en unidad_medida
        DB::table('unidad_medida')->updateOrInsert(
            ['codigo' => 'BOL'],
            ['descripcion' => 'BOLSA']
        );
        DB::table('unidad_medida')->updateOrInsert(
            ['codigo' => 'PAQ'],
            ['descripcion' => 'PAQUETE']
        );

        // 2. Actualizar productos terminados GA con unidad de medida
        $productosTerminados = [
            [
                'codigo' => 'GA07-Z016.A',
                'descripcion' => 'GANCHO LEON PLAST MOD. PALETA SURTIDO BOLSA AZUL',
                'codigo_tipo_producto' => 'PDT',
                'codigo_unidad_medida' => 'BOL',
                'estado' => 1
            ],
            [
                'codigo' => 'GA07-Z001.B',
                'descripcion' => 'GANCHO LEON PLAST MOD.CLASICO CHUPON (GA01T-24C) X 24 GRS',
                'codigo_tipo_producto' => 'PDT',
                'codigo_unidad_medida' => 'BOL',
                'estado' => 1
            ],
            [
                'codigo' => 'GA07-Z003.A',
                'descripcion' => 'GANCHO LEON PLAST MOD.ANIMALITO C/ENTERO SUELTO (GA04E-24P) X 24 GRS',
                'codigo_tipo_producto' => 'PDT',
                'codigo_unidad_medida' => 'BOL',
                'estado' => 1
            ]
        ];

        foreach ($productosTerminados as $p) {
            DB::table('producto')->updateOrInsert(
                ['codigo' => $p['codigo']],
                $p
            );
        }

        // 3. Fórmulas de Embolsado en formula_produccion
        $formulas = [
            [
                'codigo' => 'GA07-Z016.A',
                'descripcion' => 'EMBOLSADO GANCHO MOD. PALETA SURTIDO BOLSA AZUL',
                'estado' => 1,
                'codigo_producto_resultante' => 'GA07-Z016.A',
                'codigo_material_reciclado' => null,
                'fecha_creacion' => $now
            ],
            [
                'codigo' => 'GA07-Z001.B',
                'descripcion' => 'EMBOLSADO GANCHO MOD. CLASICO CHUPON BOLSA AZUL',
                'estado' => 1,
                'codigo_producto_resultante' => 'GA07-Z001.B',
                'codigo_material_reciclado' => null,
                'fecha_creacion' => $now
            ]
        ];

        foreach ($formulas as $f) {
            DB::table('formula_produccion')->updateOrInsert(
                ['codigo' => $f['codigo']],
                $f
            );
        }

        // 4. Limpiar componentes anteriores de estas fórmulas
        DB::table('composicion_formula')->whereIn('codigo_formula', ['GA07-Z016.A', 'GA07-Z001.B'])->delete();

        // 5. Insertar composición para GA07-Z016.A
        // 1 Bolsa contiene 6 Sets de Encartonado Paleta + 1 Bolsa Azul
        $compsPaleta = [
            [
                'codigo_formula' => 'GA07-Z016.A',
                'codigo_tipo_producto' => 'PEP',
                'codigo_producto' => 'EN07-Z001.A',
                'cantidad_nominal' => 6.0000,
                'cantidad_real' => 6.0000,
                'codigo_unidad_medida' => 'SET',
                'codigo_molde' => null,
            ],
            [
                'codigo_formula' => 'GA07-Z016.A',
                'codigo_tipo_producto' => 'EMB',
                'codigo_producto' => '01.20.007.002',
                'cantidad_nominal' => 1.0000,
                'cantidad_real' => 1.0000,
                'codigo_unidad_medida' => 'UNI',
                'codigo_molde' => null,
            ]
        ];
        DB::table('composicion_formula')->insert($compsPaleta);

        // Composición para GA07-Z001.B
        // 1 Bolsa contiene 6 Sets de Encartonado Chupón + 1 Bolsa Azul Clásico
        $compsChupon = [
            [
                'codigo_formula' => 'GA07-Z001.B',
                'codigo_tipo_producto' => 'PEP',
                'codigo_producto' => 'EN07-Z002.A',
                'cantidad_nominal' => 6.0000,
                'cantidad_real' => 6.0000,
                'codigo_unidad_medida' => 'SET',
                'codigo_molde' => null,
            ],
            [
                'codigo_formula' => 'GA07-Z001.B',
                'codigo_tipo_producto' => 'EMB',
                'codigo_producto' => '01.20.007.005',
                'cantidad_nominal' => 1.0000,
                'cantidad_real' => 1.0000,
                'codigo_unidad_medida' => 'UNI',
                'codigo_molde' => null,
            ]
        ];
        DB::table('composicion_formula')->insert($compsChupon);
    }
}
