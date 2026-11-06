<?php
namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AmbitoEncuestas
{
    public static function ids(User $user): array
    {
        if ($user->role === 'digitador' && $user->establecimiento_id) return [$user->establecimiento_id];
        if ($user->role === 'administrador') {
            $ids = DB::table('administrador_establecimiento')->where('user_id', $user->id)->pluck('establecimiento_id')->all();
            abort_if($ids === [], 403, 'No hay establecimientos autorizados para esta cuenta.');
            return $ids;
        }
        abort(403);
    }
}
