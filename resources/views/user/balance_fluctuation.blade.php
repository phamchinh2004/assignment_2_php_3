@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/balance_fluctuation.css')
@endsection
@section('script-libs')
<!-- ApexCharts CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@vite('resources/js/user/balance_fluctuation.js')
@endsection
@section('content')
<div class="bg-white d-flex flex-row position-relative border-bottom">
    <a class="text-dark fw-bold text-decoration-none p-2 hover btn-back" href="{{ route('home') }}">
        <i class="fa fa-arrow-left fa-sm pe-1"></i>{{__('balance_fluctuation.QuayLai')}}
    </a>
    <h3 class="position-absolute title">{{__('balance_fluctuation.ThongKeGiaoDich')}}</h3>
</div>

<!-- Tab Navigation -->
<div class="bg-white border-bottom sticky-tabs">
    <div class="d-flex flex-row align-items-center p-2 justify-content-center">
        <a href="{{ route('balance_fluctuation') }}?tab=overview" 
           class="btn_tab cspt pt-2 pb-2 text-center {{ $tab === 'overview' ? 'active' : '' }}" 
           id="btn_overview">
            <i class="fa-solid fa-chart-line"></i>
            <span>{{__('balance_fluctuation.TongQuan')}}</span>
        </a>
        <a href="{{ route('balance_fluctuation') }}?tab=distribution" 
           class="btn_tab cspt pt-2 pb-2 text-center {{ $tab === 'distribution' ? 'active' : '' }}" 
           id="btn_distribution">
            <i class="fa-solid fa-boxes"></i>
            <span>{{__('balance_fluctuation.PhanPhoi')}}</span>
        </a>
        <a href="{{ route('balance_fluctuation') }}?tab=deposit" 
           class="btn_tab cspt pt-2 pb-2 text-center {{ $tab === 'deposit' ? 'active' : '' }}" 
           id="btn_deposit">
            <i class="fa-solid fa-arrow-down"></i>
            <span>{{__('balance_fluctuation.NapTien')}}</span>
        </a>
        <a href="{{ route('balance_fluctuation') }}?tab=withdraw" 
           class="btn_tab cspt pt-2 pb-2 text-center {{ $tab === 'withdraw' ? 'active' : '' }}" 
           id="btn_withdraw">
            <i class="fa-solid fa-arrow-up"></i>
            <span>{{__('balance_fluctuation.RutTien')}}</span>
        </a>
    </div>
</div>

