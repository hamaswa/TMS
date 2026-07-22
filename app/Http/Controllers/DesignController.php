<?php

namespace App\Http\Controllers;

use App\Models\Design;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    public function index()
    {
        $designs = Design::all();
        return view('Design.index',compact('designs'));
    }

    public function create()
    {
        return view('Design.create');
    }

    public function store(Request $request)
    {
        $name = $request->input('name');
        $rupee = $request->input('rupee');

        foreach (array_map(null, $name, $rupee) as [$name, $rupee]) {
            Design::create([
                'design_name' => $name,
                'design_price' => $rupee,
            ]);
        }

        return redirect()->route('admin.design.index');
    }

    public function edit($id)
    {
        $data = Design::find($id);
        return view('Design.edit',compact('data'));
    }

    public function update(Request $request,$id)
    {
        $data = Design::find($id);
        $name = $request->input('name');
        $rupee = $request->input('rupee');

        foreach (array_map(null, $name, $rupee) as [$name, $rupee]) {
            $data->update([
                'design_name' => $name,
                'design_price' => $rupee,
            ]);
        }

        return redirect()->route('admin.design.index');
    }

    public function delete($id)
    {
        $data = Design::find($id);
        if($data){
            $data->delete();
        }
        return redirect()->back();
    }

    public function price($name)
    {
        // Retrieve the design by ID
        $design = Design::findOrFail($name);

        // Return the price of the design
        return $design->design_price;
    }
}
