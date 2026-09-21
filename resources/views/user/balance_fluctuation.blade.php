@extends('user.layouts.master')
@section('css-libs')
    @vite('resources/css/user/balance_fluctuation.css')
@endsection
@section('script-libs')
    <script>window.transactionStatistics={!! json_encode(['profitLoss'=>$statistics['profit_loss_series'],'status'=>['completed'=>$statistics['summary']['completed_count'],'pending'=>$statistics['summary']['pending_count'],'cancelled'=>$statistics['summary']['cancelled_count']]]) !!};</script>
    @vite('resources/js/user/balance_fluctuation.js')
@endsection
@section('content')
@php
    $summary=$statistics['summary'];
    $money=fn($value,$precision=2)=>format_money((float)$value,$precision).'$';
    $labels=['deposit'=>'Nạp tiền','withdraw'=>'Rút tiền','order'=>'Thanh toán đơn','profit'=>'Hoa hồng','penalty'=>'Tiền phạt','settlement'=>'Hoàn nhập đơn','refund'=>'Hoàn tiền rút'];
    $icons=['deposit'=>'fa-arrow-down','withdraw'=>'fa-arrow-up','order'=>'fa-bag-shopping','profit'=>'fa-coins','penalty'=>'fa-triangle-exclamation','settlement'=>'fa-rotate-left','refund'=>'fa-rotate-left'];
    $statusLabels=['completed'=>'Hoàn thành','processing'=>'Đang xử lý','cancelled'=>'Đã huỷ','recorded'=>'Đã ghi sổ'];
    $detailLabels=['normal'=>'Tiền nạp','bonus'=>'Tiền thưởng','virtual_withdraw'=>'Rút tiền','balance'=>'Số dư khả dụng','frozen_balance'=>'Số dư đóng băng'];
    $trend=fn($value)=>($value>0?'+':'').format_money($value,1).'%';
