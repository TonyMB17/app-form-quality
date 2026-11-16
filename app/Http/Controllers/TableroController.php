<?php
namespace App\Http\Controllers;

use App\Support\AmbitoEncuestas;
use App\Support\EncuestaItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TableroController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->role === 'administrador', 403);
        $ids = AmbitoEncuestas::ids($r->user());
        $catalogo = DB::table('establecimientos')->whereIn('id', $ids)->orderBy('nombre')->get(['id', 'nombre', 'codigo_renipress', 'microred']);
        $filters = $r->validate([
            'microred' => ['nullable', Rule::in($catalogo->pluck('microred')->unique()->all())],
            'eess' => ['nullable', 'integer', Rule::in($ids)],
        ]);
        $eessFiltrados = $catalogo->filter(fn ($eess) =>
            (empty($filters['microred']) || $eess->microred === $filters['microred'])
            && (empty($filters['eess']) || $eess->id == $filters['eess'])
        )->pluck('id');
        $digitadores = DB::table('users as u')->join('establecimientos as s', 's.id', '=', 'u.establecimiento_id')
            ->where('u.role', 'digitador')->whereIn('s.id', $eessFiltrados)
            ->orderBy('u.name')->get(['u.id', 'u.name', 's.nombre as establecimiento', 's.microred']);
        $filters = array_merge($filters, $r->validate([
            'digitador' => ['nullable', 'integer', Rule::in($digitadores->pluck('id')->all())],
        ]));
        $q = DB::table('encuestas as e')->join('establecimientos as s', 's.id', '=', 'e.establecimiento_id')->whereIn('s.id', $ids);
        if (!empty($filters['microred'])) $q->where('s.microred', $filters['microred']);
        if (!empty($filters['eess'])) $q->where('s.id', $filters['eess']);
        if (!empty($filters['digitador'])) $q->where('e.user_id', $filters['digitador']);
        $total = (clone $q)->count();
        $eessTotal = (clone $q)->distinct()->count('s.id');
        $numeric = "r.expectativa IN ('1','2','3','4','5','6','7') AND r.percepcion IN ('1','2','3','4','5','6','7')";
        $counts = DB::table('respuestas as r')->whereIn('r.encuesta_id', (clone $q)->select('e.id'))
            ->select('r.item')->selectRaw("SUM(CASE WHEN {$numeric} THEN 1 ELSE 0 END) as validos")
            ->selectRaw("SUM(CASE WHEN {$numeric} AND CAST(r.percepcion AS DECIMAL) >= CAST(r.expectativa AS DECIMAL) THEN 1 ELSE 0 END) as satisfechos")
            ->selectRaw("SUM(CASE WHEN r.expectativa IS NULL OR r.percepcion IS NULL THEN 1 ELSE 0 END) as incompletos")
            ->selectRaw("SUM(CASE WHEN r.expectativa = 'No aplica' OR r.percepcion = 'No aplica' THEN 1 ELSE 0 END) as no_aplica")
            ->selectRaw("SUM(CASE WHEN r.expectativa = 'Sin respuesta' OR r.percepcion = 'Sin respuesta' THEN 1 ELSE 0 END) as sin_respuesta")
            ->groupBy('r.item')->get()->keyBy('item');
        $items = array_map(function ($item) use ($counts) {
            $c = $counts->get($item['numero']);
            return $item + ['validos' => (int) ($c->validos ?? 0), 'satisfechos' => (int) ($c->satisfechos ?? 0), 'sin_respuesta' => (int) ($c->sin_respuesta ?? 0), 'incompletos' => (int) ($c->incompletos ?? 0), 'no_aplica' => (int) ($c->no_aplica ?? 0)];
        }, EncuestaItems::all());
        $dimensions = collect($items)->groupBy('dimension')->map(fn ($rows, $name) => ['nombre' => $name, 'validos' => $rows->sum('validos'), 'satisfechos' => $rows->sum('satisfechos')])->values();
        // Aggregate only surveys in the same authorized, filtered query as the overview.
        $groupCounts = DB::table('respuestas as r')->join('encuestas as ge', 'ge.id', '=', 'r.encuesta_id')
            ->whereIn('ge.id', (clone $q)->select('e.id'))
            ->select('ge.establecimiento_id', 'r.item')
            ->selectRaw("SUM(CASE WHEN {$numeric} THEN 1 ELSE 0 END) as validos")
            ->selectRaw("SUM(CASE WHEN {$numeric} AND CAST(r.percepcion AS DECIMAL) >= CAST(r.expectativa AS DECIMAL) THEN 1 ELSE 0 END) as satisfechos")
            ->groupBy('ge.establecimiento_id', 'r.item')->get()->groupBy('establecimiento_id');
        $surveyCounts = (clone $q)->select('s.id')->selectRaw('COUNT(*) as total')->groupBy('s.id')->pluck('total', 'id');
        $comparison = $catalogo->filter(fn ($s) => (empty($filters['microred']) || $s->microred === $filters['microred']) && (empty($filters['eess']) || $s->id == $filters['eess']))
            ->map(function ($s) use ($groupCounts, $surveyCounts, $items) {
                $rows = collect($groupCounts->get($s->id, []))->keyBy('item');
                return ['id' => $s->id, 'nombre' => $s->nombre, 'codigo' => $s->codigo_renipress, 'microred' => $s->microred,
                    'encuestas' => (int) ($surveyCounts[$s->id] ?? 0),
                    'items' => array_map(fn ($i) => ['numero' => $i['numero'], 'dimension' => $i['dimension'],
                        'validos' => (int) ($rows->get($i['numero'])->validos ?? 0),
                        'satisfechos' => (int) ($rows->get($i['numero'])->satisfechos ?? 0)], $items)];
            })->values();
        $demographicLabels = [
            'condicion' => ['Usuario', 'Acompañante'], 'sexo' => ['Masculino', 'Femenino'],
            'estudios' => ['Analfabeto', 'Primaria', 'Secundaria', 'Superior técnico', 'Superior universitario'],
            'seguro' => ['SIS', 'Ninguno', 'Otro'],
        ];
        $demographics = [];
        foreach ($demographicLabels as $field => $labels) {
            $buckets = [];
            $countsByValue = (clone $q)->select("e.{$field} as valor")->selectRaw('COUNT(*) as total')->groupBy("e.{$field}")->get();
            foreach ($countsByValue as $row) {
                $label = $labels[(int) $row->valor - 1] ?? 'Sin dato';
                $buckets[$label] = ($buckets[$label] ?? 0) + (int) $row->total;
            }
            $demographics[$field] = collect($buckets)->map(fn ($n, $label) => ['nombre' => $label, 'total' => $n])->values();
        }
        $byUser = (clone $q)->select('e.user_id')->selectRaw('COUNT(*) as total')->groupBy('e.user_id')->pluck('total', 'user_id');
        $accounts = DB::table('users as u')->join('establecimientos as s', 's.id', '=', 'u.establecimiento_id')
            ->where('u.role', 'digitador')->whereIn('s.id', $comparison->pluck('id'))
            ->orderBy('u.name')->get(['u.id', 'u.name', 's.nombre as establecimiento', 's.microred'])
            ->when(!empty($filters['digitador']), fn ($users) => $users->where('id', $filters['digitador']))
            ->map(fn ($u) => ['id' => $u->id, 'nombre' => $u->name, 'establecimiento' => $u->establecimiento, 'microred' => $u->microred, 'encuestas' => (int) ($byUser[$u->id] ?? 0)]);
        return Inertia::render('Tablero', [
            'datos_generales' => ['distribuciones' => $demographics, 'cuentas' => $accounts, 'microredes' => (clone $q)->select('s.microred as nombre')->selectRaw('COUNT(*) as total')->groupBy('s.microred')->get()],
            'filtros' => $filters,
            'digitadores' => $digitadores->map(fn ($u) => ['id' => $u->id, 'nombre' => $u->name, 'establecimiento' => $u->establecimiento]),
            'comparacion' => $comparison, 'catalogo' => $catalogo, 'items' => $items, 'dimensiones' => $dimensions,
            'resumen' => ['encuestas' => $total, 'establecimientos' => $eessTotal, 'validos' => array_sum(array_column($items, 'validos')), 'satisfechos' => array_sum(array_column($items, 'satisfechos')), 'excluidos' => $total * 22 - array_sum(array_column($items, 'validos'))],
            'encuestas' => (clone $q)->select('e.id', 'e.numero', 'e.fecha', 's.nombre as establecimiento')->orderByDesc('e.id')->paginate(15)->withQueryString(),
            'actualizado' => now('America/Lima')->format('d/m/Y H:i:s'),
            'es_digitador' => false,
        ]);
    }
}
