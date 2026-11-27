<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Models\Setting;
use App\Models\OptionType;
class SettingController extends Controller
{
    public function list()
    {
        $settings = Setting::where('user_id',Auth::user()->businessOwnerId())->get();
        // The order screen layout is set on this page too, so the owner's business is needed even without a link.
        $user = Auth::user()->loadMissing(['business', 'ownedBusiness']);
        $business = $user->business ?? $user->ownedBusiness;
        return view('setting.list', compact('settings', 'business'));
    }
    public function add()
    {
        return view('setting.add');
    }
    public function insert(Request $req)
    {
        $validated = $req->validate([
            'title' => ['required', 'string', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'note' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:2000'],
            'print_paper_size' => ['required', \Illuminate\Validation\Rule::in(array_keys(Setting::printPaperSizes()))],
            'print_show_qr' => ['nullable', 'boolean'],
        ]);
        $imageName = '';
        if ($req->hasFile('logo')) {
            $image = $req->file('logo');
            $imageName = $image->hashName();
            $image->move(public_path('images/setting'), $imageName);
        }
        $ownerId = Auth::user()->businessOwnerId();
        $isFirstSetting = ! Setting::where('user_id', $ownerId)->exists();
        $obj = new Setting();
        $obj->name=$validated['title'];
        $parts = explode(' ', $obj->name);
        $firstPart = strtolower($parts[0]);
        $slug = $firstPart . '_shop';
        $obj->contact_no=$validated['contact_no'] ?? '';
        $obj->logo=$imageName;
        $obj->shop_slug = $slug;
        $obj->note=$validated['note'] ?? '';
        $obj->user_id=$ownerId;
        $obj->address=$validated['address'] ?? '';
        $obj->status = $isFirstSetting ? 1 : 0;
        $obj->print_paper_size = $validated['print_paper_size'];
        $obj->print_show_qr = $req->boolean('print_show_qr');
        $obj->save();
        return redirect('admin/setting')->with('success','دکان کی ترتیب شامل ہو گئی ہے۔');
    }
    public function update(Request $req, $id)
    {
        $validated = $req->validate([
            'title' => ['required', 'string', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'note' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:2000'],
            'print_paper_size' => ['required', \Illuminate\Validation\Rule::in(array_keys(Setting::printPaperSizes()))],
            'print_show_qr' => ['nullable', 'boolean'],
        ]);
        $obj = Setting::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $imageName = $obj->logo;
        if($req->hasFile('logo'))
        {
            $image = $req->file('logo');
            $imageName = $image->hashName();
            $image->move(public_path('images/setting'), $imageName);
        }

        $obj->name=$validated['title'];
        $parts = explode(' ', $obj->name);
        $firstPart = strtolower($parts[0]);
        $obj->contact_no=$validated['contact_no'] ?? '';
        $slug = $firstPart . '_shop';
        // dd($slug);
        $obj->shop_slug = $slug;
        $obj->logo=$imageName;
        $obj->note=$validated['note'] ?? '';
        $obj->user_id=Auth::user()->businessOwnerId();
        $obj->address=$validated['address'] ?? '';
        $obj->print_paper_size = $validated['print_paper_size'];
        $obj->print_show_qr = $req->boolean('print_show_qr');
        $obj->save();
        return redirect('admin/setting')->with('update','دکان کی ترتیب اپ ڈیٹ ہو گئی ہے۔');
    }
    public function edit($id)
    {
        $setting = Setting::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        return view('setting.edit',compact('setting'));
    }
    public function delete($id)
    {
        Setting::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id)->delete();

        return back()->with('delete', 'دکان کی ترتیب حذف ہو گئی ہے۔');
    }
    public function active($id)
    {
        $setting = Setting::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $setting->status = 1;
        $setting->save();
        return back()->with('success','دکان کی ترتیب فعال ہو گئی ہے۔');
    }
    public function deactive($id)
    {
        $setting = Setting::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $setting->status = 0;
        $setting->save();
        return back()->with('delete','دکان کی ترتیب غیر فعال ہو گئی ہے۔');
    }

    /** Saves the tailoring slip's line height and row gap, set from the slip itself while previewing it. */
    public function printSpacing(Request $request)
    {
        $validated = $request->validate([
            'print_line_height' => ['required', 'numeric', 'between:1,3'],
            'print_row_gap' => ['required', 'integer', 'between:0,24'],
        ]);
        Setting::ensureDefaultFor(Auth::user())->update($validated);

        return response()->json(['message' => 'پرنٹ کی ترتیب محفوظ ہو گئی ہے۔']);
    }

    public function langChange(Request $request)
    {

        // dd($_GET['lang']);
        \App::setLocale($request->lang);
        session()->put('locale', $request->lang);  
        return redirect()->back();
    }
}
