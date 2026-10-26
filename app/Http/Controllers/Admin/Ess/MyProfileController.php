<?php

namespace App\Http\Controllers\Admin\Ess;

use App\Http\Concerns\ResolvesCurrentEmployee;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Support\ReportingChain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MyProfileController extends Controller
{
    use ResolvesCurrentEmployee;

    /** What an employee may change about themselves. Everything else (name, job, pay, dates) is HR's. */
    public const OWN_FIELDS = ['phone', 'address', 'city', 'state', 'country', 'postal_code'];

    public const PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const PHOTO_MAX_KILOBYTES = 2048;

    /** x-avatar and the profile hero read photos from the public disk. */
    protected const PHOTO_DISK = 'public';

    public function index(): View
    {
        $employee = $this->currentEmployee();
        $employee->load(['department:id,name', 'branch:id,name', 'familyMembers', 'educations', 'experiences', 'skills']);
        $companyId = (int) $employee->company_id;

        // The reporting line is the person's own chain, so it doesn't depend on what their role may browse.
        $people = fn () => Employee::withoutGlobalScopes()->where('company_id', $companyId);
        $aboveIds = ReportingChain::above((int) $employee->id, $companyId);
        $above = $people()->whereIn('id', $aboveIds)->get(['id', 'name', 'designation', 'employee_code', 'avatar', 'email', 'phone'])
            ->sortBy(fn (Employee $e) => array_search($e->id, $aboveIds, true))->reverse()->values();
        $reports = $people()->where('manager_id', $employee->id)->whereNotIn('status', ['inactive', 'terminated'])->orderBy('name')
            ->get(['id', 'name', 'designation', 'employee_code', 'avatar']);

        $balances = LeaveBalance::withoutGlobalScopes()->where('employee_id', $employee->id)->where('year', now()->year)
            ->with('leaveType:id,name')->get()->sortBy(fn (LeaveBalance $b) => $b->leaveType?->name)->values();

        // What makes a profile "complete": the things HR and colleagues actually look for.
        $checks = [
            'Photo' => filled($employee->avatar),
            'Phone' => filled($employee->phone),
            'Address' => filled($employee->address) && filled($employee->city),
            'Date of birth' => filled($employee->date_of_birth),
            'Family / emergency contact' => $employee->familyMembers->isNotEmpty(),
            'Education' => $employee->educations->isNotEmpty(),
            'Skills' => $employee->skills->isNotEmpty(),
        ];

        return view('admin.ess.profile', [
            'employee' => $employee,
            'manager' => $above->last(),
            'reportingLine' => $above,
            'directReports' => $reports,
            'teamSize' => count(ReportingChain::below((int) $employee->id, $companyId)),
            'balances' => $balances,
            'leaveLeft' => (float) $balances->sum(fn (LeaveBalance $b) => max(0, (float) $b->remaining)),
            'checks' => $checks,
            'completeness' => (int) round(collect($checks)->filter()->count() / count($checks) * 100),
        ]);
    }

    /** The employee corrects their own contact details. */
    public function update(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9 ()\-]{5,28}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9][A-Za-z0-9 \-]{2,19}$/'],
        ], [
            'phone.regex' => 'Enter a phone number using digits only (spaces, dashes and a leading + are fine).',
            'postal_code.regex' => 'Enter a valid postal code.',
        ]);

        // Only the listed fields, whatever else was posted (name, manager_id, status...).
        $values = collect(self::OWN_FIELDS)->mapWithKeys(fn ($field) => [$field => filled($data[$field] ?? null) ? trim(strip_tags($data[$field])) : null])->all();
        $changed = array_keys(array_filter($values, fn ($value, $field) => (string) $value !== (string) $employee->{$field}, ARRAY_FILTER_USE_BOTH));

        if ($changed === []) {
            return back()->with('status', 'Nothing was changed.');
        }
        DB::table('employees')->where('id', $employee->id)->where('company_id', $employee->company_id)->update($values + ['updated_at' => now()]);

        return back()->with('status', 'Your '.Str::of(collect($changed)->map(fn ($f) => str_replace('_', ' ', $f))->join(', ', ' and '))->toString().' '.(count($changed) === 1 ? 'was' : 'were').' updated.');
    }

    public function photo(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        $request->validate([
            'photo' => ['required', 'file', 'extensions:'.implode(',', self::PHOTO_EXTENSIONS), 'mimes:'.implode(',', self::PHOTO_EXTENSIONS), 'max:'.self::PHOTO_MAX_KILOBYTES, 'dimensions:min_width=100,min_height=100,max_width=6000,max_height=6000'],
        ], [
            'photo.required' => 'Choose a photo to upload.',
            'photo.extensions' => 'The photo must be a JPG, PNG or WebP image.',
            'photo.mimes' => 'The photo must be a JPG, PNG or WebP image.',
            'photo.max' => 'The photo can\'t be larger than 2 MB.',
            'photo.dimensions' => 'The photo must be a real image at least 100 pixels wide and tall.',
            'photo.uploaded' => 'The photo could not be uploaded. It may be larger than the server allows.',
        ]);

        $file = $request->file('photo');
        // A name the uploader doesn't choose, with the extension the content actually is.
        $path = $file->storeAs('avatars/'.$employee->company_id, Str::random(40).'.'.$file->extension(), self::PHOTO_DISK);
        $old = $employee->avatar;
        DB::table('employees')->where('id', $employee->id)->where('company_id', $employee->company_id)->update(['avatar' => $path, 'updated_at' => now()]);
        $this->removeFile($old);

        return back()->with('status', 'Your photo was updated.');
    }

    public function removePhoto(): RedirectResponse
    {
        $employee = $this->currentEmployee();
        if (! $employee->avatar) {
            return back()->with('status', 'You have no photo to remove.');
        }
        DB::table('employees')->where('id', $employee->id)->where('company_id', $employee->company_id)->update(['avatar' => null, 'updated_at' => now()]);
        $this->removeFile($employee->avatar);

        return back()->with('status', 'Your photo was removed.');
    }

    /** Only files this page stored are deleted; a path set some other way is left alone. */
    protected function removeFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'avatars/') && ! str_contains($path, '..')) {
            Storage::disk(self::PHOTO_DISK)->delete($path);
        }
    }
}
