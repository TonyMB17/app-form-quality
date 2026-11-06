<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
class SurveyAccess {
    public static function settings(bool $lock = false): object {
        $query = DB::table('survey_settings')->where('id', 1);
        return ($lock ? $query->lockForUpdate() : $query)->first();
    }
    public static function open(object $settings): bool {
        return $settings->published && (!$settings->closes_at || now('America/Lima')->lt(CarbonImmutable::parse($settings->closes_at, 'America/Lima')));
    }
    public static function check(): void {
        if (!self::open(self::settings(true))) throw ValidationException::withMessages(['acceso' => 'La encuesta está cerrada o el plazo ha finalizado. Tus cambios no se guardaron.']);
    }
}
