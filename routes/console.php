<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('encuestas:limpiar-fotos-eliminadas', function () {
    $pending = \App\Support\DeletedSurveyPhotos::cleanup();
    $this->info("Fotografías pendientes: {$pending}");
    return $pending ? 1 : 0;
})->purpose('Reintenta borrar fotografías de cuentas eliminadas');
\Illuminate\Support\Facades\Schedule::command('encuestas:limpiar-fotos-eliminadas')->everyFiveMinutes()->withoutOverlapping();
