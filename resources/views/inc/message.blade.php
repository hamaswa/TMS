<div id="message">
@if (Session::has('insert'))
    <div class="alert alert-success">{{ Session::get('insert') }}</div>
@endif

@if (Session::has('update'))
    <div class="alert alert-warning">{{ Session::get('update') }}</div>
@endif

@if (Session::has('delete'))
    <div class="alert alert-danger">{{ Session::get('delete') }}</div>
@endif
@if(Session::has('balanceError'))
    <div class="alert alert-danger">
        {{ Session::get('balanceError') }}
    </div>
@endif
</div>
