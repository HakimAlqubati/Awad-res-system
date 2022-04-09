<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\RequestState;
use App\Models\Unit;
use App\Models\User;
use stdClass;

class TransferController extends Controller
{
    public function index()
    {

        $orderDetails = OrderDetails::where(
            'available_in_store',
            1
        )->get();


        $orderIds = array();
        $data = array();

        foreach ($orderDetails as $key => $value) {

            $orderIds[] = $value['order_id'];
        }




        if (count($orderIds)) {
            // $data = Order::whereIn('id', $orderIds)->get();
            foreach (Order::whereIn('id', $orderIds)->get() as   $value) {

                $data[]  = [
                    'id' => $value->id,
                    'created_by' => User::where('id', $value->created_by)->first()->name,
                    'branch_id' =>  Branch::where('id', $value->branch_id)->first()->name,
                    'created_at' => $value->created_at
                ];
            }
        }

        // dd($data);
        // dd($data);



        return  view(
            'transfers.browse',
            compact('data')

        );
    }

    public function show($id)
    {

        $order = Order::where('id', $id)->get();


        foreach ($order as   $val) {
            $finalResultOrder = array();


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

            if ($value->available_in_store == 1) {
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
        }

        // dd($finalResult);

        return  view(
            'transfers.show',
            compact(
                'finalResult',
                'finalResultOrder'
            )

        );
    }

    public function getStateNameById($id)
    {
        return RequestState::where('id', $id)->get();
    }

    public function getProductProductNameById($id)
    {
        return Product::where('id', $id)->get();
    }

    public function getData()
    {

        $data = Order::get();
        return  view(
            'transfers.products',
            compact('data')
        );
    }
    public function getUnitNameById($id)
    {
        return Unit::where('id', $id)->get();
    }


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

        return view('vendor.voyager.orders.pdf_view_transfer', compact('finalResult'));
    }
}
