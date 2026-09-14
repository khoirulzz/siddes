@if($paginator->hasPages())
    <div class="table-pagination">
        <small class="muted">Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} {{ $label }}</small>
        <div class="pager-controls">
            @if($paginator->onFirstPage())
                <span class="pager-link is-disabled">Sebelumnya</span>
            @else
                <a class="pager-link" href="{{ $paginator->previousPageUrl() }}">Sebelumnya</a>
            @endif
            <span class="pager-meta">Halaman {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @if($paginator->hasMorePages())
                <a class="pager-link" href="{{ $paginator->nextPageUrl() }}">Berikutnya</a>
            @else
                <span class="pager-link is-disabled">Berikutnya</span>
            @endif
        </div>
    </div>
@endif
