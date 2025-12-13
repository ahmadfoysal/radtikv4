@php
    use Illuminate\Support\Facades\View;

    $preferred = optional($router->voucherTemplate)->component ?? 'template-1';
    $fallbacks = ['template-1', 'template-2', 'template-3', 'template-4', 'template-5'];

    // Pick the first template that actually exists on disk
    $templateComponent = collect(array_merge([$preferred], $fallbacks))
        ->unique()
        ->first(fn($tpl) => View::exists('components.vouchers.' . $tpl))
        ?? 'template-1';

    $voucherCount = $vouchers->count();
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Vouchers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body class="bg-base-100 p-6 min-h-screen">

    <!-- Action bar -->
    <div class="no-print fixed top-0 left-0 right-0 bg-base-100 shadow-md p-4 flex justify-between items-center z-50 border-b border-base-300">
        <div>
            <h1 class="text-xl font-bold text-base-content">Printing {{ $voucherCount }} Vouchers</h1>
            <p class="text-sm text-base-content/70">{{ $router->name }}</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.history.back()" class="btn btn-ghost">Back</button>
            <button onclick="window.print()" class="btn btn-primary">Print / Save PDF</button>
        </div>
    </div>

    <!-- Voucher grid -->
    <div class="mt-16 print:mt-0 flex flex-wrap gap-4 print:gap-4 max-w-7xl mx-auto">
        @foreach ($vouchers as $voucher)
            <div class="break-inside-avoid">
                <x-dynamic-component :component="'vouchers.' . $templateComponent" :voucher="$voucher" :router="$router" />
            </div>
        @endforeach
    </div>

    <script>
        window.onload = function() {
            setTimeout(() => window.print(), 200);
        };
    </script>
</body>

</html>
