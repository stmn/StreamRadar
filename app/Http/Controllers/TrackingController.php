<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\TrackedChannel;
use App\Services\SyncService;
use App\Services\TwitchApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackingController extends Controller
{
    public function index(Request $request): Response
    {
        $sort = $request->input('sort', 'name');
        $query = Category::withCount('streams')
            ->withSum('streams', 'viewer_count');

        $query = match ($sort) {
            'streams' => $query->orderByDesc('streams_count'),
            'viewers' => $query->orderByDesc('streams_sum_viewer_count'),
            default => $query->orderBy('name'),
        };

        $channels = TrackedChannel::orderBy('user_name')->get();

        return Inertia::render('Tracking', [
            'categories' => $query->get(),
            'channels' => $channels,
            'sort' => $sort,
            'tab' => $request->input('tab', 'categories'),
        ]);
    }

    public function searchCategories(Request $request, TwitchApiService $twitch): JsonResponse
    {
        $request->validate(['query' => 'required|string|min:1']);

        if (! $twitch->isConfigured()) {
            return response()->json(['error' => 'Twitch API not configured'], 422);
        }

        try {
            $results = $twitch->searchCategories($request->input('query'));
            $trackedIds = Category::pluck('twitch_id')->toArray();

            $results = array_map(function ($cat) use ($trackedIds) {
                $cat['is_tracked'] = in_array($cat['id'], $trackedIds);

                return $cat;
            }, $results);

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function storeCategory(Request $request, SyncService $sync, TwitchApiService $twitch): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'twitch_id' => 'required|string|unique:categories,twitch_id',
            'name' => 'required|string|max:255',
            'box_art_url' => 'nullable|string',
        ]);

        $category = Category::create([
            ...$validated,
            'is_active' => true,
            'notifications_enabled' => true,
            'use_global_filters' => true,
        ]);

        if ($twitch->isConfigured()) {
            try {
                $sync->syncCategory($category, silentAlerts: true);
            } catch (\Exception $e) {
                // Sync will catch up later
            }
        }

        return back()->with('success', "Tracking \"{$validated['name']}\" — {$category->streams()->count()} streams found.");
    }

    public function updateCategory(Request $request, Category $category): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => 'sometimes|boolean',
            'notifications_enabled' => 'sometimes|boolean',
            'use_global_filters' => 'sometimes|boolean',
            'min_viewers' => 'nullable|integer|min:0',
            'languages' => 'nullable|array',
            'keywords' => 'nullable|array',
        ]);

        $category->update($validated);

        return back()->with('success', "Category \"{$category->name}\" updated.");
    }

    public function destroyCategory(Category $category): \Illuminate\Http\RedirectResponse
    {
        $name = $category->name;
        $category->streams()->delete();
        $category->delete();

        return back()->with('success', "Category \"{$name}\" removed.");
    }

    public function syncCategory(Category $category, SyncService $sync, TwitchApiService $twitch): \Illuminate\Http\RedirectResponse
    {
        if (! $twitch->isConfigured()) {
            return back()->with('error', 'Twitch API not configured.');
        }

        $result = $sync->syncCategory($category);
        $sync->getAlertService()->sendNotifications($result['triggered_alerts']);

        return back()->with('success', "\"{$category->name}\" synced — {$result['new']} new, {$result['updated']} updated.");
    }

    public function storeChannel(Request $request, TwitchApiService $twitch, SyncService $sync): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['user_login' => 'required|string|max:255']);

        $login = strtolower($request->input('user_login'));

        if (TrackedChannel::where('user_login', $login)->exists()) {
            return back()->with('error', "Channel \"{$login}\" is already tracked.");
        }

        $userId = $login;
        $userName = $login;
        $avatar = null;

        if ($twitch->isConfigured()) {
            try {
                $users = $twitch->getUsers([$login]);
                if (! empty($users)) {
                    $userId = $users[0]['id'];
                    $userName = $users[0]['display_name'] ?? $login;
                    $login = strtolower($users[0]['login']);
                    $avatar = $users[0]['profile_image_url'] ?? null;
                } else {
                    return back()->with('error', "Channel \"{$login}\" not found on Twitch.");
                }
            } catch (\Exception $e) {
                // Continue with what we have
            }
        }

        TrackedChannel::create([
            'twitch_user_id' => $userId,
            'user_login' => $login,
            'user_name' => $userName,
            'profile_image_url' => $avatar,
        ]);

        // Sync immediately
        if ($twitch->isConfigured()) {
            try {
                $sync->syncTrackedChannels(silentAlerts: true);
            } catch (\Exception $e) {
                // Will catch up
            }
        }

        return back()->with('success', "Tracking channel \"{$userName}\".");
    }

    public function destroyChannel(TrackedChannel $channel): \Illuminate\Http\RedirectResponse
    {
        $name = $channel->user_name;
        $this->removeChannelStreams($channel->user_login);
        $channel->delete();

        return back()->with('success', "Channel \"{$name}\" removed.");
    }

    public function destroyChannelByLogin(string $login): \Illuminate\Http\RedirectResponse
    {
        $channel = TrackedChannel::where('user_login', strtolower($login))->first();
        if (! $channel) {
            return back()->with('error', 'Channel not found.');
        }

        $name = $channel->user_name;
        $this->removeChannelStreams($channel->user_login);
        $channel->delete();

        return back()->with('success', "Channel \"{$name}\" untracked.");
    }

    private function removeChannelStreams(string $login): void
    {
        // Remove streams that only exist because of channel tracking (no tracked category)
        \App\Models\Stream::where('user_login', $login)
            ->where(function ($q) {
                $q->whereNull('category_id')
                    ->orWhereDoesntHave('category', fn ($c) => $c->where('is_active', true));
            })
            ->delete();
    }
}
