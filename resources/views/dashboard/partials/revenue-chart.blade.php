{{-- Invoiced by month: pure-CSS bar chart of invoices created --}}
@if (collect($chart['months'])->sum('value') <= 0)
    <div class="dash-card chart-card">
        <div class="dash-card-head">
            <div>
                <h4 class="dash-card-title">Invoiced by month</h4>
                <p class="dash-card-sub mb-0">{{ $chart['currency'] }}, last 6 months</p>
            </div>
            <span class="chart-legend"><span class="legend-dot"></span> Invoiced</span>
        </div>
        <div class="chart-empty">
            <span class="empty-icon"><i class="mdi mdi-chart-bar"></i></span>
            <p class="empty-title mb-1">No invoices yet</p>
            <p class="empty-text mb-0">Invoices you create will chart here.</p>
        </div>
    </div>
@else
    <div class="dash-card chart-card">
        <div class="dash-card-head">
            <div>
                <h4 class="dash-card-title">Invoiced by month</h4>
                <p class="dash-card-sub mb-0">{{ $chart['currency'] }}, last 6 months</p>
            </div>
            <span class="chart-legend"><span class="legend-dot"></span> Invoiced</span>
        </div>

        <div class="chart-area">
            <div class="y-axis">
                @foreach ($chart['ticks'] as $tick)
                    <span>{{ $tick['label'] }}</span>
                @endforeach
            </div>
            <div class="chart-plot">
                <div class="grid-lines">
                    @foreach ($chart['ticks'] as $tick)
                        <span></span>
                    @endforeach
                </div>
                <div class="chart-bars">
                    @foreach ($chart['months'] as $month)
                        <div class="bar-col {{ $month['isCurrent'] ? 'is-current' : '' }}">
                            <span class="bar-value">{{ $month['value'] > 0 ? $month['valueLabel'] : '' }}</span>
                            <div class="bar-track">
                                <div class="bar" style="height: {{ $month['pct'] }}%"></div>
                            </div>
                            <span class="month-label">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
