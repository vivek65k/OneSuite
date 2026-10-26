<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label - {{ $barcode->item?->name }}</title>
    <style>
        body { font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 24px; background: #f4f5f8; }
        .label { background: #fff; border: 1px dashed #999; border-radius: 8px; width: 320px; padding: 16px; margin: 0 auto; text-align: center; }
        .label h2 { font-size: 1rem; margin: 0 0 4px; }
        .label .sku { font-size: 0.8rem; color: #666; margin-bottom: 12px; }
        .barcode-bars { display: flex; justify-content: center; align-items: flex-end; height: 60px; gap: 2px; margin-bottom: 8px; }
        .barcode-bars span { display: inline-block; width: 3px; background: #111; }
        .barcode-value { font-family: 'Courier New', monospace; font-size: 0.95rem; letter-spacing: 2px; }
        .price { margin-top: 10px; font-size: 1.1rem; font-weight: 700; }
        @media print { body { background: #fff; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="label">
        <h2>{{ $barcode->item?->name }}</h2>
        <div class="sku">{{ $barcode->item?->sku }}</div>
        <div class="barcode-bars" aria-hidden="true">
            @php
                // Deterministic bar pattern from the barcode value - a visual
                // placeholder label, not a scannable symbology.
                $seed = array_map('ord', str_split($barcode->barcode));
            @endphp
            @foreach (range(0, 39) as $i)
                @php $h = 20 + ($seed[$i % count($seed)] % 40); @endphp
                <span style="height: {{ $h }}px;"></span>
            @endforeach
        </div>
        <div class="barcode-value">{{ $barcode->barcode }}</div>
        @if ($barcode->item?->sale_price)
            <div class="price">{{ number_format($barcode->item->sale_price, 2) }}</div>
        @endif
    </div>
    <div class="no-print text-center mt-3">
        <button onclick="window.print()" style="padding:8px 16px;">Print</button>
    </div>
</body>
</html>
