<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {






        $firstChart = Product::skip(0)->take(10)->orderBy('number_orders', 'DESC')->get();
        $finalDataFirstChar = [];
        foreach ($firstChart as $key => $value) {
            $finalDataFirstChart[] = [
                'label' => $value->name, 'y' => $value->number_orders
            ];
        }
        // -----------
        $startMonth = '2022-5-1';
        $lastMonth = '2022-5-31';
        $month = 'May';
        if ($request->month && $request->month != null) {

            $month = $request->month;

            $startMonth = date('Y-m-d', strtotime('first day of ' . $request->month . ' 2022'));
            $lastMonth = date('Y-m-d', strtotime('last day of ' . $request->month . ' 2022'));
        }


        $secondChart = DB::select("
        select order_details.product_id, products.name as p_name, order_details.qty,

        case when  order_details.product_unit_id = 2
        then  sum(order_details.qty)  * 10
        else  sum(order_details.qty) 
        end as total_qty 
        
        
        from order_details

        inner join orders on orders.id = order_details.order_id and orders.created_at BETWEEN '" . $startMonth . "' and '" . $lastMonth . "'
        inner join products on products.id = order_details.product_id

        GROUP BY order_details.product_id 
        ORDER BY total_qty DESC
        LIMIT 10 OFFSET 0
        ");
        $finalDataSecondChart = [];
        foreach ($secondChart as $key => $value) {
            $finalDataSecondChart[] = [
                'y' => $value->total_qty, 'label' => $value->p_name
            ];
        }
        // ---------- 


        $ordersCount = Order::get()->count();



        $thirdChart = Branch::with('orders')->get();
        foreach ($thirdChart as $key => $value) {
            // dd($value->name, $value->orders->count(), $value);

            $finalDataThirdChart[] = [
                'y' => $value->orders->count(), 'label' => $value->name
            ];
        }

        // -----------


        $fordChart = DB::select("
            select orders.branch_id, branches.name, order_details.price,  sum(order_details.price) as total_price from order_details

            inner join orders on orders.id = order_details.order_id 
            inner join branches on branches.id = orders.branch_id
            
            GROUP BY orders.branch_id 
            ORDER BY total_price DESC
            
            ");

        $finalDataFordChart = [];
        foreach ($fordChart as $key => $value) {
            $finalDataFordChart[] = [
                'y' => $value->total_price, 'label' => $value->name
            ];
        }
        // ---------- 


        return view('dashboard.index', compact(
            'finalDataFirstChart',
            'finalDataSecondChart',
            'finalDataThirdChart',
            'finalDataFordChart',
            'ordersCount',
            'month'
        ));
    }
}
