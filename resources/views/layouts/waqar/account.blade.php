@include('layouts.waqar.header')

<style>
    .centered {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 90vh;
        background-color: #f8f9fa;
    }

    .card {
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: #007bff;
        color: #fff;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    .card-body {
        padding: 20px;
    }

    .card-text p {
        font-size: 18px;
        margin-bottom: 15px;
    }

    .btn-primary {
        margin-right: 10px;
    }

    .btn {
        border-radius: 4px;
        padding: 10px 20px;
        font-size: 16px;
    }
</style>

<div class="container">
    <div class="row justify-content-center centered">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h1 class="text-center mb-0">
                        Account Details
                        {{-- @php
                            echo auth()->user()->name. " Account Details";
                        @endphp --}}
                    </h1>
                </div>
                <div class="card-body">
                    <div class="card-text" style="font-size: 24px;">
                        <p><strong>Name:</strong> {{ $user->name }}</p>
                        <p><strong>Email:</strong> {{ $user->email }}</p>
                        <a href="{{ route('user.customers.edit', ['id' => $user->id]) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Update Account
                        </a>
                        <a class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Delete Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('.btn-danger').on('click', async function(e) {
            e.preventDefault(); // Prevent the default action of the link

            if (await window.TmsConfirm.ask(
                'Are you sure you want to delete your account? You will lose all your data.', {
                    title: 'Delete account',
                    acceptLabel: 'Delete account',
                    trigger: this
                })) {
                $.ajax({
                    url: "{{ route('user.customers.delete', ['slug' => $slug, 'id' => $user->id]) }}",
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        alert(response);
                        window.location.href = "{{route('login')}}";
                    },
                    error: function(xhr, status, error) {
                        alert('An error occurred: ' + error);
                    }
                });
            }
        });
    });
</script>
@include('layouts.waqar.footer')
