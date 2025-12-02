<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Vouchers</title>
    <!-- Use CDN for simplicity in print view or your vite directive -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
            }

            /* Force exact colors implementation */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 p-8 min-h-screen">

    <!-- Action Bar -->
    <div class="no-print fixed top-0 left-0 right-0 bg-white shadow-md p-4 flex justify-between items-center z-50">
        <h1 class="text-xl font-bold text-gray-800">
            Printing {{ count($vouchers) }} Vouchers for {{ $router->name }}
        </h1>
        <div class="flex gap-3">
            <button onclick="window.history.back()" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Back</button>
            <button onclick="window.print()"
                class="px-6 py-2 bg-blue-600 text-white rounded font-bold hover:bg-blue-700">Print / Save PDF</button>
        </div>
    </div>

    <!-- Voucher Grid -->
    <!-- Adjust grid cols based on template type if needed using logic -->
    <div
        class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 print:grid-cols-3 print:gap-4 max-w-7xl mx-auto">

        @foreach ($vouchers as $voucher)
            <!-- Dynamic Component Loading -->
            <!-- It looks for resources/views/components/vouchers/{template-name}.blade.php -->
            <x-dynamic-component :component="'vouchers.' . $router->voucher_template" :voucher="$voucher" :router="$router" />
        @endforeach

    </div>

</body>

</html>
