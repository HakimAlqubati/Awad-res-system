<?php

namespace  App\Http\Controllers\Voyager;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\RequestState;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use stdClass;
use PDF;

use App\Exports\OrderExport;
// use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Facades\Excel;

class OrderController extends  VoyagerBaseController
{
    use BreadRelationshipParser;

    //***************************************
    //               ____
    //              |  _ \
    //              | |_) |
    //              |  _ <
    //              | |_) |
    //              |____/
    //
    //      Browse our Data Type (B)READ
    //
    //****************************************

    public function getReport(Request $request)
    {

        // dd($request->all());
        $strSelect = "select orders.id, orders.request_state_id , request_states.name as state_name
        , orders.created_by,  users.name as created_by_name ,orders.branch_id, branches.name as branch_name
       ,  orders.created_at
        ,order_details.product_id , products.name as product_name, order_details.product_unit_id , units.name as unit_name
        , sum(order_details.qty) as qty , sum(order_details.price) as price
        from orders
        left join order_details on (orders.id = order_details.order_id)
        left join branches on (orders.branch_id = branches.id)
        left join request_states on (orders.request_state_id = request_states.id)
        left join users on (orders.created_by = users.id)
        left join products on (order_details.product_id = products.id)
        left join units on (order_details.product_unit_id = units.id)
         ";

        $strSelect .= "where orders.active =1 and order_details.available_in_store = 1";

        if ($request->branch_id && $request->branch_id != null) {
            // $strSelect .= " and  orders.branch_id = " . $request->branch_id;
            $strSelect .= " and  orders.branch_id in ( " .  implode(',', $request->branch_id) . " ) ";
        }


        if ($request->product_id && $request->product_id != null) {
            $strSelect .= " and  order_details.product_id in ( " . implode(',', $request->product_id) . " )";
        }



        if ($request->unit_id && $request->unit_id != null) {
            $strSelect .= " and  order_details.product_unit_id = " . $request->unit_id;
        }




        $from_date = null;
        $to_date = null;

        if (($request->from_date)) {
            $from_date = $request->from_date;
            if ($request->to_date) {
                $to_date = $request->to_date;
            } else {
                $to_date = date('Y-m-d');
                // $to_date = date('Y-m-d', strtotime(DB::select('select max(created_at) as max_date from orders')[0]->max_date));
            }

            $strSelect .= " and orders.created_at between '$from_date' AND '$to_date'  ";
        }
        //  else {
        //     $from_date =  date('Y-m-d', strtotime(DB::select('select min(created_at) as min_date from orders')[0]->min_date));
        //     $to_date = date('Y-m-d', strtotime(DB::select('select max(created_at) as max_date from orders')[0]->max_date));
        // }



        // $strSelect .= " where orders.created_at between '$from_date' AND '$to_date'  ";


        $strSelect .= " group by
        products.cat_id,
        order_details.product_unit_id,
        order_details.product_id
        ";


        $strSelect .= " ORDER BY id DESC
        ";


        if ($request->branch_id && $request->branch_id != null) {
            $data = DB::select($strSelect);
        } else {
            $data = [];
        }
        // dd($request->branch_id, $data, $strSelect);

        $branches = Branch::get();
        $products = Product::get();
        $units = Unit::get();
        return view('voyager::orders.order-report', compact('data', 'branches', 'products', 'units', 'from_date', 'to_date'));
    }
    public function index(Request $request)
    {
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];
        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            $query = $model::select($dataType->name . '.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%' . $search->value . '%';

                $searchField = $dataType->name . '.' . $search->key;
                if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck('id')->toArray()
                    );
                } else {
                    if ($dataType->browseRows->pluck('field')->contains($search->key)) {
                        $query->where($searchField, $search_filter, $search_value);
                    }
                }
            }

            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name . '.*',
                        'joined.' . $row->details->label . ' as ' . $orderBy,
                    ])->leftJoin(
                        $row->details->table . ' as joined',
                        $dataType->name . '.' . $row->details->column,
                        'joined.' . $row->details->key
                    );
                }

                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);

        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }

        return Voyager::view($view, compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortableColumns',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn'
        ));
    }

    //***************************************
    //                _____
    //               |  __ \
    //               | |__) |
    //               |  _  /
    //               | | \ \
    //               |_|  \_\
    //
    //  Read an item of our Data Type B(R)EAD
    //
    //****************************************

    public function show(Request $request, $id)
    {


        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $isSoftDeleted = false;

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
            if ($dataTypeContent->deleted_at) {
                $isSoftDeleted = true;
            }
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        // Replace relationships' keys for labels and create READ links if a slug is provided.
        $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType, true);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'read');

        // Check permission
        $this->authorize('read', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'read', $isModelTranslatable);

        $view = 'voyager::bread.read';

        if (view()->exists("voyager::$slug.read")) {
            $view = "voyager::$slug.read";
        }

        $order = Order::where('id', $id)->get();

        $finalResultOrder = [];
        $finalResult = [];


        foreach ($order as   $val) {
            $obj = new stdClass();
            $obj->id = $val->id;
            $obj->desc =  $val->desc;
            $obj->branch_id = $val->branch_id;
            $obj->branch_name =  Branch::where('id', $val->branch_id)->get()[0]->name;
            $obj->created_at = $val->created_at;
            $obj->request_state_id = $val->request_state_id;
            $obj->request_state_name = $this->getStateNameById($val->request_state_id)[0]->name;
            $obj->restricted_state_name =    $this->getStateNameById($val->restricted_state_id)[0]->name;;;
            $obj->created_by  = $val->created_by;

            $obj->notes  = $val->notes;
            $obj->user_name  =  User::where('id', $val->created_by)->get()[0]->name;;
            $finalResultOrder[] = $obj;
        }

        $orderDetails = OrderDetails::where('order_id', $order[0]->id)->get();



        foreach ($orderDetails as $key => $value) {

            $obj = new stdClass();

            if ($value->product_id != null && $value->product_unit_id != null && $value->price != null) {
                $obj->product_id = $value->product_id;
                $obj->product_name = $this->getProductProductNameById($value->product_id)[0]->name;
                $obj->unit_id = $value->product_unit_id;
                $obj->unit_name = $this->getUnitNameById($value->product_unit_id)[0]->name;
                $obj->price =  $value->price;
            } else {
                $obj->product_id = null;
                $obj->product_name =  $value->product_name;
                $obj->unit_id = null;
                $obj->unit_name = null;
                $obj->price =  null;
            }

            $obj->qty = $value->qty;
            $finalResult[] = $obj;
        }



        // $unitData = $this->getUnitNameById();
        return Voyager::view($view, compact(
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'isSoftDeleted',
            'finalResult',
            'finalResultOrder'
        ));
    }

    public function getUnitNameById($id)
    {
        return Unit::where('id', $id)->get();
    }


    public function getProductProductNameById($id)
    {
        return Product::where('id', $id)->get();
    }

    public function getStateNameById($id)
    {
        return RequestState::where('id', $id)->get();
    }
    //***************************************
    //                ______
    //               |  ____|
    //               | |__
    //               |  __|
    //               | |____
    //               |______|
    //
    //  Edit an item of our Data Type BR(E)AD
    //
    //****************************************

    public function edit(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);
            $query = $model->query();

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query = $query->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
                $query = $query->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$query, 'findOrFail'], $id);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }
        $order = Order::where('id', $id)->get();

        $created_by = $order[0]->created_by;
        $desc = $order[0]->desc;
        $request_state_id = $order[0]->request_state_id;
        $restricted_state_id = $order[0]->restricted_state_id;
        $created_at = $order[0]->created_at;
        $id = $order[0]->id;

        $userName =  User::where('id', $created_by)->get()[0]->name;
        $stateName = $this->getStateNameById($request_state_id)[0]->name;
        $restrectedStateName = $this->getStateNameById($restricted_state_id)[0]->name;
        $arrayOrder = array(
            "created_by" => $created_by, "desc" => $desc, "request_state_id" => $request_state_id,
            "restricted_state_id" => $restricted_state_id, "created_at" => $created_at, "id" => $id,
            "user_name" => $userName, "state_name" => $stateName
        );

        $requestStates = RequestState::whereIn('id', array(2, 3, 4, 5, 8))->get();
        $destrectedStates = RequestState::whereIn('id', array(6, 7))->get();

        $orderDetails =  OrderDetails::where('order_id', $id)->get();
        // foreach ($orderDetails as $key => $value) {
        //     $obj = new stdClass();
        //     $obj->order_detail_id = $value->id;
        //     $obj->order_id = $value->order_id;
        //     $obj->product_id = $value->product_id;
        //     $obj->product_name = $this->getProductProductNameById($value->product_id)[0]->name;
        //     $obj->unit_id = $value->product_unit_id;
        //     $obj->unit_name = $this->getUnitNameById($value->product_unit_id)[0]->name;
        //     $obj->price =  $value->price;
        //     $obj->qty = $value->available_qty;
        //     $orderDetailsForEdit[] = $obj;
        // }

        foreach ($orderDetails as $key => $value) {
            $obj = new stdClass();

            $obj->order_detail_id = $value->id;
            $obj->order_id = $value->order_id;
            if ($value->product_id != null && $value->product_unit_id != null && $value->price != null) {
                $obj->product_id = $value->product_id;
                $obj->product_name = $this->getProductProductNameById($value->product_id)[0]->name;
                $obj->unit_id = $value->product_unit_id;
                $obj->unit_name = $this->getUnitNameById($value->product_unit_id)[0]->name;
                $obj->price =  $value->price;
            } else {
                $obj->product_id = null;
                $obj->product_name =  $value->product_name;
                $obj->unit_id = null;
                $obj->unit_name = '--';
                $obj->price =  0;
            }

            $obj->qty = $value->qty;
            $orderDetailsForEdit[] = $obj;
        }

        // dd($finalResult);
        return Voyager::view($view, compact(
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'arrayOrder',
            'requestStates',
            'destrectedStates',
            'orderDetailsForEdit'
        ));
    }

    // POST BR(E)AD
    public function update(Request $request, $id)
    {
      

        $order = Order::find($id);
        if ($request->user()->role_id != 5) {
            $order->request_state_id =    $request->request_state_id;
        }

        $order->restricted_state_id =    $request->restricted_state_id;

        foreach ($request->order_detail_id as $key => $value) {
            $order_detail = $request->order_detail_id[$key];
            $qty = $request->qty[$key];
            $price = $request->price[$key];
            $orderDetail = OrderDetails::find($order_detail);
            if ($qty > 0) {

                $orderDetail->available_qty = $qty;
            }
            $orderDetail->price = $price;
            $orderDetail->save();
        }

        $update =  $order->save();

        if ($update == 1) {
            $slug = 'orders';
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

            $redirect = redirect()->route("voyager.{$dataType->slug}.index");
            return $redirect->with([
                'message'    =>   " Order #" . $id . " Updated Successfully",
                'alert-type' => 'success',
            ]);
        }

        // dd($requestStateId . " - " . $restrectedStateId);
        // $slug = $this->getSlug($request);

        // $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // // Compatibility with Model binding.
        // $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        // $model = app($dataType->model_name);
        // $query = $model->query();
        // if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
        //     $query = $query->{$dataType->scope}();
        // }
        // if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
        //     $query = $query->withTrashed();
        // }

        // $data = $query->findOrFail($id);

        // // Check permission
        // $this->authorize('edit', $data);

        // // Validate fields with ajax
        // $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();

        // // Get fields with images to remove before updating and make a copy of $data
        // $to_remove = $dataType->editRows->where('type', 'image')
        //     ->filter(function ($item, $key) use ($request) {
        //         return $request->hasFile($item->field);
        //     });
        // $original_data = clone ($data);

        // $this->insertUpdateData($request, $slug, $dataType->editRows, $data);

        // // Delete Images
        // $this->deleteBreadImages($original_data, $to_remove);

        // event(new BreadDataUpdated($dataType, $data));

        // if (auth()->user()->can('browse', app($dataType->model_name))) {
        //     $redirect = redirect()->route("voyager.{$dataType->slug}.index");
        // } else {
        // }

    }

    //***************************************
    //
    //                   /\
    //                  /  \
    //                 / /\ \
    //                / ____ \
    //               /_/    \_\
    //
    //
    // Add a new item of our Data Type BRE(A)D
    //
    //****************************************

    public function create(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        $dataTypeContent = (strlen($dataType->model_name) != 0)
            ? new $dataType->model_name()
            : false;

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'add');

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'add', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    /**
     * POST BRE(A)D - Store data.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));

        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
                $redirect = redirect()->route("voyager.{$dataType->slug}.index");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message'    => __('voyager::generic.successfully_added_new') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }

    //***************************************
    //                _____
    //               |  __ \
    //               | |  | |
    //               | |  | |
    //               | |__| |
    //               |_____/
    //
    //         Delete an item BREA(D)
    //
    //****************************************

    public function destroy(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Init array of IDs
        $ids = [];
        if (empty($id)) {
            // Bulk delete, get IDs from POST
            $ids = explode(',', $request->ids);
        } else {
            // Single item delete, get ID from URL
            $ids[] = $id;
        }
        foreach ($ids as $id) {
            $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);

            // Check permission
            $this->authorize('delete', $data);

            $model = app($dataType->model_name);
            if (!($model && in_array(SoftDeletes::class, class_uses_recursive($model)))) {
                $this->cleanup($dataType, $data);
            }
        }

        $displayName = count($ids) > 1 ? $dataType->getTranslatedAttribute('display_name_plural') : $dataType->getTranslatedAttribute('display_name_singular');

        $res = $data->destroy($ids);
        $data = $res
            ? [
                'message'    => __('voyager::generic.successfully_deleted') . " {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_deleting') . " {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataDeleted($dataType, $data));
        }

        return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
    }

    public function restore(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $model = app($dataType->model_name);
        $this->authorize('delete', $model);

        // Get record
        $query = $model->withTrashed();
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
            $query = $query->{$dataType->scope}();
        }
        $data = $query->findOrFail($id);

        $displayName = $dataType->getTranslatedAttribute('display_name_singular');

        $res = $data->restore($id);
        $data = $res
            ? [
                'message'    => __('voyager::generic.successfully_restored') . " {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_restoring') . " {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataRestored($dataType, $data));
        }

        return redirect()->route("voyager.{$dataType->slug}.index")->with($data);
    }

    //***************************************
    //
    //  Delete uploaded file
    //
    //****************************************

    public function remove_media(Request $request)
    {
        try {
            // GET THE SLUG, ex. 'posts', 'pages', etc.
            $slug = $request->get('slug');

            // GET file name
            $filename = $request->get('filename');

            // GET record id
            $id = $request->get('id');

            // GET field name
            $field = $request->get('field');

            // GET multi value
            $multi = $request->get('multi');

            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

            // Load model and find record
            $model = app($dataType->model_name);
            $data = $model::find([$id])->first();

            // Check if field exists
            if (!isset($data->{$field})) {
                throw new Exception(__('voyager::generic.field_does_not_exist'), 400);
            }

            // Check permission
            $this->authorize('edit', $data);

            if (@json_decode($multi)) {
                // Check if valid json
                if (is_null(@json_decode($data->{$field}))) {
                    throw new Exception(__('voyager::json.invalid'), 500);
                }

                // Decode field value
                $fieldData = @json_decode($data->{$field}, true);
                $key = null;

                // Check if we're dealing with a nested array for the case of multiple files
                if (is_array($fieldData[0])) {
                    foreach ($fieldData as $index => $file) {
                        // file type has a different structure than images
                        if (!empty($file['original_name'])) {
                            if ($file['original_name'] == $filename) {
                                $key = $index;
                                break;
                            }
                        } else {
                            $file = array_flip($file);
                            if (array_key_exists($filename, $file)) {
                                $key = $index;
                                break;
                            }
                        }
                    }
                } else {
                    $key = array_search($filename, $fieldData);
                }

                // Check if file was found in array
                if (is_null($key) || $key === false) {
                    throw new Exception(__('voyager::media.file_does_not_exist'), 400);
                }

                $fileToRemove = $fieldData[$key]['download_link'] ?? $fieldData[$key];

                // Remove file from array
                unset($fieldData[$key]);

                // Generate json and update field
                $data->{$field} = empty($fieldData) ? null : json_encode(array_values($fieldData));
            } else {
                if ($filename == $data->{$field}) {
                    $fileToRemove = $data->{$field};

                    $data->{$field} = null;
                } else {
                    throw new Exception(__('voyager::media.file_does_not_exist'), 400);
                }
            }

            $row = $dataType->rows->where('field', $field)->first();

            // Remove file from filesystem
            if (in_array($row->type, ['image', 'multiple_images'])) {
                $this->deleteBreadImages($data, [$row], $fileToRemove);
            } else {
                $this->deleteFileIfExists($fileToRemove);
            }

            $data->save();

            return response()->json([
                'data' => [
                    'status'  => 200,
                    'message' => __('voyager::media.file_removed'),
                ],
            ]);
        } catch (Exception $e) {
            $code = 500;
            $message = __('voyager::generic.internal_error');

            if ($e->getCode()) {
                $code = $e->getCode();
            }

            if ($e->getMessage()) {
                $message = $e->getMessage();
            }

            return response()->json([
                'data' => [
                    'status'  => $code,
                    'message' => $message,
                ],
            ], $code);
        }
    }

    /**
     * Remove translations, images and files related to a BREAD item.
     *
     * @param \Illuminate\Database\Eloquent\Model $dataType
     * @param \Illuminate\Database\Eloquent\Model $data
     *
     * @return void
     */
    protected function cleanup($dataType, $data)
    {
        // Delete Translations, if present
        if (is_bread_translatable($data)) {
            $data->deleteAttributeTranslations($data->getTranslatableAttributes());
        }

        // Delete Images
        $this->deleteBreadImages($data, $dataType->deleteRows->whereIn('type', ['image', 'multiple_images']));

        // Delete Files
        foreach ($dataType->deleteRows->where('type', 'file') as $row) {
            if (isset($data->{$row->field})) {
                foreach (json_decode($data->{$row->field}) as $file) {
                    $this->deleteFileIfExists($file->download_link);
                }
            }
        }

        // Delete media-picker files
        $dataType->rows->where('type', 'media_picker')->where('details.delete_files', true)->each(function ($row) use ($data) {
            $content = $data->{$row->field};
            if (isset($content)) {
                if (!is_array($content)) {
                    $content = json_decode($content);
                }
                if (is_array($content)) {
                    foreach ($content as $file) {
                        $this->deleteFileIfExists($file);
                    }
                } else {
                    $this->deleteFileIfExists($content);
                }
            }
        });
    }

    /**
     * Delete all images related to a BREAD item.
     *
     * @param \Illuminate\Database\Eloquent\Model $data
     * @param \Illuminate\Database\Eloquent\Model $rows
     *
     * @return void
     */
    public function deleteBreadImages($data, $rows, $single_image = null)
    {
        $imagesDeleted = false;

        foreach ($rows as $row) {
            if ($row->type == 'multiple_images') {
                $images_to_remove = json_decode($data->getOriginal($row->field), true) ?? [];
            } else {
                $images_to_remove = [$data->getOriginal($row->field)];
            }

            foreach ($images_to_remove as $image) {
                // Remove only $single_image if we are removing from bread edit
                if ($image != config('voyager.user.default_avatar') && (is_null($single_image) || $single_image == $image)) {
                    $this->deleteFileIfExists($image);
                    $imagesDeleted = true;

                    if (isset($row->details->thumbnails)) {
                        foreach ($row->details->thumbnails as $thumbnail) {
                            $ext = explode('.', $image);
                            $extension = '.' . $ext[count($ext) - 1];

                            $path = str_replace($extension, '', $image);

                            $thumb_name = $thumbnail->name;

                            $this->deleteFileIfExists($path . '-' . $thumb_name . $extension);
                        }
                    }
                }
            }
        }

        if ($imagesDeleted) {
            event(new BreadImagesDeleted($data, $rows));
        }
    }

    /**
     * Order BREAD items.
     *
     * @param string $table
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function order(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        if (empty($dataType->order_column) || empty($dataType->order_display_column)) {
            return redirect()
                ->route("voyager.{$dataType->slug}.index")
                ->with([
                    'message'    => __('voyager::bread.ordering_not_set'),
                    'alert-type' => 'error',
                ]);
        }

        $model = app($dataType->model_name);
        $query = $model->query();
        if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query = $query->withTrashed();
        }
        $results = $query->orderBy($dataType->order_column, $dataType->order_direction)->get();

        $display_column = $dataType->order_display_column;

        $dataRow = Voyager::model('DataRow')->whereDataTypeId($dataType->id)->whereField($display_column)->first();

        $view = 'voyager::bread.order';

        if (view()->exists("voyager::$slug.order")) {
            $view = "voyager::$slug.order";
        }

        return Voyager::view($view, compact(
            'dataType',
            'display_column',
            'dataRow',
            'results'
        ));
    }

    public function update_order(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        $model = app($dataType->model_name);

        $order = json_decode($request->input('order'));
        $column = $dataType->order_column;
        foreach ($order as $key => $item) {
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $i = $model->withTrashed()->findOrFail($item->id);
            } else {
                $i = $model->findOrFail($item->id);
            }
            $i->$column = ($key + 1);
            $i->save();
        }
    }

    public function action(Request $request)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $action = new $request->action($dataType, null);

        return $action->massAction(explode(',', $request->ids), $request->headers->get('referer'));
    }

    /**
     * Get BREAD relations data.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function relation(Request $request)
    {
        $slug = $this->getSlug($request);
        $page = $request->input('page');
        $on_page = 50;
        $search = $request->input('search', false);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $method = $request->input('method', 'add');

        $model = app($dataType->model_name);
        if ($method != 'add') {
            $model = $model->find($request->input('id'));
        }

        $this->authorize($method, $model);

        $rows = $dataType->{$method . 'Rows'};
        foreach ($rows as $key => $row) {
            if ($row->field === $request->input('type')) {
                $options = $row->details;
                $model = app($options->model);
                $skip = $on_page * ($page - 1);

                $additional_attributes = $model->additional_attributes ?? [];

                // Apply local scope if it is defined in the relationship-options
                if (isset($options->scope) && $options->scope != '' && method_exists($model, 'scope' . ucfirst($options->scope))) {
                    $model = $model->{$options->scope}();
                }

                // If search query, use LIKE to filter results depending on field label
                if ($search) {
                    // If we are using additional_attribute as label
                    if (in_array($options->label, $additional_attributes)) {
                        $relationshipOptions = $model->get();
                        $relationshipOptions = $relationshipOptions->filter(function ($model) use ($search, $options) {
                            return stripos($model->{$options->label}, $search) !== false;
                        });
                        $total_count = $relationshipOptions->count();
                        $relationshipOptions = $relationshipOptions->forPage($page, $on_page);
                    } else {
                        $total_count = $model->where($options->label, 'LIKE', '%' . $search . '%')->count();
                        $relationshipOptions = $model->take($on_page)->skip($skip)
                            ->where($options->label, 'LIKE', '%' . $search . '%')
                            ->get();
                    }
                } else {
                    $total_count = $model->count();
                    $relationshipOptions = $model->take($on_page)->skip($skip)->get();
                }

                $results = [];

                if (!$row->required && !$search && $page == 1) {
                    $results[] = [
                        'id'   => '',
                        'text' => __('voyager::generic.none'),
                    ];
                }

                // Sort results
                if (!empty($options->sort->field)) {
                    if (!empty($options->sort->direction) && strtolower($options->sort->direction) == 'desc') {
                        $relationshipOptions = $relationshipOptions->sortByDesc($options->sort->field);
                    } else {
                        $relationshipOptions = $relationshipOptions->sortBy($options->sort->field);
                    }
                }

                foreach ($relationshipOptions as $relationshipOption) {
                    $results[] = [
                        'id'   => $relationshipOption->{$options->key},
                        'text' => $relationshipOption->{$options->label},
                    ];
                }

                return response()->json([
                    'results'    => $results,
                    'pagination' => [
                        'more' => ($total_count > ($skip + $on_page)),
                    ],
                ]);
            }
        }

        // No result found, return empty array
        return response()->json([], 404);
    }

    protected function findSearchableRelationshipRow($relationshipRows, $searchKey)
    {
        return $relationshipRows->filter(function ($item) use ($searchKey) {
            if ($item->details->column != $searchKey) {
                return false;
            }
            if ($item->details->type != 'belongsTo') {
                return false;
            }

            return !$this->relationIsUsingAccessorAsLabel($item->details);
        })->first();
    }

    protected function getSortableColumns($rows)
    {
        return $rows->filter(function ($item) {
            if ($item->type != 'relationship') {
                return true;
            }
            if ($item->details->type != 'belongsTo') {
                return false;
            }

            return !$this->relationIsUsingAccessorAsLabel($item->details);
        })
            ->pluck('field')
            ->toArray();
    }

    protected function relationIsUsingAccessorAsLabel($details)
    {
        return in_array($details->label, app($details->model)->additional_attributes ?? []);
    }

    // Generate PDF
    public function createPDF(Request $request, $id)
    {
        // retreive all records from db

        $order = Order::where('id', $id)->get();
        $orderDetails = OrderDetails::where('order_id', $order[0]->id)->get();

        $objOrder = new stdClass();
        $objOrder->orderId = $order[0]->id;
        $objOrder->createdBy = $order[0]->created_by;
        $objOrder->createdByUserName =  User::where('id', $order[0]->created_by)->get()[0]->name;
        $objOrder->createdAt = $order[0]->created_at;
        $objOrder->stateId = $order[0]->request_state_id;
        $objOrder->state_name =  $this->getStateNameById($order[0]->request_state_id)[0]->name;;
        $objOrder->restricted_state_name =  $this->getStateNameById($order[0]->restricted_state_id)[0]->name;;
        $objOrder->desc = $order[0]->desc;
        $objOrder->branch_id = $order[0]->branch_id;
        $objOrder->branch_name =  Branch::where('id', $order[0]->branch_id)->get()[0]->name;
        $objOrder->manager_name = User::where('role_id', 4)->get()[0]->name;
        $objOrder->notes =   $order[0]->notes;



        $finalResult[] = $objOrder;
        foreach ($orderDetails as $key => $value) {
            $obj = new stdClass();
            $obj->product_id = $value->product_id ?? '--';

            $obj->product_name =  $this->getProductProductNameById($value->product_id)[0]->name ?? $value->product_name;
            $obj->product_code = $value->product_id > 0 ?  $this->getProductProductNameById($value->product_id)[0]->code  ?? '--' : '--';
            $obj->product_desc = $value->product_id > 0 ?   $this->getProductProductNameById($value->product_id)[0]->desc  ?? '--' : '--';
            $obj->unit_id = $value->product_unit_id ?? null;
            $obj->unit_name = $this->getUnitNameById($value->product_unit_id)[0]->name ?? '--';
            $obj->price =  $value->price ?? '--';
            $obj->qty = $value->qty;
            array_push($finalResult, $obj);
        }

        // dd($finalResult);
        // foreach ($finalResult as $key => $value) {
        //     if ($key > 0) {


        //         $obj = new stdClass();

        //         $obj->key =   $key;
        //         $obj->product_id =  $value->product_id;
        //         $obj->product_name =     $value->product_name;
        //         $obj->unit_name =    $value->unit_name;
        //         $obj->product_code =    $value->product_code;
        //         $obj->product_desc =   $value->product_desc;
        //         $obj->qty =    $value->qty;
        //         $obj->price =  $value->price;
        //         $array[] = $obj;
        //     }
        // }

        // dd($array);

        return view('vendor.voyager.orders.pdf_view', compact('finalResult'));
        // share data to view
        // view()->share('vendor.voyager.orders.pdf_view', $finalResult);
        $pdf = PDF::loadView('vendor.voyager.orders.pdf_view', ['finalResult' => $finalResult]);

        $content = $pdf->download()->getOriginalContent();

        $down =    Storage::put('public/pdf_files/order-no' . $order[0]->id . '.pdf', $content);

        if ($down == 1) {


            return Redirect::back()->with([
                'message'    => "done download successfully",
                'alert-type' => 'success',
            ]);
        }
        return $down;
        // download PDF file with download method
        // return $pdf->download('pdf_file.pdf');
    }

    public function export($id)
    {

        return Excel::download(new OrderExport, 'order-no-' . $id . '-.xlsx');
    }
}
