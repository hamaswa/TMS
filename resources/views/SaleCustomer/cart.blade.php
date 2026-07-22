@include('layouts.waqar.header')
<style>
    .main{
        height: 100vh;
    }
</style>
@section('content')
    <div class="main">
        <h2 class="mt-4 text-center mb-5">Cart Items</h2> <!-- Removed unnecessary quotes around the heading text -->
        <div class="d-flex justify-content-center"> <!-- Center the content horizontally -->
            <div class="table-responsive" style="width: 80%;"> <!-- Adjusted width to 80% -->
                <table class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr style="text-align: center;">
                            <th>User Name</th>
                            <th>Brand Name</th>
                            <th>Cloth Type</th>
                            <th>Length</th>
                            <th>Price per Meter</th>
                            <th>Total Price</th>
                            <th colspan="2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cart_records as $records)
                        <tr style="text-align: center;">
                            <td><b><b>{{ $records->user->name }}</b></td>
                            <td><b>{{ $records->cloth->brand->name }}</b></td>
                            <td><b>{{ $records->cloth->type->name }}</b></td>
                            <td><b>{{ $records->length }}</b></td>
                            <td><b>{{ $records->price }}</b></td>
                            <td><b>{{ $records->price * $records->length}}</b></td>
                            <td>
                                <a href="{{ route('user.cart.delete',['id'=>$records->id]) }}" class="btn btn-primary">Remove</a>
                            </td>
                            <td>
                                <form action="{{ route('user.cart.buy',['id'=>$records->id]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Buy Now</button>
                                </form>
                            </td>

                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@include('layouts.waqar.footer')
