@extends('voyager::master')


@section('page_header')


    <h1 class="page-title">




    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')

    <div class="page-content read container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div class="container">

                        <form class="form-inline form-filter" method="GET" action="<?php echo url('/'); ?>/admin/stock-report">

                            <div class="form-group">
                                <label for="status">Stock:</label>
                                <select class="form-control" name="stock_id" id="stock_id">

                                    @foreach ($stocks as $item)
                                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                                    @endforeach
                                </select>

                                <label for="status">Supplier:</label>
                                <select class="form-control" name="supplier_id" id="supplier_id">
                                    <option value="">-Choose-</option>
                                    @foreach ($suppliers as $item)
                                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                                    @endforeach
                                </select>



                                <label for="status">Product:</label>
                                <select class="form-control" name="product_id" id="product_id">
                                    <option value="">-Choose-</option>

                                    @foreach ($products as $item)
                                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                                    @endforeach
                                </select>


                                <label for="status">Unit:</label>
                                <select class="form-control" name="unit_id" id="unit_id">
                                    <option value="">-Choose-</option>

                                    @foreach ($units as $item)
                                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                                    @endforeach
                                </select>

                            </div>
                            <input class="form-control btn btn-primary" type="submit" value="Search">
                        </form>


                        <div class="row">
                            <div class="col-md-4" style="    height: 100px;
                                                                                           
                                                                                            padding-top: 40px;
                                                                                            text-align: center;">
                                <p style="font-weight: bold;">
                                    Stock report of <strong> ( {{ $data ? $data[0]->stock_name : '' }} ) </strong>
                                </p>
                            </div>
                            <div class="col-md-4" style="text-align: center; padding: 20px;">
                                <img src="http://15.185.62.165/Ressystemv2/public/logo.png" alt="" style="height: 77px;">
                            </div>
                            <div class="col-md-4" style="    height: 100px;
                                                           
                                                            padding-top: 40px;
                                                            text-align: center;">
                                <p style="font-weight: bold;">
                                    Date <strong> ( {{ date('y-m-d') }} ) </strong>
                                </p>
                            </div>
                        </div>
                        <table class="table table-striped">
                            <thead>






                                <tr>

                                    <th>Product id</th>
                                    <th>Product name</th>
                                    <th>Unit name</th>
                                    <th>Quantity</th>
                                    <th>Supplier</th>

                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $value)
                                    <tr>
                                        <td> {{ $value->product_id }} </td>
                                        <td> {{ $value->product_name }} </td>
                                        <td> {{ $value->unit_name }} </td>
                                        <td> {{ ($value->qty - $value->ordered_qty) }} </td>
                                        <td> {{ $value->supplier_name }} </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop

@section('javascript')

    <script src="{{ asset('js/app.js') }}" type="text/js"></script>


    <script>
        var deleteFormAction;
        $('.delete').on('click', function(e) {
            var form = $('#delete_form')[0];

            if (!deleteFormAction) {
                // Save form action initial value
                deleteFormAction = form.action;
            }

            form.action = deleteFormAction.match(/\/[0-9]+$/) ?
                deleteFormAction.replace(/([0-9]+$)/, $(this).data('id')) :
                deleteFormAction + '/' + $(this).data('id');

            $('#delete_modal').modal('show');
        });
    </script>
@stop

<style>
    label {}

</style>
