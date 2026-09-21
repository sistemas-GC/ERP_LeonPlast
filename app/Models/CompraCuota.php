<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraCuota extends Model
{
    protected $table = 'compra_cuotas';

    protected $fillable = [
        'id_compra',
        'numero_cuota',
        'fecha_vencimiento',
        'monto',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'monto' => 'decimal:2',
        'numero_cuota' => 'integer',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'id_compra', 'id_compra');
    }
}
