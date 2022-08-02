<?php

namespace App\Http\Controllers\Voyager;

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
        sum(purchase_invoice_details.qty) as pur_qty,
        sum(order_details.qty) as or_qty,
        order_details.qty as or2_qty,
        case when order_details.qty IS not NULL or order_details.qty != ''
           then sum(purchase_invoice_details.qty)  - sum(order_details.qty)
           else sum(purchase_invoice_details.qty) 
        end as qty ,
        -- sum(purchase_invoice_details.qty) as qty,
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
        left join order_details on
        (
           (order_details.product_id = purchase_invoice_details.product_id)
            and 
           (order_details.product_unit_id = purchase_invoice_details.unit_id)
        )
     
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
        , purchase_invoice_details.unit_id
        ,  order_details.product_unit_id
        , order_details.product_id"
        ;

        // dd($strSelect);
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
