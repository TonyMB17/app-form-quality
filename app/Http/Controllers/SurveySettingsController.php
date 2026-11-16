<?php
namespace App\Http\Controllers;
use App\Support\SurveyAccess;
use App\Support\AmbitoEncuestas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
class SurveySettingsController extends Controller {
    private function authorizeGlobal(Request $r): void {
        abort_unless($r->user()->role === 'administrador', 403);
        abort_unless(count(AmbitoEncuestas::ids($r->user())) === DB::table('establecimientos')->count(), 403, 'La configuración global requiere acceso a todos los establecimientos.');
    }
    public function index(Request $r) {
        $this->authorizeGlobal($r);
        $periodos = DB::table('periodos')->orderByDesc('abre_at')->get()
            ->map(function ($periodo) {
                $periodo->encuestas = DB::table('encuestas')->where('periodo_id', $periodo->id)->count();
                $periodo->abierto = SurveyAccess::open($periodo);
                $now = now('America/Lima')->format('Y-m-d H:i:s');
                $periodo->estado_visual = $periodo->estado === 'borrador' ? 'Borrador'
                    : ($periodo->estado === 'cerrado' || !$periodo->habilitado ? 'Cerrado'
                    : ($now < $periodo->abre_at ? 'Programado' : ($now >= $periodo->cierra_at ? 'Finalizado' : 'Abierto')));
                return $periodo;
            });
        return Inertia::render('SurveySettings', ['periodos' => $periodos]);
    }
    public function update(Request $r) {
        $this->authorizeGlobal($r);
        $data = $r->validate([
            'id'=>['nullable','integer','exists:periodos,id'], 'nombre'=>['required','string','max:150'],
            'estado'=>['required','in:borrador,publicado,cerrado'],
            'abre_at'=>['required','date_format:Y-m-d\TH:i'], 'cierra_at'=>['required','date_format:Y-m-d\TH:i','after:abre_at'],
        ]);
        DB::transaction(function () use ($data) {
            SurveyAccess::settings(true); // Serialize schedule changes, including new periods.
            $id = $data['id'] ?? null;
            if (DB::table('periodos')->where('nombre',$data['nombre'])->when($id,fn($q)=>$q->where('id','!=',$id))->exists()) throw \Illuminate\Validation\ValidationException::withMessages(['nombre'=>'Ya existe un periodo con este nombre.']);
            $start = str_replace('T',' ',$data['abre_at']).':00';
            $end = str_replace('T',' ',$data['cierra_at']).':00';
            if ($data['estado'] === 'publicado' && DB::table('periodos')->where('estado','publicado')->when($id,fn($q)=>$q->where('id','!=',$id))->where('abre_at','<',$end)->where('cierra_at','>',$start)->exists()) throw \Illuminate\Validation\ValidationException::withMessages(['abre_at'=>'El horario coincide con otro periodo publicado. Cierra o ajusta ese periodo primero.']);
            $values = ['nombre'=>$data['nombre'],'estado'=>$data['estado'],'habilitado'=>$data['estado']==='publicado','abre_at'=>$start,'cierra_at'=>$end,'inicio'=>substr($start,0,10),'fin'=>substr($end,0,10)];
            if ($id) DB::table('periodos')->where('id',$id)->update($values); else DB::table('periodos')->insert($values);
        });
        return back()->with('success', 'Periodo guardado.');
    }

    public function destroy(Request $r, int $periodo) {
        $this->authorizeGlobal($r);
        DB::transaction(function () use ($periodo) {
            SurveyAccess::settings(true);
            $record = DB::table('periodos')->where('id', $periodo)->lockForUpdate()->first();
            abort_unless($record, 404);
            if (DB::table('encuestas')->where('periodo_id', $periodo)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'periodo' => 'No se puede eliminar un periodo que ya tiene encuestas. Cámbialo a cerrado para conservar sus registros.',
                ]);
            }
            DB::table('periodos')->where('id', $periodo)->delete();
        });
        return back()->with('success', 'Periodo eliminado.');
    }
}