@endphp
<main class="finance-page">
    <header class="finance-header">
        <a href="{{ route('home') }}" class="finance-back" aria-label="Quay lại"><i class="fas fa-arrow-left"></i></a>
        <div><div class="finance-eyebrow">Tài chính cá nhân</div><h1>Thống kê giao dịch</h1><p>Theo dõi dòng tiền, đơn hàng và thu nhập của bạn.</p></div>
    </header>

    <section class="balance-hero">
        <div><div class="hero-label">Số dư khả dụng</div><div class="hero-balance">{{ $money($user->balance,5) }}</div><div class="hero-meta"><span><i class="fas fa-snowflake"></i> Đóng băng {{ $money($user->frozen_balance,5) }}</span><span><i class="fas fa-clock"></i> Hoa hồng chờ {{ $money($summary['pending_commission'],5) }}</span><span><i class="fas fa-receipt"></i> {{ number_format($summary['transaction_count']) }} bút toán</span></div></div>
        <div class="hero-change"><span>Biến động kỳ này</span><strong class="{{ $summary['net_movement']>=0?'positive':'negative' }}">{{ $summary['net_movement']>=0?'+':'-' }}{{ $money(abs($summary['net_movement']),5) }}</strong><small class="{{ $summary['net_growth']>=0?'positive':'negative' }}">{{ $trend($summary['net_growth']) }} so với kỳ trước</small></div>
    </section>

    <form class="finance-filter" method="GET" action="{{ route('balance_fluctuation') }}">
        <input type="hidden" name="range" value="{{ $selectedRange }}">
        <div class="preset-scroll">@foreach(['today'=>'Hôm nay','7d'=>'7 ngày','30d'=>'30 ngày','month'=>'Tháng này'] as $value=>$label)<a href="{{ route('balance_fluctuation',['range'=>$value,'type'=>$selectedType]) }}" class="preset {{ $selectedRange===$value?'active':'' }}">{{ $label }}</a>@endforeach</div>
        <div class="filter-row"><select name="type" aria-label="Loại giao dịch">@foreach(['all'=>'Tất cả giao dịch','wallet'=>'Nạp / rút','order'=>'Tiền đơn','profit'=>'Hoa hồng','penalty'=>'Tiền phạt','refund'=>'Hoàn nhập'] as $value=>$label)<option value="{{ $value }}" @selected($selectedType===$value)>{{ $label }}</option>@endforeach</select><button class="filter-submit"><i class="fas fa-filter"></i> Lọc</button></div>
        <div class="custom-range"><input type="date" name="start_date" value="{{ request('start_date',$statistics['range']['start']) }}"><input type="date" name="end_date" value="{{ request('end_date',$statistics['range']['end']) }}"><button type="submit" onclick="this.form.elements.range.value='custom'">Áp dụng ngày</button></div>
    </form>
    @error('start_date')<div class="finance-error">{{ $message }}</div>@enderror

    <section class="kpi-grid">
        <article class="kpi-card income"><span class="kpi-icon"><i class="fas fa-coins"></i></span><div class="kpi-label">Hoa hồng đã nhận</div><div class="kpi-value">{{ $money($summary['commission_amount'],5) }}</div><div class="kpi-note">{{ $trend($summary['commission_growth']) }} so với kỳ trước</div></article>
        <article class="kpi-card deposit"><span class="kpi-icon"><i class="fas fa-arrow-down"></i></span><div class="kpi-label">Tổng tiền nạp</div><div class="kpi-value">{{ $money($summary['deposit_amount']) }}</div><div class="kpi-note">{{ $trend($summary['deposit_growth']) }} so với kỳ trước</div></article>
        <article class="kpi-card withdraw"><span class="kpi-icon"><i class="fas fa-arrow-up"></i></span><div class="kpi-label">Đã rút</div><div class="kpi-value">{{ $money($summary['withdraw_amount']) }}</div><div class="kpi-note">Đang chờ {{ $money($summary['pending_withdraw_amount']) }}</div></article>
        <article class="kpi-card hvo"><span class="kpi-icon"><i class="fas fa-gem"></i></span><div class="kpi-label">Đơn hàng giá trị cao</div><div class="kpi-value">{{ number_format($summary['high_value_order_received_count']) }}</div><div class="kpi-note">Số lượng HVO đã nhận trong kỳ</div></article>
        <article class="kpi-card order"><span class="kpi-icon"><i class="fas fa-bag-shopping"></i></span><div class="kpi-label">Đơn đã hoàn thành</div><div class="kpi-value">{{ number_format($summary['completed_order_count']) }}</div><div class="kpi-note">Hoàn thành trong kỳ đang chọn</div></article>
        <article class="kpi-card penalty"><span class="kpi-icon"><i class="fas fa-triangle-exclamation"></i></span><div class="kpi-label">Tổng tiền phạt</div><div class="kpi-value">{{ $money($summary['penalty_amount']) }}</div><div class="kpi-note">Đã ghi nhận trong sổ giao dịch</div></article>
    </section>

    <section class="analytics-grid">
        <article class="finance-card"><div class="card-head"><div><h2>Xu hướng Lãi/Lỗ</h2><p>Hoa hồng thực nhận trừ tiền phạt thực tế, lũy kế từ đầu kỳ.</p></div></div><div id="profitLossChart" class="chart-box"><div class="chart-loading"><i class="fas fa-spinner fa-spin"></i> Đang tải biểu đồ</div></div></article>
        <article class="finance-card"><div class="card-head"><div><h2>Trạng thái ví</h2><p>Nạp/rút theo trạng thái xử lý.</p></div></div><div id="statusChart" class="chart-box compact"></div><div class="status-summary"><span><b>{{ $summary['completed_count'] }}</b> hoàn thành</span><span><b>{{ $summary['pending_count'] }}</b> đang chờ</span><span><b>{{ $summary['cancelled_count'] }}</b> đã huỷ</span></div></article>
    </section>

    <section class="finance-card breakdown-card"><div class="card-head"><div><h2>Cơ cấu giao dịch</h2><p>Mỗi nguồn được tách riêng; không cộng chéo settlement với commission/phạt.</p></div></div><div class="breakdown-grid">@forelse($statistics['breakdown'] as $item)<div class="breakdown-item"><span class="transaction-icon {{ $item->direction }}"><i class="fas {{ $icons[$item->type]??'fa-receipt' }}"></i></span><div><strong>{{ $labels[$item->type]??$item->type }}</strong><small>{{ number_format($item->transaction_count) }} giao dịch</small></div><b>{{ $money($item->total_amount,5) }}</b></div>@empty<div class="empty-state">Chưa có dữ liệu trong kỳ.</div>@endforelse</div></section>

    <section class="finance-card history-card">
        <div class="card-head"><div><h2>Lịch sử giao dịch</h2><p>{{ number_format($statistics['transactions']->total()) }} bản ghi theo filter.</p></div></div>
        <div class="transaction-list">@forelse($statistics['transactions'] as $item)
            @php $isCancelled=$item->status==='cancelled'; $isIn=$item->direction==='in'; $isInfo=$item->direction==='info'; @endphp
            <article class="transaction-row">
                <span class="transaction-icon {{ $isCancelled?'cancelled':($isIn?'in':($isInfo?'info':'out')) }}"><i class="fas {{ $icons[$item->type]??'fa-receipt' }}"></i></span>
                <div class="transaction-main"><div class="transaction-title">{{ $labels[$item->type]??$item->type }} <span class="status-pill {{ $item->status }}">{{ $statusLabels[$item->status]??$item->status }}</span></div><div class="transaction-meta">{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y · H:i') }}@if($item->note) · {{ $item->note }}@endif</div>@if($item->detail)<div class="transaction-detail">{{ $detailLabels[$item->detail]??$item->detail }}</div>@endif</div>
                <div class="transaction-money {{ $isCancelled?'muted':($isIn?'positive':($isInfo?'info':'negative')) }}">{{ $isCancelled?'':($isIn?'+':($isInfo?'+':'-')) }}{{ $money($item->value,5) }}</div>
            </article>
        @empty<div class="empty-state"><i class="fas fa-receipt"></i><strong>Chưa có giao dịch</strong><span>Thử chọn khoảng thời gian hoặc loại giao dịch khác.</span></div>@endforelse</div>
        @if($statistics['transactions']->hasPages())<div class="pagination-wrap">{{ $statistics['transactions']->onEachSide(1)->links('user.partials.finance-pagination') }}</div>@endif
    </section>
    <div class="calculation-note"><i class="fas fa-circle-info"></i><span>Hoàn nhập chỉ ghi nhận khi có settlement bất biến, bộ bút toán hoàn tất đối chiếu được, hoặc khoản rút đã bị huỷ và thực tế cộng lại ví. Biến động kỳ tính từng dòng tiền tại đúng thời điểm phát sinh nên không cộng trùng hoa hồng, phạt hay hoàn nhập.</span></div>
</main>
@endsection
