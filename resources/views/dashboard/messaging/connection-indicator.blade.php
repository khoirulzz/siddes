<span class="messaging-connection-icon messaging-connection-icon--{{ $connection['tone'] }}" aria-hidden="true">
    <svg class="messaging-connection-symbol" viewBox="0 0 48 48" fill="none"><path d="M40 23a16 16 0 0 1-23.5 14.2L8 40l2.8-8.5A16 16 0 1 1 40 23Z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="m18 15 4 5-2 3c2 3 3 4 6 6l3-2 5 4c-1 4-4 5-7 3-6-3-10-7-13-13-1-3 0-5 4-6Z" fill="currentColor"/></svg>
    <span class="messaging-connection-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            @switch($connection['icon'])
                @case('check')<path d="m5 12 4 4L19 6"/>@break
                @case('clock')<circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/>@break
                @case('qr')<path d="M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h2v2h-2zM21 15v6h-6"/>@break
                @case('alert')<path d="M12 5v9M12 19h.01"/>@break
                @default<path d="M6 12h12"/>
            @endswitch
        </svg>
    </span>
</span>
