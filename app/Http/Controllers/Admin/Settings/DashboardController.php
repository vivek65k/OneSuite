<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use App\Support\AppLauncher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            [
                'label' => 'Users',
                'value' => User::count(),
                'icon' => 'ti ti-users',
                'color' => 'primary',
                'route' => 'admin.users.index',
            ],
            [
                'label' => 'Roles',
                'value' => Role::count(),
                'icon' => 'ti ti-shield-lock',
                'color' => 'info',
                'route' => 'admin.roles.index',
            ],
            [
                'label' => 'Branches',
                'value' => Branch::count(),
                'icon' => 'ti ti-building',
                'color' => 'success',
                'route' => Route::has('admin.branches.index') ? 'admin.branches.index' : null,
            ],
            [
                'label' => 'Departments',
                'value' => Department::count(),
                'icon' => 'ti ti-rectangle-vertical',
                'color' => 'warning',
                'route' => Route::has('admin.departments.index') ? 'admin.departments.index' : null,
            ],
        ];

        return view('admin.settings.dashboard', [
            'stats' => $stats,
            'apps' => AppLauncher::accessibleApps(Auth::user()),
            'recentUsers' => User::latest()->take(5)->get(),
        ]);
    }
}
