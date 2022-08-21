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
use App\Models\User;
use Illuminate\Http\Request;
use stdClass;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

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
            $obj = new stdClass();
            $obj->id = $value->id;
            $obj->order_id = $value->order_id;
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
            $obj->unit_price = $value->unit_price;
            $fresult[] = $obj;

            $price = $value->price;
            $totalPrice += $price;
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

                if ($currentRole == 3) {

                    // FcmNotificationJob::dispatchNow("New Order", "Order from " . $currentUser->name .
                    //     " Manager of branch " .  $branch->name, $branch);

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

                        if ($request->orderDetails == null) {
                            $request->orderDetails = array();
                        }

                        $this->addPendingOrderDetails(
                            $request->orderDetails,
                            $pendingApprovalOrder->id,
                            $currentUser->id
                        );


                        $obj = new stdClass();
                        $obj->res = 'success';
                        $obj->msg = 'Your Order Submitted Successfully in the order no ' . $pendingApprovalOrder->id;
                        $obj->yourOrder = $pendingApprovalOrder->with('orderDetails')->where('id', $pendingApprovalOrder->id)->get();

                        return $obj;
                    }
                } elseif ($currentRole == 8) {

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

                        if ($request->orderDetails == null) {
                            $request->orderDetails = array();
                        }

                        $this->addPendingOrderDetails(
                            $request->orderDetails,
                            $pendingApprovalOrder->id,
                            $currentUser->id
                        );
                        $obj = new stdClass();
                        $obj->res = 'success';
                        $obj->msg = 'Your Order Submitted Successfully in the order no ' . $pendingApprovalOrder->id;
                        $obj->yourOrder = $pendingApprovalOrder->with('orderDetails')->where('id', $pendingApprovalOrder->id)->get();

                        return $obj;
                    }
                }

                $order = $this->addOrder($orderState, $request->desc, $createdBy, $branchId);
                if ($request->orderDetails == null) {
                    $request->orderDetails = array();
                }

                $this->addOrderDetails($request->orderDetails,  $order->id, $currentUser->id);

                $obj = new stdClass();
                $obj->res = 'success';
                $obj->msg = 'Your Order Submitted Successfully';
                $obj->yourOrder = $order->with('orderDetails')->where('id', $order->id)->get();

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






    public function getOrderDetailsToDelete($order_id)
    {

        return OrderDetails::where('order_id', $order_id)->get();
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



    public function addPendingOrderDetails(array $orderDetails,   $orderId, $currentUser)
    {

        foreach ($orderDetails as   $data) {
            $productPurchaseInvoice = PurchaseInvoiceDetails::orderBy('id', 'ASC')->where('product_id', $data['product_id'])->where('unit_id', $data['product_unit_id'])->get()->toArray();

            $lastPurchaseInvoice = end($productPurchaseInvoice);
            $orderDetailsQty = OrderDetails::where('product_unit_id', $data['product_unit_id'])->where('product_id', $data['product_id'])->where('purchase_invoice_id', $lastPurchaseInvoice['id'])->get()->sum('qty');

            if ($orderDetailsQty >= $lastPurchaseInvoice['qty']) {
                OrderDetails::create(
                    [
                        'product_id' => $data['product_id'],
                        'product_unit_id' => $data['product_unit_id'],
                        'qty' => $data['qty'],
                        'available_qty' => $data['qty'],
                        'price' => $lastPurchaseInvoice['price']  * $data['qty'],
                        'unit_price' => $lastPurchaseInvoice['price'],
                        'order_id' => $orderId,
                        'created_by' => $currentUser,
                        'purchase_invoice_id' => $lastPurchaseInvoice['id']
                    ]
                );
            }
            $resultPurchase = new stdClass();

            $alreadyQuantityOrder = $data['qty'];
            foreach ($productPurchaseInvoice as $key => $value) {
                $orderDetailsQty = OrderDetails::where('product_unit_id', $data['product_unit_id'])->where('product_id', $data['product_id'])->where('purchase_invoice_id', $value['id'])->where('order_id', $orderId)->get()->sum('qty');
                $orderDetailsData = OrderDetails::where('product_unit_id', $data['product_unit_id'])->where('product_id', $data['product_id'])->where('purchase_invoice_id', $value['id'])->where('order_id', $orderId)->first();
                $resultPurchase->purchase_invoice_id = $value['purchase_invoice_id'];
                if ($value['qty'] > $orderDetailsQty) {
                    if (($alreadyQuantityOrder + $orderDetailsQty) <= $value['qty']) {
                        if (is_null($orderDetailsData)) {
                            OrderDetails::create(
                                [
                                    'product_id' => $data['product_id'],
                                    'product_unit_id' => $data['product_unit_id'],
                                    'qty' => $alreadyQuantityOrder,
                                    'available_qty' => $alreadyQuantityOrder,
                                    'price' => $value['price']  * $alreadyQuantityOrder,
                                    'unit_price' => $value['price'],
                                    'order_id' => $orderId,
                                    'created_by' => $currentUser,
                                    'purchase_invoice_id' => $value['id']
                                ]
                            );
                        } else {
                            $orderDetailsData->update(
                                [
                                    'qty' => $alreadyQuantityOrder + $orderDetailsData->qty,
                                    'available_qty' => $alreadyQuantityOrder + $orderDetailsData->qty,
                                    'price' => $value['price']  * ($alreadyQuantityOrder + $orderDetailsData->qty),
                                    'created_by' => $currentUser,
                                ]
                            );
                        }

                        break;
                    } else if (($alreadyQuantityOrder + $orderDetailsQty) > $value['qty']) {


                        if ($value === end($productPurchaseInvoice)) {
                            $qty = $alreadyQuantityOrder;
                        } else {
                            $qty = $value['qty'] -  $orderDetailsQty;
                        }

                        $alreadyQuantityOrder = $alreadyQuantityOrder - ($value['qty'] -  $orderDetailsQty);

                        if (is_null($orderDetailsData)) {

                            OrderDetails::create(
                                [
                                    'product_id' => $data['product_id'],
                                    'product_unit_id' => $data['product_unit_id'],
                                    'qty' => $qty,
                                    'available_qty' => $qty,
                                    'price' => $value['price']  * $qty,
                                    'unit_price' => $value['price'],
                                    'order_id' => $orderId,
                                    'created_by' => $currentUser,
                                    'purchase_invoice_id' => $value['id']
                                ]
                            );
                        } else {
                            $orderDetailsData->update(
                                [
                                    'qty' => $qty + $orderDetailsData->qty,
                                    'available_qty' => $qty + $orderDetailsData->qty,
                                    'price' => $value['price']  * ($qty + $orderDetailsData->qty),
                                    'created_by' => $currentUser,
                                ]
                            );
                        }


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
        return;
        if (count($orderDetails) == 0) {
            $answers = array();
        }



        $answers =   json_decode(json_encode($answers), true);

        // dd($answers);
        return $answers;
    }



    public function addOrderDetails(array $orderDetails, $orderId, $currentUser)
    {
        foreach ($orderDetails as   $data) {
            $productPurchaseInvoice =  PurchaseInvoiceDetails::orderBy('id', 'ASC')->where('product_id', $data['product_id'])->where('unit_id', $data['product_unit_id'])->get()->toArray();


            if (count($productPurchaseInvoice) > 0) {
                $lastPurchaseInvoice = end($productPurchaseInvoice);


                $orderDetailsQty = OrderDetails::where('product_unit_id', $data['product_unit_id'])->where('product_id', $data['product_id'])->where('purchase_invoice_id', $lastPurchaseInvoice['id'])->get()->sum('qty');

                if ($orderDetailsQty >= $lastPurchaseInvoice['qty']) {
                    OrderDetails::create(
                        [
                            'product_id' => $data['product_id'],
                            'product_unit_id' => $data['product_unit_id'],
                            'qty' => $data['qty'],
                            'available_qty' => $data['qty'],
                            'price' => $lastPurchaseInvoice['price']  * $data['qty'],
                            'unit_price' => $lastPurchaseInvoice['price'],
                            'order_id' => $orderId,
                            'created_by' => $currentUser,
                            'purchase_invoice_id' => $lastPurchaseInvoice['id']
                        ]
                    );
                }
                $resultPurchase = new stdClass();

                $alreadyQuantityOrder = $data['qty'];
                foreach ($productPurchaseInvoice as $key => $value) {
                    $orderDetailsQty = OrderDetails::where('product_unit_id', $data['product_unit_id'])->where('product_id', $data['product_id'])->where('purchase_invoice_id', $value['id'])->get()->sum('qty');
                    $resultPurchase->purchase_invoice_id = $value['purchase_invoice_id'];
                    if ($value['qty'] > $orderDetailsQty) {
                        if (($alreadyQuantityOrder + $orderDetailsQty) <= $value['qty']) {
                            OrderDetails::create(
                                [
                                    'product_id' => $data['product_id'],
                                    'product_unit_id' => $data['product_unit_id'],
                                    'qty' => $alreadyQuantityOrder,
                                    'available_qty' => $alreadyQuantityOrder,
                                    'price' => $value['price']  * $alreadyQuantityOrder,
                                    'unit_price' => $value['price'],
                                    'order_id' => $orderId,
                                    'created_by' => $currentUser,
                                    'purchase_invoice_id' => $value['id']
                                ]
                            );
                            break;
                        } elseif (($alreadyQuantityOrder + $orderDetailsQty) > $value['qty']) {
                            if ($value === end($productPurchaseInvoice)) {
                                $qty = $alreadyQuantityOrder;
                            } else {
                                $qty = $value['qty'] -  $orderDetailsQty;
                            }

                            $alreadyQuantityOrder = $alreadyQuantityOrder - ($value['qty'] -  $orderDetailsQty);

                            OrderDetails::create(
                                [
                                    'product_id' => $data['product_id'],
                                    'product_unit_id' => $data['product_unit_id'],
                                    'qty' => $qty,
                                    'available_qty' => $alreadyQuantityOrder,
                                    'price' => $value['price']  * ($value['qty'] -  $orderDetailsQty),
                                    'unit_price' => $value['price'],
                                    'order_id' => $orderId,
                                    'created_by' => $currentUser,
                                    'purchase_invoice_id' => $value['id']
                                ]
                            );
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
            } else {
                exit('no purchase for product (' . Product::find($data['product_id'])->name  . ') unit (' . Unit::find($data['product_unit_id'])->name . ')');
                // dd('dd');
                return response()->json([
                    '1' => '0',
                ])
                    ->setStatusCode(500, Response::$statusTexts[500]);
            }
        }

        // return;
        // $final_products =   json_decode(json_encode($answers), true);
        // return $final_products;
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
}
