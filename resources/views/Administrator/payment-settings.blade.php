@extends('main')

@section('content')
<div class="container-fluid py-4" style="max-width:760px">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Payment settings</h1>
            <p class="text-muted mb-0">Shops see these Easypaisa details when they choose a plan, then send you the transaction ID.</p>
        </div>
        <a href="{{ route('administrator.subscriptions.index', ['state' => 'requested']) }}" class="btn btn-outline-primary mt-2 mt-md-0">Payment requests</a>
    </div>

    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="POST" action="{{ route('administrator.payment-settings.update') }}" enctype="multipart/form-data" class="card">
        @csrf @method('PUT')
        <div class="card-header bg-white d-flex align-items-center"><i class="fas fa-mobile-alt text-success mr-2"></i><h2 class="h5 mb-0">Easypaisa</h2></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="account_name">Account name</label>
                    <input id="account_name" name="account_name" class="form-control" maxlength="100" required placeholder="As shown in the Easypaisa app" value="{{ old('account_name', $accountName) }}">
                </div>
                <div class="form-group col-md-6">
                    <label for="number">Easypaisa number</label>
                    <input id="number" name="number" class="form-control" inputmode="tel" required placeholder="0300-1234567" value="{{ old('number', $number) }}">
                </div>
            </div>
            <div class="form-group mb-0">
                <label for="qr">QR code <span class="text-muted">(optional)</span></label>
                <div class="d-flex flex-wrap align-items-start" style="gap:1rem">
                    @if($hasQr)
                        <img src="{{ route('platform.easypaisa-qr') }}?v={{ now()->timestamp }}" alt="Current Easypaisa QR code" class="border rounded" style="width:140px;height:140px;object-fit:contain">
                    @endif
                    <div class="flex-grow-1">
                        <input id="qr" type="file" name="qr" class="form-control-file" accept="image/png,image/jpeg,image/webp">
                        <small class="form-text text-muted">PNG or JPG, up to 2 MB. Save it from Easypaisa app → My QR.</small>
                        @if($hasQr)
                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="remove_qr" name="remove_qr" value="1">
                                <label class="custom-control-label" for="remove_qr">Remove the current QR code</label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white text-right"><button class="btn btn-primary">Save</button></div>
    </form>
</div>
@endsection
