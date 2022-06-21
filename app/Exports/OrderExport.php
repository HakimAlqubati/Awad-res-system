<?php

namespace App\Exports;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use App\Models\RequestState;
use App\Models\Unit;
use App\Models\User;

use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use stdClass;

class OrderExport implements FromView
{
    /**
     * @return \Illuminate\Support\Collection
     */
    // public function collection()
    // {
    //     return Order::all();
    // }



    public function view(): View
    {

        $path_exploded = explode("/", url()->current());
        $id =  end($path_exploded);
        

       

       
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

        return view(
            'vendor.voyager.orders.export_excel'
            // ,
            // [
            //     'finalResult' => $finalResult
            // ]
        );
    }



    public function getProductProductNameById($id)
    {
        return Product::where('id', $id)->get();
    }


    public function getUnitNameById($id)
    {
        return Unit::where('id', $id)->get();
    }

    public function getStateNameById($id)
    {
        return RequestState::where('id', $id)->get();
    }
}
