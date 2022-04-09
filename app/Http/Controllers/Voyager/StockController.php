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

        $purchaseInoivce = PurchaseInvoice::get();
        $purchaseInoivceDetails = PurchaseInvoiceDetails::get();

        
        return view('voyager::stock.stock-report');
    }
}
