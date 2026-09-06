<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of registered users.
     */
    public function index(Request $request): View
    {
        $query = User::withCount('ratings')->withAvg('ratings', 'rating');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($cluster = $request->input('cluster')) {
            $query->where('cluster_id', $cluster);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        $clusterDistribution = User::where('role', 'user')
            ->whereNotNull('cluster_id')
            ->selectRaw('cluster_id, count(*) as count')
            ->groupBy('cluster_id')
            ->pluck('count', 'cluster_id')
            ->all();

        return view('admin.users.index', compact('users', 'clusterDistribution'));
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna '{$name}' berhasil dihapus.");
    }
}
