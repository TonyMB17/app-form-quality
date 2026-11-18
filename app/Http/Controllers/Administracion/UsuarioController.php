<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AmbitoEncuestas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UsuarioController extends Controller
{
    private function establecimientosPermitidos(Request $request): array
    {
        abort_unless($request->user()->role === 'administrador', 403);

        return AmbitoEncuestas::ids($request->user());
    }

    public function index(Request $request)
    {
        $ids = $this->establecimientosPermitidos($request);

        $filters = $request->validate(['buscar'=>['nullable','string','max:100'], 'eess'=>['nullable','integer',Rule::in($ids)]]);
        $usuarios = User::query()
            ->where('role', 'digitador')
            ->whereIn('establecimiento_id', $ids)
            ->join('establecimientos', 'establecimientos.id', '=', 'users.establecimiento_id')
            ->select('users.id', 'users.name', 'users.dni', 'users.establecimiento_id', 'establecimientos.nombre as establecimiento', 'establecimientos.codigo_renipress')
            ->addSelect('users.activo')
            ->addSelect(['encuestas_count' => DB::table('encuestas')->selectRaw('count(*)')->whereColumn('encuestas.user_id', 'users.id')])
            ->when(!empty($filters['buscar']), fn ($q) => $q->where(fn ($search) => $search->where('users.name','like','%'.$filters['buscar'].'%')->orWhere('users.dni','like','%'.$filters['buscar'].'%')))
            ->when(!empty($filters['eess']), fn ($q) => $q->where('users.establecimiento_id',$filters['eess']))
            ->orderBy('establecimientos.nombre')
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Administracion/Usuarios/Lista', [
            'success' => $request->session()->get('success'),
            'filtros' => $filters,
            'usuarios' => $usuarios,
            'administradores' => User::query()->where('role', 'administrador')->orderBy('name')->get(['id', 'name', 'dni']),
            'establecimientos' => DB::table('establecimientos')->whereIn('id', $ids)->orderBy('nombre')->get(['id', 'nombre', 'codigo_renipress']),
        ]);
    }

    public function create(Request $request)
    {
        $ids = $this->establecimientosPermitidos($request);

        return Inertia::render('Administracion/Usuarios/Formulario', [
            'usuario' => null,
            'establecimientos' => DB::table('establecimientos')->whereIn('id', $ids)->orderBy('nombre')->get(['id', 'nombre', 'codigo_renipress']),
        ]);
    }

    public function state(Request $request, int $usuario)
    {
        $ids = $this->establecimientosPermitidos($request);
        $data = $request->validate(['activo' => ['required', 'boolean']]);
        DB::transaction(function () use ($ids, $usuario, $data) {
            $user = User::where('role', 'digitador')->whereIn('establecimiento_id', $ids)->lockForUpdate()->findOrFail($usuario);
            if ($user->activo !== (bool) $data['activo']) {
                $user->activo = (bool) $data['activo'];
                if (!$user->activo) {
                    $user->auth_version++;
                    $user->remember_token = null;
                }
                $user->save();
            }
        });
        return back()->with('success', $data['activo'] ? 'Usuario reactivado. Ya puede iniciar sesión.' : 'Usuario desactivado. Sus encuestas se conservan.');
    }

    public function destroy(Request $request, int $usuario)
    {
        $ids = $this->establecimientosPermitidos($request);
        DB::transaction(function () use ($request, $ids, $usuario) {
            $user = User::where('role', 'digitador')->whereIn('establecimiento_id', $ids)->lockForUpdate()->findOrFail($usuario);
            $data = $request->validate([
                'confirmado' => ['required', 'accepted'],
                'confirmacion' => ['required', Rule::in(['ELIMINAR '.$user->dni])],
                'encuestas_count' => ['required', 'integer', 'min:0'],
            ], ['confirmacion.in' => 'Escribe ELIMINAR seguido del DNI de esta cuenta.']);
            $surveys = DB::table('encuestas')->where('user_id', $user->id)->lockForUpdate()->get(['id', 'establecimiento_id', 'foto_path']);
            // A moved digitador may still own surveys from another establishment.
            abort_if($surveys->contains(fn ($survey) => !in_array($survey->establecimiento_id, $ids)), 403, 'La cuenta tiene encuestas fuera de tu ámbito autorizado.');
            if ($surveys->count() !== (int) $data['encuestas_count']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['encuestas_count' => 'Cambió la cantidad de encuestas. Cierra esta ventana, actualiza la lista y confirma nuevamente.']);
            }
            foreach ($surveys as $survey) {
                if ($survey->foto_path) DB::table('pending_photo_deletions')->insertOrIgnore(['path' => $survey->foto_path]);
            }
            DB::table('encuesta_cambios')->whereIn('encuesta_id', $surveys->pluck('id'))->delete();
            DB::table('encuesta_cambios')->where('user_id', $user->id)->delete();
            DB::table('encuestas')->where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        }, 3);
        $pending = \App\Support\DeletedSurveyPhotos::cleanup();
        return redirect('/administracion/usuarios')->with('success', $pending
            ? 'Cuenta y encuestas eliminadas. Hay fotografías pendientes de limpieza; contacta al responsable técnico para reintentar.'
            : 'Cuenta, encuestas y fotografías eliminadas definitivamente.');
    }

    public function store(Request $request)
    {
        $ids = $this->establecimientosPermitidos($request);
        $data = $request->validate([
            'dni' => ['required', 'regex:/^[0-9]{8}$/', 'unique:users,dni'],
            'name' => ['required', 'string', 'max:255'],
            'establecimiento_id' => ['required', 'integer', Rule::in($ids)],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ], [
            'dni.regex' => 'El DNI debe tener ocho dígitos.',
            'dni.unique' => 'Ya existe una cuenta con este DNI.',
            'establecimiento_id.in' => 'Selecciona un establecimiento autorizado.',
            'password.min' => 'La contraseña debe tener al menos ocho caracteres.',
        ]);

        $user = new User;
        $user->dni = $data['dni'];
        $user->name = $data['name'];
        $user->establecimiento_id = $data['establecimiento_id'];
        $user->password = $data['password'];
        $user->role = 'digitador';
        $user->save();

        return redirect('/administracion/usuarios')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(Request $request, int $usuario)
    {
        $ids = $this->establecimientosPermitidos($request);
        $user = User::query()->whereIn('role', ['administrador', 'digitador'])->findOrFail($usuario);
        if ($user->role === 'digitador') {
            abort_unless(in_array($user->establecimiento_id, $ids), 404);
        }

        return Inertia::render('Administracion/Usuarios/Formulario', [
            'usuario' => $user->only(['id', 'dni', 'name', 'establecimiento_id', 'role']),
            'establecimientos' => DB::table('establecimientos')->whereIn('id', $ids)->orderBy('nombre')->get(['id', 'nombre', 'codigo_renipress']),
        ]);
    }

    public function update(Request $request, int $usuario)
    {
        $ids = $this->establecimientosPermitidos($request);
        $user = User::query()->whereIn('role', ['administrador', 'digitador'])->findOrFail($usuario);
        if ($user->role === 'administrador') {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'current_password' => ['nullable', 'required_with:password', 'string', 'current_password'],
                'password' => ['nullable', 'required_with:current_password', 'string', 'min:8', 'max:255'],
            ], [
                'current_password.required_with' => 'Ingresa la contraseña actual del administrador para confirmar el cambio.',
                'current_password.current_password' => 'La contraseña actual del administrador no es correcta.',
                'password.required_with' => 'Ingresa la nueva contraseña.',
                'password.min' => 'La nueva contraseña debe tener al menos ocho caracteres.',
            ]);

            $user->name = $data['name'];
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();

            return redirect('/administracion/usuarios')->with('success', 'Cuenta administrativa actualizada correctamente.');
        }

        abort_unless(in_array($user->establecimiento_id, $ids), 404);
        $data = $request->validate([
            'dni' => ['required', 'regex:/^[0-9]{8}$/', Rule::unique('users', 'dni')->ignore($user->id)],
            'name' => ['required', 'string', 'max:255'],
            'establecimiento_id' => ['required', 'integer', Rule::in($ids)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ], [
            'dni.regex' => 'El DNI debe tener ocho dígitos.',
            'dni.unique' => 'Ya existe una cuenta con este DNI.',
            'establecimiento_id.in' => 'Selecciona un establecimiento autorizado.',
            'password.min' => 'La contraseña debe tener al menos ocho caracteres.',
        ]);

        $user->dni = $data['dni'];
        $user->name = $data['name'];
        $user->establecimiento_id = $data['establecimiento_id'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return redirect('/administracion/usuarios')->with('success', 'Usuario actualizado correctamente.');
    }
}
