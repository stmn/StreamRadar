<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\SyncService;
use App\Services\TwitchApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
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

        return Inertia::render('Categories', [
            'categories' => $query->get(),
            'sort' => $sort,
        ]);
    }

    public function search(Request $request, TwitchApiService $twitch): JsonResponse
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

    public function preview(string $twitchId, TwitchApiService $twitch): JsonResponse
    {
        if (! $twitch->isConfigured()) {
            return response()->json(['error' => 'Twitch API not configured'], 422);
        }

        try {
            $categories = $twitch->getCategories([$twitchId]);
            $category = $categories[0] ?? null;

            if (! $category) {
                return response()->json(['error' => 'Category not found'], 404);
            }

            $streams = $twitch->getStreamsByCategory($twitchId);

            return response()->json([
                'category' => $category,
                'streams' => array_slice($streams['data'] ?? [], 0, 10),
                'total_streams' => count($streams['data'] ?? []),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request, SyncService $sync, TwitchApiService $twitch): \Illuminate\Http\RedirectResponse
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

        // Sync streams for this category immediately
        if ($twitch->isConfigured()) {
            try {
                $sync->syncCategory($category);
            } catch (\Exception $e) {
                // Don't fail the follow — sync will catch up later
            }
        }

        return back()->with('success', "Following \"{$validated['name']}\" — {$category->streams()->count()} streams found.");
    }

    public function update(Request $request, Category $category): \Illuminate\Http\RedirectResponse
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

    public function sync(Category $category, SyncService $sync, TwitchApiService $twitch): \Illuminate\Http\RedirectResponse
    {
        if (! $twitch->isConfigured()) {
            return back()->with('error', 'Twitch API not configured.');
        }

        $result = $sync->syncCategory($category);

        return back()->with('success', "\"{$category->name}\" synced — {$result['new']} new, {$result['updated']} updated.");
    }

    public function destroy(Category $category): \Illuminate\Http\RedirectResponse
    {
        $name = $category->name;
        $category->streams()->delete();
        $category->delete();

        return back()->with('success', "Category \"{$name}\" removed.");
    }
}
