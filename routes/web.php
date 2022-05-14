<?php

use App\Http\Controllers\AutoCompleteController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\Voyager\OrderController;
use App\Http\Controllers\Voyager\ProductController;
use App\Http\Controllers\Voyager\PurchaseInvoiceController;
use App\Http\Controllers\Voyager\StockController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Voyager\UnitPriceController;
use App\Http\Controllers\Voyager\UserController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // return view('welcome');
    return redirect('admin/login');
});
Route::get('/admin', function () {
    // return view('welcome');
    return redirect('admin/orders');
});

Route::get('/admin/transfer-list', [TransferController::class, 'index']);
Route::get('/admin/transfer-list/{id}', [TransferController::class, 'show']);
Route::post('/get-data', [TransferController::class, 'getData']);

Route::put('/update-order/{id}', [OrderController::class, 'update']);
Route::put('/update-unit-price/{id}', [UnitPriceController::class, 'update']);
Route::post('/add-unit-price', [UnitPriceController::class, 'store']);
Route::post('/add-product', [ProductController::class, 'store']);
Route::put('/update-product/{id}', [ProductController::class, 'update']);

// for order
Route::get('/get-pdf/{id}', [OrderController::class, 'createPDF']);
Route::get('/get-excel/{id}', [OrderController::class, 'createExcel']);

//for transfers
Route::get('/get-pdf/{id}', [TransferController::class, 'createPDF']);
Route::get('/get-excel/{id}', [TransferController::class, 'createExcel']);

// for purchase invoice
Route::post('/add-purchase-invoice', [PurchaseInvoiceController::class, 'store']);
Route::get('/autocomplete-product', [AutoCompleteController::class, 'autocompleteProduct']);
Route::get('/autocomplete-unit', [AutoCompleteController::class, 'autocompleteUnit']);



Route::post('/add-user', [UserController::class, 'store']);
Route::put('/update-user/{id}', [UserController::class, 'update']);
Route::get('/admin/products-without-units', [ProductController::class, 'getProducts']);

// for stock report
Route::get('/admin/stock-report', [StockController::class, 'getReport']);


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});


Route::get('download/{file}', [OrderController::class, 'getPdf']);

Route::get('orders/export/{id}', [OrderController::class, 'export']);
Route::get('/admin/filter-orders', [OrderController::class, 'filterOrder']);

// for order report
Route::get('/admin/order-report', [OrderController::class, 'getReport']);

// Route::get('storage/{filename}', function ($filename)
// {
//     $path = storage_path('public/' . $filename);

//     if (!File::exists($path)) {
//         abort(404);
//     }

//     $file = File::get($path);
//     $type = File::mimeType($path);

//     $response = Response::make($file, 200);
//     $response->header("Content-Type", $type);

//     return $response;
// });
