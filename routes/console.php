<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// §15.4 : cycle de suspension automatique pour non-paiement.
Schedule::command('app:check-abonnements')->dailyAt('06:00');
