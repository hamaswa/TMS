@extends('cust_main')

@section('content')
    <style>
        .container-fluid {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* Style for the search form */
        .search-form {
            position: absolute;
            top: 15%;
            right: 5%;
        }

        .form-control {
            width: 200px;
            /* Adjust width as needed */
        }

        .box {
            display: flex;
            flex-wrap: wrap;
            /* Allow cards to wrap to the next line */
            justify-content: center;
            /* Center the cards horizontally */
        }

        .card {
            width: 300px;
            margin: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.2s ease-in-out;
            cursor: pointer;
        }

        stock-card img {
            margin-bottom: 10px;
            width: 100%;
        }

        .card-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .card-text {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>

    <div class="container-fluid mt-2">
        <!-- Search form -->
        <div class="search-form">
            <form action="{{ route('user.stock.search',['slug'=>$slug]) }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="brand">Brand</label>
                        <select class="form-control" id="brand" name="brand">
                            <option value="{{ $brand_name->id }}">{{ $brand_name->name }}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="type">ClothType</label>
                        <select class="form-control" id="type" name="type">
                            <option value="">Select Type</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="row box">
            @if ($stocks->isEmpty())
                <div class="col-md-12 text-center">
                    <p>No data found for searched result.</p>
                </div>
            @else
                @foreach ($stocks as $stock)
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <img class="card-img-top mb-2" src="{{ asset('/storage/' . $stock->image) }}"
                                    alt="Product Image" title="{{ $stock->type->name }}">
                                <h5 class="card-title"><strong>Brand Name : </strong>{{ $stock->brand->name }}</h5>
                                <p class="card-text"><strong>Cloth Type:</strong> {{ $stock->type->name }}</p>
                                <p class="card-text"><strong>Color:</strong> {{ $stock->color }}</p>
                                <a href="{{ route('user.customer.stock.show', ['brand_id' => $stock->cloth_brand_id, 'type_id' => $stock->cloth_type_id, 'color' => $stock->color]) }}"
                                    class="btn btn-primary">Check Details</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
