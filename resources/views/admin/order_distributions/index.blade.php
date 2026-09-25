@extends('admin.layouts.master')

@section('title', 'Quản lý phân phối đơn hàng')

@section('style-libs')
    @vite(['resources/css/admin/common-modern.css', 'resources/css/admin/order_distribution/index.css'])
@endsection

@section('content')
@php
    $canViewDistributionDetail = app(\App\Services\AuthorizationService::class)->can(auth()->user(), config('authorization.capabilities.order_distributions_view_detail'));
@endphp
@php
    $advancedKeys = ['user_id', 'assigned_by', 'order_id', 'source', 'from', 'to', 'updated_from', 'updated_to'];
    $advancedCount = collect(request()->only($advancedKeys))->filter(fn ($value) => $value !== null && $value !== '')->count();
    $hasAdvancedFilters = $advancedCount > 0
        || !in_array(request('sort', 'created_at'), ['created_at', null], true)
        || request('direction', 'desc') !== 'desc';
    $selectedStatus = request('status');
    $statusNames = $statuses->pluck('display_name', 'name');
    $quickStatuses = [
        '' => ['label' => 'Tất cả', 'count' => $stats['total']],
        'pending' => ['label' => 'Chờ xác nhận'],
        'confirmed' => ['label' => 'Đã xác nhận'],
        'shipping' => ['label' => 'Đang giao'],
        'completed' => ['label' => 'Hoàn thành', 'count' => $stats['completed']],
    ];
    $otherStatus = $selectedStatus !== null && $selectedStatus !== '' && !array_key_exists($selectedStatus, $quickStatuses);
