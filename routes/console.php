<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\TodoCleanup;
use App\Console\Commands\SendMorningReminders;
use App\Console\Commands\SendEveningReminders;

// スケジューラーの設定
Schedule::command("todos:cleanup")->dailyAt("09:06");

// メール送信のスケジュール設定
Schedule::command("reminders:morning")->dailyAt("20:30");

Schedule::command("reminders:evening")->dailyAt("20:30");
