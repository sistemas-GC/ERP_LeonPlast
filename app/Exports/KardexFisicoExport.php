<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class KardexFisicoExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected $filtros;

    public function __construct($filtros)
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $query = DB::table('kardex')
            ->join('producto', 'kardex.codigo_producto', '=', 'producto.codigo')
            ->join('almacen', 'kardex.codigo_almacen', '=', 'almacen.codigo_almacen')
            ->leftJoin('unidad_medida', 'kardex.codigo_unidad_medida', '=', 'unidad_medida.codigo')
            ->leftJoin('usuarios', 'kardex.usuario_registro', '=', 'usuarios.id_usuario')
            ->where('kardex.codigo_almacen', '!=', 'ALM04')
            ->where(function($q) {
                $q->whereNull('kardex.observaciones')
                  ->orWhere(function($subq) {
                      $subq->where('kardex.observaciones', 'NOT LIKE', '%[ANULADO]%')
                           ->where('kardex.observaciones', 'NOT LIKE', '%[EXTORNADO]%');
                  });
            })
            ->where('kardex.tipo_movimiento', '!=', 'EXTORNO')
            ->select(
                'kardex.*',
                'producto.descripcion as producto',
                'almacen.descripcion as almacen',
                'unidad_medida.descripcion as unidad_medida',
                'usuarios.nombre_usuario as nombre_usuario'
            );

        if (!empty($this->filtros['codigo_almacen'])) {
            $query->where('kardex.codigo_almacen', $this->filtros['codigo_almacen']);
        }

        if (!empty($this->filtros['codigo_producto'])) {
            $prod = $this->filtros['codigo_producto'];
            $query->where(function ($q) use ($prod) {
                $q->where('kardex.codigo_producto', $prod)
                  ->orWhere('kardex.codigo_producto', 'LIKE', "%{$prod}%")
                  ->orWhere('producto.descripcion', 'LIKE', "%{$prod}%");
            });
        }

        if (!empty($this->filtros['tipo_movimiento'])) {
            $query->where('kardex.tipo_movimiento', $this->filtros['tipo_movimiento']);
        }

        if (!empty($this->filtros['documento'])) {
            $query->where('kardex.documento', $this->filtros['documento']);
        }

        if (!empty($this->filtros['fecha_desde'])) {
            $query->where('kardex.fecha_movimiento', '>=', $this->filtros['fecha_desde'] . ' 00:00:00');
        }

        if (!empty($this->filtros['fecha_hasta'])) {
            $query->where('kardex.fecha_movimiento', '<=', $this->filtros['fecha_hasta'] . ' 23:59:59');
        }

        return $query->orderBy('kardex.fecha_movimiento', 'asc')
                     ->orderBy('kardex.id_kardex', 'asc')
                     ->get();
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Almacén',
            'Cód. Producto',
            'Producto / Material',
            'U.M.',
            'Tipo Operación',
            'Doc. Referencia',
            'N° Documento',
            'Lote',
            'Cant. Entrada',
            'Cant. Salida',
            'Cant. Saldo',
            'Observaciones',
            'Usuario'
        ];
    }

    public function map($mov): array
    {
        return [
            \Carbon\Carbon::parse($mov->fecha_movimiento)->format('d/m/Y H:i'),
            $mov->almacen,
            $mov->codigo_producto,
            $mov->producto,
            $mov->unidad_medida ?? $mov->codigo_unidad_medida,
            $mov->tipo_movimiento,
            $mov->documento,
            $mov->numero_documento,
            $mov->lote ?? '-',
            $mov->cantidad_entrada ? (float) $mov->cantidad_entrada : null,
            $mov->cantidad_salida ? (float) $mov->cantidad_salida : null,
            (float) $mov->cantidad_saldo,
            $mov->observaciones ?? '',
            $mov->nombre_usuario ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => '1E293B'], // Slate 800
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
