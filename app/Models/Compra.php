<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras';
    protected $primaryKey = 'id_compra';
    
    const CREATED_AT = 'fecha_creacion'; 
    const UPDATED_AT = null;

    protected $fillable = [
    'tipo_documento',
    'serie_documento',
    'numero_documento',
    'proveedor',      // Nuevo: Es NOT NULL en la BD
    'ruc_proveedor',
    'fecha_compra',
    'subtotal',
    'igv',            // Corregido: En la BD es 'igv', no 'impuestos'
    'total',
    'moneda',
    'tipo_cambio',
    'estado',
    'usuario_creacion',
    'usuario_aprobacion',
    'id_guia_remision_compra',
    'igv_incluido',
    'condicion_pago',
    'fecha_vencimiento'
];

    public function datosProveedor()
    {
        return $this->belongsTo(Proveedor::class, 'ruc_proveedor', 'ruc');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class, 'id_compra', 'id_compra');
    }

    public function cuotas()
    {
        return $this->hasMany(CompraCuota::class, 'id_compra', 'id_compra')->orderBy('numero_cuota');
    }

    public function guias()
    {
        return $this->hasMany(GuiaRemisionCompra::class, 'id_compra', 'id_compra');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'id_almacen', 'id_almacen');
    }

    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'usuario_creacion', 'id_usuario');
    }

    public function guiaRemision()
    {
        return $this->belongsTo(GuiaRemisionCompra::class, 'id_guia_remision_compra', 'id_guia');
    }
}