@extends('layout')

@section('content')
    <style>
        .hist-wrap {
            max-width: 1200px;
            margin: 25px auto;
            padding: 0 20px;
            font-family: 'Segoe UI', sans-serif;
        }

        .hist-title {
            font-size: 30px;
            font-weight: 600;
            margin: 10px 0 18px;
            color: #111;
        }

        .hist-toolbar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 18px;
            align-items: center;
            margin: 10px 0 18px;
        }

        .search-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-box {
            width: 350px;
            padding: 14px 16px;
            border-radius: 10px;
            border: 1px solid #ddd;
            background: #fff;
            outline: none;
            font-size: 14px;
        }

        .search-box:focus {
            border-color: #C0392B;
            box-shadow: 0 0 0 3px rgba(192, 57, 43, 0.12);
        }

        .filters {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .filter-label {
            font-size: 13px;
            color: #555;
            font-weight: 700;
            margin-right: 6px;
        }

        .filter-select {
            padding: 12px 12px;
            border-radius: 10px;
            border: 1px solid #ddd;
            background: #fff;
            font-size: 14px;
            cursor: pointer;
        }

        .filter-btn {
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #ddd;
            background: #fff;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .filter-btn:hover {
            border-color: #C0392B;
            color: #C0392B;
            background: #fff5f5;
        }

        input[type="date"]::-webkit-datetime-edit-fields-wrapper {
            display: none;
        }

        input[type="date"]:not([value=""])::-webkit-datetime-edit-fields-wrapper {
            display: flex;
        }

        .hint {
            margin: 6px 0 18px;
            color: #777;
            font-size: 13px;
        }

        .hint b {
            color: #C0392B;
        }

        .cards {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #eee;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
            padding: 18px 18px;
            transition: 0.2s;
        }

        .card:hover {
            border-color: #C0392B;
        }

        .card-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            align-items: center;
        }

        .meta {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .meta b {
            color: #111;
        }

        .actions {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .pill {
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 800;
            border: 1px solid transparent;
        }

        .pill.done {
            background: rgba(39, 174, 96, 0.12);
            color: #27AE60;
            border-color: rgba(39, 174, 96, 0.18);
        }

        .pill.pending {
            background: rgba(241, 196, 15, 0.16);
            color: #B8860B;
            border-color: rgba(241, 196, 15, 0.2);
        }

        .pill.cancelled {
            background: rgba(192, 57, 43, 0.10);
            color: #C0392B;
            border-color: rgba(192, 57, 43, 0.16);
        }

        .btn {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #ddd;
            background: #f7f7f7;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
        }

        .btn:hover {
            border-color: #C0392B;
            color: #C0392B;
            background: #fff5f5;
        }

        .btn.primary {
            background: #C0392B;
            border-color: #C0392B;
            color: #fff;
        }

        @media (max-width: 860px) {
            .hist-toolbar {
                grid-template-columns: 1fr;
            }

            .search-group {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                width: 100%;
            }

            .filters {
                justify-content: flex-start;
            }

            .card-row {
                grid-template-columns: 1fr;
            }

            .actions {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="hist-wrap">
        <div class="hist-title">Lịch Sử Giao Dịch Của Bạn</div>

        <form method="GET" action="{{ route('transaction_history') }}" id="searchForm">
            <div class="hist-toolbar">
                <div class="search-group">
                    <select name="search_type" id="search_type" class="filter-select" onchange="toggleInputs()">
                        <option value="bill_code" {{ $search_type === 'bill_code' ? 'selected' : '' }}>Tìm theo mã hóa đơn
                        </option>
                        <option value="payment_status" {{ $search_type === 'payment_status' ? 'selected' : '' }}>Tìm theo
                            trạng thái thanh toán</option>
                    </select>

                    <div id="q_input_group"
                        style="display: {{ $search_type === 'bill_code' ? 'flex' : 'none' }}; gap: 10px; align-items: center;">
                        <input class="search-box" type="text" name="q" value="{{ $q }}" placeholder="Nhập mã hóa đơn..."
                            autocomplete="off">
                    </div>

                    <div id="status_input_group"
                        style="display: {{ $search_type === 'payment_status' ? 'flex' : 'none' }}; gap: 10px; align-items: center;">
                        <select name="is_paid" class="filter-select">
                            <option value="" {{ ($is_paid === null || $is_paid === '') ? 'selected' : '' }}>Tất cả</option>
                            <option value="1" {{ (string) $is_paid === '1' ? 'selected' : '' }}>Đã thanh toán</option>
                            <option value="0" {{ (string) $is_paid === '0' ? 'selected' : '' }}>Chưa thanh toán</option>
                        </select>
                    </div>

                    <button type="submit" class="btn primary"><i class="fas fa-search"></i> Tìm kiếm</button>
                </div>

                <div class="filters">
                    <span class="filter-label">Bộ lọc theo ngày</span>
                    <input type="{{ $date ? 'date' : 'text' }}" name="date" id="date_filter" class="filter-select"
                        placeholder="Ngày/Tháng/Năm" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'"
                        value="{{ $date }}" onchange="this.form.submit()">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    @php
                        $toggleSort = $sort === 'asc' ? 'desc' : 'asc';
                        $qs = request()->query();
                        $qs['sort'] = $toggleSort;
                    @endphp
                    <a class="filter-btn" href="{{ route('transaction_history', $qs) }}"
                        style="text-decoration: none; color: #111;">
                        <i class="fas fa-sort-amount-{{ $sort === 'asc' ? 'up' : 'down' }}"></i>
                        {{ $sort === 'asc' ? 'Cũ nhất' : 'Mới nhất' }}
                    </a>
                </div>
            </div>
        </form>

        <div class="hint">
            Lưu ý: Hệ thống chỉ lưu lịch sử hóa đơn tối đa <b>3 tháng</b> gần nhất (từ {{ $minDate->format('d/m/Y') }} đến
            nay).
        </div>

        <div class="cards">
            @forelse($bills as $bill)
                @php
                    $status = $bill->status;
                    $pillClass = 'pending';
                    $pillText = 'Chờ thanh toán';

                    if ($status === 'cancelled') {
                        $pillClass = 'cancelled';
                        $pillText = 'Đã hủy';
                    } elseif ((bool) $bill->is_paid || $status === 'completed') {
                        $pillClass = 'done';
                        $pillText = 'Hoàn thành';
                    }

                    $tableText = '';
                    if ($bill->order_type === 'dat-ban') {
                        $nums = $bill->bookings->pluck('table_number')->sort()->values()->implode(', ');
                        $tableText = $nums ? ('Bàn: ' . $nums) : ($bill->table_number ? ('Bàn: ' . $bill->table_number) : '');
                    }
                @endphp

                <div class="card">
                    <div class="card-row">
                        <div class="meta">
                            <div><span style="color:#999;">Mã đơn:</span> <b>{{ $bill->bill_code }}</b></div>
                            <div><span style="color:#999;">Thời gian lập hóa đơn:</span>
                                <b>{{ optional($bill->created_at)->format('d/m/Y H:i') }}</b>
                            </div>
                            <div>
                                <span style="color:#999;">Loại:</span>
                                <b>{{ $bill->order_type === 'mang-ve' ? 'Mang về' : 'Tại bàn' }}</b>
                                @if($tableText) <span style="color:#bbb;">|</span> <b>{{ $tableText }}</b> @endif
                            </div>

                            @if($bill->order_type === 'dat-ban')
                                <div>
                                    <span style="color:#999;">Ngày giờ ăn:</span>
                                    <b>
                                        {{ $bill->booking_date ? \Carbon\Carbon::parse($bill->booking_date)->format('d/m/Y') : 'N/A' }},
                                        {{ $bill->arrival_time ?? '??' }} - {{ $bill->finish_time ?? '??' }}
                                    </b>
                                </div>
                            @endif

                            <div style="color: #C0392B; font-weight: bold; margin-top: 5px;">
                                Tổng tiền: {{ number_format($bill->total_amount, 0, ',', '.') }}đ
                            </div>
                        </div>

                        <div class="actions">
                            @if($status === 'pending')
                                <a href="{{ url('/gio-hang') }}" class="btn primary"
                                    style="text-decoration: none; background: #27AE60; border-color: #27AE60;">
                                    <i class="fas fa-credit-card"></i> Thanh toán ngay
                                </a>
                            @endif
                            <span class="pill {{ $pillClass }}">{{ $pillText }}</span>
                            <a href="{{ url('/export-pdf?code=' . $bill->bill_code) }}" target="_blank" class="btn primary"
                                style="text-decoration: none;">
                                <i class="fas fa-file-pdf"></i> Xuất PDF
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card" style="text-align:center; color:#999; padding: 50px;">
                    <i class="fas fa-search" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                    Không tìm thấy hóa đơn nào phù hợp.
                </div>
            @endforelse
        </div>
    </div>

    <script>
        function toggleInputs() {
            const type = document.getElementById('search_type').value;
            const qGroup = document.getElementById('q_input_group');
            const statusGroup = document.getElementById('status_input_group');

            if (type === 'bill_code') {
                qGroup.style.display = 'flex';
                statusGroup.style.display = 'none';
            } else {
                qGroup.style.display = 'none';
                statusGroup.style.display = 'flex';
            }
        }
    </script>
@endsection