<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SyncService;
use App\Services\TwitchApiService;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    public function trigger(SyncService $sync, TwitchApiService $twitch): \Illuminate\Http\RedirectResponse
    {
        if (! $twitch->isConfigured()) {
            return back()->with('error', 'Twitch API credentials not configured.');
        }

        $result = $sync->sync();

        return back()->with('success', "Sync complete: {$result->newStreams} new, {$result->updatedStreams} updated, {$result->endedStreams} ended.");
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'last_sync_at' => Setting::get('last_sync_at'),
            'sync_frequency_minutes' => Setting::get('sync_frequency_minutes', 5),
        ]);
    }
}
