<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProvinceRequest;
use App\Models\Province;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProvinceController extends Controller
{
    /**
     * Display a listing of provinces for admin.
     */
    public function index(Request $request): View
    {
        $query = Province::withCount(['destinations' => fn ($q) => $q->forActiveDataset()]);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $provinces = $query->orderBy('name')->paginate(15)->withQueryString();

        $topProvinces = Province::withCount(['destinations' => fn ($q) => $q->forActiveDataset()])
            ->withAvg(['destinations' => fn ($q) => $q->forActiveDataset()], 'google_rating')
            ->orderByDesc('destinations_count')
            ->take(10)
            ->get();

        return view('admin.provinces.index', compact('provinces', 'topProvinces'));
    }

    /**
     * Show the form for creating a new province.
     */
    public function create(): View
    {
        return view('admin.provinces.create');
    }

    /**
     * Store a newly created province in storage.
     */
    public function store(StoreProvinceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        Province::create($data);

        return redirect()->route('admin.provinces.index')
            ->with('success', 'Provinsi baru berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified province.
     */
    public function edit(Province $province): View
    {
        return view('admin.provinces.edit', compact('province'));
    }

    /**
     * Update the specified province in storage.
     */
    public function update(StoreProvinceRequest $request, Province $province): RedirectResponse
    {
        $data = $request->validated();
        if ($data['name'] !== $province->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        $province->update($data);

        return redirect()->route('admin.provinces.index')
            ->with('success', 'Data provinsi berhasil diperbarui!');
    }

    /**
     * Remove the specified province from storage.
     */
    public function destroy(Province $province): RedirectResponse
    {
        $name = $province->name;
        $province->delete();

        return redirect()->route('admin.provinces.index')
            ->with('success', "Provinsi '{$name}' berhasil dihapus.");
    }
}
