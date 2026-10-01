@if($visitorStatistics !== null)
    <section class="footer-visitor-stats" aria-labelledby="visitor-statistics-title">
        <div class="footer-visitor-heading">
            <h4 id="visitor-statistics-title">Statistik Kunjungan</h4>
            <p>Pengunjung dihitung sekali per browser setiap hari (WIB).</p>
        </div>
        @if($visitorStatistics['available'])
            <dl class="footer-visitor-grid">
                @foreach(['today' => 'Hari ini', 'yesterday' => 'Kemarin', 'month' => 'Bulan ini', 'total' => 'Total kunjungan'] as $key => $label)
                    <div>
                        <dt>{{ $label }}</dt>
                        <dd>{{ number_format($visitorStatistics[$key], 0, ',', '.') }}</dd>
                    </div>
                @endforeach
            </dl>
            @if($visitorStatistics['started_on'])
                <p class="footer-visitor-note">Total merupakan akumulasi pengunjung harian sejak {{ \Carbon\CarbonImmutable::parse($visitorStatistics['started_on'])->format('d/m/Y') }}.</p>
            @endif
        @else
            <p class="footer-visitor-note">Statistik sementara tidak tersedia.</p>
        @endif
    </section>
@endif
