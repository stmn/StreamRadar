<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class SetupCommand extends Command
{
    protected $signature = 'app:setup';

    protected $description = 'Initial setup for StreamRadar';

    public function handle(): int
    {
        $this->info('Setting up StreamRadar...');

        // Create SQLite database if missing
        $dbPath = database_path('database.sqlite');
        if (! file_exists($dbPath)) {
            touch($dbPath);
            $this->info('Created SQLite database.');
        }

        // Run migrations
        $this->call('migrate', ['--force' => true]);

        // Seed default settings
        $defaults = [
            'theme' => 'system',
            'sync_frequency_minutes' => '5',
            'global_min_viewers' => '0',
            'global_languages' => '[]',
            'global_keywords' => '[]',
        ];

        foreach ($defaults as $key => $value) {
            if (Setting::get($key) === null) {
                Setting::set($key, $value);
            }
        }

        $this->info('Default settings initialized.');
        $this->newLine();
        $this->info('StreamRadar is ready! Configure your Twitch API keys in Settings.');

        return self::SUCCESS;
    }
}
