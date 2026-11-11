<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'producto';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descripcion',
        'codigo_tipo_producto',
        'codigo_unidad_medida',
        'codigo_color',
        'es_producto_proceso',
        'estado',
        'fecha_creacion',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'estado' => 'integer',
        'es_producto_proceso' => 'integer',
    ];

    /**
     * Relación con la tabla Tipo de Producto.
     */
    public function tipo()
    {
        return $this->belongsTo(TipoProducto::class, 'codigo_tipo_producto', 'codigo');
    }

    /**
     * Relación con la tabla Unidad de Medida.
     */
    public function unidad()
    {
        return $this->belongsTo(UnidadMedida::class, 'codigo_unidad_medida', 'codigo');
    }

    /**
     * Alias para relación con Unidad de Medida (compatibilidad).
     */
    public function unidadMedida()
    {
        return $this->unidad();
    }

    /**
     * Relación con la tabla Color.
     */
    public function color()
    {
        return $this->belongsTo(Color::class, 'codigo_color', 'codigo');
    }
}