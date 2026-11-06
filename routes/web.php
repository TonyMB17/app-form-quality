<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Inicio'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [\App\Http\Controllers\AuthController::class, 'create'])->name('login');
    Route::post('/ingresar', [\App\Http\Controllers\AuthController::class, 'store'])->middleware('throttle:20,1');
});
Route::middleware('auth')->group(function () {
    Route::get('/administracion/usuarios', [\App\Http\Controllers\Administracion\UsuarioController::class, 'index']);
    Route::get('/administracion/usuarios/nuevo', [\App\Http\Controllers\Administracion\UsuarioController::class, 'create']);
    Route::post('/administracion/usuarios', [\App\Http\Controllers\Administracion\UsuarioController::class, 'store']);
    Route::get('/administracion/usuarios/{usuario}/editar', [\App\Http\Controllers\Administracion\UsuarioController::class, 'edit'])->whereNumber('usuario');
    Route::put('/administracion/usuarios/{usuario}', [\App\Http\Controllers\Administracion\UsuarioController::class, 'update'])->whereNumber('usuario');
    Route::get('/administracion/encuesta', [\App\Http\Controllers\SurveySettingsController::class, 'index']);
    Route::put('/administracion/encuesta', [\App\Http\Controllers\SurveySettingsController::class, 'update']);
    Route::get('/encuestas/{encuesta}/editar', [\App\Http\Controllers\EncuestaController::class, 'edit'])->whereNumber('encuesta');
    Route::put('/encuestas/{encuesta}', [\App\Http\Controllers\EncuestaController::class, 'update'])->whereNumber('encuesta');

    Route::get('/tablero', [\App\Http\Controllers\TableroController::class, 'index'])->name('tablero');
    Route::get('/encuestas', [\App\Http\Controllers\EncuestaController::class, 'index'])->name('encuestas.index');
    Route::get('/encuestas/nueva', [\App\Http\Controllers\EncuestaController::class, 'create'])->name('encuestas.create');
    Route::post('/encuestas', [\App\Http\Controllers\EncuestaController::class, 'store'])->name('encuestas.store');
    Route::get('/encuestas/{encuesta}', [\App\Http\Controllers\EncuestaController::class, 'show'])->whereNumber('encuesta')->name('encuestas.show');
    Route::get('/mi-establecimiento', [\App\Http\Controllers\AuthController::class, 'profile'])->name('dashboard');
    Route::post('/salir', [\App\Http\Controllers\AuthController::class, 'destroy'])->name('logout');
});
