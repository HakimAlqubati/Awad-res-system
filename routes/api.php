<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\NotificationOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailsController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitPriceController;
use App\Models\Device;
use App\Models\OrderDetails;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Voyager\OrderController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:api'])->group(function () {
    Route::post('/add-order', [OrderController::class, 'store']);
    Route::put('/update-order', [OrderController::class, 'update']);
    Route::post('/update-order-details', [OrderDetailsController::class, 'update']);
    Route::get('/get-order', [OrderController::class, 'index']);
    Route::get('get-units', [UnitController::class, 'index']);
    Route::get('/get-unit-prices', [UnitPriceController::class, 'index']);
    Route::get('/products',  [ProductController::class, 'index']);
    Route::get('/get-product-categories', [ProductCategoryController::class, 'index']);
    Route::get('/get-notification-orders', [NotificationOrderController::class, 'index']);
});

Route::get('/get-pdf/{id}', [OrderController::class, 'createPDF']);

// Route::post('/login',[LoginController::class,'login'] );

Route::get('/update', function (Request $request) {

    $orderDetails = OrderDetails::groupBy('product_id')
        ->whereNotNull('product_id')
        ->selectRaw('*, sum(qty) as total_qty')
        ->get();



    foreach ($orderDetails as $key => $value) {
        $obj = new stdClass();
        $obj->product_id = $value->product_id;
        $obj->qty = $value->qty;
        $obj->total_qty = $value->total_qty;
        $product =   Product::find($value->product_id);
        $product->number_orders = $value->total_qty;
        $product->save();
        $result[] = $obj;
    }
    return $result;
});
Route::middleware('auth:api')->get('/user', function (Request $request) {

    Device::UpdateOrCreate(
        [
            "user_id" => $request->user()->id,
        ],
        [
            "name" => $request->header('name'),
            "token" => $request->header('device_token')
        ]
    );
    return $request->user();
});
