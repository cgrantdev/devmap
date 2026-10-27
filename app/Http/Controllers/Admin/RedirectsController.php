<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class RedirectsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Redirect::query()->orderByDesc('created_at');
        if ($q !== '') {
            $query->where(fn ($w) => $w
                ->where('from_path', 'like', "%{$q}%")
                ->orWhere('to_path', 'like', "%{$q}%")
                ->orWhere('note', 'like', "%{$q}%"));
        }
        return Inertia::render('Admin/Redirects', [
            'redirects' => $query->paginate(50)->withQueryString(),
            'q' => $q,
            'summary' => [
                'total' => Redirect::count(),
                'total_hits' => (int) Redirect::sum('hits'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by_user_id'] = $request->user()?->id;
        Redirect::updateOrCreate(['from_path' => $data['from_path']], $data);
        Cache::forget('redirects:map');
        return back()->with('success', 'Redirect saved.');
    }

    public function update(Request $request, Redirect $redirect)
    {
        $redirect->update($this->validated($request));
        Cache::forget('redirects:map');
        return back()->with('success', 'Redirect updated.');
    }

    public function destroy(Redirect $redirect)
    {
        $redirect->delete();
        Cache::forget('redirects:map');
        return back()->with('success', 'Redirect removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'from_path' => 'required|string|max:1024',
            'to_path' => 'required|string|max:1024',
            'status_code' => 'nullable|integer|in:301,302,307,308',
            'note' => 'nullable|string|max:255',
        ]);
        $data['from_path'] = '/' . ltrim(trim($data['from_path']), '/');
        $data['to_path'] = trim($data['to_path']);
        // Allow absolute URLs (for cross-domain forwards); otherwise ensure leading slash
        if (!preg_match('~^https?://~i', $data['to_path'])) {
            $data['to_path'] = '/' . ltrim($data['to_path'], '/');
        }
        $data['status_code'] = $data['status_code'] ?? 301;
        return $data;
    }
}
