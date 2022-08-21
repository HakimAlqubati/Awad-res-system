<?php

namespace App\Http\Controllers\Voyager;

use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetails;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\UnitPrice;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;

class StockController extends VoyagerBaseController
{
    use BreadRelationshipParser;

    public function getReportV2(Request $request)
    {

        $data = UnitPrice::whereNotNull('id');

        if (isset($request->product_id) && $request->product_id != null) {
            $data->where('product_id', $request->product_id);
        }
        $data =  $data->get();


        foreach ($data as $key => $value) {
            $obj = new stdClass();
            $obj->product_id = $value->product_id;
            $obj->product_name = $value->product->name;
            $obj->unit_name = $value->unit->name;
            $obj->qty_in_purchase = $this->getQuantityInPurchase($value->product_id, $value->unit_id);
            $obj->qty_in_orders = $this->getQuantityInOrders($value->product_id, $value->unit_id);
            $obj->remaining_qty = $this->getQuantityInPurchase($value->product_id, $value->unit_id) - $this->getQuantityInOrders($value->product_id, $value->unit_id);
            $final_result[] = $obj;
        }


        // dd($final_result);

        return view('voyager::stock.stock-report-v2', compact('final_result'));
    }

    public function getQuantityInPurchase($product_id, $unit_id)
    {
        return PurchaseInvoiceDetails::where('product_id', $product_id)->where('unit_id', $unit_id)->get()->sum('qty');
    }

    public function getQuantityInOrders($product_id, $unit_id)
    {
        return OrderDetails::where('product_unit_id', $unit_id)->where('product_id', $product_id)->get()->sum('qty');
    }
    public function getReport(Request $request)
    {


        $strSelect = "select
        purchase_invoices.supplier_id,
        users.name as supplier_name,
        purchase_invoices.stock_id,
        stocks.name as stock_name,
        purchase_invoice_details.product_id,
        products.name as product_name,
        purchase_invoice_details.unit_id,
        units.name as unit_name,
        sum(purchase_invoice_details.qty) as qty,  
        sum(purchase_invoice_details.ordered_qty) as ordered_qty,  
        purchase_invoice_details.price
        from
        purchase_invoices
        left join purchase_invoice_details on (
            purchase_invoices.id = purchase_invoice_details.purchase_invoice_id
        )
        left join products on (
            products.id = purchase_invoice_details.product_id
        )
        left join users on (users.id = purchase_invoices.supplier_id)
        left join units on (units.id = purchase_invoice_details.unit_id)
        left join stocks on (stocks.id = purchase_invoices.stock_id)
      
     
         ";


        $strSelect .= "where purchase_invoices.id IS NOT NULL";

        if ($request->supplier_id && $request->supplier_id != null) {
            $strSelect .= " and  purchase_invoices.supplier_id = " . $request->supplier_id;
        }

        if ($request->product_id && $request->product_id != null) {
            $strSelect .= " and  purchase_invoice_details.product_id = " . $request->product_id;
        }

        if ($request->stock_id && $request->stock_id != null) {
            $strSelect .= "  and purchase_invoices.stock_id = " . $request->stock_id;
        }

        if ($request->unit_id && $request->unit_id != null) {
            $strSelect .= "  and purchase_invoice_details.unit_id = " . $request->unit_id;
        }




        $strSelect .= " group by
        purchase_invoice_details.product_id
        , purchase_invoice_details.unit_id ";

        $data = DB::select($strSelect);

        // dd($data);
        $stocks = Stock::get();

        $suppliers = User::where('role_id', 9)->get();

        $products = Product::get();
        $units = Unit::get();

        return view(
            'voyager::stock.stock-report',
            compact(
                'data',
                'stocks',
                'suppliers',
                'products',
                'units'
            )
        );
    }

    public function stock(Request $request)
    {
    }

    public function get(Request $request)
    {
    }
}
