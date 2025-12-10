<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationsController extends Controller
{
    public function index(Request $request)
    {
        $query = Location::withCount(['products', 'vendorSettings']);
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortType = $request->get('sort_type', 'asc');
        
        // Validate sortBy
        $allowedSortColumns = ['id', 'name', 'created_at', 'products_count', 'vendor_settings_count'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'name';
        }
        
        // Validate sortType
        $sortType = strtolower($sortType) === 'desc' ? 'desc' : 'asc';
        
        // Apply sorting
        if ($sortBy === 'products_count' || $sortBy === 'vendor_settings_count') {
            $query->orderBy($sortBy, $sortType);
        } else {
            $query->orderBy($sortBy, $sortType);
        }
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $locations = $query->paginate($perPage)
            ->through(function ($location) {
                // Count products that belong to brands whose vendor settings are tied to this location.
                $productsViaVendor = \App\Models\Product::whereHas('brand.vendorSetting', function ($q) use ($location) {
                    $q->where('location_id', $location->id);
                })->count();

                $totalProducts = $location->products_count + $productsViaVendor;

                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'products_count' => $totalProducts,
                    'vendor_settings_count' => $location->vendor_settings_count,
                    'created_at' => $location->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Admin/Locations', [
            'locations' => $locations,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/LocationEdit', [
            'location' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:locations,name',
        ]);

        $location = Location::create($validated);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location created successfully.');
    }

    public function edit($id)
    {
        $location = Location::findOrFail($id);

        return Inertia::render('Admin/LocationEdit', [
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:locations,name,' . $location->id,
        ]);

        $location->update($validated);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location updated successfully.');
    }

    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        
        // Check if location is being used
        if ($location->products()->count() > 0 || $location->vendorSettings()->count() > 0) {
            return redirect()->route('admin.locations.index')
                ->with('error', 'Cannot delete location. It is being used by products or vendors.');
        }

        $location->delete();

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location deleted successfully.');
    }
}
