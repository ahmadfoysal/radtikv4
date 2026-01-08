@php
    use Illuminate\Support\Facades\View;

    $preferred = optional($router->voucherTemplate)->component ?? 'template-1';
    $fallbacks = ['template-1', 'template-2', 'template-3', 'template-4', 'template-5'];

    // Pick the first template that actually exists on disk
    $templateComponent =
        collect(array_merge([$preferred], $fallbacks))
            ->unique()
            ->first(fn($tpl) => View::exists('components.vouchers.' . $tpl)) ?? 'template-1';

    $voucherCount = $vouchers->count();
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print WiFi Vouchers - {{ $router->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
                margin: 0;
                padding: 0;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .voucher-grid {
                display: grid;
                grid-template-columns: repeat(8, 1fr);
                gap: 3px;
                width: 100%;
                max-width: 100%;
            }

            .voucher-item {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 12px;
        }
    </style>
</head>

<body class="bg-gray-50 p-6 min-h-screen">

    <!-- Action bar -->
    <div
        class="no-print fixed top-0 left-0 right-0 bg-white shadow-lg p-4 flex justify-between items-center z-50 border-b-2 border-gray-200">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">WiFi Vouchers - {{ $voucherCount }} Cards</h1>
            <p class="text-sm text-gray-600 mt-1">{{ $router->name }} •
                {{ $router->login_address ?? 'No login address' }} • 48 cards per page (A4 Landscape)</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.history.back()"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                ← Back
            </button>
            <button onclick="window.print()"
                class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition font-semibold">
                🖨️ Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Voucher grid -->
    <div class="mt-20 print:mt-0 voucher-grid max-w-7xl mx-auto">
        @foreach ($vouchers as $voucher)
            <div class="voucher-item">
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
