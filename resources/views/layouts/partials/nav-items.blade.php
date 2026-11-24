
@foreach($items as $item)
    @php
        $active  = $isActive($item);
        $isGroup = ! empty($item['children']);
        $isSub   = $depth > 0;
    @endphp

    @if($isGroup)
        <details @class(['jn-dropdown', 'jn-subdropdown' => $isSub]) @if($active) open @endif>

            <summary @class(['active' => $active])>
                <i data-lucide="{{ $item['icon'] }}"></i>
                {{ $item['label'] }}
                <i data-lucide="chevron-down" class="jn-chevron"></i>
            </summary>

            <div class="jn-dropdown-menu">
                @include('layouts.partials.nav-items', [
                    'items' => $item['children'],
                    'depth' => $depth + 1,
                ])
            </div>

        </details>
    @else
        <a href="{{ route($item['route']) }}"
           @class(['jn-link' => ! $isSub, 'active' => $active])
           @if($active) aria-current="page" @endif>
            <i data-lucide="{{ $item['icon'] }}"></i>
            {{ $item['label'] }}
        </a>
    @endif
@endforeach
