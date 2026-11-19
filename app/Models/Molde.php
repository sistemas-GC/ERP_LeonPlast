<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Molde extends Model
{
    use HasFactory;

    protected $table = 'molde';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = null;

    protected $fillable = [
        'codigo',
        'descripcion',
        'cavidades',
        'peso_unitario_gr',
        'activo'
    ];

    // Traer solo los moldes activos
    public function scopeActivos($query)
    {
        return $query->where('activo', 1);
    }
}