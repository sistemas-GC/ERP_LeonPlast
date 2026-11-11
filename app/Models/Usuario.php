<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = null;

    protected $fillable = [
        'codigo_trabajador',
        'nombre_usuario',
        'contrasena_hash',
        'email',
        'rol',
        'ultimo_login',
        'activo',
        'remember_token',
    ];

    protected $hidden = [
        'contrasena_hash',
        'remember_token',
    ];

    // Le indicamos a Laravel qué campo usar como contraseña para el Login
    public function getAuthPassword()
    {
        return $this->contrasena_hash;
    }

    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('activo', 1);
    }

    // Relación con la tabla Trabajador
    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'codigo_trabajador', 'codigo');
    }

    // Verificar permisos dinámicos (con caché)
    public function hasAccess(string $slug): bool
    {
        if ($this->rol === 'Administrador') {
            return true;
        }

        $slugPermisos = $this->getSlugPermisos();

        return in_array($slug, $slugPermisos);
    }

    public function hasAnyAccess(array $slugs): bool
    {
        if ($this->rol === 'Administrador') return true;

        $slugPermisos = $this->getSlugPermisos();

        foreach ($slugs as $slug) {
            if (in_array($slug, $slugPermisos)) return true;
        }
        return false;
    }

    private function getSlugPermisos(): array
    {
        $cacheKey = 'permisos_rol_' . $this->rol;

        return Cache::remember($cacheKey, 300, function () {
            return \Illuminate\Support\Facades\DB::table('roles')
                ->where('roles.nombre', $this->rol)
                ->join('rol_modulo', 'roles.id', '=', 'rol_modulo.rol_id')
                ->join('modulos', 'modulos.id', '=', 'rol_modulo.modulo_id')
                ->pluck('modulos.slug')
                ->toArray();
        });
    }
}
