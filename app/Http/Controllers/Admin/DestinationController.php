<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDestinationRequest;
use App\Http\Requests\UpdateDestinationRequest;
use App\Models\Destination;
use App\Models\Province;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DestinationController extends Controller
{
    /**
     * Display a listing of destinations for admin.
     */
    public function index(Request $request): View
    {
        $query = Destination::forActiveDataset()->with('province');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%");
        }

        if ($provinceId = $request->input('province_id')) {
            $query->where('province_id', $provinceId);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $destinations = $query->latest()->paginate(15)->withQueryString();
        $provinces = Province::orderBy('name')->get();

        return view('admin.destinations.index', compact('destinations', 'provinces'));
    }

    /**
     * Show the form for creating a new destination.
     */
    public function create(): View
    {
        $provinces = Province::orderBy('name')->get();

        return view('admin.destinations.create', compact('provinces'));
    }

    /**
     * Store a newly created destination in storage.
     */
    public function store(StoreDestinationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        // Ensure unique slug
        $baseSlug = $data['slug'];
        $count = 1;
        while (Destination::where('slug', $data['slug'])->exists()) {
            $data['slug'] = "{$baseSlug}-{$count}";
            $count++;
        }

        Destination::create($data);

        return redirect()->route('admin.destinations.index')
            ->with('success', 'Destinasi wisata baru berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified destination.
     */
    public function edit(Destination $destination): View
    {
        $provinces = Province::orderBy('name')->get();

        return view('admin.destinations.edit', compact('destination', 'provinces'));
    }

    /**
     * Update the specified destination in storage.
     */
    public function update(UpdateDestinationRequest $request, Destination $destination): RedirectResponse
    {
        $data = $request->validated();
        if ($data['name'] !== $destination->name) {
            $data['slug'] = Str::slug($data['name']);
            $baseSlug = $data['slug'];
            $count = 1;
            while (Destination::where('slug', $data['slug'])->where('id', '!=', $destination->id)->exists()) {
                $data['slug'] = "{$baseSlug}-{$count}";
                $count++;
            }
        }

        $destination->update($data);

        return redirect()->route('admin.destinations.index')
            ->with('success', 'Data destinasi berhasil diperbarui!');
    }

    /**
     * Remove the specified destination from storage.
     */
    public function destroy(Destination $destination): RedirectResponse
    {
        $name = $destination->name;
        $destination->delete();

        return redirect()->route('admin.destinations.index')
            ->with('success', "Destinasi '{$name}' berhasil dihapus.");
    }
}
