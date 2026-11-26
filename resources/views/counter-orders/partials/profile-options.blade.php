@foreach ($profiles as $profile)
    <option value="{{ $profile->id }}" data-measurement-url="{{ route('admin.counter-orders.measurements.edit', [$counterOrder, $profile]) }}"
        data-template-ids="{{ implode(',', $profileTemplateIds->get($profile->id, [])) }}"
        data-note="{{ $profile->note ?: $profiles->firstWhere('id', $profile->parent_id)?->note }}"
        @selected((int) $selectedProfileId === $profile->id)>{{ $profile->name }}</option>
@endforeach
