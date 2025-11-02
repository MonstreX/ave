@if(!empty($metrics))
    <div class="metrics-row">
        @foreach($metrics as $metric)
            <div class="metric-card">
                @if($metric->getIcon())
                    <span class="metric-icon">{{ $metric->getIcon() }}</span>
                @else
                    <span class="metric-icon">📊</span>
                @endif
                <div class="metric-value">{{ $metric->formatValue($metric->getValue()) }}</div>
                <p class="metric-label">{{ $metric->getLabel() }}</p>
            </div>
        @endforeach
    </div>
@endif
