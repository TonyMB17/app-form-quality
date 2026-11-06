<?php
namespace App\Support;

class Instrumento
{
    public static function items(string $version): array
    {
        return array_map(function ($n) use ($version) {
            return [
                'numero' => $n,
                'dimension' => match (true) { $n <= 5 => 'Fiabilidad', $n <= 9 => 'Capacidad de respuesta', $n <= 13 => 'Seguridad', $n <= 18 => 'Empatía', default => 'Aspectos tangibles' },
                'expectativa' => $version === 'abancay-2026' ? config('abancay2026.expectativas')[$n - 1] : null,
                'percepcion' => $version === 'abancay-2026' ? config('abancay2026.percepciones')[$n - 1] : null,
            ];
        }, range(1,22));
    }
}
