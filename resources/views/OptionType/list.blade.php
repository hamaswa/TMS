@extends('main')

@section('content')
@php
    $categoryInfo = [
        1 => ['icon' => 'fa-cut', 'title' => 'سلائی کی قسم', 'help' => 'مثلاً سادہ سلائی، ڈیزائنر سلائی یا واسکٹ۔', 'color' => 'blue'],
        2 => ['icon' => 'fa-dot-circle', 'title' => 'شرٹ کے بٹن', 'help' => 'شرٹ پر لگنے والے بٹن کا انداز یا قسم۔', 'color' => 'green'],
        3 => ['icon' => 'fa-user-tie', 'title' => 'گلے کا ڈیزائن', 'help' => 'مثلاً بین، کالر یا گول گلہ۔', 'color' => 'purple'],
        4 => ['icon' => 'fa-hand-paper', 'title' => 'کف کا ڈیزائن', 'help' => 'آستین کے کف یا کھلے بازو کی قسم۔', 'color' => 'orange'],
        5 => ['icon' => 'fa-wallet', 'title' => 'جیب کا ڈیزائن', 'help' => 'جیب کی جگہ، شکل یا سلائی کا انداز۔', 'color' => 'cyan'],
        6 => ['icon' => 'fa-circle', 'title' => 'عام بٹن', 'help' => 'لباس میں استعمال ہونے والے دوسرے بٹن۔', 'color' => 'red'],
        7 => ['icon' => 'fa-grip-lines', 'title' => 'پلیٹ کا ڈیزائن', 'help' => 'شرٹ کی سامنے والی پلیٹ کا انداز۔', 'color' => 'indigo'],
        8 => ['icon' => 'fa-chevron-down', 'title' => 'دامن کا ڈیزائن', 'help' => 'قمیض کے نیچے دامن کی شکل یا قسم۔', 'color' => 'teal'],
    ];
