@php
    $tabsId = $domId ?? ('tabs-' . uniqid());
    $activeId = $activeTab ?? null;
@endphp

<div data-ave-tabs-root>
    <ul class="nav nav-tabs" data-ave-tabs="nav" role="tablist">
        @foreach($tabs as $index => $tab)
            @php
                $tabId = $tab->getId();
                $isActive = $activeId ? $activeId === $tabId : $index === 0;
            @endphp
            <li role="presentation" class="{{ $isActive ? 'active' : '' }}" data-ave-tab-target="#{{ $tabId }}">
                <a href="#{{ $tabId }}">
                    {{ $tab->getLabel() }}
                </a>
            </li>
        @endforeach
        </ul>
    <div class="tab-content" data-ave-tabs="content">
        @foreach($tabs as $index => $tab)
            @php
                $tabId = $tab->getId();
                $isActive = $activeId ? $activeId === $tabId : $index === 0;
            @endphp
            <div class="tab-pane {{ $isActive ? 'active' : '' }}" id="{{ $tabId }}" data-ave-tab-pane>
                {!! $tab->render($context) !!}
            </div>
        @endforeach
    </div>
</div>
