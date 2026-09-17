<div class="messaging-table"><table><thead><tr><th>Penerima</th><th>Pesan</th><th>Proses</th><th>Pengantaran</th><th>Aksi</th></tr></thead><tbody>
    @forelse($items as $item)
    <tr><td>+{{ $item['recipient'] }}@if(!empty($item['createdAt']))<small class="muted">{{ \Illuminate\Support\Carbon::parse($item['createdAt'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</small>@endif</td><td class="messaging-content-cell">
        @include('dashboard.messaging.message-content', ['text'=>$item['renderedMessage']])
        @if(!empty($item['errorMessage']))<small class="messaging-error">{{ $item['errorMessage'] }}</small>@endif
    </td><td><span class="messaging-status {{ $item['status']==='FAILED' ? 'messaging-status--warning' : '' }}">{{ \App\Support\MessagingLabels::status($item['status']) }}</span><small class="muted">Percobaan {{ $item['attempts'] }}/{{ $item['maxAttempts'] }}</small></td>
    <td>{{ \App\Support\MessagingLabels::status($item['deliveryStatus']) }}</td>
    <td>@if($item['status']==='FAILED' || ($item['status']==='SENT' && in_array($item['deliveryStatus'], ['ERROR','UNKNOWN'])))
        <form method="POST" action="{{ route('dashboard.messaging.messages.retry', $item['id']) }}" data-confirm="{{ $item['deliveryStatus']==='UNKNOWN' ? 'Pengiriman belum pasti. Retry dapat menyebabkan pesan ganda. Lanjutkan?' : 'Antrekan ulang pesan ini?' }}">@csrf<button class="btn btn-secondary">Antrekan ulang</button></form>
        @else<span class="muted">—</span>@endif
    </td></tr>
    @empty<tr><td colspan="5" class="messaging-empty">Belum ada pesan yang cocok.</td></tr>@endforelse
</tbody></table></div>
<p class="muted">Pengiriman tidak pasti tidak diulang otomatis. Periksa penerima sebelum mengantrekan ulang.</p>
