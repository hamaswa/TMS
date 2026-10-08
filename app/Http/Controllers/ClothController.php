<?php

namespace App\Http\Controllers;

use App\Models\Cloth;
use App\Models\ClothType;
use App\Models\ClothBrand;
use App\Models\ClothColor;
use App\Models\ClothImage;
use App\Models\ClothVideo;
use App\Models\Storefront;
use App\Models\StorefrontClothingListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryService;
use App\Services\PrintDocumentService;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ClothController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ownerId = auth()->user()->businessOwnerId();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'integer'],
            'type' => ['nullable', 'integer'],
            'color' => ['nullable', 'string', 'max:100'],
            'stock' => ['nullable', Rule::in(['all', 'available', 'low', 'empty'])],
            'basis' => ['nullable', Rule::in(['all', Cloth::SALE_PRICE_PER_METER, Cloth::SALE_PRICE_PER_SUIT])],
            'availability' => ['nullable', Rule::in(['all', 'pos_only', 'online_order'])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'brand', 'type', 'stock_high', 'stock_low'])],
            'per_page' => ['nullable', Rule::in([25, 50, 100])],
        ]);

        $query = Cloth::query()->where('user_id', $ownerId);
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where('name', 'like', $like)
                    ->orWhere('set_code', 'like', $like)
                    ->orWhere('stock_code', 'like', $like)
                    ->orWhere('display_colors', 'like', $like)
                    ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', $like))
                    ->orWhereHas('type', fn ($type) => $type->where('name', 'like', $like))
                    ->orWhereHas('colors', fn ($color) => $color->where('color', 'like', $like));
            });
        }
        if (! empty($filters['brand'])) {
            $query->where('cloth_brand_id', $filters['brand']);
        }
        if (! empty($filters['type'])) {
            $query->where('cloth_type_id', $filters['type']);
        }
        if (! empty($filters['color'])) {
            $color = $filters['color'];
            $query->where(function ($query) use ($color) {
                $query->whereHas('colors', fn ($colors) => $colors->where('color', $color))
                    ->orWhereJsonContains('display_colors', $color);
            });
        }

        $stockExpression = 'COALESCE((SELECT SUM(cloth_colors.length) FROM cloth_colors WHERE cloth_colors.cloth_id = cloths.id), 0)';
        if (($filters['stock'] ?? 'all') === 'available') {
            $query->whereRaw($stockExpression.' > 10');
        } elseif (($filters['stock'] ?? 'all') === 'low') {
            $query->whereRaw($stockExpression.' > 0 AND '.$stockExpression.' <= 10');
        } elseif (($filters['stock'] ?? 'all') === 'empty') {
            $query->whereRaw($stockExpression.' <= 0');
        }
        if (($filters['basis'] ?? 'all') !== 'all') {
            $query->where('sale_price_basis', $filters['basis']);
        }

        $onlineListing = fn ($listing) => $listing
            ->where('is_published', true)
            ->where('is_available', true)
            ->where('online_order_enabled', true);
        if (($filters['availability'] ?? 'all') === 'online_order') {
            $query->whereHas('storefrontListings', $onlineListing);
        } elseif (($filters['availability'] ?? 'all') === 'pos_only') {
            $query->whereDoesntHave('storefrontListings', $onlineListing);
        }

        $matchedSets = (clone $query)->count();
        $matchedIds = (clone $query)->select('cloths.id');
        $colorSummary = ClothColor::query()
            ->whereIn('cloth_id', $matchedIds)
            ->selectRaw('COUNT(*) as color_count, COALESCE(SUM(length), 0) as total_meters')
            ->first();
        $lowOrEmptySets = (clone $query)->whereRaw($stockExpression.' <= 10')->count();

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest('cloths.id'),
            'brand' => $query->orderBy(
                ClothBrand::select('name')->whereColumn('cloth_brands.id', 'cloths.cloth_brand_id')
            )->orderBy('cloths.id'),
            'type' => $query->orderBy(
                ClothType::select('name')->whereColumn('cloth_types.id', 'cloths.cloth_type_id')
            )->orderBy('cloths.id'),
            'stock_high' => $query->orderByRaw($stockExpression.' DESC')->orderByDesc('cloths.id'),
            'stock_low' => $query->orderByRaw($stockExpression.' ASC')->orderByDesc('cloths.id'),
            default => $query->latest('cloths.id'),
        };

        $cloths = $query
            ->with(['type', 'brand', 'colors.latestCostedStockAddition', 'images', 'storefrontListings'])
            ->paginate((int) ($filters['per_page'] ?? 25))
            ->withQueryString();
        $brands = ClothBrand::where('user_id', $ownerId)->orderBy('name')->get();
        $types = ClothType::where('user_id', $ownerId)->orderBy('name')->get();
        $stockColors = ClothColor::where('user_id', $ownerId)->whereNotNull('color')
            ->distinct()->pluck('color');
        $displayColors = Cloth::where('user_id', $ownerId)->whereNotNull('display_colors')
            ->get(['display_colors'])->flatMap(fn (Cloth $cloth) => $cloth->display_colors ?? []);
        $colors = $stockColors->merge($displayColors)->map(fn ($color) => trim((string) $color))
            ->filter()->unique()->sort()->values();
        $summary = [
            'sets' => $matchedSets,
            'colors' => (int) ($colorSummary->color_count ?? 0),
            'meters' => (float) ($colorSummary->total_meters ?? 0),
            'low_or_empty' => $lowOrEmptySets,
        ];

        return view('cloths.index', compact('cloths', 'brands', 'types', 'colors', 'summary', 'filters'));
    }

    public function qrLabels(PrintDocumentService $documents)
    {
        $cloths = Cloth::where('user_id', Auth::user()->businessOwnerId())
            ->with(['type', 'brand', 'colors'])
            ->orderBy('id')
            ->get()
            ->map(function (Cloth $cloth) use ($documents) {
                $cloth->qr_svg = $documents->qrSvg('BNS-SET:'.$cloth->set_code, 220);

                return $cloth;
            });

        return view('cloths.qr-labels', compact('cloths'));
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
            [$storefront, $canConfigureOnline] = $this->storefrontContext();
            return view('cloths.create', compact('cloth_types', 'cloth_brands', 'storefront', 'canConfigureOnline'));
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
            $this->normalizeInventoryGridInput($request);
            $request->mergeIfMissing([
                'color_tracking_mode' => Cloth::COLOR_TRACKING_NONE,
                'online_availability' => 'pos_only',
                'sale_price_basis' => Cloth::SALE_PRICE_PER_METER,
            ]);
            $validated = $request->validate([
                'name' => ['nullable', 'string', 'max:100'],
                'cloth_type_id' => ['required', 'integer'],
                'cloth_brand_id' => ['required', 'integer'],
                'length' => ['nullable', 'array'],
                'length.*' => ['nullable', 'numeric', 'min:0'],
                'length_colors' => ['nullable', 'array'],
                'length_colors.*' => ['required', 'string', 'max:100'],
                'price' => ['required', 'numeric', 'min:0'],
                'sale_price' => ['required', 'numeric', 'min:0'],
                'suit_sale_price' => ['nullable', 'numeric', 'min:0'],
                'default_sale_length' => ['nullable', 'numeric', 'gt:0'],
                'sale_price_basis' => ['required', Rule::in([Cloth::SALE_PRICE_PER_METER, Cloth::SALE_PRICE_PER_SUIT])],
                'color_tracking_mode' => ['required', Rule::in([
                    Cloth::COLOR_TRACKING_NONE,
                    Cloth::COLOR_TRACKING_DISPLAY_ONLY,
                    Cloth::COLOR_TRACKING_PER_COLOR,
                ])],
                'colors' => ['nullable', 'string', 'max:1000'],
                'inventory_grid' => ['nullable', 'boolean'],
                'stock_mode' => ['nullable', Rule::in(['shared', 'per_color'])],
                'shared_length' => ['nullable', 'numeric', 'min:0'],
                'color_names' => ['nullable', 'array'],
                'color_names.*' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
                'color_hexes' => ['nullable', 'array'],
                'color_hexes.*' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'color_lengths' => ['nullable', 'array'],
                'color_lengths.*' => ['nullable', 'numeric', 'min:0'],
                'color_images' => ['nullable', 'array'],
                'color_images.*' => ['nullable', 'image', 'max:4096'],
                'images' => ['nullable', 'array'],
                'images.*' => ['image', 'max:4096'],
                'image_colors' => ['nullable', 'array'],
                'image_colors.*' => ['nullable', 'string', 'max:100'],
                'video_colors' => ['nullable', 'array'],
                'video_colors.*' => ['nullable', 'string', 'max:100'],
                'videos' => ['nullable', 'array'],
                'videos.*' => ['nullable', 'mimes:mp4,mov,ogg,qt', 'max:20000'],
                'online_availability' => ['required', Rule::in(['pos_only', 'online_order'])],
            ]);
            if ($validated['sale_price_basis'] === Cloth::SALE_PRICE_PER_SUIT
                && (empty($validated['default_sale_length']) || empty($validated['suit_sale_price']))) {
                throw ValidationException::withMessages(array_filter([
                    'default_sale_length' => empty($validated['default_sale_length']) ? 'فی سوٹ قیمت کے لیے سوٹ کی ڈیفالٹ لمبائی درج کریں۔' : null,
                    'suit_sale_price' => empty($validated['suit_sale_price']) ? 'فی سوٹ قیمت درج کریں۔' : null,
                ]));
            }
            [$storefront, $canConfigureOnline] = $this->storefrontContext();
            $this->validateOnlineAvailability(
                $validated['online_availability'],
                $storefront,
                $canConfigureOnline
            );
            // dd($validated);
            ClothType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_type_id']);
            ClothBrand::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_brand_id']);
            $trackingMode = $validated['color_tracking_mode'];
            $colors = array_values(
                                array_filter(
                                    array_map(
                                        'trim',
                                        preg_split('/[,،]/u', $validated['colors'] ?? '')
                                    ),
                                    fn ($color) => $color !== ''
                                )
                            );

                            $displayColors = [];
                            $submittedNames = array_values($validated['color_names'] ?? []);
                            $submittedHexes = array_values($validated['color_hexes'] ?? []);
                            $colorCodeByName = [];
                            foreach ($submittedNames as $index => $submittedName) {
                                $hex = $submittedHexes[$index] ?? null;
                                $colorCodeByName[trim($submittedName)] = $hex ?: null;
                            }
                            if ($trackingMode === Cloth::COLOR_TRACKING_NONE) {
                                $stockColors = ['عام'];
                            } elseif ($trackingMode === Cloth::COLOR_TRACKING_DISPLAY_ONLY) {
                                $colors = array_values(array_filter($colors, fn ($color) => mb_strtolower($color) !== 'عام'));
                                if ($colors === []) {
                                    throw ValidationException::withMessages([
                                        'colors' => 'آن لائن انتخاب کے لیے کم از کم ایک رنگ درج کریں۔',
                                    ]);
                                }
                                $displayColors = $colors;
                                $stockColors = ['عام'];
                            } else {
                                $colors = array_values(array_filter($colors, fn ($color) => mb_strtolower($color) !== 'عام'));
                                if ($colors === []) {
                                    throw ValidationException::withMessages([
                                        'colors' => 'رنگ کے حساب سے اسٹاک رکھنے کے لیے کم از کم ایک رنگ درج کریں۔',
                                    ]);
                                }
                                $stockColors = $colors;
                            }

                            $lengths = $validated['length'] ?? [];

                            if (!empty($validated['length_colors'])) {

                                if (count($validated['length_colors']) !== count($lengths)) {
                                    throw ValidationException::withMessages([
                                        'length' => 'ہر رنگ کے لیے ایک لمبائی درج کریں۔'
                                    ]);
                                }

                                $lengthByColor = [];

                                foreach ($validated['length_colors'] as $index => $color) {
                                    if (
                                        !in_array($color, $stockColors, true) ||
                                        array_key_exists($color, $lengthByColor)
                                    ) {
                                        throw ValidationException::withMessages([
                                            'length_colors' => 'ہر منتخب رنگ صرف ایک مرتبہ استعمال کریں۔'
                                        ]);
                                    }

                                    $lengthByColor[$color] = $lengths[$index];
                                }

                                if (count($lengthByColor) !== count($stockColors)) {
                                    throw ValidationException::withMessages([
                                        'length' => 'تمام رنگوں کی لمبائی درج کریں۔'
                                    ]);
                                }

                                $lengths = array_map(
                                    fn ($color) => $lengthByColor[$color],
                                    $stockColors
                                );

                            } elseif ($lengths === []) {
                                $lengths = array_fill(0, count($stockColors), 0);
                            } elseif (count($stockColors) !== count($lengths)) {

                                throw ValidationException::withMessages([
                                    'length' => 'ہر رنگ کے لیے ایک لمبائی درج کریں۔'
                                ]);
                            }

                            $lengths = array_map(
                                fn ($length) => (float) ($length ?? 0),
                                $lengths
                            );
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
            DB::transaction(function () use ($request, $validated, $stockColors, $displayColors, $lengths, $trackingMode, $storefront, $canConfigureOnline, $colorCodeByName) {
                $cloth = Cloth::create([
                    'name' => trim((string) ($validated['name'] ?? '')) ?: null,
                    'cloth_type_id' => $validated['cloth_type_id'],
                    'cloth_brand_id' => $validated['cloth_brand_id'],
                    'price' => $validated['price'],
                    'sale_price' => $validated['sale_price'],
                    'suit_sale_price' => $validated['suit_sale_price'] ?? null,
                    'default_sale_length' => $validated['default_sale_length'] ?? null,
                    'sale_price_basis' => $validated['sale_price_basis'],
                    'color_tracking_mode' => $trackingMode,
                    'display_colors' => $displayColors,
                    'display_color_codes' => $trackingMode === Cloth::COLOR_TRACKING_DISPLAY_ONLY
                        ? array_filter($colorCodeByName)
                        : null,
                    'user_id' => Auth::user()->businessOwnerId(),
                ]);

                foreach ($stockColors as $index => $color) {
                    ClothColor::create([
                        'cloth_id' => $cloth->id,
                        'color' => $color,
                        'color_hex' => $colorCodeByName[$color] ?? null,
                        'length' => $lengths[$index],
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

                foreach ($request->file('color_images', []) as $index => $image) {
                    $colorName = trim((string) (($validated['color_names'] ?? [])[$index] ?? ''));
                    if ($colorName === '') {
                        continue;
                    }
                    ClothImage::create([
                        'cloth_id' => $cloth->id,
                        'images' => $image->store('ClothImages', 'public'),
                        'image_color' => $colorName,
                        'user_id' => Auth::user()->businessOwnerId(),
                    ]);
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
                if ($canConfigureOnline) {
                    $this->syncStorefrontListing($cloth, $storefront, $validated['online_availability']);
                }
            });

            return redirect()->route('admin.cloth.index')->with('insert', 'کپڑا کامیابی کے ساتھ شامل کیا گیا۔');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->withErrors(['inventory' => 'کپڑا محفوظ نہیں ہو سکا۔ دوبارہ کوشش کریں۔']);
        }
    }

    public function qrLabel(int $cloth, PrintDocumentService $printDocuments)
    {
        $cloth = Cloth::where('user_id', Auth::user()->businessOwnerId())
            ->with(['brand', 'type', 'colors'])
            ->findOrFail($cloth);

        return view('cloths.qr-label', [
            'cloth' => $cloth,
            'qrSvg' => $printDocuments->qrSvg('BNS-SET:'.$cloth->set_code, 240),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cloth  $cloth
     * @return \Illuminate\Http\Response
     */
    public function show(Cloth $cloth)
    {
        abort_unless((int) $cloth->user_id === (int) Auth::user()->businessOwnerId(), 404);
        $color = $cloth->colors()->value('color');

        return $color
            ? redirect()->route('admin.edit-cloths', ['id' => $cloth->id, 'color' => $color])
            : redirect()->route('admin.cloth.index');
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
            $specificColor = $cloth->colors->first();
            abort_unless($specificColor, 404);
            $data = compact('cloth', 'specificColor');

            [$storefront, $canConfigureOnline] = $this->storefrontContext();
            $onlineAvailability = $this->onlineAvailability($cloth, $storefront);
            return view('cloths.edit', compact('data', 'cloth_types', 'cloth_brands', 'storefront', 'canConfigureOnline', 'onlineAvailability'));
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

            [$storefront, $canConfigureOnline] = $this->storefrontContext();
            $onlineAvailability = $this->onlineAvailability($cloth, $storefront);
            return view('cloths.edit', compact('data', 'cloth_types', 'cloth_brands', 'storefront', 'canConfigureOnline', 'onlineAvailability'));
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
            if ($request->boolean('inventory_grid')) {
                return $this->updateFromInventoryGrid($request, $cloth);
            }
            [$storefront, $canConfigureOnline] = $this->storefrontContext();
            $currentOnlineAvailability = $this->onlineAvailability($cloth, $storefront);
            if ($canConfigureOnline) {
                $request->mergeIfMissing(['online_availability' => $currentOnlineAvailability]);
            } else {
                // Inventory staff may edit stock, but only storefront managers may
                // change whether the item is offered to online customers.
                $request->merge(['online_availability' => $currentOnlineAvailability]);
            }
            $request->mergeIfMissing([
                'sale_price_basis' => $cloth->sale_price_basis ?: Cloth::SALE_PRICE_PER_METER,
            ]);
            $validated = $request->validate([
                'name' => ['nullable', 'string', 'max:100'],
                'cloth_type_id' => ['required', 'integer'],
                'cloth_brand_id' => ['required', 'integer'],
                'length' => ['required', 'numeric', 'min:0'],
                'price' => ['required', 'numeric', 'min:0'],
                'sale_price' => ['required', 'numeric', 'min:0'],
                'suit_sale_price' => ['nullable', 'numeric', 'min:0'],
                'default_sale_length' => ['nullable', 'numeric', 'gt:0'],
                'sale_price_basis' => ['required', Rule::in([Cloth::SALE_PRICE_PER_METER, Cloth::SALE_PRICE_PER_SUIT])],
                'colors' => ['required', 'string', 'max:100'],
                'display_colors' => ['nullable', 'string', 'max:1000'],
                'images' => ['nullable', 'array'],
                'images.*' => ['image', 'max:4096'],
                'image_colors' => ['nullable', 'array'],
                'image_colors.*' => ['nullable', 'string', 'max:100'],
                'video_colors' => ['nullable', 'array'],
                'video_colors.*' => ['nullable', 'string', 'max:100'],
                'videos' => ['nullable', 'array'],
                'videos.*' => ['nullable', 'mimetypes:video/avi,video/mpeg,video/quicktime,video/mp4', 'max:20000'],
                'online_availability' => ['required', Rule::in(['pos_only', 'online_order'])],
            ]);
            if ($validated['sale_price_basis'] === Cloth::SALE_PRICE_PER_SUIT
                && (empty($validated['default_sale_length']) || empty($validated['suit_sale_price']))) {
                throw ValidationException::withMessages(array_filter([
                    'default_sale_length' => empty($validated['default_sale_length']) ? 'فی سوٹ قیمت کے لیے سوٹ کی ڈیفالٹ لمبائی درج کریں۔' : null,
                    'suit_sale_price' => empty($validated['suit_sale_price']) ? 'فی سوٹ قیمت درج کریں۔' : null,
                ]));
            }
            if ($canConfigureOnline) {
                $this->validateOnlineAvailability(
                    $validated['online_availability'],
                    $storefront,
                    $canConfigureOnline
                );
            }
            ClothType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_type_id']);
            ClothBrand::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_brand_id']);

            $displayColors = $cloth->display_colors ?? [];
            if ($cloth->usesDisplayOnlyColors()) {
                $displayColors = array_values(array_unique(array_filter(array_map(
                    'trim',
                    preg_split('/[,،]/u', $validated['display_colors'] ?? '')
                ))));
                if ($displayColors === []) {
                    throw ValidationException::withMessages([
                        'display_colors' => 'کم از کم ایک قابل انتخاب رنگ درج کریں۔',
                    ]);
                }
            }

            DB::transaction(function () use ($request, $validated, $cloth, $displayColors, $storefront, $canConfigureOnline) {
                $inventory = app(InventoryService::class);
                $cloth->update([
                    'name' => trim((string) ($validated['name'] ?? '')) ?: $cloth->name,
                    'cloth_type_id' => $validated['cloth_type_id'],
                    'cloth_brand_id' => $validated['cloth_brand_id'],
                    'price' => $validated['price'],
                    'sale_price' => $validated['sale_price'],
                    'suit_sale_price' => $validated['suit_sale_price'] ?? null,
                    'default_sale_length' => $validated['default_sale_length'] ?? null,
                    'sale_price_basis' => $validated['sale_price_basis'],
                    'display_colors' => $displayColors,
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
                if ($canConfigureOnline) {
                    $this->syncStorefrontListing($cloth, $storefront, $validated['online_availability']);
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

    private function updateFromInventoryGrid(Request $request, Cloth $cloth)
    {
        $this->normalizeInventoryGridInput($request);
        [$storefront, $canConfigureOnline] = $this->storefrontContext();
        $currentOnlineAvailability = $this->onlineAvailability($cloth, $storefront);
        if (! $canConfigureOnline) {
            $request->merge(['online_availability' => $currentOnlineAvailability]);
        }
        $request->mergeIfMissing([
            'online_availability' => $currentOnlineAvailability,
            'sale_price_basis' => $cloth->sale_price_basis ?: Cloth::SALE_PRICE_PER_METER,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'cloth_type_id' => ['required', 'integer'],
            'cloth_brand_id' => ['required', 'integer'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'suit_sale_price' => ['nullable', 'numeric', 'min:0'],
            'default_sale_length' => ['nullable', 'numeric', 'gt:0'],
            'sale_price_basis' => ['required', Rule::in([Cloth::SALE_PRICE_PER_METER, Cloth::SALE_PRICE_PER_SUIT])],
            'color_tracking_mode' => ['required', Rule::in([
                Cloth::COLOR_TRACKING_NONE,
                Cloth::COLOR_TRACKING_DISPLAY_ONLY,
                Cloth::COLOR_TRACKING_PER_COLOR,
            ])],
            'stock_mode' => ['required', Rule::in(['shared', 'per_color'])],
            'shared_length' => ['nullable', 'numeric', 'min:0'],
            'color_names' => ['nullable', 'array'],
            'color_names.*' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'color_hexes' => ['nullable', 'array'],
            'color_hexes.*' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_lengths' => ['nullable', 'array'],
            'color_lengths.*' => ['nullable', 'numeric', 'min:0'],
            'color_ids' => ['nullable', 'array'],
            'color_ids.*' => ['nullable', 'integer'],
            'original_color_names' => ['nullable', 'array'],
            'original_color_names.*' => ['nullable', 'string', 'max:100'],
            'color_images' => ['nullable', 'array'],
            'color_images.*' => ['nullable', 'image', 'max:4096'],
            'online_availability' => ['required', Rule::in(['pos_only', 'online_order'])],
        ]);

        if ($validated['sale_price_basis'] === Cloth::SALE_PRICE_PER_SUIT
            && (empty($validated['default_sale_length']) || empty($validated['suit_sale_price']))) {
            throw ValidationException::withMessages(array_filter([
                'default_sale_length' => empty($validated['default_sale_length']) ? 'فی سوٹ قیمت کے لیے سوٹ کی ڈیفالٹ لمبائی درج کریں۔' : null,
                'suit_sale_price' => empty($validated['suit_sale_price']) ? 'فی سوٹ قیمت درج کریں۔' : null,
            ]));
        }
        if ($validated['color_tracking_mode'] === Cloth::COLOR_TRACKING_PER_COLOR
            && count($validated['color_names'] ?? []) !== count($validated['color_lengths'] ?? [])) {
            throw ValidationException::withMessages(['color_lengths' => 'ہر رنگ کی موجودہ لمبائی درج کریں۔']);
        }

        ClothType::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_type_id']);
        ClothBrand::where('user_id', Auth::user()->businessOwnerId())->findOrFail($validated['cloth_brand_id']);
        if ($canConfigureOnline) {
            $this->validateOnlineAvailability($validated['online_availability'], $storefront, $canConfigureOnline);
        }

        $cloth->load('colors');
        $newMode = $validated['color_tracking_mode'];
        $changingStockModel = $cloth->color_tracking_mode !== $newMode
            && ! ($cloth->color_tracking_mode === Cloth::COLOR_TRACKING_DISPLAY_ONLY && $newMode === Cloth::COLOR_TRACKING_NONE)
            && ! ($cloth->color_tracking_mode === Cloth::COLOR_TRACKING_NONE && $newMode === Cloth::COLOR_TRACKING_DISPLAY_ONLY);
        if ($changingStockModel) {
            $colorIds = $cloth->colors->pluck('id');
            $hasHistory = $colorIds->isNotEmpty() && DB::table('inventory_movements')->whereIn('cloth_color_id', $colorIds)->exists();
            if ($hasHistory || $cloth->colors->sum(fn ($color) => (float) $color->length) > 0) {
                throw ValidationException::withMessages([
                    'stock_mode' => 'اس سیٹ میں اسٹاک یا سابقہ لین دین موجود ہے، اس لیے مشترکہ اور الگ رنگ اسٹاک کے درمیان تبدیلی نہیں کی جا سکتی۔',
                ]);
            }
        }

        $names = array_values($validated['color_names'] ?? []);
        $hexes = array_values($validated['color_hexes'] ?? []);
        $lengths = array_values($validated['color_lengths'] ?? []);
        $ids = array_values($validated['color_ids'] ?? []);
        $originalNames = array_values($validated['original_color_names'] ?? []);
        $codeByName = [];
        foreach ($names as $index => $name) {
            $codeByName[$name] = $hexes[$index] ?? null;
        }
        $previousSelectableNames = $cloth->selectableColorNames();

        DB::transaction(function () use ($request, $validated, $cloth, $storefront, $canConfigureOnline, $newMode, $names, $hexes, $lengths, $ids, $originalNames, $codeByName, $previousSelectableNames) {
            $inventory = app(InventoryService::class);
            $cloth->update([
                'name' => trim($validated['name']),
                'cloth_type_id' => $validated['cloth_type_id'],
                'cloth_brand_id' => $validated['cloth_brand_id'],
                'price' => $validated['price'],
                'sale_price' => $validated['sale_price'],
                'suit_sale_price' => $validated['suit_sale_price'] ?? null,
                'default_sale_length' => $validated['default_sale_length'] ?? null,
                'sale_price_basis' => $validated['sale_price_basis'],
                'color_tracking_mode' => $newMode,
                'display_colors' => $newMode === Cloth::COLOR_TRACKING_DISPLAY_ONLY ? $names : [],
                'display_color_codes' => $newMode === Cloth::COLOR_TRACKING_DISPLAY_ONLY ? array_filter($codeByName) : null,
            ]);

            if ($newMode === Cloth::COLOR_TRACKING_PER_COLOR) {
                $keptIds = [];
                foreach ($names as $index => $name) {
                    $color = ! empty($ids[$index]) ? $cloth->colors->firstWhere('id', (int) $ids[$index]) : null;
                    if ($color) {
                        $oldName = $color->color;
                        $difference = round((float) ($lengths[$index] ?? 0) - (float) $color->length, 2);
                        $color->update(['color' => $name, 'color_hex' => $hexes[$index] ?? null]);
                        if ($oldName !== $name) {
                            $cloth->images()->where('image_color', $oldName)->update(['image_color' => $name]);
                        }
                        if ($difference > 0) {
                            $inventory->receive($color, $difference, (float) $validated['price'], 'manual_adjustment_in', $cloth, 'Stock changed from cloth editor');
                        } elseif ($difference < 0) {
                            $inventory->issue($color, abs($difference), 'manual_adjustment_out', $cloth, 'Stock changed from cloth editor');
                        }
                    } else {
                        $color = ClothColor::create([
                            'cloth_id' => $cloth->id,
                            'color' => $name,
                            'color_hex' => $hexes[$index] ?? null,
                            'length' => 0,
                            'average_unit_cost' => $validated['price'],
                            'user_id' => Auth::user()->businessOwnerId(),
                        ]);
                        if ((float) ($lengths[$index] ?? 0) > 0) {
                            $inventory->receive($color, (float) $lengths[$index], (float) $validated['price'], 'manual_adjustment_in', $cloth, 'Color added from cloth editor');
                        }
                    }
                    $keptIds[] = $color->id;
                }
                foreach ($cloth->colors->whereNotIn('id', $keptIds) as $removedColor) {
                    $hasHistory = DB::table('inventory_movements')->where('cloth_color_id', $removedColor->id)->exists();
                    if ($hasHistory || (float) $removedColor->length > 0) {
                        throw ValidationException::withMessages(['color_names' => "{$removedColor->color} کو ہٹانے سے پہلے اس کا اسٹاک صفر کریں؛ سابقہ لین دین والا رنگ حذف نہیں کیا جا سکتا۔"]);
                    }
                    $removedColor->delete();
                }
            } else {
                if ($changing = $cloth->colors->where('color', 'عام')->first()) {
                    $aggregate = $changing;
                } else {
                    foreach ($cloth->colors as $oldColor) {
                        if ((float) $oldColor->length === 0.0 && ! DB::table('inventory_movements')->where('cloth_color_id', $oldColor->id)->exists()) {
                            $oldColor->delete();
                        }
                    }
                    $aggregate = ClothColor::firstOrCreate(
                        ['cloth_id' => $cloth->id, 'color' => 'عام'],
                        ['length' => 0, 'average_unit_cost' => $validated['price'], 'user_id' => Auth::user()->businessOwnerId()]
                    );
                }
                $difference = round((float) ($validated['shared_length'] ?? 0) - (float) $aggregate->length, 2);
                if ($difference > 0) {
                    $inventory->receive($aggregate, $difference, (float) $validated['price'], 'manual_adjustment_in', $cloth, 'Stock changed from cloth editor');
                } elseif ($difference < 0) {
                    $inventory->issue($aggregate, abs($difference), 'manual_adjustment_out', $cloth, 'Stock changed from cloth editor');
                }
            }

            $removedImageColors = array_values(array_diff($previousSelectableNames, $names));
            if ($removedImageColors !== []) {
                $cloth->images()->whereIn('image_color', $removedImageColors)->delete();
            }
            foreach ($names as $index => $name) {
                $oldName = $originalNames[$index] ?? $name;
                if ($oldName !== $name) {
                    $cloth->images()->where('image_color', $oldName)->update(['image_color' => $name]);
                }
                $image = $request->file("color_images.$index");
                if ($image) {
                    $cloth->images()->where('image_color', $name)->delete();
                    ClothImage::create([
                        'cloth_id' => $cloth->id,
                        'images' => $image->store('ClothImages', 'public'),
                        'image_color' => $name,
                        'user_id' => Auth::user()->businessOwnerId(),
                    ]);
                }
            }

            if ($canConfigureOnline) {
                $this->syncStorefrontListing($cloth, $storefront, $validated['online_availability']);
            }
        });

        return redirect()->route('admin.cloth.index')->with('insert', 'کپڑا کامیابی کے ساتھ آپڈیٹ کیا گیا۔');
    }

    private function normalizeInventoryGridInput(Request $request): void
    {
        if (! $request->boolean('inventory_grid')) {
            return;
        }

        $names = array_map(
            fn ($name) => trim((string) $name),
            array_values((array) $request->input('color_names', []))
        );
        $stockMode = $request->input('stock_mode', 'shared');
        $trackingMode = $names === []
            ? Cloth::COLOR_TRACKING_NONE
            : ($stockMode === 'per_color' ? Cloth::COLOR_TRACKING_PER_COLOR : Cloth::COLOR_TRACKING_DISPLAY_ONLY);

        if ($trackingMode === Cloth::COLOR_TRACKING_PER_COLOR) {
            $lengths = array_values((array) $request->input('color_lengths', []));
            $lengthColors = $names;
        } else {
            $lengths = [$request->input('shared_length', 0)];
            $lengthColors = ['عام'];
        }

        $request->merge([
            'color_names' => $names,
            'color_tracking_mode' => $trackingMode,
            'colors' => implode('، ', $names),
            'length' => $lengths,
            'length_colors' => $lengthColors,
        ]);
    }

    private function storefrontContext(): array
    {
        $business = Auth::user()->business;
        $storefront = $business && $business->clothing_enabled ? $business->storefront : null;
        $canConfigureOnline = (bool) ($storefront
            && $storefront->show_clothing
            && Auth::user()->hasBusinessPermission('storefront.manage'));

        return [$storefront, $canConfigureOnline];
    }

    private function validateOnlineAvailability(
        string $availability,
        ?Storefront $storefront,
        bool $canConfigureOnline
    ): void {
        if ($availability !== 'online_order') {
            return;
        }
        if (! $storefront || ! $storefront->show_clothing) {
            throw ValidationException::withMessages([
                'online_availability' => 'پہلے آن لائن دکان بنائیں اور کپڑے کی دکان عوام کے لیے فعال کریں۔',
            ]);
        }
        if (! $canConfigureOnline) {
            throw ValidationException::withMessages([
                'online_availability' => 'آپ کو آن لائن دکان کی مصنوعات تبدیل کرنے کی اجازت نہیں ہے۔',
            ]);
        }
    }

    private function onlineAvailability(Cloth $cloth, ?Storefront $storefront): string
    {
        if (! $storefront) {
            return 'pos_only';
        }
        $listing = StorefrontClothingListing::query()
            ->where('storefront_id', $storefront->id)
            ->where('cloth_id', $cloth->id)
            ->first();

        return $listing?->is_published && $listing?->online_order_enabled
            ? 'online_order' : 'pos_only';
    }

    private function syncStorefrontListing(
        Cloth $cloth,
        Storefront $storefront,
        string $availability
    ): void {
        $online = $availability === 'online_order';
        $listing = StorefrontClothingListing::query()
            ->where('storefront_id', $storefront->id)
            ->where('cloth_id', $cloth->id)
            ->first();

        if (! $online && ! $listing) {
            return;
        }

        if (! $listing) {
            $listing = new StorefrontClothingListing([
                'storefront_id' => $storefront->id,
                'cloth_id' => $cloth->id,
            ]);
            $cloth->loadMissing(['brand', 'type']);
            $listing->public_name = collect([$cloth->name, $cloth->brand?->name, $cloth->type?->name])
                ->filter()->implode(' — ');
            $listing->is_featured = false;
            $listing->sort_order = 0;
        }

        $listing->fill([
            'is_published' => $online,
            'is_available' => $online,
            'online_order_enabled' => $online,
        ])->save();
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
