<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\TouristPlace;
use Illuminate\Http\Request;

class TouristPlacePublicController extends Controller
{
    // ✅ List All (with optional filters)
    public function index(Request $request)
    {
        $query = TouristPlace::with('category');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return $query->get();
    }

    // ✅ Get by name (exact match)
    public function showByName($name)
    {
        $place = TouristPlace::with('category')->where('name', $name)->first();

        if (!$place) {
            return response()->json(['message' => 'Place not found'], 404);
        }

        return $place;
    }
}
