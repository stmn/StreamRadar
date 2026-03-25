<?php

namespace App\Http\Controllers;

use App\Models\IgnoredStreamer;
use App\Models\Stream;
use App\Services\TwitchApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IgnoredStreamerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = IgnoredStreamer::orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('user_login', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Ignored', [
            'ignoredStreamers' => $query->paginate(50)->withQueryString(),
            'filters' => [
                'search' => $request->input('search'),
            ],
        ]);
    }

    public function store(Request $request, TwitchApiService $twitch): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'user_login' => 'required_without:twitch_user_id|string|max:255',
            'twitch_user_id' => 'nullable|string',
            'user_name' => 'nullable|string|max:255',
            'profile_image_url' => 'nullable|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $login = strtolower($validated['user_login'] ?? '');
        $userId = $validated['twitch_user_id'] ?? null;
        $userName = $validated['user_name'] ?? $login;
        $avatar = $validated['profile_image_url'] ?? null;

        // Try to fetch user info from Twitch if missing
        if ((! $userId || ! $avatar) && $twitch->isConfigured() && $login) {
            try {
                $users = $twitch->getUsers([$login]);
                if (! empty($users)) {
                    $userId = $userId ?: $users[0]['id'];
                    $userName = $users[0]['display_name'] ?? $userName;
                    $avatar = $avatar ?: ($users[0]['profile_image_url'] ?? null);
                }
            } catch (\Exception $e) {
                // Continue without Twitch data
            }
        }

        if (! $userId) {
            $userId = $login;
        }

        IgnoredStreamer::updateOrCreate(
            ['twitch_user_id' => $userId],
            [
                'user_login' => $login,
                'user_name' => $userName,
                'profile_image_url' => $avatar,
                'reason' => $validated['reason'] ?? null,
            ],
        );

        // Remove their streams
        Stream::where('user_login', $login)->delete();

        return back()->with('success', "\"{$userName}\" added to ignored list.");
    }

    public function destroy(IgnoredStreamer $ignored): \Illuminate\Http\RedirectResponse
    {
        $name = $ignored->user_name;
        $ignored->delete();

        return back()->with('success', "\"{$name}\" removed from ignored list.");
    }
}
