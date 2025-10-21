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
                <div class="stat-value">${{ number_format($stats['current_balance'], 2) }}</div>
            </div>
        </div>
        
        <!-- Tổng lợi nhuận -->
        <div class="stat-card profit-card">
            <div class="stat-icon">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">{{__('balance_fluctuation.TongLoiNhuan')}}</div>
                <div class="stat-value text-success">${{ number_format($stats['total_profit'], 2) }}</div>
            </div>
        </div>
        
        <!-- Tổng nạp tiền -->
        <div class="stat-card deposit-card">
            <div class="stat-icon">
                <i class="fa-solid fa-money-bill-trend-up"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">{{__('balance_fluctuation.TongNapTien')}}</div>
                <div class="stat-value">${{ number_format($stats['total_deposit'], 2) }}</div>
            </div>
        </div>
        
        <!-- Tổng rút tiền -->
        <div class="stat-card withdraw-card">
            <div class="stat-icon">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">{{__('balance_fluctuation.TongRutTien')}}</div>
                <div class="stat-value">${{ number_format($stats['total_withdraw'], 2) }}</div>
            </div>
        </div>
    </div>
    
    <!-- Chart Container -->
    <div class="chart-container">
        <div class="chart-header">
            <h4 class="chart-title">
                <i class="fa-solid fa-chart-line me-2"></i>
                {{__('balance_fluctuation.BieuDoSoDu')}}
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
                    <div class="transaction-type">
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
<div class="bg-white" id="content_items">
    @if(optional($list_distribution)->isNotEmpty())
        @foreach($list_distribution as $item)
        <div class="transaction-list-item">
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
                @if($item->note)
                <div class="transaction-note">{{ $item->note }}</div>
                @endif
            </div>
            <div class="transaction-value {{ $item->type === 'profit' ? 'positive' : 'negative' }}">
                {{ $item->type === 'profit' ? '+' : '-' }}${{ number_format($item->value, 2) }}
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
<div class="bg-white" id="content_items">
    @if(optional($list_deposit)->isNotEmpty())
        @foreach($list_deposit as $item)
        <div class="transaction-list-item deposit-item">
            <div class="transaction-icon-list success">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
            <div class="transaction-details">
                <div class="transaction-type-name">{{__('balance_fluctuation.NapTien')}}</div>
                <div class="transaction-time">{{ $item->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="transaction-value positive">
                +${{ number_format($item->value, 2) }}
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
<div class="bg-white" id="content_items">
    @if(optional($list_withdraw)->isNotEmpty())
        @foreach($list_withdraw as $item)
        <div class="withdraw-item">
            <div class="withdraw-header">
                <div class="transaction-icon-list danger">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <div class="withdraw-status">
                    @if($item->status == "processing")
                        <span class="badge bg-warning">{{__('balance_fluctuation.ChoXacNhan')}}</span>
                    @elseif($item->status == "completed")
                        <span class="badge bg-success">{{__('balance_fluctuation.HoanThanh')}}</span>
                    @else
                        <span class="badge bg-danger">{{__('balance_fluctuation.Huy')}}</span>
                    @endif
                </div>
            </div>
            <div class="withdraw-info">
                <div class="withdraw-detail">
                    <span class="detail-label">{{__('balance_fluctuation.TenTaiKhoan')}}:</span>
                    <span class="detail-value">{{ $item->username_bank }}</span>
                </div>
                <div class="withdraw-detail">
                    <span class="detail-label">{{__('balance_fluctuation.SoTaiKhoan')}}:</span>
                    <span class="detail-value">{{ $item->account_number }}</span>
                </div>
                <div class="withdraw-detail">
                    <span class="detail-label">{{__('balance_fluctuation.NganHang')}}:</span>
                    <span class="detail-value">{{ $item->bank_name }}</span>
                </div>
                <div class="withdraw-detail">
                    <span class="detail-label">{{__('balance_fluctuation.ThoiGian')}}:</span>
                    <span class="detail-value">{{ $item->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            <div class="withdraw-amount">
                <span class="amount-label">{{__('balance_fluctuation.SoTien')}}:</span>
                <span class="amount-value">-${{ number_format($item->value, 2) }}</span>
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
