@include('inc/header')
<div class="container mt-5">
	<div class="row">
		<div class="col-md-4 mb-3">
			<div class="card">
			  <div class="card-header bg-dark"><h5 class="text-white">پچھلہ ہفتہ </h5></div>
			  <div class="card-body" >
				  	پچھلہ ہفتہ سوٹ  <h2 class="d-inline"><span class="badge badge-pill badge-success">{{$suits}}</span></h2>
			  </div>
			 </div>
		</div>
		<div class="col-md-4 mb-3">
			<div class="card">
			  <div class="card-header bg-dark"><h5 class="text-white">پچھلہ ہفتہ </h5></div>
			  <div class="card-body" >
				سلائی کی کمائی <h2 class="d-inline"><span class="badge badge-pill badge-success">روپے {{ number_format($earnings, 2) }}</span></h2>
			  </div>
			 </div>
		</div>
		<div class="col-md-4 mb-3">
			<div class="card">
			  <div class="card-header bg-dark"><h5 class="text-white">پچھلہ ہفتہ </h5></div>
			  <div class="card-body" >
				ادا شدہ رقم <h2 class="d-inline"><span class="badge badge-pill badge-info">روپے {{ number_format($paid, 2) }}</span></h2>
			  </div>
			 </div>
		</div>
	</div>
</div>

@include('inc/footer')
