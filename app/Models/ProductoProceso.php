<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoProceso extends Model
{
    use HasFactory;

    protected $table = 'producto_proceso';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descripcion',
        'estado'
    ];

    public function rutas()
    {
        return $this->belongsToMany(
            ProcesoProduccion::class, 
            'producto_proceso_rutas', 
            'codigo_producto_proceso', 
            'codigo_proceso'
        )->withPivot('secuencia', 'id')->withTimestamps()->orderBy('secuencia', 'asc');
    }

    public function moldes()
    {
        return $this->belongsToMany(
            Molde::class,
            'producto_molde',
            'codigo_producto_proceso',
            'codigo_molde'
        );
    }
}
