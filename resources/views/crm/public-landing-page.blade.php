<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $page->name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; background: #fff; margin: 0; color: #1b1f2a; }
        .hero { background: linear-gradient(135deg, #4b3fe4, #6a5bf0); color: #fff; padding: 72px 24px; text-align: center; }
        .hero h1 { font-size: 2rem; margin: 0; max-width: 720px; margin: 0 auto; }
        .content { max-width: 720px; margin: 0 auto; padding: 40px 24px; line-height: 1.7; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="hero">
        <h1>{{ $page->headline ?: $page->name }}</h1>
    </div>
    <div class="content">{!! $page->content !!}</div>
</body>
</html>
