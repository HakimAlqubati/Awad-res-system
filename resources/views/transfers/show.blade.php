@extends('voyager::master')

@section('page_title', __('voyager::generic.view'))

@section('page_header')


    <h1 class="page-title">




        <a href="{{    url('/')  }}/admin/transfer-list" class="btn btn-warning">
            <i class="glyphicon glyphicon-list"></i> <span
                class="hidden-xs hidden-sm">{{ __('voyager::generic.return_to_list') }}</span>
        </a>


        <a href="{{ url('/get-pdf/' . $finalResultOrder[0]->id . '') }}" class="btn btn-success">
            <i class="glyphicon glyphicon-file"></i> <span class="export-to-pdf">Export to pdf</span>
        </a>


        <a href="{{ url('/orders/export/' . $finalResultOrder[0]->id . '') }}" class="btn btn-success">
            <i class="glyphicon glyphicon-file"></i> <span class="export-to-pdf">Export to excel</span>
        </a>


    </h1>
    @include('voyager::multilingual.language-selector')
@stop


@section('content')

    <div class="page-content read container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered" id="headerTable" style="padding-bottom:5px;">



                    <div class="container">
                        <div class="row">

                            <div class="col-md-6">
                                <h4> Transfer ID : #{{ $finalResultOrder[0]->id }} </h4>
                            </div>


                            <div class="col-md-6">
                                <h4> Date : {{ $finalResultOrder[0]->created_at }} </h4>
                            </div>




                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h4> Created by : {{ $finalResultOrder[0]->user_name }} </h4>
                            </div>

                            <div class="col-md-6">
                                <h4> Manager of branch : {{ $finalResultOrder[0]->branch_name }} </h4>
                            </div>
                        </div>


                        @if ($finalResultOrder[0]->request_state_id == 5 && !is_null($finalResultOrder[0]->notes))
                            <h4> Missing quantities: </h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <p> {{ $finalResultOrder[0]->notes }} </p>
                                </div>
                            </div>
                        @endif

                    </div>


                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product id</th>
                                <th>Product name</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $total_price = 0; ?>
                            @foreach ($finalResult as $value)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $value->product_id ? $value->product_id : '' }}</td>
                                    <td>{{ $value->product_name }}</td>
                                    <td>{{ $value->unit_name ? $value->unit_name : '' }}</td>
                                    <td>{{ $value->qty }}</td>
                                    <td>{{ $value->price ? $value->price : '' }}</td>
                                    <?php $value->price ? ($total_price += $value->price) : ''; ?>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="5" style="text-align: center">Total price</td>
                                <td><?php echo $total_price; ?></td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

@stop
