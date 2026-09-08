<label class="sr-only" for="measurement-profile-select">محفوظ ناپ منتخب کریں</label>
<select id="measurement-profile-select" class="form-control" style="height:50px" name="sub_id">
    @forelse($data as $profile)
        <option value="{{ $profile->id }}" data-serial="{{ $profile->id }}">
            #{{ $profile->id }} — {{ $profile->name }} — {{ $profile->phone_number1 }}
        </option>
    @empty
        <option value="">کوئی محفوظ ناپ نہیں ملا</option>
    @endforelse
</select>
