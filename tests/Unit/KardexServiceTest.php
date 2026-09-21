<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\KardexService;
use Illuminate\Support\Facades\DB;

class KardexServiceTest extends TestCase
{
    private KardexService $kardexService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kardexService = new KardexService();
    }

    public function test_calcular_costos_ingreso_primera_vez()
    {
        // Producto sin historial previo
        $res = $this->kardexService->calcularCostos(
            codigoProducto: 'TEST-PROD-01',
            codigoAlmacen: 'ALM-01',
            cantidadEntrada: 100.0,
            costoUnitarioEntrada: 5.50,
            cantidadSalida: 0.0,
            saldoActual: 0.0
        );

        $this->assertEquals(5.50, $res['costo_entrada']);
        $this->assertEquals(550.00, $res['total_entrada']);
        $this->assertEquals(100.0, $res['cantidad_saldo']);
        $this->assertEquals(5.50, $res['costo_promedio']);
        $this->assertEquals(550.00, $res['total_saldo']);
    }

    public function test_calcular_costos_salida_mantiene_costo_promedio()
    {
        // Salida sin movimientos previos devuelve saldo 0
        $res = $this->kardexService->calcularCostos(
            codigoProducto: 'TEST-PROD-NONEXISTENT',
            codigoAlmacen: 'ALM-01',
            cantidadEntrada: 0.0,
            costoUnitarioEntrada: 0.0,
            cantidadSalida: 20.0,
            saldoActual: 0.0
        );

        $this->assertEquals(0, $res['costo_salida']);
        $this->assertEquals(0, $res['total_salida']);
        $this->assertEquals(0, $res['cantidad_saldo']);
        $this->assertEquals(0, $res['total_saldo']);
    }
}
