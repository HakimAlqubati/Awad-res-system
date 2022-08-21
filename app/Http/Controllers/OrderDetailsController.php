<?php

namespace App\Http\Controllers;

use App\Models\OrderDetails;
use App\Models\PurchaseInvoiceDetails;
use App\Models\UnitPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

class OrderDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OrderDetails  $orderDetails
     * @return \Illuminate\Http\Response
     */
    public function show(OrderDetails $orderDetails)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\OrderDetails  $orderDetails
     * @return \Illuminate\Http\Response
     */
    public function edit(OrderDetails $orderDetails)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\OrderDetails  $orderDetails
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,  OrderDetails $orderDetails)
    {





        $data = $request->all();

        foreach ($data as $key => $value) {
            $obj = new stdClass();


            $operation = $value['operation'];
            $orderDetails = OrderDetails::find($value['id']);

            // dd($orderDetails);
            if ($orderDetails == null) {
                $obj->res = 'faild';
                $obj->msg = 'there is no order detail with ' . $value['id'] . ' id';
            } else {
                if ($operation == "update") {
                    try {
                        if (($value['qty'] && $value['qty'] != null) || ($value['qty'] == 0)) {
                            $product_id = $orderDetails->product_id;
                            $unit_id = $orderDetails->product_unit_id;
                            if ($product_id != null && $unit_id != null) {

                                $orderDetails->price = $orderDetails->unit_price * $value['qty'];
                            }
                            $orderDetails->qty = $value['qty'];
                            $orderDetails->available_qty = $value['qty'];
                        }
                        if (($value['product_unit_id'] && $value['product_unit_id'] != null) &&
                            ($value['product_id'] && $value['product_id'] != null)
                        ) {
                            $unitPriceData = UnitPrice::where(
                                [
                                    ['product_id', '=', $value['product_id']],
                                    ['unit_id', '=', $value['product_unit_id']]
                                ]
                            )->first();
                            $orderDetails->product_id = $value['product_id'];
                            $orderDetails->product_unit_id = $value['product_unit_id'];
                            if ($value['qty'] && $value['qty'] != null) {
                                $orderDetails->price = $orderDetails->unit_price * $value['qty'];
                                $orderDetails->qty = $value['qty'];
                            } elseif ($value['qty'] == null) {
                                $orderDetails->price = $orderDetails->unit_price *  $orderDetails->qty;
                            }
                        }

                        if (($value['available_in_store'] && $value['available_in_store'] != null) || ($value['available_in_store'] == 0)) {
                            $orderDetails->available_in_store = $value['available_in_store'];
                        }
                        $orderDetails->save();

                        $obj->res = 'success';
                        $obj->msg = 'done updated successfully';

                        $result[] = $obj;
                    } catch (\Exception $e) {
                        $obj->res = 'faild';
                        $obj->msg = $e->getMessage();
                        $result[] = $obj;
                    }
                } elseif ($operation == "delete") {
                    try {
                        $orderDetails->delete();
                        $obj->res = 'success';
                        $obj->msg = 'done deleted successfully';
                        $result[] = $obj;
                    } catch (\Exception $e) {
                        $obj->res = 'faild';
                        $obj->msg = $e->getMessage();
                        $result[] = $obj;
                    }
                } elseif (!$operation) {
                    $obj->res = 'faild';
                    $obj->msg = 'operation is required';
                    $result[] = $obj;
                }
            }
        }
        return $result[0];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OrderDetails  $orderDetails
     * @return \Illuminate\Http\Response
     */
    public function destroy(OrderDetails $orderDetails)
    {
        //
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
}
