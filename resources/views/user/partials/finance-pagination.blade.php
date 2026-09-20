@if ($paginator->hasPages())
    <nav class="finance-pagination" aria-label="Phân trang lịch sử giao dịch">
        @if ($paginator->onFirstPage())
            <span class="page-control disabled" aria-disabled="true"><i class="fas fa-chevron-left"></i></span>
        @else
            <a class="page-control" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Trang trước"><i class="fas fa-chevron-left"></i></a>
        @endif

        <span class="page-position">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="page-control" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Trang sau"><i class="fas fa-chevron-right"></i></a>
        @else
            <span class="page-control disabled" aria-disabled="true"><i class="fas fa-chevron-right"></i></span>
        @endif
    </nav>
@endif
