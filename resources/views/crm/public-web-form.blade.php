<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $webForm->name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; background: #f4f5f8; margin: 0; padding: 40px 16px; display: flex; justify-content: center; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(20,24,40,0.08); max-width: 480px; width: 100%; padding: 32px; }
        h1 { font-size: 1.25rem; margin: 0 0 20px; color: #1b1f2a; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: #4b5162; }
        input, textarea { width: 100%; padding: 10px 12px; border: 1px solid #d8dbe4; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; box-sizing: border-box; }
        button { width: 100%; background: #4b3fe4; color: #fff; border: none; padding: 12px; border-radius: 8px; font-size: 0.95rem; font-weight: 600; cursor: pointer; }
        .success { text-align: center; color: #1b8a5a; }
        .error { color: #c62828; font-size: 0.8rem; margin: -12px 0 12px; }
    </style>
</head>
<body>
    <div class="card">
        @if (session('submitted'))
            <h1>Thank you!</h1>
            <p class="success">Your submission has been received.</p>
        @else
            <h1>{{ $webForm->name }}</h1>
            <form method="POST" action="{{ route('crm.public.web-forms.submit', $webForm->slug) }}">
                @csrf
                @foreach ($webForm->fields_json ?? [] as $field)
                    <label for="{{ $field['name'] }}">{{ $field['label'] ?: $field['name'] }}{{ ($field['required'] ?? false) ? ' *' : '' }}</label>
                    @if (($field['type'] ?? 'text') === 'textarea')
                        <textarea name="{{ $field['name'] }}" id="{{ $field['name'] }}" rows="3" @if($field['required'] ?? false) required @endif></textarea>
                    @else
                        <input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}" @if($field['required'] ?? false) required @endif>
                    @endif
                    @error($field['name'])<div class="error">{{ $message }}</div>@enderror
                @endforeach
                <button type="submit">Submit</button>
            </form>
        @endif
    </div>
</body>
</html>
