<?php

namespace App\Http\Requests\Admin\User;

use App\Models\Company;
use App\Support\AppLauncher;
use App\Support\BranchContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;
        $companyId = Company::current()->id;

        return [
            'avatar' => ['nullable', 'image', 'max:2048'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'is_super_admin' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')->where('company_id', $companyId)],

            'app_access' => ['array'],
            'app_access.*' => [Rule::in(array_keys(AppLauncher::companyApps()))],
            'default_app' => ['nullable', Rule::in(array_keys(AppLauncher::companyApps()))],

            // A branch-restricted admin may only assign a user to one of
            // their OWN branches, never an arbitrary company branch.
            'branches' => ['array'],
            'branches.*' => [
                'integer',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
                BranchContext::isRestricted() ? Rule::in(BranchContext::switchableBranches()->pluck('id')) : 'nullable',
            ],

            // The employee record this login is for - one of this company's,
            // and not one that already has a different login.
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('company_id', $companyId)
                ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))],

            'employee_code' => ['nullable', 'string', 'max:255', Rule::unique('users', 'employee_code')->where('company_id', $companyId)->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date'],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'joining_date' => ['nullable', 'date'],

            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.exists' => 'That employee already has a login, or is not in your company. Pick someone from the list.',
        ];
    }
}