<!-- Overview Tab - Biểu đồ và thống kê -->
@if($tab === 'overview')
<div class="statistics-container">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <!-- Số dư hiện tại -->
        <div class="stat-card balance-card">
            <div class="stat-icon">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">{{__('balance_fluctuation.SoDuHienTai')}}</div>
                <div class="stat-value text-white">${{ number_format($stats['current_balance'], 6) }}</div>
            </div>
        </div>
        
        <!-- Tổng lợi nhuận -->
        <div class="stat-card profit-card">
            <div class="stat-icon">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">{{__('balance_fluctuation.TongLoiNhuan')}}</div>
                <div class="stat-value text-success">${{ number_format($stats['total_profit'], 6) }}</div>
            </div>
        </div>
        
        <!-- Hoa hồng tạm tính -->
        <div class="stat-card deposit-card">
            <div class="stat-icon">
                <i class="fa-solid fa-money-bill-trend-up"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Hoa hồng tạm tính</div>
                <div class="stat-value text-success">${{ number_format($stats['pending_commission'], 6) }}</div>
            </div>
        </div>
        
        <!-- Số đơn hàng đã hoàn thành -->
        <div class="stat-card withdraw-card">
            <div class="stat-icon">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Số đơn hàng đã hoàn thành</div>
                <div class="stat-value text-white">{{ number_format($stats['completed_orders_count']) }}</div>
            </div>
        </div>
    </div>
    
    <!-- Chart Container -->
    <div class="chart-container">
        <div class="chart-header">
            <h4 class="chart-title">
                <i class="fa-solid fa-chart-line me-2"></i>
                {{__('balance_fluctuation.BieuDoBienDongDongTien')}}
            </h4>
            <div class="time-filter">
                <button class="filter-btn active" data-period="all">
                    {{__('balance_fluctuation.TatCa')}}
                </button>
                <button class="filter-btn" data-period="30">
                    30 {{__('balance_fluctuation.Ngay')}}
                </button>
                <button class="filter-btn" data-period="7">
                    7 {{__('balance_fluctuation.Ngay')}}
                </button>
            </div>
        </div>
        <div class="chart-body">
            <div id="balanceChart"></div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="recent-transactions">
        <h5 class="section-title">
            <i class="fa-solid fa-clock-rotate-left me-2"></i>
            {{__('balance_fluctuation.GiaoDichGanDay')}}
        </h5>
        @php
            $recentTransactions = \App\Models\Transaction_history::where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        @endphp
        
        @if($recentTransactions->isNotEmpty())
            @foreach($recentTransactions as $item)
            <div class="transaction-item">
                <div class="transaction-icon {{ $item->type === 'profit' ? 'success' : 'danger' }}">
                    <i class="fa-solid {{ $item->type === 'profit' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                </div>
                <div class="transaction-info">
                    <div class="transaction-type text-white">
                        @if($item->type === 'profit')
                            {{__('balance_fluctuation.LoiNhuan')}}
                        @elseif($item->type === 'order')
                            {{__('balance_fluctuation.DatHang')}}
                        @elseif($item->type === 'penalty')
                            {{__('balance_fluctuation.TienPhat')}}
                        @endif
                    </div>
                    <div class="transaction-date">{{ $item->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="transaction-amount {{ $item->type === 'profit' ? 'positive' : 'negative' }}">
                    {{ $item->type === 'profit' ? '+' : '-' }}${{ number_format($item->value, 2) }}
                </div>
            </div>
            @endforeach
            
            <a href="{{ route('balance_fluctuation') }}?tab=distribution" class="view-all-link">
                {{__('balance_fluctuation.XemTatCa')}} <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        @else
            <div class="empty-state">
                <i class="fa-solid fa-inbox fa-3x mb-3"></i>
                <p>{{__('balance_fluctuation.ChuaCoGiaoDich')}}</p>
            </div>
        @endif
    </div>
</div>

<!-- Pass chart data to JavaScript -->
<script>
    window.chartData = @json($chartData);
</script>
@endif

<!-- Distribution Tab -->
@if($tab === 'distribution')
<div class="tab-content-shell" id="content_items">
    @if(optional($list_distribution)->isNotEmpty())
        @php
            $grouped = $list_distribution->groupBy('note');
            $sortedGroups = $grouped->sortByDesc(function($transactions) {
                return $transactions->first()->created_at;
            });
        @endphp

        @foreach($sortedGroups as $orderCode => $transactions)
            @php
                $sortedTransactions = $transactions->sortBy(function($item) {
                    $order = ['order' => 1, 'profit' => 2, 'penalty' => 3];
                    return $order[$item->type] ?? 999;
                });
                $groupTotal = $transactions->sum(function ($transaction) {
                    if ($transaction->type === 'profit') {
                        return (float) $transaction->value;
                    }

                    if ($transaction->type === 'penalty') {
                        return -(float) $transaction->value;
                    }

                    return 0;
                });
            @endphp

            <div class="tab-card">
                <div class="tab-card-header">
                    <div class="tab-card-title-wrap">
                        <span class="mini-pill">Order</span>
                        <h4 class="tab-card-title">{{ $orderCode }}</h4>
                    </div>
                    <span class="tab-card-total {{ $groupTotal >= 0 ? 'positive' : 'negative' }}">
                        {{ $groupTotal >= 0 ? '+' : '-' }}${{ format_money(abs($groupTotal)) }}
                    </span>
                </div>

                <div class="transaction-stack">
                    @foreach($sortedTransactions as $item)
                        @php
                            $amount = format_money($item->value);
                            $isPositive = in_array($item->type, ['profit'], true);
                        @endphp
                        <div class="transaction-list-item {{ $isPositive ? 'profit' : 'expense' }}">
                            <div class="transaction-icon-list {{ $item->type === 'profit' ? 'success' : ($item->type === 'penalty' ? 'warning' : 'danger') }}">
                                <i class="fa-solid {{ $item->type === 'profit' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                            </div>
                            <div class="transaction-details">
                                <div class="transaction-type-name">
                                    @if($item->type === 'profit')
                                        {{__('balance_fluctuation.LoiNhuan')}}
                                    @elseif($item->type === 'order')
                                        {{__('balance_fluctuation.DatHang')}}
                                    @elseif($item->type === 'penalty')
                                        {{__('balance_fluctuation.TienPhat')}}
                                    @endif
                                </div>
                                <div class="transaction-time">{{ $item->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="transaction-value {{ $isPositive ? 'positive' : 'negative' }}">
                                {{ $isPositive ? '+' : '-' }}${{ $amount }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div class="empty-state">
            <i class="fa-solid fa-inbox fa-3x mb-3"></i>
            <p>{{__('balance_fluctuation.LichSuTrong')}}</p>
        </div>
    @endif
</div>
@endif

<!-- Deposit Tab -->
@if($tab === 'deposit')
<div class="tab-content-shell" id="content_items">
    @if(optional($list_deposit)->isNotEmpty())
        @foreach($list_deposit->groupBy(fn($item) => $item->created_at->format('Y-m-d')) as $date => $transactions)
            <div class="transaction-date-group">
                @php $dailyTotal = $transactions->sum('value'); @endphp
                <div class="tab-card">
                    <div class="tab-card-header compact">
                        <div class="tab-card-title-wrap">
                            <span class="mini-pill success">Deposit</span>
                            <h4 class="tab-card-title">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} - {{__('balance_fluctuation.NapTien')}}</h4>
                        </div>
                        <span class="tab-card-total positive">+${{ format_money($dailyTotal) }}</span>
                    </div>

                    <div class="transaction-stack">
                        @foreach($transactions as $item)
                            <div class="transaction-list-item profit">
                                <div class="transaction-icon-list success">
                                    <i class="fa-solid fa-money-bill-wave"></i>
                                </div>
                                <div class="transaction-details">
                                    <div class="transaction-type-name">{{__('balance_fluctuation.NapTien')}}</div>
                                    <div class="transaction-time">{{ $item->created_at->format('d/m/Y H:i') }}</div>
                                </div>
                                <div class="transaction-value positive">+${{ format_money($item->value) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="empty-state">
            <i class="fa-solid fa-inbox fa-3x mb-3"></i>
            <p>{{__('balance_fluctuation.LichSuTrong')}}</p>
        </div>
    @endif
</div>
@endif

<!-- Withdraw Tab -->
@if($tab === 'withdraw')
<div class="tab-content-shell" id="content_items">
    @if(optional($list_withdraw)->isNotEmpty())
        @foreach($list_withdraw->groupBy(fn($item) => $item->created_at->format('Y-m-d')) as $date => $transactions)
            <div class="transaction-date-group">
                @php $dailyTotal = $transactions->sum('value'); @endphp
                <div class="tab-card withdraw-card-shell">
                    <div class="tab-card-header compact">
                        <div class="tab-card-title-wrap">
                            <span class="mini-pill danger">Withdraw</span>
                            <h4 class="tab-card-title">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} - {{__('balance_fluctuation.RutTien')}}</h4>
                        </div>
                        <span class="tab-card-total negative">-${{ format_money($dailyTotal) }}</span>
                    </div>

                    <div class="transaction-stack">
                        @foreach($transactions as $item)
                            @php
                                $statusClass = match($item->status) {
                                    'processing' => 'warning',
                                    'completed' => 'success',
                                    default => 'danger',
                                };
                                $statusLabel = match($item->status) {
                                    'processing' => __('balance_fluctuation.ChoXacNhan'),
                                    'completed' => __('balance_fluctuation.HoanThanh'),
                                    default => __('balance_fluctuation.Huy'),
                                };
                            @endphp
                            <div class="transaction-list-item expense">
                                <div class="transaction-icon-list danger">
                                    <i class="fa-solid fa-arrow-up"></i>
                                </div>
                                <div class="transaction-details">
                                    <div class="transaction-type-name">{{__('balance_fluctuation.RutTien')}}</div>
                                    <div class="transaction-time">{{ $item->created_at->format('d/m/Y H:i') }}</div>
                                    <div class="transaction-time">{{ $item->username_bank }} - {{ $item->bank_name }}</div>
                                </div>
                                <div class="transaction-value negative">
                                    -${{ format_money($item->value) }}
                                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="empty-state">
            <i class="fa-solid fa-inbox fa-3x mb-3"></i>
            <p>{{__('balance_fluctuation.LichSuTrong')}}</p>
        </div>
    @endif
</div>
@endif
@endsection
