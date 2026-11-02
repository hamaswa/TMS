<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\OptionType;
use App\Models\Options;
use App\Models\MeasurementTemplate;
use App\Models\User;
class OptionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ownerId = Auth::user()->businessOwnerId();
        $templates = MeasurementTemplate::where('user_id', $ownerId)->where('is_active', true)
            ->orderByDesc('is_default')->orderBy('name')->get(['id', 'name']);

        // Without a selected template (e.g. from the sidebar), open the first active one.
        if (! $request->filled('template')) {
            if ($templates->isEmpty()) {
                return redirect()->route('admin.measurement-templates.index')
                    ->with('success', 'سلائی کے انتخاب اب متعلقہ لباس ٹیمپلیٹ کے اندر ترتیب دیے جاتے ہیں۔');
            }

            return redirect()->route('admin.OptionType.index', ['template' => $templates->first()->id]);
        }
        $template = MeasurementTemplate::where('user_id', $ownerId)->where('is_active', true)
            ->findOrFail($request->integer('template'));
        $preferenceTypes = collect($template->system_fields ?? [])->map(
            fn (string $key) => $key === 'Daaman' ? 'daaman' : $key
        )->all();
        $OptionTypes = $this->availableOptionTypes()
            ->whereIn('type', $preferenceTypes)
            ->with(['options' => fn ($query) => $query->where('user_id', $ownerId)->where('measurement_template_id', $template->id)->inOwnerSource($ownerId)->orderBy('Name')])
            ->withCount(['options as choices_count' => fn ($query) => $query->where('user_id', $ownerId)->where('measurement_template_id', $template->id)->inOwnerSource($ownerId)])
            ->orderByRaw('CASE WHEN user_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->get();
        $usesOwnChoices = Options::ownerUsesOwnChoices($ownerId);

        return view('OptionType.list', compact('OptionTypes', 'template', 'templates', 'usesOwnChoices'));
    }

    public function updateSource(Request $request)
    {
        $validated = $request->validate([
            'option_source' => ['required', Rule::in([User::OPTION_SOURCE_DEFAULT, User::OPTION_SOURCE_OWN])],
        ]);
        $owner = User::findOrFail(Auth::user()->businessOwnerId());
        $owner->option_source = $validated['option_source'];
        $owner->save();

        return back()->with('success', $validated['option_source'] === User::OPTION_SOURCE_OWN
            ? 'اب پیمائش میں صرف آپ کے اپنے انتخاب دکھائی دیں گے۔'
            : 'اب پیمائش میں ڈیفالٹ انتخاب کے ساتھ آپ کے اپنے انتخاب بھی دکھائی دیں گے۔');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect()->route('admin.measurement-templates.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate(['Name' => ['required', 'string', 'max:255']]);
        $slug = Str::slug($validated['Name'], '_');
        $obj = new OptionType();
        $obj->user_id = Auth::user()->businessOwnerId();
        $obj->Name =$validated['Name'];
        $obj->slug =$slug;
        $obj->type =$slug;
        $obj->save();
        return back()->with('insert','Option Type Inserted');
       

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->availableOptionTypes()->findOrFail($id);

        return redirect()->route('admin.options.add', $id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
       $OptionType = OptionType::where('user_id', Auth::user()->businessOwnerId())->find($id);
       if (! $OptionType) {
           $this->availableOptionTypes()->findOrFail($id);

           return redirect()->route('admin.OptionType.index')
               ->with('insert', 'محفوظ سسٹم آپشن کی قسم تبدیل نہیں کی جا سکتی۔');
       }
       $OptionTypes = $this->availableOptionTypes()->get();
       return view('OptionType.edit',compact('OptionTypes','OptionType'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate(['Name' => ['required', 'string', 'max:255']]);
        $slug = Str::slug($validated['Name'], '_');
        $obj = OptionType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $obj->Name =$validated['Name'];
        $obj->slug =$slug;
        $obj->type =$slug;
        $obj->save();
        return redirect('admin/OptionType')->with('update','Data Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $obj = OptionType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $obj->delete();
        return back()->with('del','OptionType Deleted!');
    }

    private function availableOptionTypes()
    {
        return OptionType::where(function ($query) {
            $query->whereNull('user_id')->orWhere('user_id', Auth::user()->businessOwnerId());
        });
    }
}
