
@foreach($items as $item)
    @php
        $active  = $isActive($item);
        $isGroup = ! empty($item['children']);
        $isSub   = $depth > 0;
    @endphp

    @if($isGroup)
        <details
            @class([
                'jn-nav-group',
                'jn-nav-subgroup' => $isSub,
                'active' => $active,
            ])
            @if($active) open @endif
        >
            <summary @class(['active' => $active])>
                <i data-lucide="{{ $item['icon'] ?? 'circle' }}"></i>

                <span>{{ $item['label'] }}</span>

                <i data-lucide="chevron-right"
                   class="jn-nav-chevron"></i>
            </summary>

            <div class="jn-nav-children">
                @include('layouts.partials.sidebar-items', [
                    'items' => $item['children'],
                    'depth' => $depth + 1,
                    'isActive' => $isActive,
                ])
            </div>
        </details>
    @else
        <a href="{{ route($item['route']) }}"
           @class([
               'jn-nav-item',
               'active' => $active,
           ])
           @if($active) aria-current="page" @endif>

            <i data-lucide="{{ $item['icon'] ?? 'circle' }}"></i>

            <span>{{ $item['label'] }}</span>
        </a>
    @endif
@endforeach
