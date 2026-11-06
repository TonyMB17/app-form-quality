<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    protected $table = 'establecimientos';

    protected $fillable = [
        'codigo_renipress', 'nombre', 'clasificacion', 'tipo', 'departamento',
        'provincia', 'distrito', 'red', 'microred', 'categoria', 'norte', 'este', 'cota',
    ];

    protected function casts(): array
    {
        return ['norte' => 'decimal:8', 'este' => 'decimal:8', 'cota' => 'decimal:8'];
    }
}
