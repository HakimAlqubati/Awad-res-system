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


                        <div class="row">

                            <div class="col-md-4" style="text-align: center; padding: 20px;">
                                <img src="http://15.185.62.165/Ressystemv2/public/logo.png" alt=""
                                    style="height: 77px;">
                            </div>
                            <div class="col-md-4"
                                style="    height: 100px;
                                                           
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
                                @foreach ($final_result as $value)
                                    <tr>
                                        <td> {{ $value->product_id }} </td>
                                        <td> {{ $value->product_name }} </td>
                                        <td>{{ $value->unit_name }}</td>

                                        <td> {{ $value->remaining_qty }} </td>
                                        <td></td>
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
