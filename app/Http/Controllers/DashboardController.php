<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {





       
        $firstChart = Product::skip(0)->take(10)->orderBy('number_orders', 'DESC')->get();
        foreach ($firstChart as $key => $value) {
            $finalDataFirstChart[] = [
                'label' => $value->name, 'y' => $value->number_orders
            ];
        }
        // -----------


        $secondChart = Product::skip(0)->take(10)->orderBy('number_orders', 'DESC')->get();
        foreach ($secondChart as $key => $value) {
            $finalDataSecondChart[] = [
                'y' => $value->number_orders, 'label' => $value->name
            ];
        }
        // ----------



        $thirdChart = Branch::with('orders')->get();
        foreach ($thirdChart as $key => $value) {
            // dd($value->name, $value->orders->count(), $value);

            $finalDataThirdChart[] = [
                'y' => $value->orders->count(), 'label' => $value->name
            ];
        }

        return view('dashboard.index', compact(
            'finalDataFirstChart',
            'finalDataSecondChart',
            'finalDataThirdChart'
        ));
    }
}
