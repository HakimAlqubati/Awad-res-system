@php
$edit = !is_null($dataTypeContent->getKey());
$add = is_null($dataTypeContent->getKey());
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_title', __('voyager::generic.' . ($edit ? 'edit' : 'add')) . ' ' .
    $dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.' . ($edit ? 'edit' : 'add')) .' ' .$dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">


                <div class="panel panel-bordered">
                    <!-- form start -->
                    <form role="form" class="form-edit-add"
                        action={{ $edit ? url('/update-product', [$dataTypeContent->getKey()]) : url('/add-purchase-invoice') }}
                        method="POST" enctype="multipart/form-data">
                        <!-- PUT Method if we are editing -->
                        @if ($edit)
                            {{ method_field('PUT') }}
                        @endif

                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}

                        <div class="panel-body">


                            <table class="table table-striped" id="my-table" cellspacing="0">
                                <thead>

                                    <tr>

                                        <td colspan="2">
                                            <div class="form-group">
                                                <label style="font-weight: bold"> Inovoice number</label>
                                                <input type="text" placeholder="Inoivce number" class="form-control"
                                                    name="invoice_no" required />
                                            </div>
                                        </td>

                                        <td colspan="2">
                                            <div class="form-group">
                                                <label style="font-weight: bold"> Date</label>
                                                <input type="date" placeholder="Date" name="date" class="form-control"
                                                    value="<?php echo date('Y-m-d'); ?>" required />
                                            </div>
                                        </td>



                                        <td colspan="2">
                                            <div class="form-group">
                                                <label style="font-weight: bold"> Supplier</label>
                                                <select name="supplier_id" class="form-control" required>
                                                    <?php 
                                                 foreach ($suppliers as $key => $value) {
                                                       ?>

                                                    <option value="<?php echo $value->id; ?>"> <?php echo $value->name; ?></option>

                                                    <?php }?>


                                                </select>
                                            </div>
                                        </td>

                                        <td colspan="2">
                                            <div class="form-group">
                                                <label style="font-weight: bold"> Stock</label>
                                                <select name="stock_id" class="form-control" required>
                                                    <?php 
                                                 foreach ($stocks as $key => $value) {
                                                       ?>

                                                    <option value="<?php echo $value->id; ?>"> <?php echo $value->name; ?></option>

                                                    <?php }?>


                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="8">

                                            <textarea class="form-control" name="notes" id="" rows="4" style="width: 100%"
                                                placeholder="Write some notes here..."></textarea>
                                        </td>
                                    </tr>

                            </table>

                            <table class="table table-striped" id="table-details" cellspacing="0">

                                <thead>
                                    <tr>
                                        <th> <label style="font-weight: bold"> Product </label></th>
                                        <th> <label style="font-weight: bold"> Quantity </label></th>
                                        <th> <label style="font-weight: bold"> Unit </label></th>
                                        <th> <label style="font-weight: bold">Price </label></th>
                                        <th> <label style="font-weight: bold">Total </label></th>
                                        <th> <label style="font-weight: bold">Notes </label></th>
                                        <th> <label style="font-weight: bold">Discount </label></th>
                                    </tr>
                                </thead>
 
                                <tbody>
                                    @for ($i = 0; $i < 30; $i++)
                                        <tr class="row_unit" id="row_unit">

                                            <td>
                                                <input autocomplete="off" type="text" id="product{{ $i }}"
                                                    name="product[]" placeholder="Search product"
                                                    class="form-control product" />
                                            </td>

                                            <td>
                                                <input autocomplete="off" class="form-control" type="text" id="qty"
                                                    name="qty[]" id="">
                                            </td>

                                            <td>
                                                <input autocomplete="off" type="text" id="unit{{ $i }}"
                                                    name="unit[]" placeholder="Search unit" class="form-control unit" />
                                            </td>

                                            <td>
                                                <input autocomplete="off" class="form-control" type="text" id="price"
                                                    name="price[]" id="">
                                            </td>

                                            <td>
                                                <input autocomplete="off" class="form-control" type="text" id="total"
                                                    value="0" name="total[]" id="">
                                            </td>

                                            <td>
                                                <input autocomplete="off" class="form-control" type="text"
                                                    name="notes_details[]" id="">
                                            </td>

                                            <td>
                                                <input autocomplete="off" class="form-control" type="text"
                                                    name="discount[]" id="">
                                            </td>

                                        </tr>
                                    @endfor
                                </tbody>
                            </table>

                            <div style="width: 100%;text-align: center">
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div><!-- panel-body -->



                    </form>




                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-danger" id="confirm_delete_modal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-warning"></i> {{ __('voyager::generic.are_you_sure') }}
                    </h4>
                </div>

                <div class="modal-body">
                    <h4>{{ __('voyager::generic.are_you_sure_delete') }} '<span class="confirm_delete_name"></span>'</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default"
                        data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-danger"
                        id="confirm_delete">{{ __('voyager::generic.delete_confirm') }}</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Delete File Modal -->
