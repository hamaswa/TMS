<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\OptionType;
use App\Models\Options;
class OptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect()->route('admin.OptionType.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect()->route('admin.Options.index');
    }

    public function add($id)
    {
        $optionType = $this->availableOptionTypes()->findOrFail($id);

        return redirect()->route('admin.OptionType.index')->with('openChoiceModal', $optionType->id);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Name' => ['required', 'string', 'max:255'],
            'OptionTypeId' => ['required', 'integer'],
        ], ['Name.required' => 'نئے انتخاب کا نام لکھیں۔']);
        $this->availableOptionTypes()->findOrFail($validated['OptionTypeId']);
        $slug = Str::slug($validated['Name'], '_');
        $obj = new Options;
        $obj->Name = $validated['Name'];
        $obj->slug = $slug;
        $obj->option_id=$validated['OptionTypeId'];
        $obj->user_id = Auth::user()->businessOwnerId();
        $obj->save();
        return redirect()->route('admin.OptionType.index')
            ->with('success', 'نیا انتخاب شامل کر دیا گیا ہے۔')
            ->with('openChoiceModal', $validated['OptionTypeId']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        Options::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);

        return redirect()->route('admin.Options.edit', $id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $Option = Options::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);

        return redirect()->route('admin.OptionType.index')
            ->with('openChoiceModal', $Option->option_id)
            ->with('editChoice', $Option->id);
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
        $validated = $request->validate([
            'Name' => ['required', 'string', 'max:255'],
            'OptionTypeId' => ['required', 'integer'],
        ], ['Name.required' => 'انتخاب کا نام خالی نہیں ہو سکتا۔']);
        $OptionTypeId = $validated['OptionTypeId'];
        $this->availableOptionTypes()->findOrFail($OptionTypeId);
        $slug = Str::slug($validated['Name'], '_');
        $obj = Options::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $obj->Name = $validated['Name'];
        $obj->slug = $slug;
        $obj->save();
        return redirect()->route('admin.OptionType.index')
            ->with('success', 'انتخاب کا نام تبدیل کر دیا گیا ہے۔')
            ->with('openChoiceModal', $OptionTypeId);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $obj = Options::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $optionTypeId = $obj->option_id;
        $obj->delete();
        return redirect()->route('admin.OptionType.index')
            ->with('success', 'انتخاب حذف کر دیا گیا ہے۔')
            ->with('openChoiceModal', $optionTypeId);
    }

    private function availableOptionTypes()
    {
        return OptionType::where(function ($query) {
            $query->whereNull('user_id')->orWhere('user_id', Auth::user()->businessOwnerId());
        });
    }
}
