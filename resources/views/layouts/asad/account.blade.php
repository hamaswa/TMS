@include('layouts.asad.header')

<style>
    .centered {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 90vh;
    }
</style>

<div class="container">
    <div class="row justify-content-center centered">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h1 class="text-center">
                        @php
                            echo auth()->user()->name. " Account Details";
                        @endphp
                    </h1>
                </div>
                <div class="card-body">
                        <div class="card-text" style="font-size: 24px;">
                            <p><strong>Name:</strong> {{ $user->name }}</p>
                            <p><strong>Email:</strong> {{ $user->email }}</p>
                            <a href="{{route('user.customers.edit',['id'=>$user->id])}}" class="btn btn-primary">Update Account</a>
                            <a href="#" class="btn btn-danger">Delete Account</a>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('layouts.asad.footer')
