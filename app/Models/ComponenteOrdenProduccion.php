<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponenteOrdenProduccion extends Model
{
    use HasFactory;

    protected $table = 'componentes_orden_produccion_global';
    protected $primaryKey = 'id_op_componentes';
    public $timestamps = true;

    protected $fillable = [
        'idop',
        'id_proceso',
        'codigo_tipo_producto',
        'descripcion_tipo_producto',
        'codigo_producto',
        'descripcion_producto',
        'codigo_centro_trabajo',
        'descripcion_centro_trabajo',
        'codigo_molde',
        'descripcion_molde',
        'codigo_trabajador',
        'descripcion_trabajador',
        'codigo_unidad_medida',
        'descripcion_unidad_medida',
        'cantidad',
        'codigo_color',
        'descripcion_color',
        'codigo_formula_produccion',
        'descripcion_formula_produccion',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'fecha_inicio_maquina',
        'hora_inicio_maquina',
        'fecha_fin_maquina',
        'hora_fin_maquina',
        'inyectada_inicial',
        'inyectada_final',
        'estado',
        'numero_ot',
        'tipo_operacion',
        'observacion'
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenProduccion::class, 'idop', 'idop');
    }

    public function proceso()
    {
        return $this->belongsTo(OrdenProceso::class, 'id_proceso', 'id');
    }
}
