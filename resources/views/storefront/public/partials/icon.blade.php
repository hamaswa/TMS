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
        @case('search')
            <circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>
            @break
        @case('cart')
            <path d="M3 4h2l2 11h10l3-8H6"/><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/>
            @break
        @case('grid')
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            @break
        @case('bag')
            <path d="M5 8h14l1 13H4L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>
            @break
        @case('tag')
            <path d="M20 13 11 22 2 13V4h9l9 9Z"/><circle cx="7" cy="9" r="1.5"/>
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6"/>
            @break
        @case('chevron-left')
            <path d="m15 18-6-6 6-6"/>
            @break
        @case('phone')
            <path d="M6 3h4l2 5-3 2c2 4 4 6 8 8l2-3 5 2v4c0 2-2 3-4 3C10 23 1 14 2 6c0-2 2-3 4-3Z"/>
            @break
        @case('headset')
            <path d="M4 14v-2a8 8 0 0 1 16 0v7h-5"/><rect x="3" y="13" width="4" height="6" rx="2"/><rect x="17" y="13" width="4" height="6" rx="2"/><path d="M15 21h-3"/>
            @break
        @case('box')
            <path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7M12 11v10"/>
            @break
        @case('copyright')
            <circle cx="12" cy="12" r="9"/><path d="M15 9a4 4 0 1 0 0 6"/>
            @break
        @case('globe')
            <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 4 6 4 9s-1 6-4 9c-3-3-4-6-4-9s1-6 4-9Z"/>
            @break
        @case('wifi')
            <path d="M4 10a12 12 0 0 1 16 0M7 14a7 7 0 0 1 10 0m-7 4a3 3 0 0 1 4 0"/><circle cx="12" cy="21" r="1"/>
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3"/><path d="M19 13.5v-3l-2.1-.7-.8-1.9 1-2-2.1-2.1-2 1-1.9-.8-.6-2.1h-3L7.8 4l-1.9.8-2-1-2.1 2.1 1 2-.8 1.9-2.1.7v3l2.1.7.8 1.9-1 2L3.9 20l2-1 1.9.8.7 2.1h3l.7-2.1 1.9-.8 2 1 2.1-2.1-1-2 .8-1.9 2-.5Z" transform="translate(2) scale(.83)"/>
            @break
        @case('smile')
            <circle cx="12" cy="12" r="9"/><path d="M8 14c1 3 7 3 8 0M9 9h.01M15 9h.01"/>
            @break
        @case('eye')
            <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
            @break
        @case('edit')
            <path d="m4 16-1 5 5-1L20 8l-4-4L4 16Z"/><path d="m14 6 4 4"/>
            @break
        @case('external-link')
            <path d="M14 4h6v6M20 4 10 14"/><path d="M18 13v7H4V6h7"/>
            @break
        @default
            <rect x="4" y="4" width="16" height="16" rx="2"/>
    @endswitch
</svg>
