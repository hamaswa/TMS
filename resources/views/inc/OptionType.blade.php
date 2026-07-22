<div class="col-md-3">
    <h4 class="mb-4 text-right">آپشن کی قسم</h4>

    <ul class="options-list">
        @foreach($OptionTypes as $OptionType)
        <li class="text-right">
            <a class="option-link" href="{{ url('admin/Options/add',$OptionType->id) }}"><span class="optiontype">{{$OptionType->Name}}</span></a>
        </li>
        @endforeach
    </ul>
</div>