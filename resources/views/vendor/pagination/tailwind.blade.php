@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex gap-4 items-center justify-between flex-wrap mt-8">

        {{-- Results Info --}}
        <div>
            <p class="text-sm text-slate-400">
                {!! __('pagination.showing') !!}
                @if ($paginator->firstItem())
                    <span class="font-semibold text-white">{{ $paginator->firstItem() }}</span>
                    {!! __('pagination.to') !!}
                    <span class="font-semibold text-white">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                {!! __('pagination.of') !!}
                <span class="font-semibold text-white">{{ $paginator->total() }}</span>
                {!! __('pagination.results') !!}
            </p>
        </div>

        {{-- Pagination Links --}}
        <div class="flex gap-2 flex-wrap">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-2 text-sm font-medium text-slate-500 bg-white/5 border border-white/10 rounded-lg cursor-not-allowed">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-3 py-2 text-sm font-medium text-slate-300 bg-white/10 border border-white/20 rounded-lg hover:bg-white/20 transition-colors">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="px-3 py-2 text-sm font-medium text-slate-500">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="px-3 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-2 text-sm font-medium text-slate-300 bg-white/10 border border-white/20 rounded-lg hover:bg-white/20 transition-colors">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-3 py-2 text-sm font-medium text-slate-300 bg-white/10 border border-white/20 rounded-lg hover:bg-white/20 transition-colors">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="px-3 py-2 text-sm font-medium text-slate-500 bg-white/5 border border-white/10 rounded-lg cursor-not-allowed">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>
    </nav>
@endif
