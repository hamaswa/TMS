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
        $settings = Setting::where('user_id',Auth::user()->id)->get();
        return view('setting.list',compact('settings'));
    }
    public function add()
    {
        return view('setting.add');
    }
    public function insert(Request $req)
    {
        $image = $req->file('logo');
        $imageName = $image->getClientOriginalName();
        $image->move('public/images/setting/',$imageName);
        $obj = new Setting();
        $obj->name=$req->title;
        $parts = explode(' ', $obj->name);
        $firstPart = strtolower($parts[0]);
        $slug = $firstPart . '_shop';
        $obj->contact_no=$req->contact_no;
        $obj->logo=$imageName;
        $obj->shop_slug = $slug;
        $obj->note=$req->note;
        $obj->user_id=Auth::user()->id;
        $obj->address=$req->address;
        $obj->save();
        return redirect('admin/setting')->with('success','Setting Added');
    }
    public function update(Request $req, $id)
    {
        $imageName="";
        if($req->has('logo'))
        {
            $image = $req->file('logo');
            $imageName = $image->getClientOriginalName();
            $image->move('public/images/setting/',$imageName);
        }else{
            $imageName = $req->oldlogo;
        }
        
        $obj = Setting::find($id);
        $obj->name=$req->title;
        $parts = explode(' ', $obj->name);
        $firstPart = strtolower($parts[0]);
        $obj->contact_no=$req->contact_no;
        $slug = $firstPart . '_shop';
        // dd($slug);
        $obj->shop_slug = $slug;
        $obj->logo=$imageName;
        $obj->note=$req->note;
        $obj->user_id=Auth::user()->id;
        $obj->address=$req->address;
        $obj->save();
        return redirect('admin/setting')->with('update','Setting Updated');
    }
    public function edit($id)
    {
        $setting = Setting::find($id);
        return view('setting.edit',compact('setting'));
    }
    public function delete($id)
    {
        echo 'delte:'.$id;
    }
    public function active($id)
    {
        $setting = Setting::find($id);
        $setting->status = 1;
        $setting->save();
        return back()->with('success','Setting Activated');
    }
    public function deactive($id)
    {
        $setting = Setting::find($id);
        $setting->status = 0;
        $setting->save();
        return back()->with('delete','Setting Deactivated');
    }

    public function langChange(Request $request)
    {

        // dd($_GET['lang']);
        \App::setLocale($request->lang);
        session()->put('locale', $request->lang);  
        return redirect()->back();
    }
}