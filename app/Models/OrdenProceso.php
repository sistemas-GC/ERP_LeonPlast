<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenProceso extends Model
{
    use HasFactory;

    protected $table = 'orden_proceso';
    protected $primaryKey = 'id';
    public $timestamps = false; // Basado en el esquema legacy

    protected $fillable = [
        'idop',
        'secuencia',
        'codigo_proceso',
        'descripcion_proceso',
        'observaciones',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'estado_avance',
        'inyectada_inicial',
        'inyectada_final',
        'tiempo_ciclo'
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenProduccion::class, 'idop', 'idop');
    }
}
