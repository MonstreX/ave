<ul>
@foreach ($items as $item)
    @php
        $isActive = null;
        $url = $item->url ?: ($item->route ? route($item->route) : '#');

        // Check if link is current
        if (url($url) == url()->current()) {
            $isActive = 'active';
        }
    @endphp

    <li class="{{ $isActive }}{{ $item->children->count() ? ' has-children' : '' }}">
        <a href="{{ $url }}" target="{{ $item->target ?? '_self' }}">
            @if($item->icon)
                <i class="{{ $item->icon }}"></i>
            @endif
            <span>{{ $item->title }}</span>
        </a>
        @if($item->children->count())
            @include('ave::menu.default', ['items' => $item->children, 'options' => $options])
        @endif
    </li>
@endforeach
</ul>
