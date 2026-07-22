<?php

namespace App\Http\Controllers;

use App\Models\Cloth;
use App\Models\ClothType;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothImage;
use App\Models\ClothVideo;
use Illuminate\Http\Request;

class ClothController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $cloths = Cloth::where('user_id', auth()->user()->id)->with(['colors', 'images', 'videos'])->latest()->get();
            return view('cloths.index', compact('cloths'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            $cloth_types = ClothType::where('user_id', auth()->user()->id)->latest()->get();
            $cloth_brands = ClothBrand::where('user_id', auth()->user()->id)->latest()->get();
            return view('cloths.create', compact('cloth_types', 'cloth_brands'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

            $request->validate([
                'cloth_type_id' => 'required',
                'cloth_brand_id' => 'required',
                'length' => 'required|array',
                'price' => 'required|numeric',
                'sale_price' => 'required|numeric',
                'colors' => 'required|string',
                'images' => 'nullable|array', // Ensure this is an array if multiple images are uploaded
                'image_colors' => 'nullable|array',
                'video_colors' => 'nullable|array',
                'videos' => 'nullable|array', // Make the videos array nullable
                'videos.*' => 'nullable|mimes:mp4,mov,ogg,qt|max:20000', // Allow each video to be nullable
            ]);
            // $formData = $request->validate([
            //     'cloth_type_id' => 'required|string',
            //     'cloth_brand_id' => 'required',
            //     'color' => 'required|string',
            //     'length' => 'required|numeric',
            //     'price' => 'required|numeric', // No change here
            //     'sale_price' => 'required|numeric', //new change
            //     'image' => 'required|mimes:png,jpg,jpeg', //new change
            // ]);

            // previous code
            // Cloth::create($request->all());

            // New Code

            // Serialize colors to JSON
            // $colors = json_encode($formData['color']);
            // dd($colors);
            // $cloth = Cloth::create([
            //     'cloth_type_id' => $request->cloth_type_id,
            //     'cloth_brand_id' => $request->cloth_brand_id,
            //     'length' => $request->length,
            //     'price' => $request->price,
            //     'sale_price' => $request->sale_price,
            //     'user_id' => auth()->user()->id,
            // ]);

            // Save the cloth
            $cloth = new Cloth;
            $cloth->cloth_type_id = $request->cloth_type_id;
            $cloth->cloth_brand_id = $request->cloth_brand_id;
            $cloth->price = $request->price;
            $cloth->sale_price = $request->sale_price;
            $cloth->user_id = auth()->user()->id;
            $cloth->save();

            // Save colors and lengths
            $colors = explode(',', $request->colors);
            foreach ($colors as $index => $color) {
                ClothColor::create([
                    'cloth_id' => $cloth->id,
                    'color' => $color,
                    'length' => $request->length[$index],
                    'user_id' => auth()->user()->id,
                ]);
            }

            // Save images and their corresponding colors
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $imageName = $image->getClientOriginalName();
                    $path = $image->move('public/images/setting/', $imageName);
                    ClothImage::create([
                        'cloth_id' => $cloth->id,
                        'images' => $path,
                        'image_color' => $request->image_colors[$index],
                        'user_id' => auth()->user()->id,
                    ]);
                }
            }

            // Save videos
            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $index => $video) {
                    $videoName = time() . '.' . $video->getClientOriginalExtension();
                    $path = $video->storeAs('ClothVideos', $videoName, 'public');
                    ClothVideo::create([
                        'cloth_id' => $cloth->id,
                        'video' => $path,
                        'video_color' => $request->video_colors[$index],
                        'user_id' => auth()->user()->id,
                    ]);
                }
            }

            return redirect()->route('admin.cloth.index')->with('insert', 'کپڑا کامیابی کے ساتھ شامل کیا گیا۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cloth  $cloth
     * @return \Illuminate\Http\Response
     */
    public function show(Cloth $cloth)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Cloth  $cloth
     * @return \Illuminate\Http\Response
     */
    public function edit(Cloth $cloth)
    {
        try {
            $cloth_types = ClothType::latest()->get();
            $cloth_brands = ClothBrand::latest()->get();
            // Fetch related colors, images, and videos
            $cloth->load('colors', 'images', 'videos');
            return view('cloths.edit', compact('cloth', 'cloth_types', 'cloth_brands'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function editCloth($id, $color)
    {
        $cloth_types = ClothType::latest()->get();
        $cloth_brands = ClothBrand::latest()->get();
        // Fetch the cloth details based on $id and $color
        $cloth = Cloth::findOrFail($id);

        // Assuming you want to find the specific color details
        $specificColor = $cloth->colors->firstWhere('color', $color);

        // dd($specificColor);
        // Ensure $specificColor is found before proceeding
        if ($specificColor) {
            // Prepare any additional data needed for your edit view
            $data = [
                'cloth' => $cloth,
                'specificColor' => $specificColor,
            ];

            return view('cloths.edit', compact('data', 'cloth_types', 'cloth_brands'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cloth  $cloth
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cloth $cloth)
    {
        try {
            $request->validate([
                'cloth_type_id' => 'required|exists:cloth_types,id',
                'cloth_brand_id' => 'required|exists:cloth_brands,id',
                'length' => 'required|string',
                'price' => 'required|numeric',
                'sale_price' => 'required|numeric',
                'colors' => 'required|string',
                'images.*' => 'nullable|image',
                'image_colors.*' => 'nullable|string',
                'video_colors' => 'nullable|array',
                'videos.*' => 'nullable|mimetypes:video/avi,video/mpeg,video/quicktime,video/mp4',
            ]);

            // Update cloth details
            $cloth->update($request->only(['cloth_type_id', 'cloth_brand_id', 'price', 'sale_price']));


            // Save colors and lengths
            $colors = $request->colors;
            $clothColor = ClothColor::where('cloth_id', $cloth->id)->where('color', $colors)->first();

            if ($clothColor) {
                // Update existing color length
                $clothColor->update(['length' => $request->length]);
            }

            // Update images
            if ($request->hasFile('images')) {
                $cloth->images()->where('image_color', $colors)->delete();
                foreach ($request->file('images') as $key => $image) {
                    $imageName = $image->getClientOriginalName();
                    $path = $image->move('public/images/setting/', $imageName);
                    ClothImage::create([
                        'cloth_id' => $cloth->id,
                        'images' => $path,
                        'image_color' => $request->image_colors[$key],
                        'user_id' => auth()->user()->id,
                    ]);
                }
            }

            // Update videos
            if ($request->hasFile('videos')) {
                $cloth->videos()->where('video_color', $colors)->delete();
                foreach ($request->file('videos') as $index => $video) {
                    $videoName = $video->getClientOriginalExtension();
                    $path = $video->move('public/images/setting/', $videoName);
                    // $path = $video->storeAs('ClothVideos', $videoName, 'public');
                    ClothVideo::create([
                        'cloth_id' => $cloth->id,
                        'video' => $path,
                        'video_color' => $request->video_colors[$index],
                        'user_id' => auth()->user()->id,
                    ]);
                }
            }




            return redirect()->route('admin.cloth.index')->with('insert', 'کپڑا کامیابی کے ساتھ آپڈیٹ کیا گیا۔');
        } catch (\Exception $e) {
            // Return detailed validation errors
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : $e->getMessage()
            ], 422);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cloth  $cloth
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cloth $cloth)
    {
        try {
            $cloth->delete();
            return back()->with('insert', 'کپڑا کامیابی کے ساتھ حذف کر دیا گیا ہے۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteCloth(Request $request)
    {
        $clothId = $request->input('id');
        $clothColor = $request->input('color');
        try {
            // Delete related models
            ClothImage::where('cloth_id', $clothId)->where('image_color', $clothColor)->delete();
            ClothColor::where('cloth_id', $clothId)->where('color', $clothColor)->delete();
            ClothVideo::where('cloth_id', $clothId)->where('video_color', $clothColor)->delete();

            // Check if there are any remaining records for the given cloth_id and cloth_color
            $remainingClothColor = ClothColor::where('cloth_id', $clothId)
                ->where('color', $clothColor)
                ->exists();

            if (!$remainingClothColor) {
                Cloth::where('id', $clothId)->delete();
            }

            // Cloth::where('id', $clothId)->delete();


            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