@endphp
<main class="container-fluid px-3 px-lg-4 pb-5 distribution-page distribution-index">
    <header class="distribution-hero">
        <div class="distribution-hero-copy">
            <span class="distribution-kicker"><i class="fas fa-route" aria-hidden="true"></i> Vận hành đơn hàng</span>
            <h1>Quản lý phân phối đơn hàng</h1>
            <p>Theo dõi đơn đã giao, người nhận và tiến độ xử lý trong một nơi.</p>
        </div>
        <div class="distribution-hero-aside" aria-label="Tổng quan nhanh">
            <span class="distribution-hero-aside-icon"><i class="fas fa-clipboard-check" aria-hidden="true"></i></span>
            <div>
                <small>Đơn đang xử lý</small>
                <strong>{{ number_format($stats['processing']) }}</strong>
                <span>Từ xác nhận đến giao hàng</span>
            </div>
        </div>
    </header>

    @foreach(['success' => 'success', 'error' => 'danger'] as $flash => $tone)
        @if(session($flash))
            <div class="distribution-alert is-{{ $tone }}" role="{{ $tone === 'danger' ? 'alert' : 'status' }}">
                <i class="fas {{ $tone === 'danger' ? 'fa-exclamation-circle' : 'fa-check-circle' }}" aria-hidden="true"></i>
                {{ session($flash) }}
            </div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="distribution-alert is-danger" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $errors->first() }}
        </div>
    @endif

    <section class="distribution-overview" aria-label="Thống kê phân phối">
        <article class="distribution-overview-card">
            <span class="distribution-overview-icon"><i class="fas fa-boxes" aria-hidden="true"></i></span>
            <div><small>Tổng đơn phân phối</small><strong>{{ number_format($stats['total']) }}</strong></div>
        </article>
        <article class="distribution-overview-card">
            <span class="distribution-overview-icon is-pending"><i class="fas fa-clock" aria-hidden="true"></i></span>
            <div><small>Chờ / chưa ghi trạng thái</small><strong>{{ number_format($stats['pending']) }}</strong></div>
        </article>
        <article class="distribution-overview-card">
            <span class="distribution-overview-icon is-progress"><i class="fas fa-truck" aria-hidden="true"></i></span>
            <div><small>Đang xử lý</small><strong>{{ number_format($stats['processing']) }}</strong></div>
        </article>
        <article class="distribution-overview-card">
            <span class="distribution-overview-icon is-complete"><i class="fas fa-check-circle" aria-hidden="true"></i></span>
            <div><small>Hoàn thành</small><strong>{{ number_format($stats['completed']) }}</strong></div>
        </article>
    </section>

    <section class="distribution-search-card" aria-labelledby="distribution-filter-title">
        <div class="distribution-search-heading">
            <div>
                <span class="distribution-kicker">Tra cứu đơn hàng</span>
                <h2 id="distribution-filter-title">Tìm kiếm và bộ lọc</h2>
                <p>Tìm nhanh theo đơn hàng hoặc người nhận; mở thêm điều kiện khi cần.</p>
            </div>
            @if($advancedCount)
                <span class="distribution-filter-count">{{ $advancedCount }} bộ lọc nâng cao</span>
            @endif
        </div>
        <form method="GET" action="{{ route('order_distributions.index') }}" class="distribution-search-form">
            <div class="distribution-search-row">
                <label for="distribution-q" class="sr-only">Mã đơn, tên sản phẩm, ID hoặc người nhận</label>
                <div class="distribution-search-input">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input id="distribution-q" name="q" value="{{ request('q') }}" maxlength="120"
                        placeholder="Mã đơn, tên sản phẩm, ID hoặc người nhận…" autocomplete="off">
                </div>
                <button type="submit" class="distribution-search-submit">
                    <i class="fas fa-search" aria-hidden="true"></i><span>Tìm kiếm</span>
                </button>
                <a class="distribution-clear-button" href="{{ route('order_distributions.index') }}" title="Xóa tất cả bộ lọc">
                    <i class="fas fa-undo" aria-hidden="true"></i><span>Đặt lại</span>
                </a>
            </div>

            <details class="distribution-more-filters" @if($hasAdvancedFilters || $otherStatus) open @endif>
                <summary>
                    <span><i class="fas fa-sliders-h" aria-hidden="true"></i> Bộ lọc nâng cao
                        @if($advancedCount)<b>{{ $advancedCount }}</b>@endif
                    </span>
                    <i class="fas fa-chevron-down distribution-more-chevron" aria-hidden="true"></i>
                </summary>
                <div class="distribution-advanced-body">
                    <div class="distribution-advanced-grid">
                        <div class="distribution-field">
                            <label for="distribution-status">Trạng thái</label>
                            <select id="distribution-status" name="status" class="form-control">
                                <option value="">Tất cả trạng thái</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->name }}" @selected($selectedStatus === $status->name)>{{ $status->display_name }}</option>
                                @endforeach
                                <option value="unknown" @selected($selectedStatus === 'unknown')>Chưa ghi nhận</option>
                            </select>
                        </div>
                        <div class="distribution-field">
                            <label for="distribution-user">ID người nhận</label>
                            <input id="distribution-user" type="number" min="1" name="user_id" class="form-control"
                                value="{{ request('user_id') }}" placeholder="Nhập ID người nhận">
                        </div>
                        <div class="distribution-field">
                            <label for="distribution-assigner">Người phân phối</label>
                            <select id="distribution-assigner" name="assigned_by" class="form-control">
                                <option value="">Tất cả người phân phối</option>
                                @foreach($assigners as $assigner)
                                    <option value="{{ $assigner->id }}" @selected((string) request('assigned_by') === (string) $assigner->id)>{{ $assigner->full_name ?: $assigner->username }} (#{{ $assigner->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="distribution-field">
                            <label for="distribution-source">Nguồn phân phối</label>
                            <select id="distribution-source" name="source" class="form-control">
                                <option value="">Tất cả nguồn</option>
                                <option value="admin" @selected(request('source') === 'admin')>Quản trị viên giao</option>
                                <option value="spin" @selected(request('source') === 'spin')>Người dùng tự nhận</option>
                                <option value="unknown" @selected(request('source') === 'unknown')>Chưa ghi nhận</option>
                            </select>
                        </div>
                        <div class="distribution-field">
                            <label for="distribution-order">ID Order nguồn</label>
                            <input id="distribution-order" type="number" min="1" name="order_id" class="form-control"
                                value="{{ request('order_id') }}" placeholder="Nhập ID Order">
                        </div>
                        <div class="distribution-field">
                            <label for="distribution-from">Phân phối từ</label>
                            <input id="distribution-from" type="date" name="from" value="{{ request('from') }}" class="form-control">
                        </div>
                        <div class="distribution-field">
                            <label for="distribution-to">Phân phối đến</label>
                            <input id="distribution-to" type="date" name="to" value="{{ request('to') }}" class="form-control">
                        </div>
                        <div class="distribution-field">
                            <label for="distribution-updated-from">Cập nhật từ</label>
                            <input id="distribution-updated-from" type="date" name="updated_from" value="{{ request('updated_from') }}" class="form-control">
                        </div>
                        <div class="distribution-field">
                            <label for="distribution-updated-to">Cập nhật đến</label>
                            <input id="distribution-updated-to" type="date" name="updated_to" value="{{ request('updated_to') }}" class="form-control">
                        </div>
                        <div class="distribution-field">
                            <label for="distribution-sort">Sắp xếp theo</label>
                            <select id="distribution-sort" name="sort" class="form-control">
                                <option value="created_at" @selected(request('sort', 'created_at') === 'created_at')>Thời gian phân phối</option>
                                <option value="updated_at" @selected(request('sort') === 'updated_at')>Thời gian cập nhật</option>
                                <option value="status" @selected(request('sort') === 'status')>Trạng thái</option>
                                <option value="id" @selected(request('sort') === 'id')>ID phân phối</option>
                            </select>
                        </div>
                        <div class="distribution-field">
                            <label for="distribution-direction">Thứ tự</label>
                            <select id="distribution-direction" name="direction" class="form-control">
                                <option value="desc" @selected(request('direction', 'desc') === 'desc')>Mới nhất trước</option>
                                <option value="asc" @selected(request('direction') === 'asc')>Cũ nhất trước</option>
                            </select>
                        </div>
                    </div>
                    <div class="distribution-advanced-actions">
                        <button class="distribution-search-submit" type="submit"><i class="fas fa-filter" aria-hidden="true"></i> Áp dụng bộ lọc</button>
                        <a href="{{ route('order_distributions.index') }}" class="distribution-clear-button">Xóa tất cả</a>
                    </div>
                </div>
            </details>
        </form>
    </section>

    <section class="distribution-results-card" aria-labelledby="distribution-list-title">
        <div class="distribution-results-head">
            <div>
                <span class="distribution-kicker">Danh sách phân phối</span>
                <h2 id="distribution-list-title">Danh sách đơn phân phối</h2>
                <p><strong>{{ number_format($frozenOrders->total()) }}</strong> đơn phù hợp
                    @if($selectedStatus) · {{ $statusNames[$selectedStatus] ?? ($selectedStatus === 'unknown' ? 'Chưa ghi nhận' : $selectedStatus) }} @endif
                </p>
            </div>
            <span class="distribution-result-page">Trang {{ $frozenOrders->currentPage() }}/{{ $frozenOrders->lastPage() }}</span>
        </div>
        <nav class="distribution-status-tabs" aria-label="Lọc nhanh theo trạng thái">
            @foreach($quickStatuses as $status => $tab)
                <a href="{{ route('order_distributions.index', array_merge(request()->except(['status', 'page']), $status !== '' ? ['status' => $status] : [])) }}"
                    class="{{ (string) ($selectedStatus ?? '') === $status ? 'is-active' : '' }}"
                    @if((string) ($selectedStatus ?? '') === $status) aria-current="page" @endif>
                    {{ $tab['label'] }}
                    @isset($tab['count'])<span>{{ number_format($tab['count']) }}</span>@endisset
                </a>
            @endforeach
            @if($otherStatus)
                <span class="distribution-status-tab-other is-active" aria-current="page">{{ $statusNames[$selectedStatus] ?? ($selectedStatus === 'unknown' ? 'Chưa ghi nhận' : $selectedStatus) }}</span>
            @endif
        </nav>

        <div class="distribution-results-table-wrap">
            <table class="distribution-results-table">
                <thead>
                    <tr>
                        <th scope="col">Đơn hàng</th>
                        <th scope="col">Người nhận</th>
                        <th scope="col">Người phân phối</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Thời gian</th>
                        <th scope="col">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($frozenOrders as $item)
                        @php
                            $recipientName = $item->user?->full_name ?: ($item->user?->username ?: 'Tài khoản không còn tồn tại');
                            $recipientInitial = mb_strtoupper(mb_substr($recipientName, 0, 1));
                            $assignerName = $item->assignedBy?->full_name ?: $item->assignedBy?->username;
                            $assignerDisplay = $assignerName ?: ($item->assignment_source === 'spin'
                                ? 'Hệ thống tự phân phối'
                                : ($item->assignment_source === 'admin' ? 'Không còn thông tin' : 'Chưa ghi nhận'));
                            $statusName = $statusNames[$item->status] ?? ($item->status ?: 'Chưa ghi nhận');
                        @endphp
                        <tr>
                            <td data-label="Đơn hàng">
                                <div class="distribution-result-order">
                                    @if($item->snapshot_image)
                                        <img src="{{ Storage::url($item->snapshot_image) }}" alt="" loading="lazy">
                                    @else
                                        <span class="distribution-result-order-placeholder"><i class="fas fa-box" aria-hidden="true"></i></span>
                                    @endif
                                    <span class="distribution-result-order-copy">
                                        <strong>{{ $item->snapshot_order_code ?: 'Đơn #' . $item->id }}</strong>
                                        <small>{{ $item->snapshot_name ?: 'Tên sản phẩm chưa ghi nhận' }}</small>
                                        <em>#{{ $item->id }} · {{ $item->custom_price !== null ? 'Đơn giá trị cao' : 'Đơn thường' }}</em>
                                    </span>
                                </div>
                            </td>
                            <td data-label="Người nhận">
                                <div class="distribution-result-user">
                                    <b aria-hidden="true">{{ $recipientInitial }}</b>
                                    <span><strong>{{ $recipientName }}</strong><small>User #{{ $item->user_id }}</small></span>
                                </div>
                            </td>
                            <td data-label="Người phân phối">
                                <span class="distribution-result-person"><strong>{{ $assignerDisplay }}</strong>
                                    <small><i class="fas {{ $item->assignment_source === 'admin' ? 'fa-user-cog' : ($item->assignment_source === 'spin' ? 'fa-sync' : 'fa-question-circle') }}" aria-hidden="true"></i>
                                        {{ $item->assignment_source === 'admin' ? 'Giao thủ công' : ($item->assignment_source === 'spin' ? 'Người dùng tự nhận' : 'Chưa có dữ liệu nguồn') }}
                                    </small>
                                </span>
                            </td>
                            <td data-label="Trạng thái">
                                <span class="distribution-status distribution-status-{{ $item->status ?: 'unknown' }}">{{ $statusName }}</span>
                                <small class="distribution-result-subline">{{ $item->spun ? 'Đã nhận đơn' : 'Chưa nhận đơn' }}</small>
                            </td>
                            <td data-label="Thời gian">
                                <time class="distribution-result-time" datetime="{{ $item->created_at?->toISOString() }}">
                                    <strong>{{ $item->created_at?->format('d/m/Y') ?: '—' }}</strong>
                                    <small>{{ $item->created_at?->format('H:i') ?: '—' }}</small>
                                </time>
                            </td>
                            <td data-label="Thao tác">
                                @if ($canViewDistributionDetail)
                                <a class="distribution-result-action" href="{{ route('order_distributions.show', array_merge(['frozenOrder' => $item->id], request()->query())) }}">
                                    Xem Audit <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="distribution-results-empty-row">
                            <td colspan="6">
                                <div class="distribution-results-empty">
                                    <i class="fas fa-box-open" aria-hidden="true"></i>
                                    <strong>Chưa có đơn phân phối phù hợp</strong>
                                    <p>Thử tìm theo mã đơn, thay đổi trạng thái hoặc đặt lại bộ lọc.</p>
                                    <a href="{{ route('order_distributions.index') }}" class="distribution-clear-button">Xóa bộ lọc</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($frozenOrders->hasPages())
            <div class="distribution-results-pagination">{{ $frozenOrders->links('pagination::bootstrap-4') }}</div>
        @endif
    </section>
</main>
@endsection
