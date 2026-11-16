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
    public static function open(object $period): bool {
        $start = $period->abre_at ?? $period->inicio.' 00:00:00';
        $end = $period->cierra_at ?? $period->fin.' 23:59:59';
        return $period->habilitado && $period->estado === 'publicado' && now('America/Lima')->gte(CarbonImmutable::parse($start, 'America/Lima')) && now('America/Lima')->lt(CarbonImmutable::parse($end, 'America/Lima'));
    }
    public static function check(int $id): void {
        $period = DB::table('periodos')->where('id',$id)->lockForUpdate()->first();
        if (!$period || !self::open($period)) throw ValidationException::withMessages(['acceso' => 'El periodo no está abierto. Los cambios no se guardaron.']);
    }
}
