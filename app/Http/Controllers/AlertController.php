<?php

namespace App\Http\Controllers;

use App\Models\AlertRule;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(): Response
    {
        $alertRules = AlertRule::with('category')
            ->orderByDesc('created_at')
            ->get();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Alerts', [
            'alertRules' => $alertRules,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'streamer_login' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'match_mode' => 'required|in:first_time,always',
            'min_viewers' => 'nullable|integer|min:0',
            'language' => 'nullable|string|max:10',
            'keywords' => 'nullable|array',
            'notify_email' => 'boolean',
            'notify_discord' => 'boolean',
        ]);

        AlertRule::create($validated);

        return back()->with('success', 'Alert rule created.');
    }

    public function update(Request $request, AlertRule $alert): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'streamer_login' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'match_mode' => 'sometimes|in:first_time,always',
            'min_viewers' => 'nullable|integer|min:0',
            'language' => 'nullable|string|max:10',
            'keywords' => 'nullable|array',
            'notify_email' => 'sometimes|boolean',
            'notify_discord' => 'sometimes|boolean',
        ]);

        $alert->update($validated);

        return back()->with('success', 'Alert rule updated.');
    }

    public function destroy(AlertRule $alert): \Illuminate\Http\RedirectResponse
    {
        $alert->trackings()->delete();
        $alert->delete();

        return back()->with('success', 'Alert rule deleted.');
    }
}
