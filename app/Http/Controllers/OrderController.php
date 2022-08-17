<?php

namespace App\Http\Controllers;

use App\Jobs\FcmNotificationJob;
use App\Models\Branch;
use App\Models\NotificationOrder;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\PurchaseInvoiceDetails;
use App\Models\RequestState;
use App\Models\UnitPrice;
use App\Models\User;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use stdClass;
use PDF;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function index(Request $request)
    {

        $currentRole = $request->user()->role_id;

        $order_id = $request->order_id;
        $created_by = $request->created_by;
        $request_state_id = $request->request_state_id;
        $restricted_state_id = $request->restricted_state_id;
        $branch_id = $request->branch_id;


        if ($currentRole == 3) {
            $created_by = $request->user()->id;
        }
        $strSelect = 'select * from orders ';

        if (($order_id && $order_id != null)  || ($created_by && ($created_by != $request->user()->id))
            || ($request_state_id && $request_state_id != null) || ($restricted_state_id && $restricted_state_id != null)
            || ($branch_id && $branch_id != null)
        ) {

            $strSelect .=   " where (";

            if ($order_id) {
                $strSelect .= "id = "    . $order_id;
            }

            if ($currentRole != 3 && $created_by) {
                $this->checkSqlStatement($request, $strSelect);
                $strSelect .= "  created_by = " . $created_by;
            }

            if ($request_state_id) {
                $this->checkSqlStatement($request, $strSelect);
                if ($currentRole != 7) {
                    $request_state_id == null;
                }
                $strSelect .= "  request_state_id = " . $request_state_id;
            }

            if ($restricted_state_id) {
                $this->checkSqlStatement($request, $strSelect);
                $strSelect .= "  restricted_state_id = " . $restricted_state_id;
            }

            if ($branch_id) {

                $this->checkSqlStatement($request, $strSelect);
                $strSelect .= "  branch_id = " . $branch_id;
            }

            $strSelect .= " ) ";
        }

        if ($currentRole == 7) {

            if (($order_id && $order_id != null)  || ($created_by && ($created_by))
                || ($request_state_id && $request_state_id != null) || ($restricted_state_id && $restricted_state_id != null)
                || ($branch_id && $branch_id != null)
            ) {

                $strSelect .= " AND request_state_id <> 8  ";
            } else {


                $strSelect .= " where request_state_id <> 8  ";
            }
        }


        if ($currentRole == 3) {
            if (($order_id && $order_id != null)  || ($created_by && ($created_by != $request->user()->id))
                || ($request_state_id && $request_state_id != null) || ($restricted_state_id && $restricted_state_id != null)
                || ($branch_id && $branch_id != null)
            ) {
                $strSelect .= " AND created_by = $created_by  ";
            } else {
                $strSelect .= " where created_by = $created_by  ";
            }
        }

        $strSelect .= "ORDER BY id DESC limit 20";
        $dborders  =  DB::select($strSelect);


        if (count($dborders) > 0) {

            foreach ($dborders as $key => $value) {

                $obj = new stdClass();
                $obj->id = $value->id;
                $obj->desc = $value->desc;
                $obj->created_by = $value->created_by;
                $obj->created_by_user_name = $this->getUserDataById($value->created_by)[0]->name;
                $obj->request_state_id = $value->request_state_id;
                $obj->request_state_name = RequestState::where('id', $value->request_state_id)->get()[0]->name;
                $obj->restricted_state_id = $value->restricted_state_id;
                $obj->restricted_state_name = RequestState::where('id', $value->restricted_state_id)->get()[0]->name;
                $obj->branch_id = $value->branch_id;
                $obj->full_quantity = $value->full_quantity;
                $obj->notes = $value->notes;
                if ($value->branch_id != 0) {
                    $obj->branch_name =  Branch::where('id', $value->branch_id)->get()[0]->name;
                }
                $obj->created_at = $value->created_at;
                $obj->updated_at = $value->updated_at;
                $obj->total_price =  (int) $this->getOrderDetailsTotalPrice($value->id);
                $obj->order_details = $this->getOrderDetailsByOrderId($value->id);
                $array[] = $obj;
            }
            return $array;
        } else {
            return  [];
        }
    }


    public function checkSqlStatement(Request $request, $strSelect)
    {

        $order_id = $request->order_id;
        $created_by = $request->created_by;
        $request_state_id = $request->request_state_id;
        $restricted_state_id = $request->restricted_state_id;
        $branch_id = $request->branch_id;
        if ($order_id || $request_state_id || $created_by || $restricted_state_id || $branch_id) {
            $strSelect .= " AND ";
        }
    }


    public function getOrderDetailsByOrderId($id)
    {
        $data = OrderDetails::where('order_id', $id)->get();
        $totalPrice = 0;
        $fresult = array();
        foreach ($data as $key => $value) {
            // if ($value->available_in_store == 0) {
            $obj = new stdClass();
            $obj->id = $value->id;
            $obj->order_id = $value->order_id;
            // $obj->qty =   number_format($value->qty, 1);
            // $obj->qty =      $value->qty;
            $obj->qty = sprintf("%.2f", $value->qty);
            $obj->price = (int)  $value->price ?? 0;

            $obj->product_unit_id = $value->product_unit_id ?? null;
            $obj->product_unit_name =   $this->getUnitNameById($value->product_unit_id)[0]->name ?? '--';
            $obj->product_id = $value->product_id ?? null;
            $obj->product_name =  Product::where('id', $value->product_id)->first()->name  ?? $value->product_name;
            $obj->product_category =  Product::where('id', $value->product_id)->first()->cat_id  ?? '--';
            $obj->created_by_id = $value->created_by;

            $obj->created_by_name =   $this->getUserDataById($value->created_by)[0]->name;
            $obj->available_in_store = $value->available_in_store;
            $fresult[] = $obj;

            $price = $value->price;
            $totalPrice += $price;
            // }
        }
        return $fresult;
    }


    public function getOrderDetailsTotalPrice($id)
    {
        $data = OrderDetails::where('order_id', $id)->get();
        $totalPrice = 0;

        foreach ($data as $key => $value) {
            $price = $value->price;
            $totalPrice += $price;
        }


        return  $totalPrice;
    }
    public function getUserDataById($id)
    {
        return User::where('id', $id)->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $result =    DB::transaction(function () use ($request) {
            $currentRole =  $request->user()->role_id;
            $currentUser = $request->user();
            $branch = Branch::where('manager_id', $currentUser->id)->first();

            if ($currentRole == 3 &&  !$branch) {
                $obj = new stdClass();
                $obj->res = "faild";
                $obj->msg = "you are not manager for any branch";
            } elseif ($branch || $currentRole == 3 || $currentRole == 1  || $currentRole == 8) {
                // $storeManager = User::where('id', 3)->first();

                if ($currentRole == 3) {

                    FcmNotificationJob::dispatchNow("New Order", "Order from " . $currentUser->name .
                        " Manager of branch " .  $branch->name, $branch);
                    $branchId =  $branch->id;
                    $orderState = 2;
                    $createdBy = $request->user()->id;

                    $pendingApprovalOrder = Order::where(
                        [
                            [
                                'request_state_id', '=', 8
                            ],
                            [
                                'created_by', '=', $createdBy
                            ]
                        ]
                    )->get()->first();


                    if ($pendingApprovalOrder != null) {


                        if ($request->productsNotFound == null) {
                            $request->productsNotFound = array();
                        }

                        if ($request->orderDetails == null) {
                            $request->orderDetails = array();
                        }


                        $all_products = $this->addOrderDetails(
                            $request->orderDetails,

                            $pendingApprovalOrder->id,
                            $currentUser->id
                        );

                        $new_products = [];
                        foreach ($all_products as $valueOrderDetail) {
                            $data = $this->getOrderDetaisPendingToDelete($pendingApprovalOrder->id, $valueOrderDetail['product_unit_id'], $valueOrderDetail['product_id'], $valueOrderDetail['qty']);


                            if (!is_null($data)) {
                                $new_products[] = [
                                    'product_id' => $data->product_id,
                                    'product_unit_id' => $data->product_unit_id,
                                    'qty' => $data->qty + $valueOrderDetail['qty'],
                                    'available_qty' =>  $data->qty + $valueOrderDetail['qty'],
                                    'order_id' => $data->order_id,
                                    'price' => ($data->qty + $valueOrderDetail['qty']) * $this->getUnitPriceData($data->product_id, $data->product_unit_id)->price,
                                    'product_name' => null,
                                    'created_by' => $valueOrderDetail['created_by']
                                ];
                            } elseif (is_null($data)) {
                                $new_products[] = [
                                    'product_id' => $valueOrderDetail['product_id'],
                                    'product_unit_id' => $valueOrderDetail['product_unit_id'],
                                    'qty' =>  $valueOrderDetail['qty'],
                                    'available_qty' =>  $valueOrderDetail['available_qty'],
                                    'order_id' => $valueOrderDetail['order_id'],
                                    'price' => $valueOrderDetail['price'] * $valueOrderDetail['qty'],
                                    'product_name' => null,
                                    'created_by' => $valueOrderDetail['created_by']
                                ];
                            }
                        }


                        OrderDetails::insert($new_products);

                        $obj = new stdClass();
                        $obj->res = 'success';
                        $obj->msg = 'Your Order Submitted Successfully in the order no ' . $pendingApprovalOrder->id;
                        $obj->yourOrder = $pendingApprovalOrder->with('orderDetails')->where('id', $pendingApprovalOrder->id)->get();

                        return $obj;
                    }
                } elseif ($currentRole == 8) {
                    // dd($request);

                    $orderState = 8;
                    $branchId = Branch::where('manager_id', $request->user()->owner_id)->first()->id;
                    $createdBy = $request->user()->owner_id;

                    $pendingApprovalOrder = Order::where(
                        [
                            [
                                'request_state_id', '=', 8
                            ],
                            [
                                'created_by', '=', $createdBy
                            ]
                        ]
                    )->get()->first();


                    if ($pendingApprovalOrder != null) {

                        if ($request->productsNotFound == null) {
                            $request->productsNotFound = array();
                        }


                        if ($request->orderDetails == null) {
                            $request->orderDetails = array();
                        }



                        // dd(Auth::user()->id, $pendingApprovalOrder->id, $request->orderDetails);

                        $all_products = $this->addPendingOrderDetails(
                            $request->orderDetails,
                            $pendingApprovalOrder->id,
                            $currentUser->id
                        );

                        // dd($all_products);
                        OrderDetails::insert($all_products);


                        // dd($all_products);
                        // $new_products = [];
                        // foreach ($all_products as $valueOrderDetail) {

                        //     $data = $this->getOrderDetaisPendingToDelete($pendingApprovalOrder->id, $valueOrderDetail['product_unit_id'], $valueOrderDetail['product_id'], $valueOrderDetail['qty']);



                        //     if (!is_null($data)) {
                        //         // dd(
                        //         //     $data->qty,
                        //         //     $valueOrderDetail['qty'],
                        //         //     PurchaseInvoiceDetails::find($data->purchase_invoice_id)->price
                        //         // );
                        //         $new_products[] = [
                        //             'product_id' => $data->product_id,
                        //             'product_unit_id' => $data->product_unit_id,
                        //             'qty' => $data->qty + $valueOrderDetail['qty'],
                        //             'available_qty' =>  $data->qty + $valueOrderDetail['qty'],
                        //             'order_id' => $data->order_id,
                        //             'price' => ($data->qty + $valueOrderDetail['qty']) * PurchaseInvoiceDetails::find($data->purchase_invoice_id)->price,
                        //             'product_name' => null,
                        //             'created_by' => $valueOrderDetail['created_by']
                        //         ];
                        //     } elseif (is_null($data)) {
                        //         $new_products[] = [
                        //             'product_id' => $valueOrderDetail['product_id'],
                        //             'product_unit_id' => $valueOrderDetail['product_unit_id'],
                        //             'qty' =>  $valueOrderDetail['qty'],
                        //             'available_qty' =>  $valueOrderDetail['available_qty'],
                        //             'order_id' => $valueOrderDetail['order_id'],
                        //             'price' => $valueOrderDetail['price'] * $valueOrderDetail['qty'],
                        //             'product_name' => null,
                        //             'created_by' => $valueOrderDetail['created_by']
                        //         ];
                        //     }
                        // }

                        // OrderDetails::insert($new_products);


                        $obj = new stdClass();
                        $obj->res = 'success';
                        $obj->msg = 'Your Order Submitted Successfully in the order no ' . $pendingApprovalOrder->id;
                        $obj->yourOrder = $pendingApprovalOrder->with('orderDetails')->where('id', $pendingApprovalOrder->id)->get();

                        return $obj;
                    }
                }





                $order = $this->addOrder($orderState, $request->desc, $createdBy, $branchId);


                if ($request->productsNotFound == null) {
                    $request->productsNotFound = array();
                }
                if ($request->orderDetails == null) {
                    $request->orderDetails = array();
                }

                // dd(gettype($request->productsNotFound));
                // dd($order->id);
                $all_products = $this->addOrderDetails($request->orderDetails,  $order->id, $currentUser->id);

                OrderDetails::insert($all_products);



                $obj = new stdClass();
                $obj->res = 'success';
                $obj->msg = 'Your Order Submitted Successfully';
                $obj->yourOrder = $order->with('orderDetails')->where('id', $order->id)->get();

                // dd("zzzzzzzzzz");
                return $obj;
            } else {
                $obj = new stdClass();
                $obj->res = "error";
                $obj->msg = "you are not authrozied";
            }
            return $obj;
        });
        return $result;
    }

    public function getUnitPriceData($product_id, $unit_id)
    {
        return UnitPrice::where(
            [
                [
                    'product_id', '=', $product_id
                ],
                [
                    'unit_id', '=', $unit_id
                ]
            ]
        )->first();
    }





    public function getOrderDetailsToDelete($order_id)
    {

        return OrderDetails::where('order_id', $order_id)->get();
    }

    public function getOrderDetaisPendingToDelete($order_id, $unit_id, $product_id, $qty)
    {
        $orderDetail = OrderDetails::where('order_id', $order_id)->where('product_unit_id', $unit_id)->where('product_id', $product_id)->first();



        // dd($orderDetail);

        $newDataDetail = null;
        if ($orderDetail != null) {
            // $orderDetail->qty = $orderDetail->qty + $qty;
            // $orderDetail->available_qty = $orderDetail->qty;
            // $orderDetail->save();
            $newDataDetail = $orderDetail;

            $orderDetail->delete();
        }
        return $newDataDetail;
        // return OrderDetails::where('order_id', $order_id)->where('product_unit_id', $unit_id)->where('product_id', $product_id)->get();
    }
    public function addOrder($orderState, $desc, $createdBy, $branchId)
    {
        $order = new Order(
            [
                'active' =>  1,
                'request_state_id' => $orderState,
                'desc' => $desc,
                'created_by' =>  $createdBy,
                'restricted_state_id' => 6,
                'branch_id' => $branchId,
            ]
        );

        $order->save();
        return $order;
    }

    public function addOrderDetails(array $orderDetails, $orderId, $currentUser)
    {
        foreach ($orderDetails as   $data) {

            $productPurchaseInvoice = PurchaseInvoiceDetails::orderBy('id', 'ASC')->where('product_id', $data['product_id'])->where('unit_id', $data['product_unit_id'])->get();
            $productPurchaseInvoiceLatest = PurchaseInvoiceDetails::orderBy('id', 'DESC')->where('product_id', $data['product_id'])->where('unit_id', $data['product_unit_id'])->first();

            $orderDetailsQty = OrderDetails::where('product_unit_id', $data['product_unit_id'])->where('product_id', $data['product_id'])->get()->sum('qty');


            $resultPurchase = new stdClass();



            $alreadyQuantityOrder = $data['qty'];
            foreach ($productPurchaseInvoice as $key => $value) {
                $resultPurchase->purchase_invoice_id = $value->purchase_invoice_id;


                if ($value->qty > $orderDetailsQty) {

                    if (($alreadyQuantityOrder + $orderDetailsQty) <= $value->qty) {


                        $obj = new stdClass();
                        $obj->product_id = $data['product_id'];
                        $obj->product_unit_id =  $data['product_unit_id'];

                        $obj->qty = $alreadyQuantityOrder;
                        $obj->available_qty = $alreadyQuantityOrder;
                        $obj->price = $value->price  * $alreadyQuantityOrder;
                        $obj->unit_price = $value->price;
                        $obj->purchase_invoice_id = $value->id;
                        $obj->order_id = $orderId;
                        $obj->created_by = $currentUser;
                        $obj->key = $key;

                        $answers[] = $obj;
                        break;
                    } else if (($alreadyQuantityOrder + $orderDetailsQty) > $value->qty) {


                        $obj = new stdClass();
                        $obj->product_id = $data['product_id'];
                        $obj->product_unit_id =  $data['product_unit_id'];

                        $obj->qty = ($value->qty -  $orderDetailsQty);
                        $obj->available_qty = ($value->qty -  $orderDetailsQty);
                        $obj->price = $value->price  * ($value->qty -  $orderDetailsQty);
                        $obj->unit_price = $value->price;
                        $obj->purchase_invoice_id = $value->id;
                        $obj->order_id = $orderId;
                        $obj->created_by = $currentUser;
                        $obj->key = $key;

                        // dd(
                        //     $alreadyQuantityOrder,
                        //     $orderDetailsQty
                        // );
                        // dd($alreadyQuantityOrder, $orderDetailsQty);
                        $alreadyQuantityOrder = ($alreadyQuantityOrder - $orderDetailsQty);

                        // dd($alreadyQuantityOrder);
                        $answers[] = $obj;
                        continue;
                    }
                }
            }



            // to update orders quqntity in products
            $productData = Product::where('id', $data['product_id'])->first();
            $number_orders = $productData->number_orders;
            $productData->number_orders = $number_orders + $data['qty'];
            $productData->save();
            // -------


        }



        $final_products =   json_decode(json_encode($answers), true);

        dd($final_products);
        return $final_products;
    }



    public function addPendingOrderDetails(array $orderDetails,   $orderId, $currentUser)
    {



        foreach ($orderDetails as   $data) {

            $productUnitPricePurchaseInvoice = PurchaseInvoiceDetails::orderBy('id', 'ASC')->where('product_id', $data['product_id'])->where('unit_id', $data['product_unit_id'])->get();
            $productUnitPricePurchaseInvoiceLatest = PurchaseInvoiceDetails::orderBy('id', 'DESC')->where('product_id', $data['product_id'])->where('unit_id', $data['product_unit_id'])->first();

            $resultPurchase = new stdClass();
            $alreadyQuantityOrder = $data['qty'];



            if ($productUnitPricePurchaseInvoiceLatest->qty == $productUnitPricePurchaseInvoiceLatest->ordered_qty) {


                $resultPrice = $productUnitPricePurchaseInvoiceLatest->price;
                $purchaseInvoiceId = $productUnitPricePurchaseInvoiceLatest->purchase_invoice_id;
                $obj = new stdClass();
                $obj->product_id = $data['product_id'];
                $obj->product_unit_id =  $data['product_unit_id'];
                $obj->qty = $data['qty'];
                $obj->available_qty = $data['qty'];
                $obj->order_id = $orderId;
                $obj->price = $resultPrice  * $data['qty'];
                $obj->product_name = null;
                $obj->created_by = $currentUser;
                $obj->purchase_invoice_id = $purchaseInvoiceId;
                $answers[] = $obj;
            } else {


                foreach ($productUnitPricePurchaseInvoice as $key => $value) {


                    $resultPurchase->purchase_invoice_id = $value->purchase_invoice_id;

                    $purchaseInvoiceData = PurchaseInvoiceDetails::find($value->id);

                    $existing_ordered_qty = $purchaseInvoiceData->ordered_qty;


                    if (($value->ordered_qty < $value->qty)) {

                        $resultPurchase->price = $value->price;
                        if (($existing_ordered_qty + $alreadyQuantityOrder) <= $value->qty) {

                            $purchaseInvoiceData->ordered_qty = $existing_ordered_qty + $alreadyQuantityOrder;

                            $purchaseInvoiceData->save();
                            $resultPrice = $resultPurchase->price;
                            $purchaseInvoiceId = $resultPurchase->purchase_invoice_id;

                            $obj = new stdClass();
                            $obj->product_id = $data['product_id'];
                            $obj->product_unit_id =  $data['product_unit_id'];
                            $orderDetail = OrderDetails::where('product_unit_id', $data['product_unit_id'])->where('product_id', $data['product_id'])->where('purchase_invoice_id', $value->purchase_invoice_id)->first();

                            if ($existing_ordered_qty > 0) {
                                $tempOrderDetail = $orderDetail;
                                if (!is_null($orderDetail)) {
                                    $orderDetail->delete();
                                }
                                $obj->qty = $tempOrderDetail->qty + $data['qty'];
                                $obj->price = $resultPrice  * ($tempOrderDetail->qty + $data['qty']);
                            } else {
                                $obj->qty = $alreadyQuantityOrder;
                                $obj->price = $resultPrice  * $alreadyQuantityOrder;
                            }

                            $obj->order_id = $orderId;
                            $obj->product_name = null;
                            $obj->created_by = $currentUser;
                            $obj->purchase_invoice_id = $purchaseInvoiceId;
                            $answers[] = $obj;
                            break;
                        }
                        if (($existing_ordered_qty + $alreadyQuantityOrder) > $value->qty) {

                            $purchaseInvoiceData->ordered_qty = $existing_ordered_qty + ($value->qty - $value->ordered_qty);

                            $alreadyQuantityOrder = $alreadyQuantityOrder - ($value->qty - $existing_ordered_qty);

                            dd($alreadyQuantityOrder);
                            $purchaseInvoiceData->save();
                            $resultPrice = $resultPurchase->price;
                            $purchaseInvoiceId = $resultPurchase->purchase_invoice_id;
                            $obj = new stdClass();
                            $obj->product_id = $data['product_id'];
                            $obj->product_unit_id =  $data['product_unit_id'];
                            if ($existing_ordered_qty > 0) {

                                $orderDetail = OrderDetails::where('product_unit_id', $data['product_unit_id'])->where('product_id', $data['product_id'])->where('purchase_invoice_id', $value->purchase_invoice_id)->first();

                                dd(
                                    $value->qty,
                                    $value->ordered_qty,
                                    $alreadyQuantityOrder,
                                    (($value->qty - $value->ordered_qty) + $alreadyQuantityOrder)
                                );

                                if ((($value->qty - $value->ordered_qty) + $alreadyQuantityOrder) >=  $value->qty) {

                                    $tempOrderDetail = $orderDetail;
                                    if (!is_null($orderDetail)) {
                                        $orderDetail->delete();
                                    }

                                    // dd($tempOrderDetail->qty, $data['qty'], $alreadyQuantityOrder);

                                    $obj->qty =  ($data['qty'] - $alreadyQuantityOrder) + $tempOrderDetail->qty;

                                    $obj->price = $resultPrice  * (($data['qty'] - $alreadyQuantityOrder) + $tempOrderDetail->qty);
                                    // $obj->available_qty =  ($value->qty - $value->ordered_qty) + $alreadyQuantityOrder;
                                } else {


                                    $obj->qty =  ($value->qty - $value->ordered_qty);
                                    // $obj->qty =  1;
                                    $obj->price = $resultPrice  * ($value->qty - $value->ordered_qty);
                                    // $obj->available_qty =  ($value->qty - $value->ordered_qty);
                                }
                            } else {
                                $obj->qty = $existing_ordered_qty + ($value->qty - $value->ordered_qty);
                                // $obj->available_qty = $existing_ordered_qty + ($value->qty - $value->ordered_qty);
                                $obj->price = $resultPrice  * ($existing_ordered_qty + ($value->qty - $value->ordered_qty));
                            }

                            $obj->order_id = $orderId;
                            $obj->product_name = null;
                            $obj->created_by = $currentUser;
                            $obj->purchase_invoice_id = $purchaseInvoiceId;
                            $answers[] = $obj;
                            continue;
                        }
                    }
                }
            }



            // to update orders quqntity in products
            $productData = Product::where('id', $data['product_id'])->first();
            $number_orders = $productData->number_orders;
            $productData->number_orders = $number_orders + $data['qty'];
            $productData->save();
            // -------


        }
        if (count($orderDetails) == 0) {
            $answers = array();
        }



        $answers =   json_decode(json_encode($answers), true);

        // dd($answers);
        return $answers;
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {


        // return RequestState::get();
        $current_role = $request->user()->role_id;


        if ($current_role == 1 || $current_role == 4 || $current_role == 7) {

            $order = Order::find($request->order_id);
            if ($order->request_state_id != 5) {
                $order->request_state_id = $request->request_state_id;
                if ($request->request_state_id == 3 || $request->request_state_id == 4) {
                    $update =  $order->save();
                    if ($update == 1) {
                        $obj = new stdClass();
                        $obj->res = "success";
                        $obj->order_state_id = $order->request_state_id;
                        $obj->order_state_name = RequestState::where('id', $order->request_state_id)->get()->first()->name;

                        $obj->msg = "Order no " . $order->id . " has been updated " .  $request->request_state_id;
                    } else {
                        $obj = new stdClass();
                        $obj->res = "faild";
                        $obj->msg = "there is some errors";
                    }
                } else {
                    $obj = new stdClass();
                    $obj->res = "faild";
                    $obj->msg = "Only states  ( [Ready for delivery] or [Processing] ) can be sent , because your role is store manager";
                }
            } else {
                $obj = new stdClass();
                $obj->res = "faild";
                $obj->msg = "you cannot update this order because its state is delivered";
            }
        } elseif ($current_role == 5) {

            if ($request->restricted_state_id == 7 || $request->restricted_state_id == 6) {
                $order = Order::find($request->order_id);
                $order->restricted_state_id = $request->restricted_state_id;


                if ($order->request_state_id == 5) {
                    if ($request->restricted_state_id == 7) {
                        $update =  $order->save();
                        $obj = new stdClass();
                        $obj->res = "success";
                        $obj->order_state_id = $order->request_state_id;
                        $obj->order_state_name = RequestState::where('id', $order->request_state_id)->get()->first()->name;

                        $obj->msg = "Order no " . $order->id . " has been recorded ";
                    } elseif ($request->restricted_state_id == 6) {
                        $update =  $order->save();
                        $obj = new stdClass();
                        $obj->res = "success";
                        $obj->order_state_id = $order->request_state_id;
                        $obj->order_state_name = RequestState::where('id', $order->request_state_id)->get()->first()->name;

                        $obj->msg = "Order no " . $order->id . " has been unrecorded ";
                    }
                } else {
                    $obj = new stdClass();
                    $obj->res = "error";
                    $obj->msg = "you can not Record this order because its state is not delivered";
                }
            } else {
                $obj = new stdClass();
                $obj->res = "error";
                $obj->msg = "you can not send order another ([Recored] or [Unrecorded])";
            }
        } elseif ($current_role == 3) {

            $order = Order::find($request->order_id);

            if ($order->request_state_id == 8) {
                $order->request_state_id = $request->request_state_id;
                if ($request->request_state_id == 2) {
                    $update =  $order->save();
                    if ($update == 1) {
                        $orderDetails = $this->getOrderDetailsToDelete($request->order_id);

                        foreach ($orderDetails as $kOd => $vOd) {
                            if ($vOd->qty == 0) {
                                $orderDetails = OrderDetails::find($vOd->id);
                                $orderDetails->delete();
                            }
                        }

                        $obj = new stdClass();
                        $obj->res = "success";
                        $obj->order_state_id = $order->request_state_id;
                        $obj->order_state_name = RequestState::where('id', $order->request_state_id)->get()->first()->name;
                        $obj->msg = "your order " . $order->id . " has been sent to store ";
                    } else {
                        $obj = new stdClass();
                        $obj->res = "faild";
                        $obj->msg = "there is some errors";
                    }
                } else {
                    $obj = new stdClass();
                    $obj->res = "faild";
                    $obj->msg = "Only state  (Ordered) can be sent , because order state is Pending for approve";
                }
            } elseif ($order->request_state_id == 4) {
                $order->request_state_id = $request->request_state_id;
                if ($request->request_state_id == 5) {
                    if ($request->full_quantity == 1) {

                        $update =  $order->save();
                        if ($update == 1) {
                            $obj = new stdClass();
                            $obj->res = "success";
                            $obj->order_state_id = $order->request_state_id;
                            $obj->order_state_name = RequestState::where('id', $order->request_state_id)->get()->first()->name;
                            $obj->msg = "Order no " . $order->id . " has been received ";
                        } else {
                            $obj = new stdClass();
                            $obj->res = "faild";
                            $obj->msg = "there is some errors";
                        }
                    } elseif ($request->full_quantity == 0) {
                        if (isset($request->notes)) {

                            $order->full_quantity = $request->full_quantity;
                            $order->notes = $request->notes;
                            $update =  $order->save();
                            if ($update == 1) {

                                $notificationOrder = new NotificationOrder(
                                    [
                                        'sender_id' => $order->created_by,
                                        'reciver_id' => User::where('role_id', 4)->get()->first()->id,
                                        'order_id' => $order->id,
                                        'title' => 'Quantity is missing of order no ' . $order->id,
                                        'body' => 'Quantity is missing  ' . $order->notes,
                                        'active' => 1
                                    ]
                                );
                                $notificationOrder->save();

                                $obj = new stdClass();
                                $obj->res = "success";
                                $obj->order_state_id = $order->request_state_id;
                                $obj->order_state_name = RequestState::where('id', $order->request_state_id)->get()->first()->name;

                                $obj->msg = "Order no " . $order->id . " has been updated ";
                            } else {
                                $obj = new stdClass();
                                $obj->res = "faild";
                                $obj->msg = "there is some errors";
                            }
                        } else {
                            $obj = new stdClass();
                            $obj->res = "faild";
                            $obj->msg = "notes is required";
                        }
                    }
                } else {
                    $obj = new stdClass();
                    $obj->res = "faild";
                    $obj->msg = "Only state  ( 5 ) can be sent , because your role is branch manager";
                }
            } else {
                $obj = new stdClass();
                $obj->res = "faild";
                $obj->msg = "you cannot update this order because its state is not ready to delivery or pending for approve";
            }
        } else {


            $obj = new stdClass();
            $obj->res = "error";
            $obj->msg = "you are not authrozied";
        }

        return $obj;
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
}
