<?php

namespace App\Http\Controllers;

use App\Models\Storefront;

class PublicStorefrontController extends Controller
{
    public function index()
    {
        $storefronts = Storefront::query()
            ->publiclyVisible()
            ->with('business:id,status,tailoring_enabled,clothing_enabled')
            ->latest('published_at')
            ->paginate(12);

        return view('storefront.public.index', compact('storefronts'));
    }

    public function show(Storefront $storefront)
    {
        abort_unless(
            $storefront->is_published && $storefront->business?->isActive(),
            404
        );

        return view('storefront.public.show', [
            'storefront' => $storefront->load('business'),
            'preview' => false,
        ]);
    }
}
