@php($iconName = $name ?? 'square')
<svg class="ui-icon {{ $class ?? '' }}" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
    @switch($iconName)
        @case('qrcode')
            <rect x="3" y="3" width="6" height="6" rx="1"/><rect x="15" y="3" width="6" height="6" rx="1"/><rect x="3" y="15" width="6" height="6" rx="1"/><path d="M15 15h2v2h-2zm4 0h2v6h-2zm-4 4h2v2h-2z"/>
            @break
        @case('layers')
            <path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/>
            @break
        @case('ruler')
            <path d="m4 17 13-13 3 3L7 20H4v-3Z"/><path d="m13 8 3 3m-6 0 3 3m-6 0 3 3"/>
            @break
        @case('scissors')
            <path d="M8.4 7.9 19 3M8.4 16.1 19 21M8.2 12H20"/><circle cx="5" cy="8" r="3"/><circle cx="5" cy="16" r="3"/>
            @break
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            @break
        @case('clipboard')
            <path d="M9 5H6a2 2 0 0 0-2 2v13h16V7a2 2 0 0 0-2-2h-3"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M8 11h8m-8 4h8"/>
            @break
        @case('users')
            <circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M3 20c0-4 2-6 6-6s6 2 6 6m0-5c3 0 5 2 5 5"/>
            @break
        @case('chart-bar')
            <path d="M4 20V10h4v10m4 0V4h4v16m4 0v-7h-4M2 20h20"/>
            @break
        @case('chart-line')
            <path d="M3 20h18M5 16l4-5 4 3 6-8M15 6h4v4"/>
            @break
        @case('arrow-left')
            <path d="M20 12H4m6-6-6 6 6 6"/>
            @break
        @case('arrow-right')
            <path d="M4 12h16m-6-6 6 6-6 6"/>
            @break
        @case('check-circle')
            <circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>
            @break
        @case('check')
            <path d="m5 12 4 4L19 6"/>
            @break
        @case('store')
            <path d="M4 10v10h16V10M3 10l2-6h14l2 6M3 10c0 2 3 3 4.5 1 1.5 2 4.5 1 4.5-1 0 2 3 3 4.5 1 1.5 2 4.5 1 4.5-1M9 20v-5h6v5"/>
            @break
        @case('language')
            <path d="M4 5h9M8.5 3v2m-3 4c2 3 5 5 8 6m-1-8c-1 4-4 7-8 9M15 20l3-8 3 8m-5-3h4"/>
            @break
        @case('cloud')
            <path d="M6 19h12a4 4 0 0 0 .5-8A7 7 0 0 0 5 9a5 5 0 0 0 1 10Z"/>
            @break
        @case('truck')
            <path d="M3 6h11v11H3zM14 10h4l3 3v4h-7"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/>
            @break
        @case('shirt')
            <path d="m8 4-5 3 3 5 2-1v9h8v-9l2 1 3-5-5-3c-1 2-7 2-8 0Z"/>
            @break
        @case('map-pin')
            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>
            @break
        @default
            <rect x="4" y="4" width="16" height="16" rx="2"/>
    @endswitch
</svg>