@stop
@section('javascript')
    <script>
        $(function() {
            // $('#addMore').on('click', function() {
            //     var data = $("#my-table tr:eq(3)").clone(true).appendTo("#my-table");
            //     data.find("input").val('');
            // });
            $(document).on('click', '#create-one', function() {
                var trIndex = $(this).closest("tr").index();

                var trHtml = $(this).closest("tr").html()

                $("<tr class='row_unit'  >" + trHtml + "</tr>").insertAfter("#row_unit");
            });

            $(document).on('click', '.remove', function() {
                var trIndex = $(this).closest("tr").index();
                if (trIndex == 0) {
                    alert("Sorry!! Can't remove first row!");
                } else {
                    $(this).closest("tr").remove();
                }
            });
        });

        $(document).ready(function() {


            // --------
            var routeautocompleteProduct = "{{ url('autocomplete-product') }}";
            var routeautocompleteUnit = "{{ url('autocomplete-unit') }}";


            for (let index = 0; index < 30; index++) {

                // for product
                $('#product' + index).typeahead({
                    source: function(query, process) {
                        return $.get(routeautocompleteProduct, {
                            query: query
                        }, function(data) {
                            return process(data);
                        });
                    }
                });


                // for unit
                $('#unit' + index).typeahead({
                    source: function(query, process) {
                        return $.get(routeautocompleteUnit, {
                            query: query
                        }, function(data) {
                            return process(data);
                        });
                    }
                });
            }






            // --------



            $(document).on('mouseup keyup', '#qty', function() {

                var price = $(this).closest("tr").find('td:eq(3) input').val();
                var qty = $(this).closest("tr").find('td:eq(1) input').val();
                $(this).closest("tr").find('td:eq(4) input').val(price * qty)
            });

            $(document).on('mouseup keyup', '#price', function() {

                var price = $(this).closest("tr").find('td:eq(3) input').val();
                var qty = $(this).closest("tr").find('td:eq(1) input').val();
                $(this).closest("tr").find('td:eq(4) input').val(price * qty)
            });



            $('td input').bind('paste', null, function(e) {
                txt = $(this);
                console.log(txt)
                // alert( txt)
                setTimeout(function() {
                    var values = txt.val().split(/\s+/);
                    var currentRowIndex = txt.parent().parent().index();
                    var currentColIndex = txt.parent().index();

                    var totalRows = $('#table-details tbody tr').length;
                    var totalCols = $('#table-details thead th').length;

                    var count = 0;

                    for (var i = currentColIndex; i < totalCols; i++) {
                        if (i != currentColIndex)
                            if (i != currentColIndex)
                                currentRowIndex = 0;
                        for (var j = currentRowIndex; j < totalRows; j++) {
                            var value = values[count];
                            var inp = $('#table-details tbody tr').eq(j).find('td').eq(i).find(
                                'input');
                            inp.val(value);
                            count++;

                        }
                    }
                }, 0);
            });



        });
    </script>


    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.1/bootstrap3-typeahead.min.js">
    
    </script>


@stop
