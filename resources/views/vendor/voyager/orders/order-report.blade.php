@extends('voyager::master')


@section('page_header')


    <h1 class="page-title">




    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')

    <div class="page-content read container-fluid">
        <form class="form-inline form-filter" method="GET" action="<?php echo url('/'); ?>/admin/order-report">

            <div class="form-group">
                <label for="status">Branch:</label>
                <select class="form-control" name="branch_id" id="branch_id">

                    <option value="">-Choose-</option>
                    @foreach ($branches as $item)
                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                    @endforeach
                </select>
            </div>


            <div class="form-group">
                <label for="status">Product:</label>
                <select class="form-control" name="product_id" id="product_id">

                    <option value="">-Choose-</option>
                    @foreach ($products as $item)
                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                    @endforeach
                </select>
            </div>


            <div class="form-group">
                <label for="status">Unit:</label>
                <select class="form-control" name="unit_id" id="unit_id">

                    <option value="">-Choose-</option>
                    @foreach ($units as $item)
                        <option value="{{ $item->id }}"> {{ $item->name }} </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="status"> From date:</label>
                <input type="date" name="from_date" type="date" class="form-control">

                <label for="status"> To date:</label>
                <input type="date" name="to_date" type="date" class="form-control">

            </div>

            <input class="form-control btn btn-primary" type="submit" value="Search">
        </form>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <table class="table table-striped">
                        <thead>
                            <tr>

                                <th>Branch</th>
                                {{-- <th>Order state</th> --}}
                                <th>Product</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Unit price</th>
                                <th>Total price</th>
                                {{-- <th>Date </th> --}}

                            </tr>
                        </thead>
                        <tbody>

                            @if (count($data) == 0)
                                <tr>
                                    <td colspan="100%" style="text-align: center">
                                        <h5 style="color: red"> No Data !, Or You Have To Choose a Branch</h5>
                                    </td>
                                </tr>
                            @endif
                            <?php
                            $total_price = 0;
                            ?>
                            @foreach ($data as $item)
                                <tr>
                                    <td><?php echo $item->branch_name; ?> </td>
                                    {{-- <td><?php echo $item->state_name; ?> </td> --}}
                                    <td><?php echo $item->product_name; ?> </td>
                                    <td><?php echo $item->unit_name; ?> </td>
                                    <td><?php echo $item->qty; ?> </td>
                                    <td><?php
                                  if($item->price > 0) {

                                      echo $item->price / $item->qty; 
                                  }
                                     
                                     ?> </td>
                                    <td><?php echo $item->price; ?> </td>
                                    {{-- <td><?php echo $item->created_at; ?> </td> --}}
                                </tr>
                                <?php $total_price += $item->price; ?>
                            @endforeach

                            <td colspan="4" style="text-align: center"> Total price</td>
                            <td> {{ $total_price }} </td>
                            <td></td>
                            <tr>

                            </tr>
                        </tbody>
                    </table>

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
