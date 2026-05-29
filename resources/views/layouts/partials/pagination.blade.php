{{-- resources/views/partials/pagination.blade.php --}}
@if ($paginator->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3 px-2">
    {{-- Texto de información (Opcional) --}}
    <div class="small text-muted">
        Mostrando 
        <strong>{{ $paginator->firstItem() }}</strong> 
        a 
        <strong>{{ $paginator->lastItem() }}</strong> 
        de 
        <strong>{{ $paginator->total() }}</strong> registros
    </div>

    <nav>
        <ul class="pagination pagination-sm mb-0">
            {{-- Botón anterior --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled"><span class="page-link">«</span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}">«</a></li>
            @endif

            {{-- Lógica de números --}}
            @php
                $end = $paginator->lastPage();
                $current = $paginator->currentPage();
            @endphp

            @foreach ($paginator->getUrlRange(1, $end) as $page => $url)
                @if ($page <= 3 || $page > $end - 2 || ($page >= $current - 1 && $page <= $current + 1))
                    <li class="page-item {{ $page == $current ? 'active' : '' }}">
                        @if ($page == $current)
                            <span class="page-link">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    </li>
                @elseif (($page == 4 && $current > 5) || ($page == $end - 2 && $current < $end - 4))
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                    @if ($page == 4) @php $page = $current - 2; @endphp @endif
                @endif
            @endforeach

            {{-- Botón siguiente --}}
            @if ($paginator->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}">»</a></li>
            @else
                <li class="page-item disabled"><span class="page-link">»</span></li>
            @endif
        </ul>
    </nav>
</div>
@endif