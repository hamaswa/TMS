<?php

namespace App\Http\Controllers;

use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesignController extends Controller
{
    public function index()
    {
        $designs = Design::where('user_id', Auth::user()->businessOwnerId())->latest()->get();
        return view('Design.index',compact('designs'));
    }

    public function create()
    {
        return view('Design.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'array', 'min:1'],
            'name.*' => ['required', 'string', 'max:255'],
            'rupee' => ['required', 'array'],
            'rupee.*' => ['required', 'numeric', 'min:0'],
        ]);
        abort_unless(count($validated['name']) === count($validated['rupee']), 422);

        foreach (array_map(null, $validated['name'], $validated['rupee']) as [$name, $rupee]) {
            Design::create([
                'user_id' => Auth::user()->businessOwnerId(),
                'design_name' => $name,
                'design_price' => $rupee,
            ]);
        }

        return redirect()->route('admin.design.index');
    }

    public function edit($id)
    {
        $data = Design::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        return view('Design.edit',compact('data'));
    }

    public function update(Request $request,$id)
    {
        $data = Design::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);
        $validated = $request->validate([
            'name' => ['required', 'array', 'min:1'],
            'name.*' => ['required', 'string', 'max:255'],
            'rupee' => ['required', 'array'],
            'rupee.*' => ['required', 'numeric', 'min:0'],
        ]);
        abort_unless(count($validated['name']) === count($validated['rupee']), 422);

        foreach (array_map(null, $validated['name'], $validated['rupee']) as [$name, $rupee]) {
            $data->update([
                'design_name' => $name,
                'design_price' => $rupee,
            ]);
        }

        return redirect()->route('admin.design.index');
    }

    public function delete($id)
    {
        Design::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id)->delete();
        return redirect()->back();
    }

    public function price($name)
    {
        // Retrieve the design by ID
        $design = Design::where('user_id', Auth::user()->businessOwnerId())->findOrFail($name);

        // Return the price of the design
        return $design->design_price;
    }
}
