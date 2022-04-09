<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
    integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<style>
    .a4-paper {
        height: 29.7cm;
        /* width: 21cm; */
        position: relative;
    }

    .footer {
        bottom: 0;
        position: absolute;
    }

    .header-table tr td {
        border-top: 1px solid #DF1E98;
        background: white;
        border-bottom: 1 px solid #DF1E98;
    }

    .table-body tr th {
        border-bottom: 2px solid #af237c;
        border-top: 2px solid #af237c;
    }

    .table-body tr td {
        background-color: white;
        border-bottom: 1px solid #af237c;
    }

</style>
<div class="page-content read container-fluid a4-paper">
    <div class="row">
        <div class="col-md-12" style="text-align: center; padding: 20px;">
            <img src="http://15.185.62.165/Ressystemv2/public/logo.png" alt="" style="height: 115px;">
        </div>
        <div class="col-md-12">

            <div class="panel panel-bordered" style="padding-bottom:5px;">



                <table class="table table-striped header-table">
                    <tbody>
                        <tr>

                            <td>
                                Transfer ID : #<?php echo $finalResult[0]->orderId; ?>
                            </td>
                            <td>
                                Created by : {{ $finalResult[0]->createdByUserName }}
                            </td>
                            <td>
                                Date : {{ $finalResult[0]->createdAt }}
                            </td>

                        </tr>
                        {{-- <tr>
                            <td>
                                Order state : {{ $finalResult[0]->state_name }}
                            </td>
                            <td></td>
                            <td>
                                ( {{ $finalResult[0]->restricted_state_name }})
                            </td>


                        </tr> --}}
                        {{-- <tr>
                            <td colspan="3">
                                Details:
                                <p> {{ $finalResult[0]->desc }} </p>
                            </td>
                        </tr> --}}
                    </tbody>












                </table>


                @if ($finalResult[0]->stateId == 5 && !is_null($finalResult[0]->notes))
                    <h4> Missing quantities: </h4>
                    <div class="row">
                        <div class="col-md-12">
                            <p> {{ $finalResult[0]->notes }} </p>
                        </div>
                    </div>
                @endif

                <table class="table table-striped table-body">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product id</th>
                            <th>Product name</th>
                            <th>Product code</th>
                            <th>Product Description</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_price = 0;
                       
                        foreach($finalResult as $key => $value) { 
                            $index = 0;
                          
                            if( is_numeric ($key) && ((int)$key) > 0) {
                            ?>
                        <tr>
                            <td>{{ $key }}</td>
                            <td>{{ $value->product_id ?? '--' }}</td>
                            <td>{{ $value->product_name ?? '--' }}</td>
                            <td>{{ $value->product_code ?? '--' }}</td>
                            <td>{{ $value->product_desc ?? '--' }}</td>
                            <td>{{ $value->unit_name ?? '--' }}</td>
                            <td>{{ $value->qty ?? '--' }}</td>
                            <td>{{ $value->price ?? '--' }}</td>
                        </tr>
                        <?php
                        if( is_numeric ($value->price )) {

                            $total_price += $value->price ;
                        }
                            }
                     } ?>
                        <tr style=" font-weight: 700;">

                            <td colspan="7" style="text-align: center">
                                Total price for transfer ID : #<?php echo $finalResult[0]->orderId; ?>
                            </td>
                            <td>
                                <?php echo (int) $total_price; ?>
                            </td>
                        </tr>

                        <tr style="height: 110px;     font-weight: 700;">
                            <td style="text-align: center" colspan="4">Store manager: <h6><?php echo $finalResult[0]->manager_name; ?> </h6>
                            </td>

                            <td style="text-align: center" colspan="4"> Created by:
                                <h6> <?php echo $finalResult[0]->createdByUserName; ?> -
                                    Manager of branch: <?php echo $finalResult[0]->branch_name; ?>
                                </h6>
                            </td>
                        </tr>


                    </tbody>
                </table>

            </div>
        </div>
        {{-- <table style="/*width: 100%;*/" class="footer">
            <tr>
                <td style="width:50%;">Store manager: <h6><?php echo $finalResult[0]->manager_name; ?> </h6>
                </td>

                <td style="width: 50%"> Created by:
                    <h6> <?php echo $finalResult[0]->createdByUserName; ?> -
                        Manager of branch: <?php echo $finalResult[0]->branch_name; ?>
                    </h6>
                </td>
            </tr>
        </table> --}}
    </div>

</div>
