<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeneralSettingsController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()->can('page_GeneralSettings'), 403);

        return view('admin.settings.organization.general-settings.edit', [
            'company' => Company::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->can('page_GeneralSettings'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:1000'],
            'login_title' => ['nullable', 'string', 'max:255'],
            'login_subtitle' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $company = Company::current();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->update($data);

        return redirect()->route('admin.general-settings.edit')->with('status', 'Settings saved.');
    }
}
