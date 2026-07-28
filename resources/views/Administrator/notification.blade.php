@extends('main')
@section('content')
<section class="main-content">
    <div class="container py-4">
        <div class="card mx-auto" style="max-width:760px">
            <div class="card-body p-4">
                @include('inc.message')
                <h1 class="h3 mb-4">Send notification</h1>
                <form method="POST" action="{{ route('administrator.send') }}">
                    @csrf
                    <input type="hidden" value="{{ $id }}" name="id">
                    <div class="form-group">
                        <label for="notification_title">Title</label>
                        <input id="notification_title" type="text" class="form-control" name="title" required value="{{ old('title') }}">
                        @error('title')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="notification_body">Message</label>
                        <textarea id="notification_body" class="form-control" name="body" rows="5" required>{{ old('body') }}</textarea>
                        @error('body')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Send notification</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
