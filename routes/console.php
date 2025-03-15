<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\TodoCleanup;
use App\Console\Commands\SendMorningReminders;
use App\Console\Commands\SendEveningReminders;

// スケジューラーの設定
Schedule::command("todos:cleanup")->dailyAt("23:59");

// メール送信のスケジュール設定 - 毎分実行して、ユーザーの希望時間に送信
Schedule::command("reminders:morning")->everyMinute();
Schedule::command("reminders:evening")->everyMinute();
