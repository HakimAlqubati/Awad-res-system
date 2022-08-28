@extends('voyager::master')
@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href={{ url('/') . '/multiselect/css/style_custom.css' }}>

    <style>
        th {
            background-color: #ededd5 !important;
            font-weight: bold;
        }

        @media print {



            .no-print,
            .no-print * {
                display: none !important;
            }


            .app-container.expanded .side-body {
                margin-right: 0px !important;
            }
        }

        p {
            font-weight: bold;
            text-align: left !important;
        }
    </style>

@stop


@section('page_header')

 
    @include('voyager::multilingual.language-selector')
@stop

@section('content')

    <div class="page-content read container-fluid">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered" style="padding-bottom:5px;">
                    <div class="container">

                        <div style="box-shadow: none;border: 1px solid #38393a;
                        border-radius: 20px; "class="row">
                
                            <div
                                style="box-shadow: none;text-align: right; padding-top: 20px;padding-right: 30px;"class="col-md-3 col-sm-3 col-xs-3">
                                <p>General Stock Report</p>
                 <p>   Date <strong> ( {{ date('y-m-d') }} ) </strong></p>
                            
                
                            </div>
                
                            <div style="box-shadow: none; text-align: center;"class="col-md-6 col-sm-6 col-xs-6">
                                <img style="margin-top: 15px;" width="155px" height="155px"
                                    src="http://15.185.62.165/Ressystemv2/public/logo.png" alt="">
                            </div>
                            <div
                                style="box-shadow: none; padding-top: 20px;text-align: left;padding-left: 30px;"class="col-md-3 col-sm-3 col-xs-3">
                
                            </div>
                        </div>

                        
                        <form class="form-inline form-filter no-print" method="GET" action="<?php echo url('/'); ?>/admin/stock-report-v2">

                            <div class="form-group">     <label for="status">Product:</label>
                                <select class="form-control" name="product_id" id="product_id">
                                    <option value="">-Choose-</option>

                                    @foreach (\App\Models\Product::get() as $item)
                                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                                    @endforeach
                                </select>


                          

                            </div>
                            <input class="form-control btn btn-primary" type="submit" value="Search">
                        </form>


                       
                        <table class="table table-striped">
                            <thead>






                                <tr>

                                    <th>Product id</th>
                                    <th>Product name</th>
                                    <th>Unit name</th>
                                    <th>Purchased quantity</th>
                                    <th>Ordered quantity</th>
                                    <th>Remaining quantity</th>


                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($final_result as $value)
                                    <tr>
                                        <td> {{ $value->product_id }} </td>
                                        <td> {{ $value->product_name }} </td>
                                        <td>{{ $value->unit_name }}</td>
                                        <td>{{ $value->qty_in_purchase }}</td>
                                        <td>{{ $value->qty_in_orders }}</td>
                                        <td> {{ $value->remaining_qty }} </td>
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
