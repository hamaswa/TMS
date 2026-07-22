<?php

namespace App\Http\Controllers;

use App\Models\Cloth;
use App\Models\ClothType;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothImage;
use App\Models\ClothVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryService;

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
            $cloths = Cloth::where('user_id', auth()->user()->businessOwnerId())->with(['colors', 'images', 'videos'])->latest()->get();
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
            $cloth_types = ClothType::where('user_id', auth()->user()->businessOwnerId())->latest()->get();
            $cloth_brands = ClothBrand::where('user_id', auth()->user()->businessOwnerId())->latest()->get();
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

            $validated = $request->validate([
                'cloth_type_id' => ['required', 'integer'],
                'cloth_brand_id' => ['required', 'integer'],
                'length' => ['required', 'array', 'min:1'],
                'length.*' => ['required', 'numeric', 'min:0'],
                'price' => ['required', 'numeric', 'min:0'],
                'sale_price' => ['required', 'numeric', 'min:0'],
                'colors' => ['required', 'string'],
                'images' => ['nullable', 'array'],
                'images.*' => ['image', 'max:4096'],
                'image_colors' => ['nullable', 'array'],
                'image_colors.*' => ['nullable', 'string', 'max:100'],
                'video_colors' => ['nullable', 'array'],
                'video_colors.*' => ['nullable', 'string', 'max:100'],
                'videos' => ['nullable', 'array'],
                'videos.*' => ['nullable', 'mimes:mp4,mov,ogg,qt', 'max:20000'],
            ]);
            ClothType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_type_id']);
            ClothBrand::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_brand_id']);
            $colors = array_values(array_filter(array_map('trim', explode(',', $validated['colors'])), fn ($color) => $color !== ''));
            abort_unless(count($colors) === count($validated['length']), 422, 'Every color must have one length.');
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
            //     'user_id' => auth()->user()->businessOwnerId(),
            // ]);

            // Save the cloth
            DB::transaction(function () use ($request, $validated, $colors) {
                $cloth = Cloth::create([
                    'cloth_type_id' => $validated['cloth_type_id'],
                    'cloth_brand_id' => $validated['cloth_brand_id'],
                    'price' => $validated['price'],
                    'sale_price' => $validated['sale_price'],
                    'user_id' => Auth::user()->businessOwnerId(),
                ]);

                foreach ($colors as $index => $color) {
                    ClothColor::create([
                        'cloth_id' => $cloth->id,
                        'color' => $color,
                        'length' => $validated['length'][$index],
                        'average_unit_cost' => $validated['price'],
                        'user_id' => Auth::user()->businessOwnerId(),
                    ]);
                }

                if ($request->hasFile('images')) {
                    abort_unless(count($request->file('images')) === count($validated['image_colors'] ?? []), 422, 'Every image must have a color.');
                    foreach ($request->file('images') as $index => $image) {
                        $path = $image->store('ClothImages', 'public');
                        ClothImage::create([
                            'cloth_id' => $cloth->id,
                            'images' => $path,
                            'image_color' => $validated['image_colors'][$index],
                            'user_id' => Auth::user()->businessOwnerId(),
                        ]);
                    }
                }

                if ($request->hasFile('videos')) {
                    abort_unless(count($request->file('videos')) === count($validated['video_colors'] ?? []), 422, 'Every video must have a color.');
                    foreach ($request->file('videos') as $index => $video) {
                        $path = $video->store('ClothVideos', 'public');
                        ClothVideo::create([
                            'cloth_id' => $cloth->id,
                            'video' => $path,
                            'video_color' => $validated['video_colors'][$index],
                            'user_id' => Auth::user()->businessOwnerId(),
                        ]);
                    }
                }
            });

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
            abort_unless((int) $cloth->user_id === (int) Auth::user()->businessOwnerId(), 404);
            $cloth_types = ClothType::where('user_id', Auth::user()->businessOwnerId())->latest()->get();
            $cloth_brands = ClothBrand::where('user_id', Auth::user()->businessOwnerId())->latest()->get();
            // Fetch related colors, images, and videos
            $cloth->load('colors', 'images', 'videos');
            return view('cloths.edit', compact('cloth', 'cloth_types', 'cloth_brands'));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function editCloth($id, $color)
    {
        $cloth_types = ClothType::where('user_id', Auth::user()->businessOwnerId())->latest()->get();
        $cloth_brands = ClothBrand::where('user_id', Auth::user()->businessOwnerId())->latest()->get();
        // Fetch the cloth details based on $id and $color
        $cloth = Cloth::where('user_id', Auth::user()->businessOwnerId())->findOrFail($id);

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
            abort_unless((int) $cloth->user_id === (int) Auth::user()->businessOwnerId(), 404);
            $validated = $request->validate([
                'cloth_type_id' => ['required', 'integer'],
                'cloth_brand_id' => ['required', 'integer'],
                'length' => ['required', 'numeric', 'min:0'],
                'price' => ['required', 'numeric', 'min:0'],
                'sale_price' => ['required', 'numeric', 'min:0'],
                'colors' => ['required', 'string', 'max:100'],
                'images' => ['nullable', 'array'],
                'images.*' => ['image', 'max:4096'],
                'image_colors' => ['nullable', 'array'],
                'image_colors.*' => ['nullable', 'string', 'max:100'],
                'video_colors' => ['nullable', 'array'],
                'video_colors.*' => ['nullable', 'string', 'max:100'],
                'videos' => ['nullable', 'array'],
                'videos.*' => ['nullable', 'mimetypes:video/avi,video/mpeg,video/quicktime,video/mp4', 'max:20000'],
            ]);
            ClothType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_type_id']);
            ClothBrand::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_brand_id']);

            DB::transaction(function () use ($request, $validated, $cloth) {
                $inventory = app(InventoryService::class);
                $cloth->update([
                    'cloth_type_id' => $validated['cloth_type_id'],
                    'cloth_brand_id' => $validated['cloth_brand_id'],
                    'price' => $validated['price'],
                    'sale_price' => $validated['sale_price'],
                ]);

                $colors = $validated['colors'];
                $clothColor = $cloth->colors()->where('color', $colors)->lockForUpdate()->firstOrFail();
                $difference = round((float) $validated['length'] - (float) $clothColor->length, 2);
                if ($difference > 0) {
                    $inventory->receive($clothColor, $difference, (float) $validated['price'], 'manual_adjustment_in', $cloth, 'Stock changed from cloth editor');
                } elseif ($difference < 0) {
                    $inventory->issue($clothColor, abs($difference), 'manual_adjustment_out', $cloth, 'Stock changed from cloth editor');
                }

                if ($request->hasFile('images')) {
                    abort_unless(count($request->file('images')) === count($validated['image_colors'] ?? []), 422, 'Every image must have a color.');
                    $cloth->images()->where('image_color', $colors)->delete();
                    foreach ($request->file('images') as $key => $image) {
                        ClothImage::create([
                            'cloth_id' => $cloth->id,
                            'images' => $image->store('ClothImages', 'public'),
                            'image_color' => $validated['image_colors'][$key],
                            'user_id' => Auth::user()->businessOwnerId(),
                        ]);
                    }
                }

                if ($request->hasFile('videos')) {
                    abort_unless(count($request->file('videos')) === count($validated['video_colors'] ?? []), 422, 'Every video must have a color.');
                    $cloth->videos()->where('video_color', $colors)->delete();
                    foreach ($request->file('videos') as $index => $video) {
                        ClothVideo::create([
                            'cloth_id' => $cloth->id,
                            'video' => $video->store('ClothVideos', 'public'),
                            'video_color' => $validated['video_colors'][$index],
                            'user_id' => Auth::user()->businessOwnerId(),
                        ]);
                    }
                }
            });




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
            abort_unless((int) $cloth->user_id === (int) Auth::user()->businessOwnerId(), 404);
            $cloth->delete();
            return back()->with('insert', 'کپڑا کامیابی کے ساتھ حذف کر دیا گیا ہے۔');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteCloth(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'color' => ['required', 'string', 'max:100'],
        ]);
        $clothId = $validated['id'];
        $clothColor = $validated['color'];
        try {
            $cloth = Cloth::where('user_id', Auth::user()->businessOwnerId())->findOrFail($clothId);
            DB::transaction(function () use ($cloth, $clothColor) {
                $cloth->images()->where('image_color', $clothColor)->delete();
                $cloth->colors()->where('color', $clothColor)->delete();
                $cloth->videos()->where('video_color', $clothColor)->delete();

                if (!$cloth->colors()->exists()) {
                    $cloth->delete();
                }
            });

            // Cloth::where('id', $clothId)->delete();


            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
