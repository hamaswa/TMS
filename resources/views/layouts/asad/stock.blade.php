@include('layouts.asad.header')


<style>
    .container-fluid {
        min-height: 100vh;
        padding: 20px;
        background: #f8f9fa;
    }

    .search-icon {
        position: absolute;
        top: 4px;
        right: 6%;
        padding: 8px;
        border-radius: 50%;
        cursor: pointer;
        z-index: 2;
    }

    .search-icon i {
        color: #fff;
        font-size: 22px;
        padding: 4px;
    }


    /* Style for the search form */
    .search-form {
        position: absolute;
        top: 10%;
        right: 5%;
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: opacity 0.3s ease, max-height 0.3s ease;
        /* Smooth transition */
        z-index: 0;
        opacity: 0;
        display: none;
    }

    .search-form.active {
        z-index: 1;
        opacity: 1;
        display: block;
    }

    .search-form:hover,
    .search-form:focus-within {
        z-index: 1;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .form-control {
        width: 200px;
        /* Adjust width as needed */
    }

    .form-control,
    .btn-primary {
        border-radius: 5px;
    }

    .box {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        margin-top: 50px;
        z-index: 0;
    }

    .card {
        width: 300px;
        margin: 10px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        overflow: hidden;
        background: #fff;
    }

    .card img {
        margin-bottom: 10px;
        width: 100%;
        height: 250px;
        transition: transform 0.2s ease-in-out;
    }

    .card-title {
        font-weight: bold;
        font-size: 18px;
        margin-bottom: 10px;
        color: #333;
    }

    .card-text {
        font-size: 16px;
        font-weight: 500;
        margin-bottom: 10px;
        color: #666;
    }

    .card:hover {
        transform: translateY(-10px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        cursor: pointer;
    }

    .card:hover img {
        transform: scale(1.05);
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #004085;
    }

    @media (max-width: 768px) {
        .search-form {
            position: static;
            margin: 20px auto;
            width: 90%;
        }

        .form-control {
            width: 100%;
        }

        .card {
            width: 90%;
            margin: 10px auto;
        }
    }
</style>

<div class="container-fluid">
    <!-- Search icon -->
    <div class="search-icon" id="searchIcon">
        <i class="fas fa-search"></i>
    </div>

    <!-- Search form -->
    <div class="search-form" id="searchForm">
        <form action="{{ route('user.stock.search', ['slug' => $slug]) }}" method="POST">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="brand">Brand</label>
                    <select class="form-control" id="brand" name="brand">
                        <option value="{{ $brand_name->id }}">{{ $brand_name->name }}</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="type">Cloth Type</label>
                    <select class="form-control" id="type" name="type">
                        <option value="">Select Type</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}"
                                {{ isset($type_id) && $type_id == $type->id ? 'selected' : '' }}>{{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="color">Color</label>
                    <select class="form-control" id="color" name="color">
                        <option value="">Select Color</option>
                        @foreach ($color as $colorValue)
                            <option value="{{ $colorValue }}"
                                {{ isset($colorName) && $colorName == $colorValue ? 'selected' : '' }}>
                                {{ $colorValue }}</option>
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
                <p>No data found for the searched result.</p>
            </div>
        @else
            @foreach ($stocks as $stock)
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <img src="{{ asset('/storage/' . $stock->image) }}" alt="Product Image"
                                title="{{ $stock->type->name }}">
                            <h5 class="card-title"><strong>Brand Name:</strong> {{ $stock->brand->name }}</h5>
                            <p class="card-text"><strong>Cloth Type:</strong> {{ $stock->type->name }}</p>
                            <p class="card-text"><strong>Color:</strong> {{ $stock->color }}</p>
                            <a href="{{ route('user.customer.stock.show', ['slug' => $slug, 'brand_id' => $stock->cloth_brand_id, 'type_id' => $stock->cloth_type_id, 'color' => $stock->color]) }}"
                                class="btn btn-primary">Check Details</a>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var searchIcon = document.getElementById('searchIcon');
        var searchForm = document.getElementById('searchForm');

        searchIcon.addEventListener('click', function() {
            if (searchForm.classList.contains('active')) {
                searchForm.classList.remove('active');
                setTimeout(function() {
                    searchForm.style.display = 'none';
                    searchIcon.style.background = '';
                }, 300); // Match this duration to the CSS transition duration
            } else {
                searchForm.style.display = 'block';
                setTimeout(function() {
                    searchForm.classList.add('active');
                    searchIcon.style.background = '#007bff';
                }, 10); // Add a slight delay to allow the display to be set before adding the class
            }
        });
    });
</script>

@include('layouts.asad.footer')
