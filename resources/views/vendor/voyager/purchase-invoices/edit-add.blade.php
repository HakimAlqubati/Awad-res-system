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

                            <table class="table table-striped" id="my-table">
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

                                            <textarea class="form-control" name="notes" id="" rows="8" style="width: 100%"
                                                placeholder="Write some notes here..."></textarea>
                                        </td>
                                    </tr>
                                    <tr>

                                        <th> <label style="font-weight: bold"> Product </label></th>
                                        <th> <label style="font-weight: bold"> Unit </label></th>
                                        <th> <label style="font-weight: bold">Price </label></th>
                                        <th> <label style="font-weight: bold"> Quantity </label></th>
                                        <th> <label style="font-weight: bold">Total </label></th>
                                        <th> <label style="font-weight: bold">Notes </label></th>
                                        <th> <label style="font-weight: bold">Discount </label></th>
                                        <th>
                                            <a href="javascript:void(0);" style="font-size:18px;" id="addMore"
                                                title="Add More Person"><span class="glyphicon glyphicon-plus"></span></a>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="row_unit" id="row_unit">
                                        <td>
                                            <select class="form-control" name="product[]" id="" required>

                                                <?php  foreach ($products as $key => $value) {
                                         ?>
                                                <option value="<?php echo $value->id; ?>"><?php echo $value->name; ?></option>

                                                <?php } ?>
                                            </select>

                                        </td>
                                        <td>
                                            <select class="form-control" name="unit[]" id="" required>

                                                <?php  foreach ($units as $key => $value) {
                                         ?>
                                                <option value="<?php echo $value->id; ?>"><?php echo $value->name; ?></option>

                                                <?php } ?>
                                            </select>

                                        </td>

                                        <td>
                                            <input class="form-control" type="number" id="price" name="price[]" id=""
                                                required>
                                        </td>

                                        <td>
                                            <input class="form-control" type="number" id="qty" name="qty[]" id=""
                                                required>
                                        </td>

                                        <td>
                                            <input class="form-control" type="number" id="total" value="0" name="total[]"
                                                id="" readonly required>
                                        </td>
                                        <td>
                                            <input class="form-control" type="text" name="notes_details[]" id="" required>
                                        </td>
                                        <td>
                                            <input class="form-control" type="number" name="discount[]" id="" required>
                                        </td>

                                        <td>
                                            <a href="javascript:void(0);" style="font-size:18px;" class="create-one"
                                                title="Add More Person"><span class="glyphicon glyphicon-plus"></span></a>
                                        </td>
                                        <td>
                                            <a href='javascript:void(0);' class='remove'><span
                                                    class='glyphicon glyphicon-remove'></span></a>
                                        </td>

                                    </tr>
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
            $(document).on('click', '.create-one', function() {
                var trIndex = $(this).closest("tr").index();
 
                     consolo.log($(this).closest("tr").html())

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



            $('#btn-add').click(function() {

                var trid = $(this).closest('tr').attr('id'); // table row ID 


                alert($('#my-table tr').index(this))

                // $('<tr class="row_unit" id="row_unit" >' + $('#row_unit').html() + '</tr>').insertAfter(
                //     'table tr:last');
            });

            $('#btn-remove').click(function() {
                // var rowIndex = $('#my-table tr:last').index(this);
                // alert(rowIndex)
                var last_id = $('#my-table tr:last').attr('id');
                $('#' + last_id).remove();
            });

            $('#qty').keyup(function() {
                $('#total').val($('#price').val() * $('#qty').val());
            });


            $('#price').keyup(function() {
                $('#total').val($('#price').val() * $('#qty').val());
            });



            // $("button").click(function() {

            //     $('<tr><td>id</td><td>name</td><td>desc</td></tr>').insertAfter('table tr:last');
            // });
        });

        var params = {};
        var $file;

        function deleteHandler(tag, isMulti) {
            return function() {
                $file = $(this).siblings(tag);

                params = {
                    slug: '{{ $dataType->slug }}',
                    filename: $file.data('file-name'),
                    id: $file.data('id'),
                    field: $file.parent().data('field-name'),
                    multi: isMulti,
                    _token: '{{ csrf_token() }}'
                }

                $('.confirm_delete_name').text(params.filename);
                $('#confirm_delete_modal').modal('show');
            };
        }

        $('document').ready(function() {
            $('.toggleswitch').bootstrapToggle();





            $('#qty_').keyup(function() {
                $('#price_').val(($(this).val() * 2));
            });

            //Init datepicker for date fields if data-datepicker attribute defined
            //or if browser does not handle date inputs
            $('.form-group input[type=date]').each(function(idx, elt) {
                if (elt.hasAttribute('data-datepicker')) {
                    elt.type = 'text';
                    $(elt).datetimepicker($(elt).data('datepicker'));
                } else if (elt.type != 'date') {
                    elt.type = 'text';
                    $(elt).datetimepicker({
                        format: 'L',
                        extraFormats: ['YYYY-MM-DD']
                    }).datetimepicker($(elt).data('datepicker'));
                }
            });

            @if ($isModelTranslatable)
                $('.side-body').multilingual({"editing": true});
            @endif

            $('.side-body input[data-slug-origin]').each(function(i, el) {
                $(el).slugify();
            });

            $('.form-group').on('click', '.remove-multi-image', deleteHandler('img', true));
            $('.form-group').on('click', '.remove-single-image', deleteHandler('img', false));
            $('.form-group').on('click', '.remove-multi-file', deleteHandler('a', true));
            $('.form-group').on('click', '.remove-single-file', deleteHandler('a', false));

            $('#confirm_delete').on('click', function() {
                $.post('{{ route('voyager.' . $dataType->slug . '.media.remove') }}', params, function(
                    response) {
                    if (response &&
                        response.data &&
                        response.data.status &&
                        response.data.status == 200) {

                        toastr.success(response.data.message);
                        $file.parent().fadeOut(300, function() {
                            $(this).remove();
                        })
                    } else {
                        toastr.error("Error removing file.");
                    }
                });

                $('#confirm_delete_modal').modal('hide');
            });
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@stop
