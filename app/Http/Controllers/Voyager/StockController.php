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
    public function getReport()
    {

        $strSelect = "select purchase_invoices.supplier_id , users.name as supplier_name 
        , purchase_invoices.stock_id , stocks.name as stock_name
         ,purchase_invoice_details.product_id , products.name as product_name
         , purchase_invoice_details.unit_id, units.name as unit_name
         , purchase_invoice_details.qty
         ,purchase_invoice_details.price 
         
         from purchase_invoices
         left join purchase_invoice_details on (purchase_invoices.id = purchase_invoice_details.purchase_invoice_id) 
         left join products on (products.id = purchase_invoice_details.product_id) 
         left join users on (users.id = purchase_invoices.supplier_id) 
         left join units on (units.id =purchase_invoice_details.unit_id) 
         left join stocks on (stocks.id = purchase_invoices.stock_id) 
         ";

        $data = DB::select($strSelect);
       
        return view('voyager::stock.stock-report', compact('data'));
    }

    public function stock(Request $request)
    {
    }

    public function get(Request $request)
    {
    }
}
