<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Inertia\Inertia;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;
use App\Support\SurveyAccess;

class EncuestaController extends Controller
{
    private function eess(Request $r)
    {
        abort_unless($r->user()->role === 'digitador' && $r->user()->establecimiento, 403);
        return $r->user()->establecimiento;
    }

    public function index(Request $r)
    {
        $esAdministrador = $r->user()->role === 'administrador';
        $eess = $esAdministrador ? null : $this->eess($r);
        $ids = \App\Support\AmbitoEncuestas::ids($r->user());
        $filters = $r->validate(['numero' => ['nullable','integer','min:1'], 'periodo' => ['nullable','integer',Rule::exists('periodos','id')], 'fecha' => ['nullable','date_format:Y-m-d']]);
        $query = DB::table('encuestas')->join('periodos', 'periodos.id', '=', 'encuestas.periodo_id')
            ->join('establecimientos', 'establecimientos.id', '=', 'encuestas.establecimiento_id')
            ->whereIn('encuestas.establecimiento_id', $ids)
            ->when(!$esAdministrador, fn ($q) => $q->where('encuestas.user_id', $r->user()->id))
            ->select('encuestas.id', 'encuestas.numero', 'encuestas.fecha', 'periodos.nombre as periodo', 'establecimientos.nombre as establecimiento', 'establecimientos.codigo_renipress');
        if (!empty($filters['numero'])) $query->where('numero', $filters['numero']);
        if (!empty($filters['periodo'])) $query->where('periodo_id', $filters['periodo']);
        if (!empty($filters['fecha'])) $query->where('fecha', $filters['fecha']);
        return Inertia::render('Encuestas/Lista', [
            'eess' => $eess?->only(['nombre', 'codigo_renipress', 'microred']),
            'es_admin' => $esAdministrador,
            'filtros' => $filters,
            'periodos' => DB::table('periodos')->orderByDesc('inicio')->get(['id','nombre']),
            'encuestas' => $query->orderByDesc('encuestas.id')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(Request $r)
    {
        $eess = $this->eess($r);
        $existing = DB::table('encuestas')->where('user_id', $r->user()->id)->orderByDesc('id')->first();
        if ($existing) return redirect('/encuestas/'.$existing->id);
        $periodos = DB::table('periodos')->where('habilitado', true)->where('inicio', '<=', now()->toDateString())->where('fin', '>=', now()->toDateString())->get();
        $token = SurveyAccess::open(SurveyAccess::settings()) && $periodos->count() === 1 ? Crypt::encryptString(json_encode(['id' => (string) Str::uuid(), 'user' => $r->user()->id, 'eess' => $eess->id, 'periodo' => $periodos->first()->id, 'inicio' => now()->toIso8601String(), 'version' => 'abancay-2026'])) : null;
        return Inertia::render('Encuestas/Formulario', [
            'eess' => $eess->only(['nombre', 'codigo_renipress', 'microred']),
            'inicio_token' => $token,
            'draft_key' => 'servqual-draft-v1-'.$r->user()->id.'-'.$eess->id,
            'items' => \App\Support\Instrumento::items('abancay-2026'),
        ]);
    }

    public function store(Request $r)
    {
        $eess = $this->eess($r);
        $rules = [
            'establecimiento_id' => ['prohibited'], 'user_id' => ['prohibited'],
            'inicio_token' => ['required', 'string'],
            'periodo_id' => ['prohibited'], 'numero' => ['prohibited'], 'fecha' => ['prohibited'],
            'hora_inicio' => ['prohibited'], 'hora_final' => ['prohibited'],
            'edad' => ['required', 'integer', 'between:0,100'], 'especialidad' => ['required', 'string', 'max:255'],
            'personal_otro' => ['required_if:personal,6', 'nullable', 'string', 'max:255'],
            'respuestas' => ['required', 'array', 'size:22'],
            'respuestas.*' => ['array:item,expectativa,percepcion'],
            'respuestas.*.item' => ['required', 'integer', 'between:1,22', 'distinct'],
            'respuestas.*.expectativa' => ['required', Rule::in(['1','2','3','4','5','6','7','No aplica','Sin respuesta'])],
            'respuestas.*.percepcion' => ['required', Rule::in(['1','2','3','4','5','6','7','No aplica','Sin respuesta'])],
        ];
        foreach (['condicion' => 2, 'sexo' => 2, 'estudios' => 5, 'seguro' => 3, 'paciente' => 2, 'personal' => 6] as $f => $max) { $rules[$f] = ['required', 'integer', "between:1,{$max}"]; }
        $data = $r->validate($rules, [
            'required' => 'Este campo es obligatorio.', 'integer' => 'Ingresa un número entero.',
            'between' => 'El valor debe estar entre :min y :max.', 'in' => 'Selecciona 1 a 7, No aplica o Sin respuesta.',
            'exists' => 'Selecciona un periodo habilitado.', 'prohibited' => 'Este dato lo asigna el servidor.',
            'date_format' => 'El formato de fecha u hora no es válido.',
        ]);
        try { $inicio = json_decode(Crypt::decryptString($data['inicio_token']), true, flags: JSON_THROW_ON_ERROR); }
        catch (\Throwable $e) { throw ValidationException::withMessages(['inicio_token' => 'No se pudo verificar el inicio. Abre una nueva encuesta.']); }
        if (($data['personal'] ?? null) != 6) { $data['personal_otro'] = null; }
        abort_unless($inicio['user'] === $r->user()->id && $inicio['eess'] === $eess->id, 403);
        unset($data['establecimiento_id'], $data['user_id'], $data['inicio_token'], $data['periodo_id'], $data['numero'], $data['fecha'], $data['hora_inicio'], $data['hora_final']);
        try {
            $id = DB::transaction(function () use ($data, $eess, $r, $inicio) {
                SurveyAccess::check();
                DB::table('users')->where('id', $r->user()->id)->lockForUpdate()->first();
                DB::table('establecimientos')->where('id', $eess->id)->lockForUpdate()->first();
                $existente = DB::table('encuestas')->where('envio_id', $inicio['id'])->first();
                if ($existente) { return $existente->id; }
                if (DB::table('encuestas')->where('user_id', $r->user()->id)->exists()) throw ValidationException::withMessages(['acceso' => 'Ya enviaste tu encuesta. Abre tu encuesta registrada para modificar las respuestas.']);
                $periodo = DB::table('periodos')->where('id', $inicio['periodo'])->lockForUpdate()->first();
                if (! $periodo || ! $periodo->habilitado) { throw ValidationException::withMessages(['inicio_token' => 'El periodo ya no admite registros.']); }
                $started = CarbonImmutable::parse($inicio['inicio'])->setTimezone('America/Lima');
                $data['periodo_id'] = $periodo->id;
                $data['numero'] = 1 + (int) DB::table('encuestas')->where('establecimiento_id', $eess->id)->where('periodo_id', $periodo->id)->max('numero');
                $data['envio_id'] = $inicio['id'];
                $data['fecha'] = $started->toDateString();
                $data['hora_inicio'] = $started->format('H:i');
                $data['hora_final'] = now('America/Lima')->format('H:i');
                $respuestas = $data['respuestas']; unset($data['respuestas']);
                $id = DB::table('encuestas')->insertGetId($data + ['establecimiento_id' => $eess->id, 'user_id' => $r->user()->id, 'version_instrumento' => $inicio['version'] ?? 'servqual-provisional-v1', 'created_at' => now(), 'updated_at' => now()]);
                foreach ($respuestas as $respuesta) {
                    DB::table('respuestas')->insert(['encuesta_id' => $id, 'item' => $respuesta['item'], 'expectativa' => $respuesta['expectativa'] ?? null, 'percepcion' => $respuesta['percepcion'] ?? null]);
                }
                return $id;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['numero' => 'Ya existe una encuesta con ese número en este establecimiento y periodo.']);
        }
        return redirect()->route('encuestas.show', $id)->with('encuesta_guardada', true);
    }

    private function editableRecord(Request $r, int $id) {
        $eess = $this->eess($r);
        $record = DB::table('encuestas')->where('id', $id)->where('user_id', $r->user()->id)->where('establecimiento_id', $eess->id)->first();
        abort_unless($record && $record->id === DB::table('encuestas')->where('user_id', $r->user()->id)->max('id'), 403);
        return $record;
    }
    public function edit(Request $r, int $encuesta) {
        $record = $this->editableRecord($r, $encuesta);
        if (!SurveyAccess::open(SurveyAccess::settings())) return redirect('/encuestas/'.$encuesta);
        return Inertia::render('Encuestas/Editar', ['encuesta_id' => $encuesta, 'items' => \App\Support\Instrumento::items($record->version_instrumento),
            'respuestas' => DB::table('respuestas')->where('encuesta_id', $encuesta)->orderBy('item')->get(['item', 'expectativa', 'percepcion'])]);
    }
    public function update(Request $r, int $encuesta) {
        $this->editableRecord($r, $encuesta);
        $data = $r->validate([
            'respuestas' => ['required', 'array', 'size:22'], 'respuestas.*' => ['array:item,expectativa,percepcion'],
            'respuestas.*.item' => ['required', 'integer', 'between:1,22', 'distinct'],
            'respuestas.*.expectativa' => ['required', Rule::in(['1','2','3','4','5','6','7','No aplica','Sin respuesta'])],
            'respuestas.*.percepcion' => ['required', Rule::in(['1','2','3','4','5','6','7','No aplica','Sin respuesta'])],
        ]);
        DB::transaction(function () use ($r, $encuesta, $data) {
            SurveyAccess::check();
            DB::table('users')->where('id', $r->user()->id)->lockForUpdate()->first();
            $this->editableRecord($r, $encuesta);
            foreach ($data['respuestas'] as $answer) {
                DB::table('respuestas')->updateOrInsert(['encuesta_id' => $encuesta, 'item' => $answer['item']], ['expectativa' => $answer['expectativa'], 'percepcion' => $answer['percepcion']]);
            }
            DB::table('encuestas')->where('id', $encuesta)->update(['updated_at' => now()]);
        });
        return redirect('/encuestas/'.$encuesta)->with('encuesta_guardada', true);
    }

    public function show(Request $r, int $encuesta)
    {
        $ids = \App\Support\AmbitoEncuestas::ids($r->user());
        $record = DB::table('encuestas')->where('id', $encuesta)->whereIn('establecimiento_id', $ids)
            ->when($r->user()->role === 'digitador', fn ($q) => $q->where('user_id', $r->user()->id))
            ->first();
        abort_unless($record, 404);
        $eess = \App\Models\Establecimiento::findOrFail($record->establecimiento_id);
        return Inertia::render('Encuestas/Detalle', [
            'eess' => $eess->only(['nombre', 'codigo_renipress', 'microred']), 'encuesta' => $record,
            'periodo' => DB::table('periodos')->where('id', $record->periodo_id)->value('nombre'),
            'respuestas' => DB::table('respuestas')->where('encuesta_id', $encuesta)->orderBy('item')->get(),
            'items' => \App\Support\Instrumento::items($record->version_instrumento),
            'editable' => $r->user()->role === 'digitador' && $record->user_id === $r->user()->id && $record->id === DB::table('encuestas')->where('user_id', $r->user()->id)->max('id') && SurveyAccess::open(SurveyAccess::settings()),
            'es_digitador' => $r->user()->role === 'digitador',
            'guardada' => (bool) $r->session()->get('encuesta_guardada', false),
        ]);
    }
}