@endphp
<style>
    .oc-page{--oc-blue:#1769e0;--oc-navy:#102a50;--oc-muted:#6d7f94;--oc-line:#e0e8f2;direction:rtl;padding:28px 0 50px}.oc-shell{width:min(100% - 32px,1250px);margin-inline:auto}
    .oc-head{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:18px}.oc-title{display:flex;align-items:center;gap:14px}.oc-title-icon{display:grid;place-items:center;width:56px;height:56px;border-radius:16px;color:#fff;background:linear-gradient(135deg,#2479ee,#0c5bd1);font-size:21px;box-shadow:0 9px 20px rgba(23,105,224,.2)}.oc-title h1{margin:0 0 4px;color:var(--oc-navy);font-size:clamp(1.45rem,2vw,1.9rem);font-weight:800}.oc-title p{margin:0;color:var(--oc-muted);font-size:.84rem}.oc-head-actions{display:flex;gap:9px}.oc-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:8px 14px;border:1px solid #d5dfeb;border-radius:10px;color:#40566f;background:#fff;font-weight:800;text-decoration:none!important}.oc-btn:hover{color:var(--oc-blue);border-color:#a9c9f3}.oc-btn.is-primary{color:#fff;border-color:var(--oc-blue);background:var(--oc-blue)}
    .oc-guide{display:flex;align-items:flex-start;gap:12px;padding:16px 18px;margin-bottom:18px;border:1px solid #d8e8fb;border-radius:14px;color:#53667e;background:#f3f8ff}.oc-guide>i{display:grid;place-items:center;flex:0 0 40px;width:40px;height:40px;border-radius:11px;color:var(--oc-blue);background:#fff;font-size:18px}.oc-guide strong{display:block;margin-bottom:3px;color:var(--oc-navy);font-size:.88rem}.oc-guide span{display:block;font-size:.76rem;line-height:1.8}
    .oc-section-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:13px}.oc-section-head h2{margin:0 0 3px;color:var(--oc-navy);font-size:1.15rem;font-weight:800}.oc-section-head p{margin:0;color:var(--oc-muted);font-size:.75rem}.oc-count{padding:6px 11px;border-radius:999px;color:var(--oc-blue);background:#eaf3ff;font-weight:800}
    .oc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.oc-card{overflow:hidden;border:1px solid var(--oc-line);border-radius:16px;background:#fff;box-shadow:0 7px 24px rgba(21,47,81,.05);transition:transform .18s ease,border-color .18s ease}.oc-card:hover{transform:translateY(-2px);border-color:#bed5f3}.oc-card-main{display:flex;align-items:flex-start;gap:13px;padding:18px}.oc-card-icon{display:grid;place-items:center;flex:0 0 48px;width:48px;height:48px;border-radius:14px;font-size:19px}.oc-card-icon.blue{color:#1769e0;background:#eaf3ff}.oc-card-icon.green{color:#148052;background:#e7f7ef}.oc-card-icon.purple{color:#8654d5;background:#f1ebff}.oc-card-icon.orange{color:#d17d08;background:#fff3dd}.oc-card-icon.cyan{color:#087f98;background:#e5f8fc}.oc-card-icon.red{color:#c44653;background:#fff0f2}.oc-card-icon.indigo{color:#4b5ec7;background:#edf0ff}.oc-card-icon.teal{color:#087d70;background:#e6f7f4}.oc-card-copy{min-width:0;flex:1}.oc-card-copy h3{margin:0 0 5px;color:var(--oc-navy);font-size:1.02rem;font-weight:800}.oc-card-copy p{min-height:42px;margin:0;color:var(--oc-muted);font-size:.73rem;line-height:1.8}.oc-choice-count{display:inline-flex;align-items:center;gap:6px;margin-top:9px;padding:4px 8px;border-radius:999px;color:#526a86;background:#f1f4f8;font-size:.68rem;font-weight:800}.oc-card-action{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 16px;border-top:1px solid #edf1f6;background:#fbfdff}.oc-open{display:inline-flex;align-items:center;justify-content:center;gap:7px;flex:1;min-height:38px;padding:7px 12px;border-radius:9px;color:#fff;background:var(--oc-blue);font-weight:800;text-decoration:none!important}.oc-open:hover{color:#fff;background:#0e5bc9}.oc-system{display:inline-flex;align-items:center;gap:5px;color:#76869a;font-size:.68rem}.oc-card-tools{display:flex;gap:5px}.oc-tool{display:grid;place-items:center;width:35px;height:35px;border:1px solid #d9e2ed;border-radius:8px;color:#5b6d84;background:#fff;text-decoration:none!important}.oc-tool:hover{color:var(--oc-blue)}.oc-delete{color:#c23b48;border-color:#f0cbd0;background:#fff7f8}.oc-empty{grid-column:1 / -1;padding:50px 20px;border:1px dashed #cfdbe9;border-radius:16px;color:var(--oc-muted);background:#fbfdff;text-align:center}.oc-empty i{display:block;margin-bottom:10px;color:#aab9cb;font-size:2rem}.oc-flash{padding:13px 16px;margin-bottom:16px;border-radius:11px}.oc-flash.is-warning{border:1px solid #f2deb0;color:#8a620c;background:#fff8e7}.oc-flash.is-danger{border:1px solid #f0c7cc;color:#a52c38;background:#fff4f5}
    .oc-flash.is-success{border:1px solid #c9eadb;color:#146e46;background:#ecf9f3}.oc-modal .modal-dialog{max-width:min(680px,calc(100% - 24px))}.oc-modal .modal-content{overflow:hidden;border:0;border-radius:17px;box-shadow:0 22px 65px rgba(15,38,70,.22)}.oc-modal .modal-header{align-items:center;padding:17px 20px;border-bottom:1px solid var(--oc-line);background:#f7faff}.oc-modal-title{display:flex;align-items:center;gap:11px}.oc-modal-title .oc-card-icon{width:42px;height:42px;flex-basis:42px;font-size:16px}.oc-modal-title h2{margin:0 0 2px;color:var(--oc-navy);font-size:1.08rem;font-weight:800}.oc-modal-title p{margin:0;color:var(--oc-muted);font-size:.7rem}.oc-modal .close{margin:-1rem auto -1rem -1rem}.oc-modal .modal-body{padding:0;text-align:right}.oc-add-choice{padding:18px 20px;border-bottom:1px solid var(--oc-line);background:#fbfdff}.oc-add-choice label{display:block;margin-bottom:7px;color:#344a67;font-size:.8rem;font-weight:800}.oc-add-row{display:flex;gap:8px}.oc-add-row .form-control{min-height:44px;border-color:#d3deeb;border-radius:9px}.oc-choice-save{display:inline-flex;align-items:center;gap:6px;min-height:44px;padding:8px 15px;border:0;border-radius:9px;color:#fff;background:#15915a;font-weight:800;white-space:nowrap}.oc-choice-list-head{display:flex;align-items:center;justify-content:space-between;padding:13px 20px;color:var(--oc-navy);font-weight:800}.oc-choice-list{max-height:330px;overflow:auto;border-top:1px solid #edf1f6}.oc-choice-row{display:flex;align-items:center;gap:10px;padding:11px 20px;border-bottom:1px solid #edf1f6}.oc-choice-row:last-child{border-bottom:0}.oc-choice-name{display:flex;align-items:center;gap:9px;min-width:0;flex:1;color:var(--oc-navy);font-weight:800}.oc-choice-name i{color:#15915a}.oc-choice-name span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.oc-choice-edit{position:relative}.oc-choice-edit summary{display:grid;place-items:center;width:34px;height:34px;border:1px solid #d9e2ed;border-radius:8px;color:var(--oc-blue);background:#fff;cursor:pointer;list-style:none}.oc-choice-edit summary::-webkit-details-marker{display:none}.oc-inline-edit{position:absolute;z-index:3;top:40px;left:0;display:flex;gap:6px;width:310px;padding:9px;border:1px solid #d7e1ed;border-radius:10px;background:#fff;box-shadow:0 12px 30px rgba(18,45,79,.16)}.oc-inline-edit .form-control{min-height:38px}.oc-inline-save{display:grid;place-items:center;flex:0 0 38px;width:38px;border:0;border-radius:8px;color:#fff;background:#15915a}.oc-choice-delete{display:grid;place-items:center;width:34px;height:34px;border:1px solid #f0cbd0;border-radius:8px;color:#c23b48;background:#fff7f8}.oc-choice-empty{padding:32px 20px;color:var(--oc-muted);text-align:center}.oc-choice-empty i{display:block;margin-bottom:8px;color:#b2bfd0;font-size:1.6rem}.oc-modal .modal-footer{padding:12px 20px;border-top:1px solid var(--oc-line)}
    @media(max-width:991px){.oc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:767px){.oc-page{padding-top:18px}.oc-shell{width:min(100% - 20px,1250px)}.oc-head{align-items:flex-start;flex-direction:column}.oc-head-actions{width:100%}.oc-btn{flex:1}.oc-grid{grid-template-columns:1fr}.oc-guide{padding:14px}.oc-card-copy p{min-height:0}}
</style>

<section class="main-content oc-page">
    <div class="oc-shell">
        <header class="oc-head">
            <div class="oc-title"><span class="oc-title-icon"><i class="fas fa-sliders-h"></i></span><div><h1>سلائی اور ڈیزائن کے انتخاب</h1><p>وہ نام ترتیب دیں جو آرڈر بناتے وقت فہرست میں دکھائی دیتے ہیں۔</p></div></div>
            <div class="oc-head-actions"><a href="{{ route('admin.measurement-templates.index') }}" class="oc-btn is-primary"><i class="fas fa-clipboard-list"></i> پیمائش فارم</a><a href="{{ route('admin.measurement-fields.index') }}" class="oc-btn"><i class="fas fa-ruler"></i> اپنی پیمائش کے خانے</a></div>
        </header>

        @if(session('success'))<div class="oc-flash is-success"><i class="fas fa-check-circle ml-1"></i>{{ session('success') }}</div>@endif
        @if(session('update'))<div class="oc-flash is-warning"><i class="fas fa-check-circle ml-1"></i>{{ session('update') }}</div>@endif
        @if(session('del'))<div class="oc-flash is-danger"><i class="fas fa-info-circle ml-1"></i>{{ session('del') }}</div>@endif
        @if(session('insert'))<div class="oc-flash is-warning"><i class="fas fa-info-circle ml-1"></i>{{ session('insert') }}</div>@endif
        @if($errors->any())<div class="oc-flash is-danger"><i class="fas fa-exclamation-circle ml-1"></i>@foreach($errors->all() as $error)<span class="d-block">{{ $error }}</span>@endforeach</div>@endif

        <div class="oc-guide"><i class="fas fa-lightbulb"></i><div><strong>یہ صفحہ کیسے استعمال کریں؟</strong><span>جس چیز کے انتخاب شامل کرنے ہوں، اس کا خانہ کھولیں۔ مثال کے طور پر “گلے کا ڈیزائن” کھول کر بین، کالر یا گول گلہ شامل کریں۔ یہی انتخاب بعد میں گاہک اور آرڈر کے فارم میں نظر آئیں گے۔</span></div></div>

        <div class="oc-section-head"><div><h2>کیا ترتیب دینا ہے؟</h2><p>نیچے مطلوبہ حصہ منتخب کریں۔</p></div><span class="oc-count">{{ $OptionTypes->count() }} حصے</span></div>
        <div class="oc-grid">
            @forelse($OptionTypes as $optionType)
                @php($info = $categoryInfo[$optionType->id] ?? ['icon'=>'fa-list-ul','title'=>$optionType->Name,'help'=>'اس حصے کے انتخاب اور نام ترتیب دیں۔','color'=>'blue'])
                <article class="oc-card">
                    <div class="oc-card-main"><span class="oc-card-icon {{ $info['color'] }}"><i class="fas {{ $info['icon'] }}"></i></span><div class="oc-card-copy"><h3>{{ $info['title'] }}</h3><p>{{ $info['help'] }}</p><span class="oc-choice-count"><i class="fas fa-list"></i>{{ $optionType->choices_count }} محفوظ انتخاب</span></div></div>
                    <div class="oc-card-action">
                        <button class="oc-open border-0" type="button" data-toggle="modal" data-target="#choiceModal_{{ $optionType->id }}"><i class="fas fa-folder-open"></i> انتخاب دیکھیں یا شامل کریں</button>
                        @if(is_null($optionType->user_id))
                            <span class="oc-system"><i class="fas fa-lock"></i> تیار حصہ</span>
                        @else
                            <div class="oc-card-tools"><a class="oc-tool" href="{{ route('admin.OptionType.edit',$optionType) }}" aria-label="{{ $optionType->Name }} کا نام تبدیل کریں"><i class="fas fa-edit"></i></a><form method="POST" action="{{ route('admin.OptionType.destroy',$optionType) }}" data-confirm="کیا آپ واقعی یہ پورا حصہ حذف کرنا چاہتے ہیں؟">@csrf @method('DELETE')<button class="oc-tool oc-delete" type="submit" aria-label="{{ $optionType->Name }} حذف کریں"><i class="fas fa-trash-alt"></i></button></form></div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="oc-empty"><i class="fas fa-folder-open"></i>کوئی سلائی یا ڈیزائن حصہ موجود نہیں۔</div>
            @endforelse
        </div>

        @foreach($OptionTypes as $optionType)
            @php($info = $categoryInfo[$optionType->id] ?? ['icon'=>'fa-list-ul','title'=>$optionType->Name,'help'=>'اس حصے کے انتخاب اور نام ترتیب دیں۔','color'=>'blue'])
            <div class="modal fade oc-modal" id="choiceModal_{{ $optionType->id }}" tabindex="-1" role="dialog" aria-labelledby="choiceModalTitle_{{ $optionType->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
                    <div class="modal-header"><div class="oc-modal-title"><span class="oc-card-icon {{ $info['color'] }}"><i class="fas {{ $info['icon'] }}"></i></span><div><h2 id="choiceModalTitle_{{ $optionType->id }}">{{ $info['title'] }} کے انتخاب</h2><p>نیا نام شامل کریں یا پہلے سے موجود نام تبدیل کریں۔</p></div></div><button type="button" class="close" data-dismiss="modal" aria-label="بند کریں"><span aria-hidden="true">&times;</span></button></div>
                    <div class="modal-body">
                        <form class="oc-add-choice" action="{{ route('admin.Options.store') }}" method="POST">@csrf<input type="hidden" name="OptionTypeId" value="{{ $optionType->id }}"><label for="newChoice_{{ $optionType->id }}"><i class="fas fa-plus-circle text-success ml-1"></i> نیا انتخاب شامل کریں</label><div class="oc-add-row"><input id="newChoice_{{ $optionType->id }}" class="form-control" name="Name" value="{{ (string)old('OptionTypeId')===(string)$optionType->id ? old('Name') : '' }}" maxlength="255" required placeholder="مثلاً {{ $optionType->id === 1 ? 'سادہ سلائی' : $info['title'] }}"><button class="oc-choice-save" type="submit"><i class="fas fa-check"></i> شامل کریں</button></div></form>
                        <div class="oc-choice-list-head"><span>پہلے سے محفوظ نام</span><span class="oc-count">{{ $optionType->options->count() }}</span></div>
                        <div class="oc-choice-list">
                            @forelse($optionType->options as $choice)
                                <div class="oc-choice-row"><div class="oc-choice-name"><i class="fas fa-check-circle"></i><span>{{ $choice->Name }}</span></div><details class="oc-choice-edit" @if((int)session('editChoice')===$choice->id) open @endif><summary aria-label="{{ $choice->Name }} کا نام بدلیں"><i class="fas fa-edit"></i></summary><form class="oc-inline-edit" method="POST" action="{{ route('admin.Options.update',$choice) }}">@csrf @method('PUT')<input type="hidden" name="OptionTypeId" value="{{ $optionType->id }}"><input class="form-control" name="Name" value="{{ $choice->Name }}" required maxlength="255"><button class="oc-inline-save" type="submit" aria-label="محفوظ کریں"><i class="fas fa-check"></i></button></form></details><form method="POST" action="{{ route('admin.Options.destroy',$choice) }}" data-confirm="کیا آپ واقعی '{{ $choice->Name }}' حذف کرنا چاہتے ہیں؟">@csrf @method('DELETE')<button class="oc-choice-delete" type="submit" aria-label="{{ $choice->Name }} حذف کریں"><i class="fas fa-trash-alt"></i></button></form></div>
                            @empty
                                <div class="oc-choice-empty"><i class="fas fa-inbox"></i>ابھی کوئی انتخاب شامل نہیں کیا گیا۔</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="oc-btn" data-dismiss="modal"><i class="fas fa-times"></i> بند کریں</button></div>
                </div></div>
            </div>
        @endforeach
    </div>
</section>
@php($modalToOpen = old('OptionTypeId') ?: session('openChoiceModal'))
@if($modalToOpen)
<script>$(function () { $('#choiceModal_{{ (int)$modalToOpen }}').modal('show'); });</script>
@endif
@endsection
