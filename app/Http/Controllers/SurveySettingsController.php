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
        return Inertia::render('SurveySettings', ['settings' => SurveyAccess::settings()]);
    }
    public function update(Request $r) {
        $this->authorizeGlobal($r);
        $data = $r->validate(['published' => ['required', 'boolean'], 'closes_at' => ['nullable', 'date_format:Y-m-d\TH:i']]);
        $data['closes_at'] = empty($data['closes_at']) ? null : str_replace('T', ' ', $data['closes_at']).':00';
        DB::transaction(function () use ($data) {
            SurveyAccess::settings(true);
            DB::table('survey_settings')->where('id', 1)->update($data + ['updated_at' => now()]);
        });
        return back()->with('success', 'Configuración guardada.');
    }
}
