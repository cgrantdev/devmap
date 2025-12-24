<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;

class DealsController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('deals')) {
            $deals = [];
        } else {
            $deals = Deal::with('vendor')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($deal) {
                    return [
                        'id' => $deal->id,
                        'code' => $deal->code,
                        'description' => $deal->description,
                        'discount' => $deal->discount,
                        'expiry_date' => $deal->expiry_date?->format('Y-m-d'),
                        'active' => $deal->active,
                        'vendor_id' => $deal->vendor_id,
                        'vendor_name' => $deal->vendor?->name,
                    ];
                });
        }

        $vendors = User::where('role', 'vendor')
            ->get()
            ->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                ];
            });

        return Inertia::render('Admin/Deals', [
            'deals' => $deals,
            'vendors' => $vendors
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('deals')) {
            return redirect()->back()->with('error', 'Deals table does not exist. Please run migrations.');
        }

        $validated = $request->validate([
            'code' => 'required|string|unique:deals,code',
            'description' => 'nullable|string',
            'discount' => 'required|integer|min:1|max:100',
            'expiry_date' => 'nullable|date',
            'active' => 'boolean',
            'vendor_id' => 'nullable|exists:users,id',
            'usage_limit' => 'nullable|integer|min:1',
            'minimum_purchase' => 'nullable|numeric|min:0',
        ]);

        Deal::create($validated);

        return redirect()->back()->with('success', 'Deal created successfully.');
    }

    public function update(Request $request, $id)
    {
        if (!Schema::hasTable('deals')) {
            return redirect()->back()->with('error', 'Deals table does not exist. Please run migrations.');
        }

        $deal = Deal::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|unique:deals,code,' . $id,
            'description' => 'nullable|string',
            'discount' => 'required|integer|min:1|max:100',
            'expiry_date' => 'nullable|date',
            'active' => 'boolean',
            'vendor_id' => 'nullable|exists:users,id',
            'usage_limit' => 'nullable|integer|min:1',
            'minimum_purchase' => 'nullable|numeric|min:0',
        ]);

        $deal->update($validated);

        return redirect()->back()->with('success', 'Deal updated successfully.');
    }

    public function destroy($id)
    {
        if (!Schema::hasTable('deals')) {
            return redirect()->back()->with('error', 'Deals table does not exist. Please run migrations.');
        }

        $deal = Deal::findOrFail($id);
        $deal->delete();

        return redirect()->back()->with('success', 'Deal deleted successfully.');
    }
}

