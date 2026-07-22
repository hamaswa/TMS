@include('layouts.waqar.header')


<style>
    .container-fluid {
        min-height: 100vh;
        padding: 20px;
        background: #f8f9fa;
        overflow: hidden;
    }

    .search-icon {
        position: absolute;
        top: 7px;
        right: 4%;
        padding: 10px;
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
        top: 15%;
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
        width: 100%;
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
        height: auto;
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

    .color-box {
        width: 30px;
        height: 30px;
        margin: 5px;
        cursor: pointer;
        border: 1px solid #ddd;
        border-radius: 50%;
        display: inline-block;
    }



    @media (max-width: 576px) {
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

        .search-icon {
            margin-right: 60px;
            top: 12px;
        }

        .search-icon i {
            font-size: 18px;
        }
    }

    @media (min-width: 768px) {
        .search-form {
            position: static;
            margin: 20px auto;
            width: 90%;
        }

        .form-control {
            width: 100%;
        }

        .search-icon {
            margin-right: 60px;
            top: 12px;
        }

        .search-icon i {
            font-size: 18px;
        }

        .card {
            width: 100%;
            height: 100%;
            max-height: 280px;
        }

        .card-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            color: #333;
        }

        .card-text {
            font-size: 12px;
            font-weight: 400;
            margin-bottom: 10px;
            color: #666;
        }
    }

    /* Large devices (desktops, 992px and up) */
    @media (min-width: 992px) {
        .search-icon {
            top: 12px;
        }

        .search-icon i {
            font-size: 24px;
        }

        .card {
            width: 100%;
            height: 100%;
            max-height: 650px;
        }

        .card-title {
            font-weight: bold;
            font-size: 20px;
            margin-bottom: 10px;
            color: #333;
        }

        .card-text {
            font-size: 16px;
            font-weight: 400;
            margin-bottom: 10px;
            color: #666;
        }
    }
</style>

<div class="container-fluid mt-2">
    <!-- Search icon -->
    <div class="search-icon" id="searchIcon">
        <i class="fas fa-search" title="search"></i>
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
                                {{ request()->input('type') == $type->id ? 'selected' : '' }}>{{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="color">Color</label>
                    <select class="form-control" id="color" name="color">
                        <option value="">Select Color</option>
                        @foreach ($colors as $color)
                            <option value="{{ $color }}"
                                {{ request()->input('color') == $color ? 'selected' : '' }}>{{ $color }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    <h2 class="text-center">Stock of {{ $brand_name->name }}</h2>

    <div class="row box">
        @if ($Stocks->isEmpty())
            <div class="col-md-12 text-center">
                <p>No data found for the searched result.</p>
            </div>
        @else
            @foreach ($Stocks as $stock)
                @foreach ($stock->colors as $color)
                    <div class="col-md-3 mb-4">
                        {{-- Making whole card as a link --}}
                        <a href="{{ route('user.customer.stock.show', ['slug' => $slug, 'brand_id' => $stock->cloth_brand_id, 'type_id' => $stock->cloth_type_id,'color' => $color->color]) }}"
                            style="text-decoration: none;">
                            <div class="card">
                                <div class="card-body">
                                    @php
                                        $image = $stock->images->firstWhere('image_color', $color->color);
                                    @endphp
                                    <img src="{{ asset('public/storage/' . $image->images) }}" alt="Cloth Image">
                                    <p class="card-title"><strong>Cloth Type:</strong> {{ $stock->type->name }}</p>
                                    <p class="card-title"><strong>Color:</strong> {{ $image->image_color }}</p>
                                    <a href="{{ route('user.customer.stock.show', [
                                        'slug' => $slug,
                                        'brand_id' => $stock->cloth_brand_id,
                                        'type_id' => $stock->cloth_type_id,
                                        'color' => $color->color
                                    ]) }}" class="btn btn-primary">Check Details</a>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
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
<script>
    // function changeBackgroundColor(select) {
    //     var selectedColor = select.value;
    //     select.style.backgroundColor = selectedColor;
    // }

    // // Initialize background color based on initial selection
    // var initialColor = document.getElementById('color').value;
    // document.getElementById('color').style.backgroundColor = initialColor;
</script>


@include('layouts.waqar.footer')
