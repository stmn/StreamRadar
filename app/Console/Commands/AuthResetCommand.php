<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AuthResetCommand extends Command
{
    protected $signature = 'auth:reset';

    protected $description = 'Disable authentication by removing stored credentials';

    public function handle(): int
    {
        Setting::where('key', 'auth_username')->delete();
        Setting::where('key', 'auth_password')->delete();
        Cache::forget('setting.auth_username');
        Cache::forget('setting.auth_password');

        $this->info('Authentication disabled. App is now open.');

        return self::SUCCESS;
    }
}
