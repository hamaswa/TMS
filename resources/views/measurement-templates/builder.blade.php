@extends('main')
@section('content')
    @php
        $editing = (bool) $template;
        $selectedSystem = old('system_fields', $template?->system_fields ?? []);
        $selectedCustom = array_map('intval', old('custom_field_ids', $template?->custom_field_ids ?? []));
        $selectedLayout = old('field_layout', $template?->field_layout ?? []);
        $selectedSections = collect(old('layout_sections', $template?->sections() ?? [['title' => null, 'columns' => 2]]))
            ->map(fn ($section) => [
                'title' => $section['title'] ?? null,
                'columns' => (int) ($section['columns'] ?? 2) === 1 ? 1 : 2,
            ])->values()->all();
        $fieldTypeLabels = ['number' => 'نمبر', 'text' => 'تحریر', 'select' => 'فہرست'];
        $fieldUnitLabels = ['inch' => 'انچ', 'cm' => 'سینٹی میٹر', 'none' => 'بغیر اکائی'];
    @endphp
    <section class="main-content template-page" dir="rtl">
        <div class="container-fluid px-3 px-lg-5 py-4">

            <div class="builder-head d-flex flex-wrap align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 font-weight-bold mb-1">
                        {{ $editing ? $template->name . ' میں ترمیم' : 'نیا لباس ٹیمپلیٹ' }}
                    </h1>
                    <p class="text-muted mb-0">
                        صرف وہ خانے منتخب کریں جو اس لباس کے گاہک فارم میں دکھانے ہیں۔
                    </p>
                </div>

                <a href="{{ route('admin.measurement-templates.index') }}" class="btn btn-outline-primary mt-2 mt-md-0">
                    <i class="fas fa-arrow-right ml-1"></i>
                    تمام ٹیمپلیٹس
                </a>
            </div>


            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger"><strong>براہ کرم معلومات درست کریں:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                action="{{ $editing ? route('admin.measurement-templates.update', $template) : route('admin.measurement-templates.store') }}"
                id="templateBuilder">
                @csrf @if ($editing)
                    @method('PUT')
                @endif
                <div class="row">
                    <div class="col-xl-8">
                        <div class="card template-card mb-4">
                            <div class="card-body p-4">
                                <div class="builder-section-title"><span>1</span>
                                    <div>
                                        <h2>بنیادی معلومات</h2>
                                        <p>نام سے واضح کریں کہ یہ فارم کس لباس کے لیے ہے۔</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-7 form-group"><label class="font-weight-bold">ٹیمپلیٹ کا
                                            نام</label><input class="form-control form-control-lg" name="name"
                                            value="{{ old('name', $template?->name) }}" placeholder="مثلاً واسکٹ" required
                                            @readonly($template?->is_builtin)></div>
                                    <div class="col-md-5 form-group"><label class="font-weight-bold">مختصر
                                            وضاحت</label><input class="form-control form-control-lg" name="description"
                                            value="{{ old('description', $template?->description) }}"
                                            placeholder="یہ ٹیمپلیٹ کب استعمال ہوگا؟"></div>
                                </div>
                                @if ($template?->is_builtin)
                                    <div class="template-default"><i class="fas fa-lock ml-1"></i><strong>بنیادی ڈیفالٹ
                                            ٹیمپلیٹ</strong><span>یہ ہمیشہ دستیاب رہے گا؛ اس کا نام نہیں بدلا جا سکتا، مگر خانے آپ
                                            منتخب کر سکتے ہیں۔</span></div>
                                @endif
                            </div>
                        </div>

                        <div class="card template-card mb-4">
                            <div class="card-body p-4">
                                <div class="builder-section-title"><span>2</span>
                                    <div>
                                        <h2>جسمانی پیمائش</h2>
                                        <p>صرف اس لباس کے لیے ضروری ناپ منتخب کریں۔</p>
                                    </div>
                                </div>
                                <div class="field-options">
                                    @foreach ($measurementFields as $key => $meta)
                                        <div class="template-system-field">
                                        @include('measurement-templates.partials.field-choice', [
                                            'kind' => 'system',
                                            'value' => $key,
                                            'label' => $meta['label'],
                                            'hint' => $meta['unit'] === 'inch' ? 'انچ' : $meta['unit'],
                                            'checked' => in_array($key, $selectedSystem, true),
                                            'defaultColumn' => 'right',
                                        ])
                                        @if($editing)
                                            <button type="button" class="template-field-edit" data-toggle="modal" data-target="#systemTitleModal" data-system-key="{{ $key }}" data-system-label="{{ $meta['label'] }}" title="خانے کا نام تبدیل کریں" aria-label="{{ $meta['label'] }} کا نام تبدیل کریں"><i class="fas fa-pen"></i></button>
                                        @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card template-card mb-4">
                            <div class="card-body p-4">
                                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                    <div class="builder-section-title"><span>3</span>
                                        <div>
                                            <h2>سلائی اور ڈیزائن کی پسند</h2>
                                            <p>معیاری خانے مشترک ہیں، مگر ان کے انتخاب صرف اسی ٹیمپلیٹ کے ہیں۔</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="field-options">
                                    @foreach ($preferenceFields as $key => $meta)
                                        <div class="template-system-field">
                                        @include('measurement-templates.partials.field-choice', [
                                            'kind' => 'system',
                                            'value' => $key,
                                            'label' => $meta['label'],
                                            'hint' => 'انتخابی فہرست',
                                            'checked' => in_array($key, $selectedSystem, true),
                                            'defaultColumn' => 'left',
                                        ])
                                            @if($editing)
                                                <button type="button" class="template-field-edit" data-toggle="modal" data-target="#systemChoiceModal" data-system-key="{{ $key }}" data-system-label="{{ $meta['label'] }}" title="انتخاب تبدیل کریں" aria-label="{{ $meta['label'] }} کے انتخاب تبدیل کریں"><i class="fas fa-pen"></i></button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card template-card mb-4">
                            <div class="card-body p-4">
                                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                    <div class="builder-section-title"><span>4</span>
                                        <div>
                                            <h2>اس ٹیمپلیٹ کے خصوصی خانے</h2>
                                            <p>یہ خانے صرف {{ $template?->name ?? 'اس لباس' }} میں استعمال ہوں گے۔</p>
                                        </div>
                                    </div>
                                    @if ($editing)
                                        <button type="button" class="btn btn-sm btn-outline-primary mb-3"
                                            data-toggle="modal" data-target="#newTemplateField"><i
                                                class="fas fa-plus ml-1"></i> نیا خانہ</button>
                                    @endif
                                </div>
                                @if (!$editing)
                                    <div class="builder-empty">پہلے ٹیمپلیٹ محفوظ کریں، پھر اس لباس کے خصوصی خانے شامل کریں۔
                                    </div>
                                @elseif($customFields->isEmpty())
                                    <div class="builder-empty">اس ٹیمپلیٹ کا کوئی خصوصی خانہ نہیں۔ ضرورت ہو تو “نیا خانہ”
                                        دبائیں۔</div>
                                @else
                                    <div class="field-options">
                                        @foreach ($customFields as $field)
                                            <div class="template-custom-field">@include('measurement-templates.partials.field-choice', [
                                                'kind' => 'custom',
                                                'value' => $field->id,
                                                'label' => $field->label,
                                                'hint' =>
                                                    $field->field_type === 'select'
                                                        ? 'فہرست'
                                                        : ($field->unit === 'inch'
                                                            ? 'انچ'
                                                            : ($field->unit === 'cm'
                                                                ? 'سینٹی میٹر'
                                                                : 'اپنی قدر')),
                                                'checked' => in_array(
                                                    $field->id,
                                                    array_map('intval', $template->custom_field_ids ?? []),
                                                    true),
                                                'defaultColumn' =>
                                                    $field->field_type === 'select' ? 'left' : 'right',
                                            ])<button
                                                    type="button" class="template-field-edit" data-toggle="modal"
                                                    data-target="#editTemplateField{{ $field->id }}"
                                                    aria-label="{{ $field->label }} تبدیل کریں"><i
                                                        class="fas fa-pen"></i></button><button type="submit" form="removeTemplateField{{ $field->id }}" class="btn btn-sm btn-outline-danger ml-1" title="خانہ فارم سے ہٹائیں" aria-label="{{ $field->label }} ہٹائیں" data-confirm="کیا یہ خانہ فارم سے ہٹانا چاہتے ہیں؟ پرانی پیمائش محفوظ رہے گی۔"><i class="fas fa-times"></i></button></div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <aside class="builder-preview card template-card">
                            <div class="card-body p-4">
                                <span class="preview-label">گاہک فارم کی ترتیب</span>
                                <h2 class="h5 font-weight-bold mt-2">فارم کے سیکشن، کالم اور ترتیب</h2>
                                <p class="text-muted small">فارم کو سیکشنز میں بانٹیں اور ہر سیکشن کے لیے ایک یا دو کالم چنیں۔
                                    کارڈ پکڑ کر اوپر نیچے یا دوسرے سیکشن میں لے جائیں، یا تیر سے دوسرے کالم میں بھیجیں۔ پرنٹ رسید بھی
                                    اسی ترتیب سے چھپے گی۔</p>
                                <div id="templateSections" class="layout-sections"
                                    data-max="{{ \App\Models\MeasurementTemplate::MAX_SECTIONS }}"></div>
                                <button type="button" id="addTemplateSection" class="btn btn-outline-primary btn-block mt-2"><i
                                        class="fas fa-plus ml-1"></i> نیا سیکشن</button>
                                <div id="templatePreviewEmpty" class="preview-empty">ابھی کوئی خانہ منتخب نہیں کیا گیا۔
                                </div>
                                <button class="btn btn-primary btn-lg btn-block mt-4"><i class="fas fa-save ml-1"></i>
                                    {{ $editing ? 'تبدیلی محفوظ کریں' : 'ٹیمپلیٹ بنائیں' }}</button>
                            </div>
                        </aside>
                    </div>
                </div>
            </form>


            @if($editing)
            <div class="modal fade template-field-modal" id="systemTitleModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content" dir="rtl">
                <div class="modal-header"><h5 class="modal-title">پیمائش کے خانے کا نام</h5><button type="button" class="close ml-0 mr-auto" data-dismiss="modal">&times;</button></div>
                <form id="systemTitleOnlyForm" action="{{ route('admin.measurement-templates.field-title', $template) }}" method="POST">@csrf<input type="hidden" name="field_key"><div class="modal-body"><label for="systemTitleOnlyInput">خانے کا نام</label><input id="systemTitleOnlyInput" class="form-control" name="title" maxlength="100" required><p class="text-danger small mt-2" id="systemTitleOnlyError"></p></div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">منسوخ</button><button type="submit" class="btn btn-primary">محفوظ کریں</button></div></form>
            </div></div></div>
            <script>document.addEventListener('DOMContentLoaded',function(){if(!window.jQuery)return;const modal=document.getElementById('systemTitleModal'),form=document.getElementById('systemTitleOnlyForm'),error=document.getElementById('systemTitleOnlyError');window.jQuery(modal).on('show.bs.modal',function(e){form.elements.field_key.value=e.relatedTarget.dataset.systemKey;form.elements.title.value=e.relatedTarget.dataset.systemLabel;error.textContent=''});form.onsubmit=async function(e){e.preventDefault();const btn=form.querySelector('[type="submit"]');btn.disabled=true;error.textContent='';try{const data=new FormData(form);data.append('_method','PATCH');const response=await fetch(form.action,{method:'POST',body:data,credentials:'same-origin',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});const result=await response.json();if(!response.ok)throw Error(result.message||Object.values(result.errors||{}).flat().join(' '));document.querySelectorAll('[data-source="system.'+result.field_key+'"]').forEach(input=>{input.dataset.label=result.title;const label=input.closest('.field-choice');if(label){const strong=label.querySelector('strong');if(strong)strong.textContent=result.title}});document.querySelectorAll('[data-system-key="'+result.field_key+'"]').forEach(edit=>edit.dataset.systemLabel=result.title);window.jQuery(modal).modal('hide')}catch(ex){error.textContent=ex.message||'نام محفوظ نہیں ہوا'}finally{btn.disabled=false}}});</script>
            <div class="modal fade template-field-modal" id="systemChoiceModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content" dir="rtl">
                    <div class="modal-header"><h5 class="modal-title" id="systemChoiceTitle">سلائی کے انتخاب</h5><button type="button" class="close ml-0 mr-auto" data-dismiss="modal" aria-label="بند کریں">&times;</button></div>
                    <div class="modal-body">
                        <form id="systemChoiceTitleForm" action="{{ route('admin.measurement-templates.field-title', $template) }}" method="POST" class="mb-3">
                            @csrf
                            <input type="hidden" name="field_key">
                            <label for="systemChoiceFieldTitle">خانے کا نام</label>
                            <div class="input-group"><input id="systemChoiceFieldTitle" name="title" class="form-control" maxlength="100" required><div class="input-group-append"><button type="submit" class="btn btn-outline-primary">نام محفوظ کریں</button></div></div>
                        </form>
                        <form id="systemChoiceAdd" method="POST" action="{{ route('admin.Options.store') }}">@csrf<input type="hidden" name="OptionTypeId"><input type="hidden" name="measurement_template_id" value="{{ $template->id }}">
                            <div class="form-group mb-0 template-field-options" data-option-editor>
                                <label for="systemChoiceName">اس خانے کے انتخاب</label>
                                <div class="template-option-entry">
                                    <input id="systemChoiceName" class="form-control template-option-draft" name="Name" maxlength="255" required placeholder="ایک انتخاب لکھیں، مثلاً مینڈرن">
                                    <button class="btn btn-outline-primary template-option-add" type="submit"><i class="fas fa-plus ml-1"></i> شامل کریں</button>
                                </div>
                                <small class="text-muted d-block mt-2">ہر انتخاب الگ شامل کریں۔ Enter دبانے سے بھی شامل ہو جائے گا۔ انتخاب پر کلک کر کے اس کا نام تبدیل کریں۔</small>
                            </div>
                        </form>
                        <p id="systemChoiceMessage" class="small text-danger mt-2" role="alert"></p>
                        <div id="systemChoiceList" class="template-option-list mt-3" aria-live="polite" style="max-height:50vh;overflow:auto"></div>
                        <div id="systemChoiceEmpty" class="template-option-empty">ابھی کوئی انتخاب شامل نہیں کیا گیا۔</div>
                    </div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">بند کریں</button></div>
                </div></div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded',function(){
                if(!window.jQuery)return;
                const modal=document.getElementById('systemChoiceModal'),form=document.getElementById('systemChoiceAdd'),list=document.getElementById('systemChoiceList'),message=document.getElementById('systemChoiceMessage');
                const types=@json($systemOptionTypes);let active=null;
                const titleForm=document.getElementById('systemChoiceTitleForm');
                function error(s){message.textContent=s||''}
                async function request(url,method,name){
                    const data=new FormData();data.append('_token',form.querySelector('[name="_token"]').value);data.append('OptionTypeId',active.id);data.append('measurement_template_id',{{ (int)$template->id }});
                    if(name!==null)data.append('Name',name);
                    if(method!=='POST')data.append('_method',method);
                    const response=await fetch(url,{method:'POST',body:data,credentials:'same-origin',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                    const result=await response.json();if(!response.ok)throw Error(result.message||Object.values(result.errors||{}).flat().join(' ')||'محفوظ نہیں ہوا');return result;
                }
                function render(){
                    list.replaceChildren();
                    document.getElementById('systemChoiceEmpty').hidden=!!(active&&active.options.length);
                    if(!active)return;
                    active.options.forEach(choice=>{
                        const chip=document.createElement('span');chip.className='template-option-chip';
                        const edit=document.createElement('button');edit.type='button';edit.className='template-option-name';edit.textContent=choice.name;edit.title='انتخاب کا نام تبدیل کریں';edit.setAttribute('aria-label',choice.name+' تبدیل کریں');
                        const del=document.createElement('button');del.type='button';del.textContent='×';del.title='انتخاب حذف کریں';del.setAttribute('aria-label',choice.name+' حذف کریں');
                        edit.onclick=()=>{
                            const input=document.createElement('input');input.className='form-control form-control-sm';input.style.width='150px';input.maxLength=255;input.value=choice.name;edit.replaceWith(input);input.focus();input.select();
                            let saving=false;
                            const save=async()=>{
                                if(saving)return;saving=true;const value=input.value.trim();
                                if(!value){error('انتخاب کا نام خالی نہیں ہو سکتا۔');saving=false;return}
                                try{await request(choice.update,'PUT',value);choice.name=value;error('');render()}catch(e){error(e.message);saving=false}
                            };
                            input.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();save()}if(e.key==='Escape')render()});
                            input.addEventListener('blur',save,{once:true});
                        };
                        del.onclick=async()=>{if(!(await window.TmsConfirm.ask('کیا انتخاب حذف کرنا چاہتے ہیں؟')))return;del.disabled=true;error('');try{await request(choice.destroy,'DELETE',null);active.options=active.options.filter(x=>x.id!==choice.id);render()}catch(e){error(e.message)}finally{del.disabled=false}};
                        chip.append(edit,del);list.append(chip);
                    });
                }
                window.jQuery(modal).on('show.bs.modal',event=>{
                    const button=event.relatedTarget;active=types[button.getAttribute('data-system-key')]||null;
                    document.getElementById('systemChoiceTitle').textContent=button.getAttribute('data-system-label')+' — انتخاب';
                    titleForm.elements.field_key.value=button.getAttribute('data-system-key');titleForm.elements.title.value=button.getAttribute('data-system-label');
                    form.querySelector('[name="OptionTypeId"]').value=active?active.id:'';
                    form.querySelector('button[type="submit"]').disabled=!active;form.querySelector('[name="Name"]').value='';error('');render();
                });
                titleForm.onsubmit=async event=>{
                    event.preventDefault();const button=titleForm.querySelector('button[type="submit"]');button.disabled=true;error('');
                    try {
                        const response=await fetch(titleForm.action,{method:'POST',body:(()=>{const data=new FormData(titleForm);data.append('_method','PATCH');return data})(),credentials:'same-origin',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
                        const result=await response.json();if(!response.ok)throw Error(result.message||Object.values(result.errors||{}).flat().join(' '));
                        document.querySelectorAll('[data-source="system.'+result.field_key+'"]').forEach(input=>{input.dataset.label=result.title;const label=input.closest('.field-choice');if(label){const strong=label.querySelector('strong');if(strong)strong.textContent=result.title}});
                        document.querySelectorAll('[data-system-key="'+result.field_key+'"]').forEach(edit=>{edit.dataset.systemLabel=result.title;edit.setAttribute('aria-label',result.title+' تبدیل کریں')});
                        document.getElementById('systemChoiceTitle').textContent=result.title+' — انتخاب';
                        window.jQuery(modal).modal('hide');
                    }catch(e){error(e.message||'نام محفوظ نہیں ہوا')}finally{button.disabled=false}
                };
                form.onsubmit=async event=>{
                    event.preventDefault();if(!active)return;const button=form.querySelector('button[type="submit"]');button.disabled=true;error('');
                    try{const name=form.querySelector('[name="Name"]').value.trim();if(!name)return;
                        const result=await request(form.action,'POST',name);
                        active.options.push({id:result.id,name:result.name,update:active.updateBase.replace('__ID__',result.id),destroy:active.deleteBase.replace('__ID__',result.id)});
                        form.querySelector('[name="Name"]').value='';render();
                    }catch(e){error(e.message)}finally{button.disabled=false}
                };
            });
            </script>
            @endif
            @if ($editing)
                <div class="modal fade template-field-modal" id="newTemplateField" tabindex="-1" role="dialog"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                        <div class="modal-content" dir="rtl">
                            <div class="modal-header">
                                <div>
                                    <h2 class="h5 font-weight-bold mb-1">{{ $template->name }} کا نیا خانہ</h2>
                                    <p class="text-muted small mb-0">یہ خانہ اور اس کے انتخاب صرف اسی ٹیمپلیٹ میں رہیں گے۔
                                    </p>
                                </div><button type="button" class="close ml-0 mr-auto" data-dismiss="modal"
                                    aria-label="بند کریں"><span>&times;</span></button>
                            </div>
                            <form method="POST" action="{{ route('admin.measurement-fields.store') }}">@csrf<input
                                    type="hidden" name="measurement_template_id" value="{{ $template->id }}"><input
                                    type="hidden" name="is_active" value="1">@include('measurement-templates.partials.custom-field-form', [
                                        'field' => null,
                                    ])<div
                                    class="modal-footer"><button type="button" class="btn btn-light"
                                        data-dismiss="modal">منسوخ</button><button class="btn btn-primary"><i
                                            class="fas fa-check ml-1"></i> خانہ شامل کریں</button></div>
                            </form>
                        </div>
                    </div>
                </div>
                @foreach ($customFields as $field)
                    <form id="removeTemplateField{{ $field->id }}" method="POST" action="{{ route('admin.measurement-fields.destroy', $field) }}">@csrf @method('DELETE')</form>
                    <div class="modal fade template-field-modal" id="editTemplateField{{ $field->id }}"
                        tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                            <div class="modal-content" dir="rtl">
                                <div class="modal-header">
                                    <h2 class="h5 font-weight-bold mb-0">{{ $field->label }}</h2><button type="button"
                                        class="close ml-0 mr-auto" data-dismiss="modal"
                                        aria-label="بند کریں"><span>&times;</span></button>
                                </div>
                                <form method="POST" action="{{ route('admin.measurement-fields.update', $field) }}">
                                    @csrf @method('PUT')<input type="hidden" name="measurement_template_id"
                                        value="{{ $template->id }}"><input type="hidden" name="is_active"
                                        value="1">@include('measurement-templates.partials.custom-field-form', [
                                            'field' => $field,
                                        ])<div class="modal-footer"><button
                                            type="button" class="btn btn-light"
                                            data-dismiss="modal">منسوخ</button><button class="btn btn-primary">تبدیلی
                                            محفوظ کریں</button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

        </div>
    </section>
    @include('measurement-templates.partials.styles')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.jQuery) window.jQuery('.template-field-modal').appendTo(document.body);
            var builder = document.getElementById('templateBuilder'),
                sectionsBox = document.getElementById('templateSections'),
                addSectionButton = document.getElementById('addTemplateSection'),
                empty = document.getElementById('templatePreviewEmpty');
            if (!builder || !sectionsBox) return;
            var maxSections = Number(sectionsBox.dataset.max || 8);
            var savedSections = @json($selectedSections);
            // Where each field sits: its section, column and order (order counts 1000 per section).
            var layout = {};
            var dragged = null;
            @foreach ($selectedLayout as $item)
                layout[@json($item['source'] ?? '')] = {
                    section: {{ (int) ($item['section'] ?? 0) }},
                    column: @json($item['column'] ?? 'right'),
                    order: {{ (int) ($item['order'] ?? 0) }}
                };
            @endforeach

            function sectionEls() {
                return Array.from(sectionsBox.querySelectorAll('.layout-section'));
            }

            function sectionColumns(section) {
                return Number(section.querySelector('[data-section-columns]').value) === 1 ? 1 : 2;
            }

            function lists(section) {
                return {
                    right: section.querySelector('.preview-list[data-column="right"]'),
                    left: section.querySelector('.preview-list[data-column="left"]')
                };
            }

            function setColumns(section, columns) {
                section.querySelector('[data-section-columns]').value = columns;
                section.querySelectorAll('.column-mode input').forEach(function(radio) {
                    radio.checked = Number(radio.value) === columns;
                });
                var l = lists(section);
                if (columns === 1) Array.from(l.left.children).forEach(function(item) {
                    l.right.appendChild(item);
                });
                section.querySelector('.layout-preview').classList.toggle('is-one-column', columns === 1);
            }

            function makeSection(data) {
                var uid = 'section-columns-' + Math.random().toString(36).slice(2, 8);
                var section = document.createElement('div');
                section.className = 'layout-section';
                // The column radios belong to no form (form="no-form"); a hidden input carries the posted value.
                section.innerHTML =
                    '<div class="layout-section-head">' +
                    '<span class="layout-section-number"></span>' +
                    '<input type="text" class="form-control form-control-sm" data-section-title maxlength="60" placeholder="سیکشن کا نام (اختیاری)">' +
                    '<button type="button" class="section-remove" title="سیکشن ہٹائیں" aria-label="سیکشن ہٹائیں"><i class="fas fa-trash-alt"></i></button>' +
                    '</div>' +
                    '<input type="hidden" data-section-columns>' +
                    '<div class="column-mode" role="radiogroup" aria-label="اس سیکشن کے کالم">' +
                    '<label><input type="radio" form="no-form" name="' + uid + '" value="1"><span><i class="fas fa-bars"></i> ایک کالم</span></label>' +
                    '<label><input type="radio" form="no-form" name="' + uid + '" value="2"><span><i class="fas fa-columns"></i> دو کالم</span></label>' +
                    '</div>' +
                    '<div class="layout-preview">' +
                    '<div class="layout-lane" data-column="right"><strong class="lane-title">دائیں کالم</strong><div class="preview-list" data-column="right"></div></div>' +
                    '<div class="layout-lane" data-column="left"><strong class="lane-title">بائیں کالم</strong><div class="preview-list" data-column="left"></div></div>' +
                    '</div>';
                section.querySelector('[data-section-title]').value = (data && data.title) || '';
                setColumns(section, data && Number(data.columns) === 1 ? 1 : 2);
                section.querySelectorAll('.column-mode input').forEach(function(radio) {
                    radio.addEventListener('change', function() {
                        setColumns(section, Number(radio.value));
                        syncLayout();
                    });
                });
                section.querySelector('.section-remove').addEventListener('click', function() {
                    var all = sectionEls();
                    if (all.length < 2) return;
                    var index = all.indexOf(section);
                    // Its fields move into the section above (or below, for the first one).
                    var target = all[index === 0 ? 1 : index - 1];
                    var from = lists(section),
                        to = lists(target),
                        oneColumn = sectionColumns(target) === 1;
                    Array.from(from.right.children).forEach(function(item) {
                        to.right.appendChild(item);
                    });
                    Array.from(from.left.children).forEach(function(item) {
                        (oneColumn ? to.right : to.left).appendChild(item);
                    });
                    section.remove();
                    syncLayout();
                });
                return section;
            }

            function moveToSection(item, step) {
                var all = sectionEls();
                var target = all[all.indexOf(item.closest('.layout-section')) + step];
                if (!target) return;
                var column = item.parentElement.dataset.column;
                var l = lists(target);
                (sectionColumns(target) === 1 || column === 'right' ? l.right : l.left).appendChild(item);
                syncLayout();
                item.scrollIntoView({
                    block: 'nearest'
                });
            }

            function syncLayout() {
                var index = 0;
                var all = sectionEls();
                all.forEach(function(section, s) {
                    var oneColumn = sectionColumns(section) === 1;
                    section.querySelector('.layout-section-number').textContent = 'سیکشن ' + (s + 1);
                    section.querySelector('[data-section-title]').name = 'layout_sections[' + s + '][title]';
                    section.querySelector('[data-section-columns]').name = 'layout_sections[' + s + '][columns]';
                    section.querySelector('.section-remove').hidden = all.length < 2;
                    var l = lists(section);
                    [l.right, l.left].forEach(function(list) {
                        Array.from(list.children).forEach(function(item, position) {
                            var column = list.dataset.column;
                            var order = (position + 1) * 10;
                            layout[item.dataset.source] = {
                                section: s,
                                column: column,
                                order: s * 1000 + order
                            };
                            item.querySelector('[data-layout-column]').value = column;
                            item.querySelector('[data-layout-order]').value = order;
                            item.querySelector('[data-layout-section]').value = s;
                            item.querySelectorAll('[data-layout-input]').forEach(function(input) {
                                input.name = 'field_layout[' + index + '][' + input.dataset.layoutInput + ']';
                            });
                            var arrow = item.querySelector('.move-column');
                            arrow.hidden = oneColumn;
                            arrow.title = column === 'right' ? 'بائیں کالم میں بھیجیں' : 'دائیں کالم میں بھیجیں';
                            arrow.setAttribute('aria-label', arrow.title);
                            arrow.querySelector('i').className = column === 'right' ? 'fas fa-arrow-left' :
                                'fas fa-arrow-right';
                            item.querySelector('.move-section-up').hidden = s === 0;
                            item.querySelector('.move-section-down').hidden = s === all.length - 1;
                            index++;
                        });
                    });
                });
                addSectionButton.disabled = all.length >= maxSections;
            }

            function smallButton(className, icon, title) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = className;
                if (title) {
                    button.title = title;
                    button.setAttribute('aria-label', title);
                }
                button.innerHTML = '<i class="fas ' + icon + '"></i>';
                return button;
            }

            function hiddenInput(name) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.dataset.layoutInput = name;
                input.setAttribute('data-layout-' + name, '');
                return input;
            }

            function makeItem(input) {
                var source = input.dataset.source;
                var item = document.createElement('div');
                item.className = 'preview-item';
                item.dataset.source = source;
                item.draggable = true;
                var label = document.createElement('span');
                label.className = 'preview-item-label';
                label.textContent = input.dataset.label || '';
                var actions = document.createElement('span');
                actions.className = 'preview-item-actions';
                var up = smallButton('move-section move-section-up', 'fa-chevron-up', 'اوپر والے سیکشن میں بھیجیں');
                var down = smallButton('move-section move-section-down', 'fa-chevron-down', 'نیچے والے سیکشن میں بھیجیں');
                var move = smallButton('move-column', 'fa-arrow-left', '');
                actions.appendChild(up);
                actions.appendChild(down);
                actions.appendChild(move);
                var sourceInput = hiddenInput('source');
                sourceInput.value = source;
                item.appendChild(smallButton('drag-handle', 'fa-grip-vertical', 'پکڑ کر ترتیب بدلیں'));
                item.appendChild(label);
                item.appendChild(actions);
                item.appendChild(sourceInput);
                item.appendChild(hiddenInput('column'));
                item.appendChild(hiddenInput('order'));
                item.appendChild(hiddenInput('section'));
                item.addEventListener('dragstart', function(event) {
                    dragged = item;
                    item.classList.add('is-dragging');
                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                        // Firefox only starts a drag when some data is set.
                        event.dataTransfer.setData('text/plain', item.dataset.source);
                    }
                });
                item.addEventListener('dragend', function() {
                    item.classList.remove('is-dragging');
                    dragged = null;
                    syncLayout();
                });
                move.addEventListener('click', function() {
                    var l = lists(item.closest('.layout-section'));
                    (item.parentElement === l.right ? l.left : l.right).appendChild(item);
                    syncLayout();
                });
                up.addEventListener('click', function() {
                    moveToSection(item, -1);
                });
                down.addEventListener('click', function() {
                    moveToSection(item, 1);
                });
                return item;
            }

            function renderPreview() {
                // Keep where cards were moved to before rebuilding the lists.
                syncLayout();
                var all = sectionEls();
                all.forEach(function(section) {
                    var l = lists(section);
                    l.right.innerHTML = '';
                    l.left.innerHTML = '';
                });
                var chosen = Array.from(builder.querySelectorAll('.field-choice input:checked'));
                chosen.sort(function(a, b) {
                    var aLayout = layout[a.dataset.source],
                        bLayout = layout[b.dataset.source];
                    return (aLayout ? aLayout.order : 99999) - (bLayout ? bLayout.order : 99999);
                });
                chosen.forEach(function(input) {
                    var source = input.dataset.source;
                    // A newly ticked field joins the last section.
                    if (!layout[source]) layout[source] = {
                        section: all.length - 1,
                        column: input.dataset.defaultColumn || 'right',
                        order: 99999
                    };
                    var section = all[Math.min(Math.max(layout[source].section || 0, 0), all.length - 1)];
                    var l = lists(section);
                    var column = sectionColumns(section) === 1 ? 'right' : layout[source].column;
                    (column === 'left' ? l.left : l.right).appendChild(makeItem(input));
                });
                syncLayout();
                empty.hidden = chosen.length > 0;
            }

            sectionsBox.addEventListener('dragover', function(event) {
                var list = event.target.closest('.preview-list');
                if (!list || !dragged) return;
                event.preventDefault();
                if (list.dataset.column === 'left' && sectionColumns(list.closest('.layout-section')) === 1) return;
                var after = Array.from(list.querySelectorAll('.preview-item:not(.is-dragging)'))
                    .find(function(item) {
                        return event.clientY < item.getBoundingClientRect().top + item.offsetHeight / 2;
                    });
                if (dragged.parentElement !== list || dragged.nextElementSibling !== (after || null)) {
                    list.insertBefore(dragged, after || null);
                    // The card moves live while dragging; keep the posted payload in step,
                    // since drop/dragend do not reliably fire for a node moved mid-drag.
                    syncLayout();
                }
            });
            sectionsBox.addEventListener('drop', function(event) {
                event.preventDefault();
                syncLayout();
            });
            addSectionButton.addEventListener('click', function() {
                if (sectionEls().length >= maxSections) return;
                var section = makeSection({
                    title: '',
                    columns: 2
                });
                sectionsBox.appendChild(section);
                syncLayout();
                section.querySelector('[data-section-title]').focus();
            });
            builder.addEventListener('change', function(event) {
                if (event.target.closest('.field-choice')) renderPreview();
            });
            // Always post exactly what the preview shows, whatever drag events fired.
            builder.addEventListener('submit', syncLayout);
            (savedSections.length ? savedSections : [{
                title: '',
                columns: 2
            }]).forEach(function(data) {
                sectionsBox.appendChild(makeSection(data));
            });
            renderPreview();

            document.querySelectorAll('.template-field-modal').forEach(function(modal) {
                var type = modal.querySelector('.template-field-type');
                var unitWrap = modal.querySelector('.template-field-unit');
                var unit = unitWrap ? unitWrap.querySelector('select') : null;
                var options = modal.querySelector('.template-field-options');
                if (!type || !options) return;
                var stored = options.querySelector('.template-field-options-value');
                var draft = options.querySelector('.template-option-draft');
                var add = options.querySelector('.template-option-add');
                var list = options.querySelector('.template-option-list');
                var values = (stored.value || '').split(/[\r\n,،]+/).map(function(value) {
                    return value.trim();
                }).filter(Boolean);

                function syncOptions() {
                    values = values.filter(function(value, index, all) {
                        return all.indexOf(value) === index;
                    });
                    stored.value = values.join('، ');
                    list.innerHTML = '';
                    values.forEach(function(value, index) {
                        var chip = document.createElement('span');
                        chip.className = 'template-option-chip';
                        var text = document.createElement('span');
                        text.textContent = value;
                        var remove = document.createElement('button');
                        remove.type = 'button';
                        remove.setAttribute('aria-label', value + ' حذف کریں');
                        remove.innerHTML = '<i class="fas fa-times"></i>';
                        remove.addEventListener('click', function() {
                            values.splice(index, 1);
                            syncOptions();
                        });
                        chip.appendChild(text);
                        chip.appendChild(remove);
                        list.appendChild(chip);
                    });
                }

                function addDraft() {
                    var additions = (draft.value || '').split(/[\r\n,،]+/).map(function(value) {
                        return value.trim();
                    }).filter(Boolean);
                    if (!additions.length) {
                        draft.setCustomValidity('پہلے ایک انتخاب لکھیں۔');
                        draft.reportValidity();
                        return;
                    }
                    draft.setCustomValidity('');
                    additions.forEach(function(value) {
                        if (values.indexOf(value) === -1) values.push(value);
                    });
                    draft.value = '';
                    syncOptions();
                    draft.focus();
                }

                function toggleOptions() {
                    options.hidden = type.value !== 'select';
                    if (unitWrap) unitWrap.hidden = type.value !== 'number';
                    if (unit && type.value !== 'number') unit.value = 'none';
                    draft.setCustomValidity('');
                }
                add.addEventListener('click', addDraft);
                draft.addEventListener('input', function() {
                    draft.setCustomValidity('');
                });
                draft.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        addDraft();
                    }
                });
                modal.querySelector('form').addEventListener('submit', function(event) {
                    if (type.value === 'select' && !values.length) {
                        event.preventDefault();
                        draft.setCustomValidity('کم از کم ایک انتخاب شامل کریں۔');
                        draft.reportValidity();
                    }
                });
                type.addEventListener('change', toggleOptions);
                syncOptions();
                toggleOptions();
            });

            // Urdu keyboard for custom measurement-field names.
            var urduKeys = [
                'ا', 'آ', 'ب', 'پ', 'ت', 'ٹ', 'ث', 'ج', 'چ', 'ح', 'خ',
                'د', 'ڈ', 'ذ', 'ر', 'ڑ', 'ز', 'ژ', 'س', 'ش', 'ص', 'ض',
                'ط', 'ظ', 'ع', 'غ', 'ف', 'ق', 'ک', 'گ', 'ل', 'م', 'ن',
                'ں', 'و', 'ہ', 'ھ', 'ء', 'ی', 'ے', 'ئ'
            ];

            document.querySelectorAll('.template-field-modal').forEach(function(modal) {
                var keyboard = modal.querySelector('.mf-urdu-keyboard');
                var input = modal.querySelector('.mf-urdu-input');

                if (!keyboard || !input) return;

                var keysContainer = keyboard.querySelector('.mf-urdu-keys');

                urduKeys.forEach(function(letter) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'mf-urdu-key';
                    button.textContent = letter;
                    button.dataset.letter = letter;
                    keysContainer.appendChild(button);
                });

                function setKeyboard(open) {
                    keyboard.classList.toggle('is-open', open);
                    input.setAttribute('aria-expanded', open ? 'true' : 'false');
                }

                function insertText(text) {
                    var start = input.selectionStart ?? input.value.length;
                    var end = input.selectionEnd ?? input.value.length;

                    input.value = input.value.slice(0, start) + text + input.value.slice(end);

                    var cursor = start + text.length;
                    input.setSelectionRange(cursor, cursor);
                    input.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    input.focus();
                }

                input.addEventListener('focus', function() {
                    setKeyboard(true);
                });

                input.addEventListener('click', function() {
                    setKeyboard(true);
                });

                keyboard.addEventListener('mousedown', function(event) {
                    event.preventDefault();
                });

                keyboard.addEventListener('click', function(event) {
                    var key = event.target.closest('.mf-urdu-key');
                    if (key) {
                        insertText(key.dataset.letter);
                        return;
                    }

                    var action = event.target.closest('[data-action]');
                    if (!action) return;

                    if (action.dataset.action === 'space') {
                        insertText(' ');
                    } else if (action.dataset.action === 'clear') {
                        input.value = '';
                        input.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                        input.focus();
                    } else if (action.dataset.action === 'backspace') {
                        var start = input.selectionStart ?? input.value.length;
                        var end = input.selectionEnd ?? input.value.length;

                        if (start === end && start > 0) {
                            start--;
                        }

                        input.value = input.value.slice(0, start) + input.value.slice(end);
                        input.setSelectionRange(start, start);
                        input.dispatchEvent(new Event('input', {
                            bubbles: true
                        }));
                        input.focus();
                    }

                    if (event.target.closest('.mf-urdu-close')) {
                        setKeyboard(false);
                        input.blur();
                    }
                });

                document.addEventListener('mousedown', function(event) {
                    if (!modal.contains(event.target)) {
                        setKeyboard(false);
                    } else if (!event.target.closest('.mf-urdu-wrap')) {
                        setKeyboard(false);
                    }
                });
            });
        });
    </script>
@endsection
