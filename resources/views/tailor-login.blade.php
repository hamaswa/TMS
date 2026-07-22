<!DOCTYPE html>
<html lang="en">

<head>
    <title>Tailor</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <meta name="author" content="" />

    <link href="https://example.com/" rel="canonical" /> <!-- preferred URL - change for you site -->

    <!--fonts-->
    <link
        href="https://fonts.googleapis.com/css?family=Alegreya+SC:400,700|Permanent+Marker|Abril+Fatface|Poppins:300,400,500,600,700"
        rel="stylesheet">

    <!-- css -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css"
        integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
    <link href="{{ asset('assets/css/bootstrap.min.css')}}" rel="stylesheet">


    <!--<link href="style.css" rel="stylesheet">-->

</head>
<div class="col-md-6 offset-3 mt-5">
	@if(Session::has('failed'))
	<div class="alert alert-danger">{{Session::get('failed')}}</div>
	@endif
<div class="card">
  <div class="card-header bg-primary text-white">Tailor Login</div>
  <div class="card-body">
  <form action="{{url('tailor-login')}}" method="post">
  	@csrf

	  <div class="form-group">
	    <label for="pwd">Phone Number:</label>
	    <input type="number" class="form-control" name="contact" placeholder="Enter Number" id="pwd">
	  </div>
	  <div class="form-group">
	    <label for="email">Password:</label>
	    <input type="password" class="form-control" name="password" placeholder="Enter Password" id="email">
	  </div>
	  <div class="form-group form-check">
	    <label class="form-check-label">
	      <input class="form-check-input" type="checkbox"> Remember me
	    </label>
	  </div>
	  <button type="submit" class="btn btn-primary">Submit</button>
</form>

</div>
</html>
