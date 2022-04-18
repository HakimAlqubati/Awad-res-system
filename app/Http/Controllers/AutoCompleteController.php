<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;

class AutoCompleteController extends Controller
{
    public function autocompleteProduct(Request $request)
    {
        $query = $request->get('query');
        $filterResult = Product::where('name', 'LIKE', '%' . $query . '%')->get();
        return response()->json($filterResult);
    }

    public function autocompleteUnit(Request $request)
    {
        $query = $request->get('query');
        $filterResult = Unit::where('name', 'LIKE', '%' . $query . '%')->get();
        return response()->json($filterResult);
    }
}
