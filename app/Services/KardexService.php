<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class KardexService
{
    public function calcularCostos(
        string $codigoProducto,
        string $codigoAlmacen,
        float $cantidadEntrada,
        float $costoUnitarioEntrada,
        float $cantidadSalida,
        float $saldoActual
    ): array {
        $ultimo = DB::table('kardex')
            ->where('codigo_producto', $codigoProducto)
            ->where('codigo_almacen', $codigoAlmacen)
            ->orderBy('fecha_movimiento', 'desc')
            ->orderBy('id_kardex', 'desc')
            ->lockForUpdate()
            ->first();

        $saldoAnterior = $ultimo?->cantidad_saldo ?? 0;
        $totalSaldoAnterior = $ultimo?->total_saldo ?? 0;
        $costoPromedioAnterior = $ultimo?->costo_promedio ?? 0;

        if ($cantidadEntrada > 0) {
            $totalEntrada = round($cantidadEntrada * $costoUnitarioEntrada, 2);
            $nuevaCantidadSaldo = round($saldoAnterior + $cantidadEntrada, 3);
            $nuevoTotalSaldo = round($totalSaldoAnterior + $totalEntrada, 2);
            $nuevoCostoPromedio = $nuevaCantidadSaldo > 0
                ? round($nuevoTotalSaldo / $nuevaCantidadSaldo, 6)
                : round($costoUnitarioEntrada, 6);

            return [
                'costo_entrada'  => round($costoUnitarioEntrada, 6),
                'total_entrada'  => $totalEntrada,
                'costo_salida'   => 0,
                'total_salida'   => 0,
                'cantidad_saldo' => $nuevaCantidadSaldo,
                'costo_promedio' => $nuevoCostoPromedio,
                'total_saldo'    => round($nuevaCantidadSaldo * $nuevoCostoPromedio, 2),
            ];
        }

        $costoSalida = max(0, round($costoPromedioAnterior, 6));
        $totalSalida = round($cantidadSalida * $costoSalida, 2);
        $nuevaCantidadSaldo = max(0, round($saldoAnterior - $cantidadSalida, 3));
        $nuevoTotalSaldo = $nuevaCantidadSaldo > 0
            ? round($nuevaCantidadSaldo * $costoSalida, 2)
            : 0;

        return [
            'costo_entrada'  => 0,
            'total_entrada'  => 0,
            'costo_salida'   => $costoSalida,
            'total_salida'   => $totalSalida,
            'cantidad_saldo' => $nuevaCantidadSaldo,
            'costo_promedio' => $nuevaCantidadSaldo > 0 ? $costoSalida : 0,
            'total_saldo'    => $nuevoTotalSaldo,
        ];
    }

    public function recalcular(string $codigoProducto, string $codigoAlmacen): void
    {
        $movimientos = DB::table('kardex')
            ->where('codigo_producto', $codigoProducto)
            ->where('codigo_almacen', $codigoAlmacen)
            ->orderBy('fecha_movimiento', 'asc')
            ->orderBy('id_kardex', 'asc')
            ->lockForUpdate()
            ->get();

        $cantidadAcumulada = 0;
        $totalValorAcumulado = 0;
        $costoPromedio = 0;

        foreach ($movimientos as $mov) {
            if ($mov->tipo_movimiento === 'SALIDA' || ($mov->tipo_movimiento === 'EXTORNO' && $mov->cantidad_salida > 0)) {
                $costoSalidaEfectivo = ($costoPromedio > 0) ? $costoPromedio : ($mov->costo_salida ?? 0);
                $totalSalida = round($mov->cantidad_salida * $costoSalidaEfectivo, 2);
                $cantidadAcumulada = round($cantidadAcumulada - $mov->cantidad_salida, 3);
                $totalValorAcumulado = max(0, round($totalValorAcumulado - $totalSalida, 2));
                DB::table('kardex')
                    ->where('id_kardex', $mov->id_kardex)
                    ->update([
                        'costo_salida' => round($costoSalidaEfectivo, 6),
                        'total_salida' => $totalSalida,
                        'costo_promedio' => round($costoPromedio, 6),
                        'cantidad_saldo' => max(0, $cantidadAcumulada),
                        'total_saldo' => max(0, round($cantidadAcumulada * $costoPromedio, 2)),
                    ]);

                if ($mov->codigo_referencia_movimiento) {
                    DB::table('movimientos_inventario')
                        ->where('id_movimiento', $mov->codigo_referencia_movimiento)
                        ->update([
                            'costo_unitario' => round($costoSalidaEfectivo, 6),
                            'total' => $totalSalida
                        ]);
                }

                // Propagar costo a la entrada de la transferencia si aplica
                if ($mov->documento === 'TRANSFERENCIA' || $mov->documento === 'ANULACION_TRANSFERENCIA') {
                    $ingresoTransf = DB::table('kardex')
                        ->where('numero_documento', $mov->numero_documento)
                        ->where('codigo_producto', $codigoProducto)
                        ->where('tipo_movimiento', 'INGRESO')
                        ->first();
                        
                    if ($ingresoTransf && abs((float)$ingresoTransf->costo_entrada - (float)$costoSalidaEfectivo) > 0.000001) {
                        DB::table('kardex')
                            ->where('id_kardex', $ingresoTransf->id_kardex)
                            ->update(['costo_entrada' => round($costoSalidaEfectivo, 6)]);
                            
                        // Llamada recursiva para propagar el recálculo en el almacén destino
                        $this->recalcular($codigoProducto, $ingresoTransf->codigo_almacen);
                    }
                }
                
                continue;
            }

            if ($mov->tipo_movimiento === 'TRASPASO') {
                $cantidadAcumulada = round($cantidadAcumulada + ($mov->cantidad_entrada - $mov->cantidad_salida), 3);
                $totalValorAcumulado = max(0, round($cantidadAcumulada * $costoPromedio, 2));
                DB::table('kardex')
                    ->where('id_kardex', $mov->id_kardex)
                    ->update([
                        'costo_promedio' => round($costoPromedio, 6),
                        'cantidad_saldo' => max(0, $cantidadAcumulada),
                        'total_saldo' => $totalValorAcumulado,
                    ]);
                continue;
            }

            $costoEntradaEfectivo = ($mov->costo_entrada > 0) ? $mov->costo_entrada : $costoPromedio;
            $totalEntrada = round($mov->cantidad_entrada * $costoEntradaEfectivo, 2);
            $cantidadAcumulada = round($cantidadAcumulada + $mov->cantidad_entrada, 3);
            $totalValorAcumulado = round($totalValorAcumulado + $totalEntrada, 2);
            $costoPromedio = $cantidadAcumulada > 0
                ? round($totalValorAcumulado / $cantidadAcumulada, 6)
                : 0;

            DB::table('kardex')
                ->where('id_kardex', $mov->id_kardex)
                ->update([
                    'total_entrada'  => $totalEntrada,
                    'costo_promedio' => $costoPromedio,
                    'cantidad_saldo' => max(0, $cantidadAcumulada),
                    'total_saldo'    => max(0, round($cantidadAcumulada * $costoPromedio, 2)),
                ]);

            if ($mov->codigo_referencia_movimiento) {
                DB::table('movimientos_inventario')
                    ->where('id_movimiento', $mov->codigo_referencia_movimiento)
                    ->update([
                        'costo_unitario' => round($costoEntradaEfectivo, 6),
                        'total' => $totalEntrada
                    ]);
            }
        }

        $ultimo = DB::table('kardex')
            ->where('codigo_producto', $codigoProducto)
            ->where('codigo_almacen', $codigoAlmacen)
            ->orderBy('fecha_movimiento', 'desc')
            ->orderBy('id_kardex', 'desc')
            ->first(['cantidad_saldo', 'costo_promedio', 'fecha_movimiento']);

        if ($ultimo) {
            $updateData = [
                'costo_promedio' => $ultimo->costo_promedio,
            ];
            if ($ultimo->fecha_movimiento) {
                $updateData['fecha_ultimo_movimiento'] = $ultimo->fecha_movimiento;
            }
            DB::table('inventario')
                ->where('codigo_producto', $codigoProducto)
                ->where('codigo_almacen', $codigoAlmacen)
                ->update($updateData);
        }
    }
}