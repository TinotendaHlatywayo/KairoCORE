<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('schedule:send-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/schedule-reminders.log'));

Schedule::command('saas:run-billing-notifications')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/saas-billing-notifications.log'));

Schedule::command('saas:expire-trials')
    ->dailyAt('07:15')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/saas-expire-trials.log'));

Schedule::command('finance:send-expense-reminders')
    ->dailyAt('07:20')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/platform-expense-reminders.log'));

Schedule::command('finance:generate-recurring-expenses')
    ->dailyAt('07:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/platform-recurring-expenses.log'));
