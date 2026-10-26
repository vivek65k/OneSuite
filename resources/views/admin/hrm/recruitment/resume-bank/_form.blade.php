<div class="card">
    <div class="card-header">
        <h4 class="card-title">Resume</h4>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $entry->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $entry->email) }}">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $entry->phone) }}">
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="skills">Skills</label>
                <input type="text" class="form-control" id="skills" name="skills" value="{{ old('skills', $entry->skills) }}" placeholder="Comma-separated">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="source">Source</label>
                <input type="text" class="form-control" id="source" name="source" value="{{ old('source', $entry->source) }}" placeholder="e.g. Job Fair, Referral">
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="form-label" for="resume">Resume</label>
            @if ($entry->resume_path)
                <div class="mb-1"><a href="{{ asset('storage/'.$entry->resume_path) }}" target="_blank">View current resume</a></div>
            @endif
            <input type="file" class="form-control @error('resume') is-invalid @enderror" id="resume" name="resume">
            @error('resume')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-group mb-3">
            <label class="form-label" for="notes">Notes</label>
            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $entry->notes) }}</textarea>
        </div>
    </div>
</div>

<div class="mb-4">
    <button type="submit" class="btn btn-de-primary text-light px-4">Save</button>
    <a href="{{ route('admin.resume-bank.index') }}" class="btn btn-de-danger text-light px-4">Cancel</a>
</div>
