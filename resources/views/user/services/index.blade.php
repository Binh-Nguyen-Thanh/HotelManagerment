<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dịch vụ</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">
</head>

<body class="bg-gray-100">
    @php
        use App\Models\Information;
        $info = Information::first();
    @endphp
    @include('layouts.header', ['info' => $info])

    <div class="max-w-7xl mx-auto p-4 lg:p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Cột trái: danh sách dịch vụ -->
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-semibold mb-4">Chọn dịch vụ</h2>
                <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($services as $s)
                        @php
                            $priceNumber = (float) preg_replace('/[^\d.]/', '', (string)$s->price);
                        @endphp
                        <div class="bg-white rounded-xl shadow p-4">
                            <div class="flex items-baseline justify-between">
                                <h3 class="font-semibold text-gray-800">{{ $s->name }}</h3>
                                <div class="text-right">
                                    <div class="font-bold text-blue-600">{{ number_format($priceNumber, 0, ',', '.') }} VNĐ</div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">{{ $s->description }}</p>

                            <div class="mt-4">
                                <label class="text-sm text-gray-700 block mb-1">Số lượng</label>
                                <input
                                    type="number"
                                    min="0"
                                    value="0"
                                    class="qty-input w-full border rounded-md px-3 py-2"
                                    data-id="{{ $s->id }}"
                                    data-name="{{ $s->name }}"
                                    data-price="{{ $priceNumber }}"
                                >
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Cột phải: tóm tắt & thanh toán -->
            <div class="lg:col-span-1">
                <div class="sticky top-4 bg-white rounded-xl shadow p-4">
                    <h3 class="text-lg font-semibold mb-3">Tóm tắt</h3>

                    <div id="svLines" class="space-y-2 text-sm"></div>

                    <div class="border-t pt-3 mt-3 flex items-center justify-between">
                        <span class="text-gray-600">Tổng cộng</span>
                        <span id="grandTotal" class="text-xl font-bold">0 VNĐ</span>
                    </div>

                    <form id="svForm" method="POST" action="{{ route('user.services.processPayment') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="hidden" name="selected_json" id="selected_json">
                        <input type="hidden" name="payment_amount" id="payment_amount">

                        {{-- NGÀY SỬ DỤNG DỊCH VỤ (booking_date) --}}
                        <div>
                            <label for="booking_date" class="block text-sm font-medium text-gray-700 mb-1">
                                Ngày sử dụng dịch vụ
                            </label>
                            <input
                                type="date"
                                id="booking_date"
                                name="booking_date"
                                class="w-full border rounded-md px-3 py-2"
                                value="{{ old('booking_date', now()->toDateString()) }}"
                                min="{{ now()->toDateString() }}"
                                required
                            >
                            @error('booking_date')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <div class="font-semibold">Phương thức thanh toán</div>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="payment_method" value="vnpay" {{ old('payment_method','vnpay')==='vnpay'?'checked':'' }}>
                                <span>VNPay</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="payment_method" value="momo" {{ old('payment_method')==='momo'?'checked':'' }}>
                                <span>MoMo</span>
                            </label>
                            @error('payment_method')
                                <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit"
                                class="w-full py-3 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                            Thanh toán
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
            const $  = (s, r = document) => r.querySelector(s);
            const fmt = n => new Intl.NumberFormat('vi-VN').format(n) + ' VNĐ';

            const linesEl       = $('#svLines');
            const totalEl       = $('#grandTotal');
            const selectedInput = $('#selected_json');
            const amountInput   = $('#payment_amount');
            const form          = $('#svForm');
            const dateInput     = $('#booking_date');

            function calc() {
                const rows = [];
                let total = 0;

                $$('.qty-input').forEach(inp => {
                    const qty = parseInt(inp.value || '0', 10);
                    if (qty > 0) {
                        const id    = parseInt(inp.dataset.id, 10);
                        const name  = inp.dataset.name;
                        const price = parseFloat(inp.dataset.price || '0');
                        const line  = price * qty;
                        total += line;
                        rows.push({ id, name, price, qty });
                    }
                });

                // render
                linesEl.innerHTML = rows.length
                  ? rows.map(r => `
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-semibold">${r.name}</div>
                                <div class="text-gray-500">x${r.qty} × ${fmt(r.price)}</div>
                            </div>
                            <div class="font-semibold">${fmt(r.price * r.qty)}</div>
                        </div>
                    `).join('')
                  : `<div class="text-gray-500">Chưa chọn dịch vụ nào.</div>`;

                totalEl.textContent = fmt(total);

                // set hidden
                selectedInput.value = JSON.stringify(rows.map(({id, qty}) => ({ id, qty })));
                amountInput.value   = total;
            }

            document.addEventListener('input', (e) => {
                if (e.target.closest('.qty-input')) calc();
            });

            form.addEventListener('submit', (e) => {
                calc();

                // Must have at least 1 service
                if (!selectedInput.value || selectedInput.value === '[]') {
                    e.preventDefault();
                    alert('Vui lòng chọn ít nhất 1 dịch vụ.');
                    return;
                }
                // Must have a valid date
                if (!dateInput.value) {
                    e.preventDefault();
                    alert('Vui lòng chọn ngày sử dụng dịch vụ.');
                    return;
                }
            });

            // init
            calc();
        })();
    </script>
    <script src="{{ asset('js/homepage.js') }}"></script>
</body>

</html>