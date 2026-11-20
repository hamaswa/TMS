@foreach ($profiles as $profile)
    <option value="{{ $profile->id }}" data-measurement-url="{{ route('admin.counter-orders.measurements.edit', [$counterOrder, $profile]) }}"
        data-template-ids="{{ implode(',', $profileTemplateIds->get($profile->id, [])) }}"
        @selected((int) $selectedProfileId === $profile->id)>{{ $profile->name }}</option>
@endforeach
