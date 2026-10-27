<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoChange;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeoChangelogController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->get('category');

        $query = SeoChange::query()
            ->orderByDesc('happened_at')
            ->orderByDesc('id');
        if ($category) {
            $query->where('category', $category);
        }

        $changes = $query->paginate(50)->withQueryString();

        return Inertia::render('Admin/SeoChangelog', [
            'changes' => $changes,
            'categories' => SeoChange::CATEGORIES,
            'category_filter' => $category,
            'summary' => [
                'total' => SeoChange::count(),
                'last_30d' => SeoChange::where('happened_at', '>=', now()->subDays(30))->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'happened_at' => 'required|date',
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:2000',
            'category' => 'nullable|string|in:' . implode(',', SeoChange::CATEGORIES),
            'actor' => 'nullable|string|max:100',
        ]);
        SeoChange::create($data);
        return back()->with('success', 'Change logged.');
    }

    public function destroy(SeoChange $seoChange)
    {
        $seoChange->delete();
        return back()->with('success', 'Entry removed.');
    }
}
