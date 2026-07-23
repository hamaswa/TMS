<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Shops</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 15px;
            border-bottom: none;
        }

        .card-body {
            text-align: center;
        }

        .img-fluid {
            transition: transform 0.35s ease-in-out;
            max-width: 200px;
            border-radius: 50%;
        }

        .img-fluid:hover {
            cursor: pointer;
            transform: scale(1.1);
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        .container {
            padding-top: 40px;
            padding-bottom: 40px;
        }

        .card-text {
            margin: 10px 0;
            font-size: 20px;
            font-weight: 500;
        }

        h2 {
            font-size: 2em;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">"Shop Whatever You Want from Wherever You Want"</h2>
        <div class="row">
            @foreach($shops as $shop)
            <a href="{{route('user.selling',['slug'=>$shop->shop_slug])}}" style="text-decoration: none;color:#000;">
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">{{$shop->name}}</h5>
                    </div>
                    <div class="card-body">
                        @if($shop->logo_url)
                            <img src="{{ $shop->logo_url }}" class="img-fluid mb-3" alt="{{ $shop->name }} لوگو">
                        @endif
                        <p class="card-text"><b>Address:</b> {{$shop->address}}</p>
                        <p class="card-text"><b>Contact No:</b> {{$shop->contact_no}}</p>
                        <a href="{{route('user.selling',['slug'=>$shop->shop_slug])}}" class="btn btn-primary">Go to Shop</a>
                    </div>
                </div>
            </div>
        </a>
            @endforeach
        </div>
    </div>
    <!-- Bootstrap JS (Optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
