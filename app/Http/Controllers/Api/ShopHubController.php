<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShopHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopHubController extends Controller
{
    public function status(Request $request, ShopHubService $hub): JsonResponse
    {
        return response()->json(['data' => $hub->status($request->user())]);
    }
}
