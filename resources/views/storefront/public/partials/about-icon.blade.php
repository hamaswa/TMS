@php($icon = $name ?? 'star')
<svg class="about-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">
    @switch($icon)
        @case('shield') <path d="M12 3 20 6v5c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-3Z"/><path d="m8 12 2.5 2.5L16 9"/> @break
        @case('fabric') <rect x="4" y="3" width="11" height="18" rx="2"/><path d="M8 3v18m7-15h3a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2h-3"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/> @break
        @case('pin') <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/> @break
        @case('star') <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-2.9-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"/> @break
        @case('users') <circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M3 20c0-4 2-6 6-6s6 2 6 6m0-5c3 0 5 2 5 5"/> @break
        @case('store') <path d="M4 10v10h16V10M3 10l2-6h14l2 6M3 10c0 2 3 3 4.5 1 1.5 2 4.5 1 4.5-1 0 2 3 3 4.5 1 1.5 2 4.5 1 4.5-1M9 20v-5h6v5"/> @break
        @case('shirt') <path d="m8 4-5 3 3 5 2-1v9h8v-9l2 1 3-5-5-3c-1 2-7 2-8 0Z"/> @break
        @case('target') <circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/><path d="m15 9 5-5"/> @break
        @case('seedling') <path d="M12 21v-9M12 14c-5 0-8-3-8-8 5 0 8 3 8 8Zm0 3c0-5 3-8 8-8 0 5-3 8-8 8Z"/> @break
        @case('arrow') <path d="M4 12h16m-6-6 6 6-6 6"/> @break
        @case('scissors') <path d="M8.4 7.9 19 3M8.4 16.1 19 21M8.2 12H20"/><circle cx="5" cy="8" r="3"/><circle cx="5" cy="16" r="3"/> @break
    @endswitch
</svg>
