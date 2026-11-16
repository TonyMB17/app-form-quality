<?php
namespace App\Support;

class EncuestaItems
{
    public static function all(): array
    {
        return array_map(function ($n) {
            return [
                'numero' => $n,
                'dimension' => match (true) { $n <= 5 => 'Fiabilidad', $n <= 9 => 'Capacidad de respuesta', $n <= 13 => 'Seguridad', $n <= 18 => 'Empatía', default => 'Aspectos tangibles' },
                'expectativa' => config('encuesta.expectativas')[$n - 1],
                'percepcion' => config('encuesta.percepciones')[$n - 1],
            ];
        }, range(1,22));
    }
}
