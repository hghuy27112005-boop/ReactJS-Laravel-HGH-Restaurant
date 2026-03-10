@extends('layout')

@section('content')
<style>
    .hist-wrap { max-width: 1200px; margin: 25px auto; padding: 0 20px; font-family: 'Segoe UI', sans-serif; }
    .hist-title { font-size: 30px; font-weight: 800; margin: 10px 0 18px; color: #111; }

    .hist-toolbar { display: grid; grid-template-columns: 1fr auto; gap: 18px; align-items: center; margin: 10px 0 18px; }
    .search-box { width: 100%; padding: 14px 16px; border-radius: 10px; border: 1px solid #ddd; background: #fff; outline: none; font-size: 14px; }
    .search-box:focus { border-color: #C0392B; box-shadow: 0 0 0 3px rgba(192,57,43,0.12); }

    .filters { display: flex; gap: 10px; align-items: center; justify-content: flex-end; flex-wrap: wrap; }
    .filter-label { font-size: 13px; color: #555; font-weight: 700; margin-right: 6px; }
    .filter-select { padding: 12px 12px; border-radius: 10px; border: 1px solid #ddd; background: #fff; font-size: 14px; cursor: pointer; }
    .filter-btn { padding: 12px 14px; border-radius: 10px; border: 1px solid #ddd; background: #fff; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; }
    .filter-btn:hover { border-color: #C0392B; color: #C0392B; background: #fff5f5; }
    .hint { margin: 6px 0 18px; color: #777; font-size: 13px; }
    .hint b { color: #C0392B; }

    .cards { display: flex; flex-direction: column; gap: 16px; }
    .card { background: #fff; border-radius: 14px; border: 1px solid #eee; box-shadow: 0 6px 18px rgba(0,0,0,0.05); padding: 18px 18px; }
    .card-row { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center; }
    .meta { color: #666; font-size: 14px; line-height: 1.6; }
    .meta b { color: #111; }
    .actions { display: flex; gap: 10px; align-items: center; justify-content: flex-end; flex-wrap: wrap; }
    .pill { border-radius: 999px; padding: 10px 14px; font-size: 13px; font-weight: 800; border: 1px solid transparent; }
    .pill.done { background: rgba(39,174,96,0.12); color: #27AE60; border-color: rgba(39,174,96,0.18); }
    .pill.pending { background: rgba(241,196,15,0.16); color: #B8860B; border-color: rgba(241,196,15,0.2); }
    .pill.cancelled { background: rgba(192,57,43,0.10); color: #C0392B; border-color: rgba(192,57,43,0.16); }
    .btn { padding: 10px 14px; border-radius: 10px; border: 1px solid #ddd; background: #f7f7f7; cursor: pointer; font-weight: 700; font-size: 13px; }
    .btn:hover { border-color: #C0392B; color: #C0392B; background: #fff5f5; }
    .btn.primary { background: #C0392B; border-color: #C0392B; color: #fff; }
    .btn.primary:hover { filter: brightness(0.95); }

    .details { display: none; margin-top: 14px; padding-top: 14px; border-top: 1px dashed #eee; }
    .details.active { display: block; }
    .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .detail-box { background: #fafafa; border: 1px solid #eee; border-radius: 12px; padding: 12px 14px; font-size: 13px; color: #444; line-height: 1.6; }
    .detail-box h4 { margin: 0 0 6px; font-size: 13px; color: #111; }
    .items { margin: 0; padding-left: 18px; }
    .items li { margin: 3px 0; }

    @media (max-width: 860px) {
        .hist-toolbar { grid-template-columns: 1fr; }
        .filters { justify-content: flex-start; }
        .card-row { grid-template-columns: 1fr; }
        .actions { justify-content: flex-start; }
        .details-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="hist-wrap">
    <div class="hist-title">Lịch Sử Giao Dịch Của Bạn</div>

    <form method="GET" action="{{ route('transaction_history') }}">
        <div class="hist-toolbar">
            <input
                class="search-box"
                type="text"
                name="q"
                value="{{ $q }}"
                placeholder="Tìm kiếm theo Mã đặt bàn hoặc Ngày đặt"
                autocomplete="off"
            >

            <div class="filters">
                <span class="filter-label">Bộ lọc</span>

                @php
                    $currentMonth = old('month', $month);
                    $currentYear = old('year', $year);
                @endphp

                <select class="filter-select" name="month">
                    <option value="">Tháng</option>
                    @for($m=1; $m<=12; $m++)
                        <option value="{{ $m }}" {{ (string)$currentMonth === (string)$m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                    @endfor
                </select>

                <select class="filter-select" name="year">
                    <option value="">Năm</option>
                    @php
                        $years = collect($filterOptions)->pluck('year')->unique()->values()->all();
                    @endphp
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ (string)$currentYear === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>

                <input type="hidden" name="sort" value="{{ $sort }}">

                <button class="filter-btn" type="submit" title="Áp dụng bộ lọc">
                    <i class="fas fa-filter"></i> Lọc
                </button>

                @php
                    $toggleSort = $sort === 'asc' ? 'desc' : 'asc';
                    $qs = request()->query();
                    $qs['sort'] = $toggleSort;
                @endphp
                <a class="filter-btn" href="{{ route('transaction_history', $qs) }}" title="Sắp xếp theo STT hóa đơn trong ngày">
                    <i class="fas fa-arrows-alt-v"></i>
                    {{ $sort === 'asc' ? 'Tăng dần' : 'Giảm dần' }}
                </a>
            </div>
        </div>
    </form>

    <div class="hint">
        Lưu ý: Hệ thống chỉ lưu lịch sử hóa đơn tối đa <b>3 tháng</b> gần nhất (từ {{ $minDate->format('d/m/Y') }} đến nay).
    </div>

    <div class="cards">
        @forelse($bills as $bill)
            @php
                $status = $bill->status;
                $pillClass = 'pending';
                $pillText = 'Đang xử lý';

                if ($status === 'cancelled') { $pillClass = 'cancelled'; $pillText = 'Đã hủy'; }
                elseif ((bool)$bill->is_paid || $status === 'completed') { $pillClass = 'done'; $pillText = 'Hoàn thành'; }

                $tableText = '';
                if ($bill->order_type === 'dat-ban') {
                    $nums = $bill->bookings->pluck('table_number')->sort()->values()->implode(', ');
                    $tableText = $nums ? ('Bàn: ' . $nums) : ($bill->table_number ? ('Bàn: ' . $bill->table_number) : '');
                }

                $guestCount = $bill->bookings->count() > 0
                    ? $bill->bookings->count() * 5
                    : null;
            @endphp

            <div class="card">
                <div class="card-row">
                    <div class="meta">
                        <div><span style="color:#999;">Mã đơn:</span> <b>{{ $bill->bill_code }}</b></div>
                        <div><span style="color:#999;">Thời gian:</span> <b>{{ optional($bill->created_at)->format('d/m/Y H:i') }}</b></div>
                        <div>
                            <span style="color:#999;">Loại:</span>
                            <b>{{ $bill->order_type === 'mang-ve' ? 'Mang về' : 'Tại bàn' }}</b>
                            @if($tableText) <span style="color:#bbb;">|</span> <b>{{ $tableText }}</b> @endif
                        </div>
                    </div>

                    <div class="actions">
                        <span class="pill {{ $pillClass }}">{{ $pillText }}</span>
                        <button class="btn" type="button" onclick="toggleDetails('{{ $bill->id }}')">Xem chi tiết</button>
                        <button class="btn primary" type="button" onclick="alert('Chức năng đánh giá món ăn: bạn muốn lưu ở đâu (DB) và đánh giá theo món hay theo hóa đơn? Mình sẽ làm tiếp đúng ý bạn.')">
                            <i class="fas fa-star"></i> Đánh giá món ăn
                        </button>
                    </div>
                </div>

                <div class="details" id="details-{{ $bill->id }}">
                    <div class="details-grid">
                        <div class="detail-box">
                            <h4>Tóm tắt</h4>
                            <div><b>Tổng tiền:</b> {{ number_format($bill->total_amount, 0, ',', '.') }}đ</div>
                            <div><b>Thanh toán:</b> {{ $bill->is_paid ? ('Đã thanh toán' . ($bill->payment_method ? ' (' . $bill->payment_method . ')' : '')) : 'Chưa thanh toán' }}</div>
                            @if($bill->order_type === 'mang-ve')
                                <div><b>Địa chỉ:</b> {{ $bill->address ?: 'N/A' }}</div>
                            @endif
                        </div>

                        <div class="detail-box">
                            <h4>Món đã đặt</h4>
                            <ul class="items">
                                @foreach($bill->details as $d)
                                    <li>
                                        <b>{{ optional($d->dish)->dish_name ?? ('Món #' . $d->dish_id) }}</b>
                                        — {{ $d->quantity }} x {{ number_format($d->price_at_time, 0, ',', '.') }}đ
                                        @if($d->note && $d->note !== 'Không có')
                                            <span style="color:#888;">(Ghi chú: {{ $d->note }})</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card" style="text-align:center; color:#999; padding: 26px;">
                Không có hóa đơn nào trong phạm vi 3 tháng gần nhất.
            </div>
        @endforelse
    </div>
</div>

<script>
    function toggleDetails(id) {
        const el = document.getElementById('details-' + id);
        if (!el) return;
        el.classList.toggle('active');
    }
</script>
@endsection