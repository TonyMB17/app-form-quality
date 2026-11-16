<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Support\AmbitoEncuestas;
use App\Support\SurveyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MetaController extends Controller
{
    private function ids(Request $request): array
    {
        abort_unless($request->user()->role === 'administrador', 403);

        return AmbitoEncuestas::ids($request->user());
    }

    public function index(Request $request)
    {
        $ids = $this->ids($request);
        $filters = $request->validate(['periodo' => ['nullable', 'integer', Rule::exists('periodos', 'id')]]);
        $periodos = DB::table('periodos')->orderByDesc('inicio')->orderByDesc('id')->get();
        $selected = $filters['periodo'] ?? $periodos->first(fn ($p) => SurveyAccess::open($p))?->id ?? $periodos->first()?->id;
        $counts = DB::table('encuestas')->whereIn('establecimiento_id', $ids)->where('periodo_id', $selected ?? 0)
            ->select('user_id', 'establecimiento_id')->selectRaw('COUNT(*) as realizadas')->groupBy('user_id', 'establecimiento_id');
        $users = DB::table('users')->where('role', 'digitador')->whereIn('users.establecimiento_id', $ids)
            ->leftJoinSub($counts, 'avance', fn ($join) => $join->on('avance.user_id', '=', 'users.id')->on('avance.establecimiento_id', '=', 'users.establecimiento_id'))
            ->select('users.id', 'users.name', 'users.establecimiento_id')->selectRaw('COALESCE(avance.realizadas,0) as realizadas')->orderBy('users.name')->get()->groupBy('establecimiento_id');
        $establishments = DB::table('establecimientos')->whereIn('id', $ids)->orderBy('nombre')->get(['id', 'nombre', 'codigo_renipress', 'microred', 'meta_encuestas'])
            ->map(function ($eess) use ($users) {
                $eess->digitadores = $users->get($eess->id, collect())->values();

                return $eess;
            });

        return Inertia::render('Administracion/Metas', ['establecimientos' => $establishments, 'periodos' => $periodos->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre]), 'periodo_id' => $selected]);
    }

    public function update(Request $request, int $establecimiento)
    {
        abort_unless(in_array($establecimiento, $this->ids($request)), 404);
        $data = $request->validate(['meta_encuestas' => ['required', 'integer', 'min:1', 'max:100000']], ['meta_encuestas.min' => 'La meta debe ser al menos 1.', 'meta_encuestas.max' => 'La meta no puede superar 100 000.', 'meta_encuestas.integer' => 'Ingresa una cantidad entera.']);
        DB::table('establecimientos')->where('id', $establecimiento)->update(['meta_encuestas' => $data['meta_encuestas'], 'updated_at' => now()]);

        return back()->with('success','Meta actualizada para todos los digitadores del establecimiento.');
    }
}
