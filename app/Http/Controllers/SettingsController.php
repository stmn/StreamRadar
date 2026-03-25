<?php

namespace App\Http\Controllers;

use App\Models\BlacklistRule;
use App\Models\Category;
use App\Models\Setting;
use App\Models\TrackedChannel;
use App\Services\TwitchApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const SETTING_KEYS = [
        'twitch_client_id',
        'twitch_client_secret',
        'sync_frequency_minutes',
        'global_min_viewers',
        'global_languages',
        'global_keywords',
        'theme',
        'mail_to',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'mail_from_address',
        'mail_from_name',
        'discord_webhook_url',
        'webhook_url',
        'auth_username',
    ];

    public function index(): Response
    {
        $settings = Setting::getMany(self::SETTING_KEYS, [
            'sync_frequency_minutes' => '5',
            'global_min_viewers' => '0',
            'global_languages' => '[]',
            'global_keywords' => '[]',
            'theme' => 'system',
        ]);

        // Mask secret
        if (! empty($settings['twitch_client_secret'])) {
            $settings['twitch_client_secret_masked'] = str_repeat('*', 8).substr($settings['twitch_client_secret'], -4);
        }

        $settings['auth_enabled'] = ! empty(Setting::get('auth_username')) && ! empty(Setting::get('auth_password'));

        return Inertia::render('Settings', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = [];

        // Only include non-null values (ConvertEmptyStringsToNull turns '' into null)
        foreach (self::SETTING_KEYS as $key) {
            if ($request->has($key) && $request->input($key) !== null) {
                $data[$key] = $request->input($key);
            }
        }

        // Don't overwrite secret with masked version
        if (isset($data['twitch_client_secret']) && str_starts_with($data['twitch_client_secret'], '***')) {
            unset($data['twitch_client_secret']);
        }

        // Handle auth password separately (hash it)
        $authPassword = $request->input('auth_password');
        if ($authPassword) {
            Setting::set('auth_password', Hash::make($authPassword));
            // Keep current session authenticated so user isn't locked out
            $request->session()->put('authenticated', true);
        }

        Setting::setMany($data);

        // Update mail config dynamically if SMTP settings changed
        if (array_intersect_key($data, array_flip(['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'mail_from_address', 'mail_from_name']))) {
            $this->updateMailConfig();
        }

        return back()->with('success', 'Settings saved.');
    }

    public function disableAuth(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        $storedPassword = Setting::where('key', 'auth_password')->value('value');
        if (! $storedPassword || ! Hash::check($request->input('password'), $storedPassword)) {
            return response()->json(['message' => 'Invalid password'], 422);
        }

        Setting::where('key', 'auth_username')->delete();
        Setting::where('key', 'auth_password')->delete();
        \Illuminate\Support\Facades\Cache::forget('setting.auth_username');
        \Illuminate\Support\Facades\Cache::forget('setting.auth_password');

        return response()->json(['message' => 'Access control disabled']);
    }

    public function export(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        unset($settings['auth_password']); // Don't export hashed password

        return response()->json([
            'settings' => $settings,
            'categories' => Category::all(['twitch_id', 'name', 'box_art_url', 'is_active', 'notifications_enabled', 'use_global_filters', 'min_viewers', 'languages', 'keywords'])->toArray(),
            'channels' => TrackedChannel::all(['twitch_user_id', 'user_login', 'user_name', 'profile_image_url', 'is_active'])->toArray(),
            'blacklist' => BlacklistRule::all(['type', 'value', 'twitch_user_id', 'profile_image_url'])->toArray(),
        ]);
    }

    public function import(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:json|max:1024']);

        $data = json_decode(file_get_contents($request->file('file')->getRealPath()), true);
        if (! $data) {
            return back()->with('error', 'Invalid JSON file.');
        }

        // Import settings (except auth)
        if (! empty($data['settings'])) {
            foreach ($data['settings'] as $key => $value) {
                if (in_array($key, ['auth_username', 'auth_password'])) {
                    continue;
                }
                Setting::set($key, $value);
            }
        }

        // Import categories
        if (! empty($data['categories'])) {
            foreach ($data['categories'] as $cat) {
                Category::firstOrCreate(
                    ['twitch_id' => $cat['twitch_id']],
                    collect($cat)->except('twitch_id')->toArray(),
                );
            }
        }

        // Import channels
        if (! empty($data['channels'])) {
            foreach ($data['channels'] as $ch) {
                TrackedChannel::firstOrCreate(
                    ['twitch_user_id' => $ch['twitch_user_id']],
                    collect($ch)->except('twitch_user_id')->toArray(),
                );
            }
        }

        // Import blacklist
        if (! empty($data['blacklist'])) {
            foreach ($data['blacklist'] as $rule) {
                BlacklistRule::firstOrCreate(
                    ['type' => $rule['type'], 'value' => $rule['value']],
                    collect($rule)->except(['type', 'value'])->toArray(),
                );
            }
        }

        return back()->with('success', 'Configuration imported successfully.');
    }

    public function testTwitch(TwitchApiService $twitch): JsonResponse
    {
        return response()->json($twitch->testConnection());
    }

    private function updateMailConfig(): void
    {
        $host = Setting::get('smtp_host');
        if (! $host) {
            return;
        }

        config([
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => Setting::get('smtp_port', 587),
            'mail.mailers.smtp.username' => Setting::get('smtp_username'),
            'mail.mailers.smtp.password' => Setting::get('smtp_password'),
            'mail.mailers.smtp.encryption' => Setting::get('smtp_encryption', 'tls'),
            'mail.from.address' => Setting::get('mail_from_address'),
            'mail.from.name' => Setting::get('mail_from_name', 'StreamRadar'),
        ]);
    }
}
