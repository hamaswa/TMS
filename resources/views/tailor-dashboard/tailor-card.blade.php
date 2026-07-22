@include('inc/header')
<div class="container mt-5">	
	<div class="row">	
		<div class="col-md-6">		
			<div class="card">
			  <div class="card-header bg-dark"><h5 class="text-white">پچھلہ ہفتہ </h5></div>
			  <div class="card-body" >
				  	پچھلہ ہفتہ سوٹ  <h2 class="d-inline"><span class="badge badge-pill badge-success">{{$suits}}</span></h2>
			  </div>
			 </div>
		</div>
		<div class="col-md-6">		
			<div class="card">
			  <div class="card-header bg-dark"><h5 class="text-white">پچھلہ ہفتہ </h5></div>
			  <div class="card-body" >
				  	پچھلہ ہفتے سوٹ پیسے <h2 class="d-inline"><span class="badge badge-pill badge-success">{{$payments}}</span></h2>
			  </div>
			 </div>
		</div>
	</div>
</div>

@include('inc/footer')
