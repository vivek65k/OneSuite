<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModulePlaceholderController extends Controller
{
    public function __invoke(Request $request): View
    {
        $module = str($request->route()->getName())
            ->after('admin.')
            ->before('.dashboard')
            ->toString();

        $app = config("onesuite_apps.apps.{$module}");

        if (! $app) {
            throw new NotFoundHttpException();
        }

        return view('admin.modules.placeholder', [
            'module' => $module,
            'app' => $app,
        ]);
    }
}
