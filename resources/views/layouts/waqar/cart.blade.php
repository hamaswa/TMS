@include('layouts.waqar.header')
<style>
    .main{
        min-height: 100vh;
    }
    @media(max-width:576px){
        .table thead>tr{
            flex-direction: column;
        }
    }
</style>
<div class="container main">
    <h2 class="mt-4 text-center mb-5">Cart Items</h2>
    <div class="row justify-content-center">
        <div class="col-12 col-md-10">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr class="text-center">
                            <th>Shop Name</th>
                            <th>Brand Name</th>
                            <th>Cloth Type</th>
                            <th>Length</th>
                            {{-- <th>Price per Meter</th> --}}
                            <th>Color</th>
                            <th>Total Price</th>
                            <th colspan="2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cart_records as $records)
                        <tr class="text-center">
                            <td><b>{{ $records->shop_name}}</b></td>
                            <td><b>{{ $records->cloth->brand->name }}</b></td>
                            <td><b>{{ $records->cloth->type->name }}</b></td>
                            <td><b>{{ $records->length }}</b></td>
                            {{-- <td><b>{{ $records->price }}</b></td> --}}
                            <td><b>{{ $records->color }}</b></td>
                            <td><b>{{ $records->price * $records->length}}</b></td>
                            <td>
                                <form action="{{ route('user.cart.delete', ['slug' => $slug, 'id' => $records->id]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-primary">Remove</button>
                                </form>
                            </td>
                            <td>
                                <form action="{{ route('user.cart.buy',['slug'=>$slug,'id'=>$records->id]) }}" method="POST">
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
</div>
@include('layouts.waqar.footer')
