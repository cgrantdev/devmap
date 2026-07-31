<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BannerEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BannerEventController extends Controller
{
    /**
     * Batch impression ingest.
     * POST /api/banner-events/impressions
     * body: { events: [ { slot, banner_key?, banner_id?, brand_id?, meta? }, ... ] }
     */
    public function impressions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'events' => 'required|array|max:50',
            'events.*.slot' => 'required|string|max:64',
            'events.*.banner_key' => 'nullable|string|max:128',
            'events.*.banner_id' => 'nullable|integer',
            'events.*.brand_id' => 'nullable|integer',
            'events.*.meta' => 'nullable|array',
        ]);

        $ctx = $this->context($request);
        $rows = [];
        $now = Carbon::now();
        $bannerIdsToBump = [];

        foreach ($data['events'] as $ev) {
            $rows[] = array_merge($ctx, [
                'event_type' => 'impression',
                'slot' => $ev['slot'],
                'banner_key' => $ev['banner_key'] ?? null,
                'banner_id' => $ev['banner_id'] ?? null,
                'brand_id' => $ev['brand_id'] ?? null,
                'meta' => isset($ev['meta']) ? json_encode($ev['meta']) : null,
                'created_at' => $now,
            ]);
            if (!empty($ev['banner_id'])) $bannerIdsToBump[] = (int) $ev['banner_id'];
        }

        BannerEvent::insert($rows);

        if ($bannerIdsToBump) {
            foreach (array_count_values($bannerIdsToBump) as $id => $n) {
                Banner::where('id', $id)->increment('impressions', $n);
            }
        }

        return response()->json(['ok' => true, 'count' => count($rows)]);
    }

    /**
     * Single click event.
     * POST /api/banner-events/click
     * body: { slot, banner_key?, banner_id?, brand_id?, destination_url?, meta? }
     */
    public function click(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slot' => 'required|string|max:64',
            'banner_key' => 'nullable|string|max:128',
            'banner_id' => 'nullable|integer',
            'brand_id' => 'nullable|integer',
            'destination_url' => 'nullable|string|max:2048',
            'meta' => 'nullable|array',
        ]);

        $ctx = $this->context($request);
        BannerEvent::create(array_merge($ctx, [
            'event_type' => 'click',
            'slot' => $data['slot'],
            'banner_key' => $data['banner_key'] ?? null,
            'banner_id' => $data['banner_id'] ?? null,
            'brand_id' => $data['brand_id'] ?? null,
            'destination_url' => $data['destination_url'] ?? null,
            'meta' => $data['meta'] ?? null,
            'created_at' => Carbon::now(),
        ]));

        if (!empty($data['banner_id'])) {
            Banner::where('id', $data['banner_id'])->increment('clicks');
        }

        return response()->json(['ok' => true]);
    }

    private function context(Request $r): array
    {
        $ua = (string) $r->userAgent();
        return [
            'user_id' => $r->user()?->id,
            'ip_hash' => hash('sha256', $r->ip() . config('app.key')),
            'session_id' => substr(hash('sha256', $r->cookie('XSRF-TOKEN', '') . $r->ip() . $ua), 0, 32),
            'user_agent' => mb_substr($ua, 0, 512),
            'referrer' => mb_substr((string) $r->headers->get('referer'), 0, 1024) ?: null,
            'page_url' => mb_substr((string) $r->input('page_url', ''), 0, 1024) ?: null,
            'is_bot' => $this->looksLikeBot($ua),
        ];
    }

    private function looksLikeBot(string $ua): bool
    {
        if ($ua === '') return true;
        return (bool) preg_match('/bot|crawl|spider|slurp|bing|google|yandex|duckduck|baidu|facebookexternal|preview|monitor|curl|wget|python-requests/i', $ua);
    }
}
