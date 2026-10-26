<div class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Regime <span class="text-danger">*</span></label>
                <select class="form-select" name="regime" required>
                    @foreach (\App\Models\TaxSlab::REGIMES as $value => $label)
                        <option value="{{ $value }}" @selected(old('regime', $slab->regime) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Financial Year <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('financial_year') is-invalid @enderror" name="financial_year" value="{{ old('financial_year', $slab->financial_year ?? \App\Models\TaxDeclaration::currentFinancialYear()) }}" placeholder="2026-2027" required>
                @error('financial_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Min Income <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" class="form-control" name="min_income" value="{{ old('min_income', $slab->min_income) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Max Income</label>
                <input type="number" step="0.01" min="0" class="form-control @error('max_income') is-invalid @enderror" name="max_income" value="{{ old('max_income', $slab->max_income) }}" placeholder="No upper bound">
                @error('max_income')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Tax Rate % <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="100" class="form-control" name="tax_rate_percent" value="{{ old('tax_rate_percent', $slab->tax_rate_percent) }}" required>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.tax-slabs.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
