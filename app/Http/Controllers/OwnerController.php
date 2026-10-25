<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\LectureAdministered;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** System owner console: every institution and user across all accounts. */
class OwnerController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()?->is_system_owner, 403);
            return $next($request);
        });
    }

    public function dashboard()
    {
        $users = User::where('is_system_owner', false);

        $stats = [
            'institutions'          => Institution::count(),
            'institutions_inactive' => Institution::where('is_active', false)->count(),
            'users'                 => (clone $users)->count(),
            'users_inactive'        => (clone $users)->where('is_active', false)->count(),
            'lecturers'             => Lecturer::withoutGlobalScopes()->count(),
            'lectures'              => LectureAdministered::withoutGlobalScopes()->count(),
        ];

        $recentInstitutions = Institution::with('owner')->latest()->take(5)->get();
        $recentUsers        = User::where('is_system_owner', false)->with('institution')->latest()->take(5)->get();

        return view('owner.dashboard', compact('stats', 'recentInstitutions', 'recentUsers'));
    }

    public function institutions(Request $request)
    {
        $institutions = Institution::with('owner')
            ->withCount([
                'users',
                'lecturers' => fn ($q) => $q->withoutGlobalScopes(),
                'departments' => fn ($q) => $q->withoutGlobalScopes(),
            ])
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->q . '%'))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('owner.institutions', compact('institutions'));
    }

    public function users(Request $request)
    {
        $users = User::where('is_system_owner', false)
            ->with(['institution', 'roles'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%' . $request->q . '%')
                ->orWhere('email', 'like', '%' . $request->q . '%')))
            ->when($request->filled('institution_id'), fn ($q) => $q->where('institution_id', $request->institution_id))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        $institutionOptions = Institution::orderBy('name')->get(['id', 'name']);

        return view('owner.users', compact('users', 'institutionOptions'));
    }

    public function toggleInstitution($id)
    {
        $institution = Institution::findOrFail($id);
        $institution->forceFill(['is_active' => !$institution->is_active])->save();

        return back()->with('success', $institution->name . ' is now ' . ($institution->is_active ? 'active.' : 'deactivated. Its users can no longer log in.'));
    }

    public function toggleUser($id)
    {
        $user = User::where('is_system_owner', false)->findOrFail($id);
        $user->forceFill(['is_active' => !$user->is_active])->save();

        return back()->with('success', $user->name . ' is now ' . ($user->is_active ? 'active.' : 'deactivated and can no longer log in.'));
    }
}
