<?php

use App\Models\TimeClock;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:auto-clock-out', function () {
    TimeClock::with('breaks')->whereNull('clock_out')->each(function (TimeClock $clock) {
        $now=now();$configured=$clock->applied_configuration['shift']['value']['end_time']??$clock->applied_configuration['attendance']['value']['auto_clock_out_time']??'04:00';
        $deadline=$clock->clock_in->copy()->startOfDay()->setTimeFromTimeString($configured);if($deadline->lte($clock->clock_in))$deadline->addDay();if($now->lt($deadline))return;
        if($active=$clock->activeBreak())$active->update(['break_out'=>$now]);
        $clock->update(['clock_out'=>$now,'total_duration'=>$clock->workedSeconds($now),'break_duration'=>$clock->totalBreakSeconds($now),'current_state'=>'off']);
    }); $this->info('Due open shifts were closed.');
})->purpose('Close all open attendance shifts');
Schedule::command('attendance:auto-clock-out')->everyMinute()->withoutOverlapping();
