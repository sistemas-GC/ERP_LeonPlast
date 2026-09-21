<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EncartonadoFormulasSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 1. Limpiar fórmulas antiguas si existían
        $viejasFormulas = ['ENC-PALETA-MAQ', 'ENC-PALETA-MAN', 'ENC-CHUPON'];
        DB::table('composicion_formula')->whereIn('codigo_formula', $viejasFormulas)->delete();
        DB::table('formula_produccion')->whereIn('codigo', $viejasFormulas)->delete();

        // 2. Crear productos resultantes PEP en la tabla `producto` si no existen
        $productosResultantes = [
            [
                'codigo' => 'EN07-Z001.A',
                'descripcion' => 'ENCARTONADO GANCHO PALETA MAQUINA',
                'codigo_tipo_producto' => 'PEP',
                'es_producto_proceso' => 1,
                'codigo_unidad_medida' => 'KG',
                'estado' => 1,
                'fecha_creacion' => $now,
            ],
            [
                'codigo' => 'EN07-Z002.A',
                'descripcion' => 'ENCARTONADO GANCHO CLASICO CHUPON',
                'codigo_tipo_producto' => 'PEP',
                'es_producto_proceso' => 1,
                'codigo_unidad_medida' => 'KG',
                'estado' => 1,
                'fecha_creacion' => $now,
            ],
            [
                'codigo' => 'EN07-Z003.A',
                'descripcion' => 'ENCARTONADO GANCHO PALETA MANUAL',
                'codigo_tipo_producto' => 'PEP',
                'es_producto_proceso' => 1,
                'codigo_unidad_medida' => 'KG',
                'estado' => 1,
                'fecha_creacion' => $now,
            ],
        ];

        foreach ($productosResultantes as $p) {
            DB::table('producto')->updateOrInsert(
                ['codigo' => $p['codigo']],
                $p
            );
        }

        // 3. Fórmulas oficiales estandarizadas
        $formulas = [
            [
                'codigo' => 'EN07-Z001.A',
                'descripcion' => 'ENCARTONADO GANCHO PALETA MAQUINA',
                'estado' => 1,
                'codigo_producto_resultante' => 'EN07-Z001.A',
                'codigo_material_reciclado' => null,
                'fecha_creacion' => $now,
            ],
            [
                'codigo' => 'EN07-Z002.A',
                'descripcion' => 'ENCARTONADO GANCHO CLASICO CHUPON',
                'estado' => 1,
                'codigo_producto_resultante' => 'EN07-Z002.A',
                'codigo_material_reciclado' => null,
                'fecha_creacion' => $now,
            ],
            [
                'codigo' => 'EN07-Z003.A',
                'descripcion' => 'ENCARTONADO GANCHO PALETA MANUAL',
                'estado' => 1,
                'codigo_producto_resultante' => 'EN07-Z003.A',
                'codigo_material_reciclado' => null,
                'fecha_creacion' => $now,
            ],
        ];

        foreach ($formulas as $f) {
            DB::table('formula_produccion')->updateOrInsert(
                ['codigo' => $f['codigo']],
                $f
            );
        }

        // 4. Lista de colores de gancho disponibles en el sistema
        $coloresPaleta = [
            'EN07-B004.A', 'EN07-B009.A', 'EN07-G001.A', 'EN07-G011.A', 'EN07-G012.A',
            'EN07-L001.A', 'EN07-M001.A', 'EN07-O005.A', 'EN07-P006.A', 'EN07-R002.A',
            'EN07-V004.A', 'EN07-Y003.A', 'EN07-Y013.A'
        ];

        $coloresChupon = [
            'EN07-B004.C', 'EN07-B009.C', 'EN07-G001.C', 'EN07-G011.C', 'EN07-G012.C',
            'EN07-L001.C', 'EN07-M001.C', 'EN07-O005.C', 'EN07-P006.C', 'EN07-R002.C',
            'EN07-V004.C', 'EN07-Y003.C', 'EN07-Y013.C'
        ];

        // 5. Composición para EN07-Z001.A (Paleta Máquina):
        // 13 Colores de Gancho Paleta con Molde MLD-006 (139.44g) + Tira Cartón Máquina 54mm (14.20g)
        DB::table('composicion_formula')->where('codigo_formula', 'EN07-Z001.A')->delete();
        $itemsPaletaMaq = [];
        foreach ($coloresPaleta as $codProd) {
            $itemsPaletaMaq[] = [
                'codigo_formula' => 'EN07-Z001.A',
                'codigo_tipo_producto' => 'PEP',
                'codigo_producto' => $codProd,
                'cantidad_nominal' => 139.4400,
                'cantidad_real' => 139.4400,
                'codigo_unidad_medida' => 'GR',
                'codigo_molde' => 'MLD-006',
            ];
        }
        $itemsPaletaMaq[] = [
            'codigo_formula' => 'EN07-Z001.A',
            'codigo_tipo_producto' => 'AUX',
            'codigo_producto' => '01.20.004.004', // Tira Cartón Máquina 54mm
            'cantidad_nominal' => 14.2000,
            'cantidad_real' => 14.2000,
            'codigo_unidad_medida' => 'GR',
            'codigo_molde' => null,
        ];
        DB::table('composicion_formula')->insert($itemsPaletaMaq);

        // 6. Composición para EN07-Z003.A (Paleta Manual):
        // 13 Colores de Gancho Paleta con Molde MLD-006 (139.44g) + Tira Cartón Paleta Manual (7.90g)
        DB::table('composicion_formula')->where('codigo_formula', 'EN07-Z003.A')->delete();
        $itemsPaletaMan = [];
        foreach ($coloresPaleta as $codProd) {
            $itemsPaletaMan[] = [
                'codigo_formula' => 'EN07-Z003.A',
                'codigo_tipo_producto' => 'PEP',
                'codigo_producto' => $codProd,
                'cantidad_nominal' => 139.4400,
                'cantidad_real' => 139.4400,
                'codigo_unidad_medida' => 'GR',
                'codigo_molde' => 'MLD-006',
            ];
        }
        $itemsPaletaMan[] = [
            'codigo_formula' => 'EN07-Z003.A',
            'codigo_tipo_producto' => 'AUX',
            'codigo_producto' => '01.20.004.001', // Tira Cartón Paleta 29x5.5cm
            'cantidad_nominal' => 7.9000,
            'cantidad_real' => 7.9000,
            'codigo_unidad_medida' => 'GR',
            'codigo_molde' => null,
        ];
        DB::table('composicion_formula')->insert($itemsPaletaMan);

        // 7. Composición para EN07-Z002.A (Clásico Chupón):
        // 13 Colores de Gancho Chupón con Molde MLD-023 (94.08g) + Tira Cartón Chupón (1.90g)
        DB::table('composicion_formula')->where('codigo_formula', 'EN07-Z002.A')->delete();
        $itemsChupon = [];
        foreach ($coloresChupon as $codProd) {
            $itemsChupon[] = [
                'codigo_formula' => 'EN07-Z002.A',
                'codigo_tipo_producto' => 'PEP',
                'codigo_producto' => $codProd,
                'cantidad_nominal' => 94.0800,
                'cantidad_real' => 94.0800,
                'codigo_unidad_medida' => 'GR',
                'codigo_molde' => 'MLD-023',
            ];
        }
        $itemsChupon[] = [
            'codigo_formula' => 'EN07-Z002.A',
            'codigo_tipo_producto' => 'AUX',
            'codigo_producto' => '01.20.004.003', // Tira Cartón Chupón 15.4x4.2cm
            'cantidad_nominal' => 1.9000,
            'cantidad_real' => 1.9000,
            'codigo_unidad_medida' => 'GR',
            'codigo_molde' => null,
        ];
        DB::table('composicion_formula')->insert($itemsChupon);
    }
}
